const CONFIG = {
    apiUrl: '',
    userId: '',
    contentId: '',
    contentType: ''
};

function toggleChat() {
    const container = document.getElementById('chatContainer');
    container.classList.toggle('active');
    if (container.classList.contains('active')) {
        document.getElementById('chatInput').focus();
    }
}

function adjustTextareaHeight(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
}

function handleKeyDown(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

function getTime() {
    const now = new Date();
    return now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

async function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();

    if (!message) return;

    addMessage(message, 'user');
    input.value = '';
    input.style.height = 'auto';

    showTypingIndicator();

    // CÓDIGO REAL - Chamada para API
    try {
        const response = await fetch(CONFIG.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                user_id: CONFIG.userId,
                content_id: CONFIG.contentId,
                content_type: CONFIG.contentType,
                message: message
            })
        });

        const data = await response.json();

        hideTypingIndicator();

        if (data.success && data.message) {
            typeMessage(data.message);
        } else if (data.error) {
            typeMessage(data.error);
        } else {
            typeMessage('Desculpe, ocorreu um erro ao processar sua mensagem. Tente novamente.');
        }
    } catch (error) {
        hideTypingIndicator();
        typeMessage('Erro de conexão. Verifique sua internet e tente novamente.');
        console.error('Erro ao enviar mensagem:', error);
    }

    /* CÓDIGO MOCK - Comentar quando usar API real
    setTimeout(() => {
        hideTypingIndicator();

        const mockResponses = [
            'Entendo sua pergunta. Baseado no contexto, posso te ajudar com isso.',
            'Interessante! Deixe-me explicar melhor sobre esse assunto.',
            'Ótima questão! Aqui está o que você precisa saber.',
            'Com certeza! Vou te auxiliar da melhor forma possível.',
            'Perfeito! Vou te dar uma resposta completa sobre isso.',
            'Essa é uma dúvida comum. Veja só a explicação.'
        ];

        const randomResponse = mockResponses[Math.floor(Math.random() * mockResponses.length)];
        typeMessage(randomResponse);

    }, 500);
    */
}

function typeMessage(text) {
    const messagesContainer = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message bot';

    const avatar = document.createElement('div');
    avatar.className = 'message-avatar';

    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';

    const bubbleDiv = document.createElement('div');
    bubbleDiv.className = 'message-bubble';

    const timeDiv = document.createElement('div');
    timeDiv.className = 'message-time';
    timeDiv.textContent = getTime();

    contentDiv.appendChild(bubbleDiv);
    contentDiv.appendChild(timeDiv);
    messageDiv.appendChild(avatar);
    messageDiv.appendChild(contentDiv);
    messagesContainer.appendChild(messageDiv);

    let index = 0;
    const typingSpeed = 30;

    function typeChar() {
        if (index < text.length) {
            const span = document.createElement('span');
            span.className = 'typing-char';
            span.textContent = text.charAt(index);
            bubbleDiv.appendChild(span);

            index++;
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            setTimeout(typeChar, typingSpeed);
        }
    }

    typeChar();
}

function addMessage(text, sender) {
    const messagesContainer = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${sender}`;

    const avatar = document.createElement('div');
    avatar.className = 'message-avatar';

    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';

    const bubbleDiv = document.createElement('div');
    bubbleDiv.className = 'message-bubble';
    bubbleDiv.textContent = text;

    const timeDiv = document.createElement('div');
    timeDiv.className = 'message-time';
    timeDiv.textContent = getTime();

    contentDiv.appendChild(bubbleDiv);
    contentDiv.appendChild(timeDiv);
    messageDiv.appendChild(avatar);
    messageDiv.appendChild(contentDiv);
    messagesContainer.appendChild(messageDiv);

    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function showTypingIndicator() {
    const messagesContainer = document.getElementById('chatMessages');
    const typingDiv = document.createElement('div');
    typingDiv.id = 'typingIndicator';
    typingDiv.className = 'message bot';
    typingDiv.innerHTML = `
        <div class="message-avatar"></div>
        <div class="typing-indicator active">
            <div class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    `;
    messagesContainer.appendChild(typingDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function hideTypingIndicator() {
    const indicator = document.getElementById('typingIndicator');
    if (indicator) {
        indicator.remove();
    }
}
