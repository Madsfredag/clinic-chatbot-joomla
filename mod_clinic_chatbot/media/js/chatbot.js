document.addEventListener('DOMContentLoaded', function () {
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
        config = {};
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

    async function sendToBackend(text) {
        if (!config.apiEndpoint) {
            const fallback = [];

            if (config.clinicPhone) {
                fallback.push('Phone: ' + config.clinicPhone);
            }

            if (config.clinicAddress) {
                fallback.push('Address: ' + config.clinicAddress);
            }

            if (config.bookingUrl) {
                fallback.push('Booking: ' + config.bookingUrl);
            }

            return (
                'Backend not connected yet for ' +
                (config.clinicName || 'this clinic') +
                '. ' +
                fallback.join(' | ')
            );
        }

        const response = await fetch(config.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                clinicId: config.clinicId,
                clinicName: config.clinicName,
                message: text
            })
        });

        if (!response.ok) {
            throw new Error('API request failed');
        }

        const data = await response.json();

        return data.answer || 'No response received.';
    }

    async function sendMessage() {
        const text = input.value.trim();

        if (!text) {
            return;
        }

        addMessage(text, 'user');
        input.value = '';

        try {
            const answer = await sendToBackend(text);
            addMessage(answer, 'bot');
        } catch (error) {
            addMessage(
                'Sorry, something went wrong. Please contact the clinic directly.',
                'bot'
            );
        }
    }

    toggle.addEventListener('click', function () {
        if (root.classList.contains('is-open')) {
            closeChat();
            return;
        }

        openChat();
    });

    minimize.addEventListener('click', function () {
        closeChat();
    });

    send.addEventListener('click', sendMessage);

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            sendMessage();
        }
    });
});