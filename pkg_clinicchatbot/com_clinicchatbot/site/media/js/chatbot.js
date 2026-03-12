document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('clinic-chatbot');
    const toggle = document.getElementById('chatbot-toggle');
    const minimize = document.getElementById('chatbot-minimize');
    const input = document.getElementById('chatbot-input');
    const send = document.getElementById('chatbot-send');
    const messages = document.getElementById('chatbot-messages');
    const windowEl = document.getElementById('chatbot-window');

    if (!root || !toggle || !minimize || !input || !send || !messages || !windowEl) {
        return;
    }

    let config = {};

    try {
        config = JSON.parse(root.dataset.chatbotConfig || '{}');
    } catch (error) {
        console.error('Invalid chatbot config JSON', error);
        config = {};
    }

    const endpoint =
        config.apiEndpoint ||
        '/index.php?option=com_clinicchatbot&task=chat.send';

    function getClinicConfig() {
        return {
            clinicName: String(config.clinicName || '').trim(),
            phone: String(config.clinicPhone || '').trim(),
            address: String(config.clinicAddress || '').trim(),
            bookingUrl: String(config.bookingUrl || '').trim()
        };
    }

    function hasValidClinicConfig(clinic) {
        return (
            clinic.clinicName.length > 0 &&
            clinic.phone.length > 0 &&
            clinic.address.length > 0 &&
            clinic.bookingUrl.length > 0
        );
    }

    function getOrCreateSessionId() {
        const storageKey = 'clinicChatbotSessionId';
        let sessionId = window.sessionStorage.getItem(storageKey);

        if (!sessionId) {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                sessionId = window.crypto.randomUUID();
            } else {
                sessionId =
                    'chatbot-' +
                    Date.now().toString(36) +
                    '-' +
                    Math.random().toString(36).slice(2);
            }

            window.sessionStorage.setItem(storageKey, sessionId);
        }

        return sessionId;
    }

    function openChat() {
        root.classList.add('is-open');
        windowEl.setAttribute('aria-hidden', 'false');
        input.focus();
    }

    function closeChat() {
        root.classList.remove('is-open');
        windowEl.setAttribute('aria-hidden', 'true');
    }

    function addMessage(text, type) {
        const message = document.createElement('div');
        message.className = 'chatbot-message ' + type;
        message.textContent = text;
        messages.appendChild(message);
        messages.scrollTop = messages.scrollHeight;
    }

    function getReplyFromResponse(data) {
        if (data && Array.isArray(data.data) && data.data[0] && typeof data.data[0].reply === 'string') {
            return data.data[0].reply;
        }

        if (data && data.data && typeof data.data.reply === 'string') {
            return data.data.reply;
        }

        return null;
    }

    async function sendToBackend(text) {
        const clinic = getClinicConfig();

        if (!hasValidClinicConfig(clinic)) {
            throw new Error('Missing clinic configuration');
        }

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: text,
                sessionId: getOrCreateSessionId(),
                pageUrl: window.location.href,
                clinic
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error((data && data.message) || 'API request failed');
        }

        const reply = getReplyFromResponse(data);

        if (!reply) {
            throw new Error('Invalid chatbot response');
        }

        return reply;
    }

    async function sendMessage() {
        const text = input.value.trim();

        if (!text) {
            return;
        }

        addMessage(text, 'user');
        input.value = '';
        send.disabled = true;
        input.disabled = true;

        try {
            const answer = await sendToBackend(text);
            addMessage(answer, 'bot');
        } catch (error) {
            console.error(error);
            addMessage(
                'Der opstod en fejl. Kontakt klinikken direkte.',
                'bot'
            );
        } finally {
            send.disabled = false;
            input.disabled = false;
            input.focus();
        }
    }

    toggle.addEventListener('click', () => {
        if (root.classList.contains('is-open')) {
            closeChat();
            return;
        }

        openChat();
    });

    minimize.addEventListener('click', () => {
        closeChat();
    });

    send.addEventListener('click', () => {
        sendMessage();
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            sendMessage();
        }
    });
});