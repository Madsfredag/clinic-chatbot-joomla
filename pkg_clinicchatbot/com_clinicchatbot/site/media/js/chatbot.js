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
    let isSending = false;
    let typingIndicator = null;
    let promptBubble = null;
    let promptDismissed = false;

    try {
        config = JSON.parse(root.dataset.chatbotConfig || '{}');
    } catch (error) {
        console.error('Invalid chatbot config JSON', error);
        config = {};
    }

    const endpoint =
        config.apiEndpoint ||
        '/index.php?option=com_ajax&plugin=clinicchatbotproxy&format=json';

    const quickQuestions = Array.isArray(config.quickQuestions) && config.quickQuestions.length > 0
        ? config.quickQuestions
        : [
            'Hvad koster en tandrensning?',
            'Hvordan booker jeg en tid?',
            'Råd om tandpleje',
            'Hvilke behandlinger tilbyder I?',
        ];

    const promptText = String(config.closedPromptText || 'Har du brug for hjælp?').trim();

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

    function scrollMessagesToBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    function showPromptBubble() {
        if (promptDismissed || root.classList.contains('is-open') || !promptText) {
            return;
        }

        if (!promptBubble) {
            promptBubble = document.createElement('button');
            promptBubble.type = 'button';
            promptBubble.className = 'clinic-chatbot__prompt';
            promptBubble.textContent = promptText;

            promptBubble.addEventListener('click', () => {
                openChat();
            });

            root.appendChild(promptBubble);
        }

        promptBubble.hidden = false;
    }

    function hidePromptBubble(permanently = false) {
        if (permanently) {
            promptDismissed = true;
        }

        if (promptBubble) {
            promptBubble.hidden = true;
        }
    }

    function openChat() {
        root.classList.add('is-open');
        windowEl.setAttribute('aria-hidden', 'false');
        hidePromptBubble(true);
        input.focus();
        scrollMessagesToBottom();
    }

    function closeChat() {
        root.classList.remove('is-open');
        windowEl.setAttribute('aria-hidden', 'true');
    }

    function createMessageElement(text, type) {
        const message = document.createElement('div');
        message.className = 'chatbot-message ' + type;
        message.textContent = text;
        return message;
    }

    function addMessage(text, type) {
        const message = createMessageElement(text, type);
        messages.appendChild(message);
        scrollMessagesToBottom();
        return message;
    }

    function showTypingIndicator() {
        if (typingIndicator) {
            return;
        }

        typingIndicator = document.createElement('div');
        typingIndicator.className = 'chatbot-message bot chatbot-message--typing';
        typingIndicator.textContent = 'Skriver...';
        messages.appendChild(typingIndicator);
        scrollMessagesToBottom();
    }

    function hideTypingIndicator() {
        if (!typingIndicator) {
            return;
        }

        typingIndicator.remove();
        typingIndicator = null;
    }

    function setSendingState(sending) {
        isSending = sending;
        send.disabled = sending;
        input.disabled = sending;
    }

    function extractReply(payload) {
        if (!payload) {
            return null;
        }

        if (typeof payload === 'string') {
            return payload.trim() || null;
        }

        if (typeof payload.reply === 'string' && payload.reply.trim()) {
            return payload.reply.trim();
        }

        if (payload.data) {
            if (typeof payload.data === 'string' && payload.data.trim()) {
                return payload.data.trim();
            }

            if (typeof payload.data.reply === 'string' && payload.data.reply.trim()) {
                return payload.data.reply.trim();
            }

            if (Array.isArray(payload.data) && payload.data.length > 0) {
                const first = payload.data[0];

                if (typeof first === 'string' && first.trim()) {
                    return first.trim();
                }

                if (first && typeof first.reply === 'string' && first.reply.trim()) {
                    return first.reply.trim();
                }
            }
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

        const rawText = await response.text();
        let data = null;

        try {
            data = rawText ? JSON.parse(rawText) : null;
        } catch (error) {
            console.error('Invalid JSON from chatbot endpoint:', rawText);
            throw new Error('Invalid JSON response');
        }

        if (!response.ok) {
            throw new Error((data && data.message) || 'API request failed');
        }

        if (data && data.success === false) {
            throw new Error((data && data.message) || 'API request failed');
        }

        const reply = extractReply(data);

        if (!reply) {
            console.error('Unexpected chatbot response shape:', data);
            throw new Error('Invalid chatbot response');
        }

        return reply;
    }

    function getFriendlyErrorMessage(error) {
        const rawMessage = error instanceof Error ? error.message : '';

        if (rawMessage.includes('Missing clinic configuration')) {
            return 'Chatten mangler opsætning. Kontakt klinikken direkte.';
        }

        if (rawMessage.includes('Failed to fetch')) {
            return 'Der er problemer med forbindelsen lige nu. Prøv igen om lidt eller kontakt klinikken direkte.';
        }

        if (rawMessage.toLowerCase().includes('timeout')) {
            return 'Det tager længere tid end forventet. Prøv igen om lidt eller kontakt klinikken direkte.';
        }

        return 'Der opstod en fejl. Kontakt klinikken direkte.';
    }

    async function sendMessage(prefilledText) {
        if (isSending) {
            return;
        }

        const text = typeof prefilledText === 'string'
            ? prefilledText.trim()
            : input.value.trim();

        if (!text) {
            return;
        }

        hidePromptBubble(true);
        addMessage(text, 'user');
        input.value = '';
        setSendingState(true);
        showTypingIndicator();

        try {
            const answer = await sendToBackend(text);
            hideTypingIndicator();
            addMessage(answer, 'bot');
        } catch (error) {
            hideTypingIndicator();
            console.error(error);
            addMessage(getFriendlyErrorMessage(error), 'bot');
        } finally {
            setSendingState(false);
            input.focus();
        }
    }

    function renderQuickQuestions() {
        if (!quickQuestions.length) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'clinic-chatbot__quick-questions';

        quickQuestions.forEach((question) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'clinic-chatbot__quick-question';
            button.textContent = question;

            button.addEventListener('click', () => {
                sendMessage(question);
            });

            wrapper.appendChild(button);
        });

        messages.appendChild(wrapper);
        scrollMessagesToBottom();
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
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    });

    renderQuickQuestions();

    window.setTimeout(() => {
        showPromptBubble();
    }, 1200);
});