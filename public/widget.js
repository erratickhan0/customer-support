(function () {
    const script = document.currentScript;
    const apiKey = script && script.getAttribute('data-api-key');
    const title = (script && script.getAttribute('data-title')) || 'Support';
    const origin = new URL(script && script.src ? script.src : window.location.href).origin;
    const storageKey = 'supportpilot_widget_session_id';
    const conversationStorageKey = 'supportpilot_widget_conversation_id';

    if (!apiKey) {
        console.error('SupportPilot widget: missing data-api-key.');
        return;
    }

    let sessionId = localStorage.getItem(storageKey);
    let conversationId = localStorage.getItem(conversationStorageKey);
    let isOpen = false;
    let isSending = false;
    let pollTimer = null;

    if (!sessionId) {
        sessionId = 'visitor_' + Math.random().toString(36).slice(2) + Date.now().toString(36);
        localStorage.setItem(storageKey, sessionId);
    }

    const styles = document.createElement('style');
    styles.textContent = `
        .sp-widget-root, .sp-widget-root * { box-sizing: border-box; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .sp-widget-button { position: fixed; right: 24px; bottom: 24px; z-index: 2147483647; border: 0; border-radius: 999px; width: 60px; height: 60px; background: #0891b2; color: #fff; box-shadow: 0 20px 40px rgba(8, 145, 178, .35); cursor: pointer; display: grid; place-items: center; }
        .sp-widget-button:hover { background: #0e7490; }
        .sp-widget-panel { position: fixed; right: 24px; bottom: 96px; z-index: 2147483647; width: min(380px, calc(100vw - 32px)); height: min(560px, calc(100vh - 128px)); border: 1px solid #dbeafe; border-radius: 20px; background: #fff; box-shadow: 0 24px 80px rgba(15, 23, 42, .22); overflow: hidden; display: none; color: #0f172a; }
        .sp-widget-panel[data-open="true"] { display: flex; flex-direction: column; }
        .sp-widget-header { padding: 16px; background: linear-gradient(135deg, #0891b2, #0f172a); color: #fff; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .sp-widget-title { font-size: 15px; font-weight: 700; margin: 0; }
        .sp-widget-subtitle { font-size: 12px; opacity: .82; margin: 2px 0 0; }
        .sp-widget-close { border: 0; background: rgba(255, 255, 255, .12); color: #fff; border-radius: 999px; width: 32px; height: 32px; cursor: pointer; }
        .sp-widget-messages { flex: 1; padding: 16px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 10px; }
        .sp-widget-empty { margin: auto 0; text-align: center; color: #64748b; font-size: 14px; line-height: 1.5; }
        .sp-widget-message { max-width: 86%; border-radius: 16px; padding: 10px 12px; font-size: 14px; line-height: 1.45; white-space: pre-wrap; }
        .sp-widget-message[data-sender="user"] { align-self: flex-end; background: #0891b2; color: #fff; border-bottom-right-radius: 4px; }
        .sp-widget-message[data-sender="ai"], .sp-widget-message[data-sender="agent"] { align-self: flex-start; background: #fff; color: #0f172a; border: 1px solid #e2e8f0; border-bottom-left-radius: 4px; }
        .sp-widget-meta { display: block; margin-top: 4px; font-size: 11px; opacity: .65; }
        .sp-widget-form { padding: 12px; border-top: 1px solid #e2e8f0; display: flex; gap: 8px; background: #fff; }
        .sp-widget-input { flex: 1; border: 1px solid #cbd5e1; border-radius: 999px; padding: 10px 12px; font-size: 14px; outline: none; min-width: 0; }
        .sp-widget-input:focus { border-color: #0891b2; box-shadow: 0 0 0 3px rgba(8, 145, 178, .16); }
        .sp-widget-send { border: 0; border-radius: 999px; padding: 0 14px; background: #0891b2; color: #fff; font-weight: 700; cursor: pointer; }
        .sp-widget-send:disabled { opacity: .55; cursor: not-allowed; }
        .sp-widget-error { padding: 8px 12px; background: #fef2f2; color: #991b1b; font-size: 12px; display: none; }
        .sp-widget-error[data-show="true"] { display: block; }
    `;
    document.head.appendChild(styles);

    const root = document.createElement('div');
    root.className = 'sp-widget-root';
    root.innerHTML = `
        <section class="sp-widget-panel" aria-label="${escapeAttribute(title)} chat">
            <header class="sp-widget-header">
                <div>
                    <p class="sp-widget-title">${escapeHtml(title)}</p>
                    <p class="sp-widget-subtitle">We usually reply in a few seconds.</p>
                </div>
                <button class="sp-widget-close" type="button" aria-label="Close chat">x</button>
            </header>
            <div class="sp-widget-error"></div>
            <div class="sp-widget-messages">
                <div class="sp-widget-empty">Ask a question and our AI support assistant will help.</div>
            </div>
            <form class="sp-widget-form">
                <input class="sp-widget-input" type="text" maxlength="5000" placeholder="Type your message..." aria-label="Message" />
                <button class="sp-widget-send" type="submit">Send</button>
            </form>
        </section>
        <button class="sp-widget-button" type="button" aria-label="Open support chat">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 5.75A3.75 3.75 0 0 1 7.75 2h8.5A3.75 3.75 0 0 1 20 5.75v5.5A3.75 3.75 0 0 1 16.25 15H12l-5 4v-4A3.75 3.75 0 0 1 4 11.25v-5.5Z" fill="currentColor"/>
                <path d="M8 8.5h8M8 11h5" stroke="#0891b2" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
        </button>
    `;
    document.body.appendChild(root);

    const panel = root.querySelector('.sp-widget-panel');
    const openButton = root.querySelector('.sp-widget-button');
    const closeButton = root.querySelector('.sp-widget-close');
    const form = root.querySelector('.sp-widget-form');
    const input = root.querySelector('.sp-widget-input');
    const sendButton = root.querySelector('.sp-widget-send');
    const messages = root.querySelector('.sp-widget-messages');
    const error = root.querySelector('.sp-widget-error');

    openButton.addEventListener('click', function () {
        setOpen(!isOpen);
    });

    closeButton.addEventListener('click', function () {
        setOpen(false);
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const content = input.value.trim();
        if (!content || isSending) {
            return;
        }

        await sendMessage(content);
    });

    if (conversationId) {
        pollConversation();
    }

    function setOpen(value) {
        isOpen = value;
        panel.setAttribute('data-open', String(isOpen));

        if (isOpen && conversationId) {
            pollConversation();
        }
    }

    async function sendMessage(content) {
        setError('');
        isSending = true;
        sendButton.disabled = true;
        input.disabled = true;

        addOptimisticMessage(content);
        input.value = '';

        try {
            const response = await fetch(origin + '/api/widget/messages', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    api_key: apiKey,
                    session_id: sessionId,
                    message: content,
                    metadata: {
                        source: 'widget',
                        page_url: window.location.href,
                        referrer: document.referrer || null,
                        locale: navigator.language || null,
                        user_agent: navigator.userAgent || null,
                    },
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Unable to send message.');
            }

            conversationId = String(data.conversation_id);
            localStorage.setItem(conversationStorageKey, conversationId);
            await pollConversation();
        } catch (requestError) {
            setError(requestError.message || 'Unable to send message.');
        } finally {
            isSending = false;
            sendButton.disabled = false;
            input.disabled = false;
            input.focus();
        }
    }

    async function pollConversation() {
        window.clearTimeout(pollTimer);

        if (!conversationId) {
            return;
        }

        try {
            const url = new URL(origin + '/api/widget/conversations/' + conversationId);
            url.searchParams.set('api_key', apiKey);
            url.searchParams.set('session_id', sessionId);

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to load conversation.');
            }

            const data = await response.json();
            renderMessages(data.messages || []);
            setError('');
        } catch (requestError) {
            setError(requestError.message || 'Unable to load conversation.');
        } finally {
            pollTimer = window.setTimeout(pollConversation, isOpen ? 3000 : 8000);
        }
    }

    function addOptimisticMessage(content) {
        const existing = Array.from(messages.querySelectorAll('.sp-widget-message'));
        if (existing.length === 0) {
            messages.textContent = '';
        }

        messages.appendChild(createMessageElement({
            sender_type: 'user',
            content,
            created_at: new Date().toISOString(),
        }));
        scrollMessagesToBottom();
    }

    function renderMessages(items) {
        messages.textContent = '';

        if (items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'sp-widget-empty';
            empty.textContent = 'Ask a question and our AI support assistant will help.';
            messages.appendChild(empty);
            return;
        }

        items.forEach(function (item) {
            messages.appendChild(createMessageElement(item));
        });

        scrollMessagesToBottom();
    }

    function createMessageElement(item) {
        const bubble = document.createElement('div');
        bubble.className = 'sp-widget-message';
        bubble.setAttribute('data-sender', item.sender_type);
        bubble.textContent = item.content;

        if (item.sender_type === 'ai' && typeof item.confidence === 'number') {
            const meta = document.createElement('span');
            meta.className = 'sp-widget-meta';
            meta.textContent = 'AI confidence ' + Math.round(item.confidence * 100) + '%';
            bubble.appendChild(meta);
        }

        return bubble;
    }

    function setError(message) {
        error.textContent = message;
        error.setAttribute('data-show', String(Boolean(message)));
    }

    function scrollMessagesToBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttribute(value) {
        return escapeHtml(value).replace(/`/g, '&#096;');
    }
})();
