const CONFIG = {
    apiUrl: '',
    userId: '',
    contentId: '',
    contentType: '',
    userAvatar: '',
    maxStoredMessages: 20,
    storageKey: 'chatWidget_messages'
};

function toggleChat() {
    const container = document.getElementById('chatContainer');
    container.classList.toggle('active');
    if (container.classList.contains('active')) {
        document.getElementById('chatInput').focus();
        loadMessagesFromStorage();
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

/**
 * Salva as mensagens no localStorage
 * Mantém apenas as últimas 20 mensagens
 */
function saveMessagesToStorage() {
    try {
        const messagesContainer = document.getElementById('chatMessages');
        const messages = [];

        // Percorre todas as mensagens no container
        const messageElements = messagesContainer.querySelectorAll('.message:not(#typingIndicator)');

        messageElements.forEach(messageEl => {
            const sender = messageEl.classList.contains('user') ? 'user' : 'bot';
            const bubbleEl = messageEl.querySelector('.message-bubble');
            const timeEl = messageEl.querySelector('.message-time');

            if (bubbleEl && timeEl) {
                messages.push({
                    text: bubbleEl.innerHTML, // Salva HTML para preservar formatação markdown
                    sender: sender,
                    time: timeEl.textContent,
                    timestamp: Date.now()
                });
            }
        });

        // Mantém apenas as últimas 20 mensagens
        const recentMessages = messages.slice(-CONFIG.maxStoredMessages);

        localStorage.setItem(CONFIG.storageKey, JSON.stringify(recentMessages));
    } catch (error) {
        console.error('Erro ao salvar mensagens:', error);
    }
}

/**
 * Carrega as mensagens do localStorage
 * Chamada quando o chat é aberto
 */
function loadMessagesFromStorage() {
    try {
        const messagesContainer = document.getElementById('chatMessages');

        // Verifica se já há mensagens carregadas (exceto a mensagem de boas-vindas padrão)
        const existingMessages = messagesContainer.querySelectorAll('.message:not(#typingIndicator)');
        if (existingMessages.length > 1) {
            return; // Já há mensagens, não precisa carregar
        }

        const stored = localStorage.getItem(CONFIG.storageKey);

        if (!stored) {
            return; // Não há mensagens salvas
        }

        const messages = JSON.parse(stored);

        if (!messages || messages.length === 0) {
            return;
        }

        // Limpa o container (remove mensagem de boas-vindas se houver)
        messagesContainer.innerHTML = '';

        // Adiciona cada mensagem salva
        messages.forEach(msg => {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${msg.sender}`;

            let avatar;
            // Adiciona imagem do usuário ou do bot
            if (msg.sender === 'user' && CONFIG.userAvatar) {
                avatar = document.createElement('img');
                avatar.src = CONFIG.userAvatar;
                avatar.alt = 'User Avatar';
                avatar.className = 'message-avatar';
            } else if (msg.sender === 'bot') {
                avatar = document.createElement('img');
                avatar.src = '/img/chat-widget/ai_agent_icon.png';
                avatar.alt = 'AI Assistant';
                avatar.className = 'message-avatar';
            } else {
                avatar = document.createElement('div');
                avatar.className = 'message-avatar';
            }

            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';

            const bubbleDiv = document.createElement('div');
            bubbleDiv.className = 'message-bubble';
            bubbleDiv.innerHTML = msg.text; // Usa innerHTML para preservar formatação

            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time';
            timeDiv.textContent = msg.time;

            contentDiv.appendChild(bubbleDiv);
            contentDiv.appendChild(timeDiv);
            messageDiv.appendChild(avatar);
            messageDiv.appendChild(contentDiv);
            messagesContainer.appendChild(messageDiv);
        });

        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    } catch (error) {
        console.error('Erro ao carregar mensagens:', error);
    }
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

function parseMarkdown(text) {
    // Converte markdown para HTML
    return text
        // Negrito: **texto** ou __texto__
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/__(.+?)__/g, '<strong>$1</strong>')
        // Itálico: *texto* ou _texto_
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/_(.+?)_/g, '<em>$1</em>')
        // Links: [texto](url)
        .replace(/\[(.+?)\]\((.+?)\)/g, '<a href="$2" target="_blank">$1</a>')
        // Listas: - item ou * item
        .replace(/^[\-\*] (.+)$/gm, '<li>$1</li>')
        // Código inline: `código`
        .replace(/`(.+?)`/g, '<code>$1</code>')
        // Quebras de linha
        .replace(/\n/g, '<br>');
}

function wrapListItems(html) {
    // Agrupa <li> consecutivos dentro de <ul>
    return html.replace(/(<li>.*?<\/li>)(<br>)?(?=<li>|$)/gs, function(match) {
        return match;
    }).replace(/(<li>.*?<\/li>(<br>)?)+/gs, function(match) {
        return '<ul>' + match.replace(/<br>/g, '') + '</ul>';
    });
}

function typeMessage(text) {
    const messagesContainer = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message bot';

    const avatar = document.createElement('img');
    avatar.src = '/img/chat-widget/ai_agent_icon.png';
    avatar.alt = 'AI Assistant';
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
    const typingSpeed = 10; // Velocidade de digitação em ms

    function typeChar() {
        if (index < text.length) {
            const currentText = text.substring(0, index + 1);
            // Aplica formatação markdown
            const formattedText = parseMarkdown(currentText);
            bubbleDiv.innerHTML = wrapListItems(formattedText);
            index++;
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            setTimeout(typeChar, typingSpeed);
        } else {
            // Salva as mensagens quando terminar de digitar
            saveMessagesToStorage();
        }
    }

    typeChar();
}

function addMessage(text, sender) {
    const messagesContainer = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${sender}`;

    let avatar;
    // Adiciona imagem do usuário ou do bot
    if (sender === 'user' && CONFIG.userAvatar) {
        avatar = document.createElement('img');
        avatar.src = CONFIG.userAvatar;
        avatar.alt = 'User Avatar';
        avatar.className = 'message-avatar';
    } else if (sender === 'bot') {
        avatar = document.createElement('img');
        avatar.src = '/img/chat-widget/ai_agent_icon.png';
        avatar.alt = 'AI Assistant';
        avatar.className = 'message-avatar';
    } else {
        avatar = document.createElement('div');
        avatar.className = 'message-avatar';
    }

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

    // Salva as mensagens após adicionar uma nova mensagem
    saveMessagesToStorage();
}

function showTypingIndicator() {
    const messagesContainer = document.getElementById('chatMessages');
    const typingDiv = document.createElement('div');
    typingDiv.id = 'typingIndicator';
    typingDiv.className = 'message bot';
    typingDiv.innerHTML = `
        <img src="/img/chat-widget/ai_agent_icon.png" alt="AI Assistant" class="message-avatar">
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

/**
 * Atualiza o contexto do chat (contentId e contentType)
 * Útil quando o usuário navega para um novo vídeo/conteúdo
 *
 * @param {string|number} contentId - ID do novo conteúdo
 * @param {string} contentType - Tipo do conteúdo (ex: 'video', 'article', etc)
 * @param {boolean} clearChat - Se true, limpa o histórico de mensagens (padrão: true)
 */
function updateChatContext(contentId, contentType = 'video', clearChat = true) {
    CONFIG.contentId = contentId;
    CONFIG.contentType = contentType;

    if (clearChat) {
        clearChatMessages();

        // Adiciona mensagem de boas-vindas com novo contexto
        const messagesContainer = document.getElementById('chatMessages');
        const welcomeMessage = document.createElement('div');
        welcomeMessage.className = 'message bot';
        welcomeMessage.innerHTML = `
            <img src="/img/chat-widget/ai_agent_icon.png" alt="AI Assistant" class="message-avatar">
            <div class="message-content">
                <div class="message-bubble">
                    Olá 👋 Sou a AnaAssist, estou aqui para te guiar e tirar dúvidas sobre os nossos vídeos. Qual seria a sua pergunta?
                </div>
                <div class="message-time">${getTime()}</div>
            </div>
        `;
        messagesContainer.appendChild(welcomeMessage);
    }

    console.log(`Chat context updated: contentId=${contentId}, contentType=${contentType}`);
}

/**
 * Limpa todas as mensagens do chat
 */
function clearChatMessages() {
    const messagesContainer = document.getElementById('chatMessages');
    if (messagesContainer) {
        messagesContainer.innerHTML = '';
    }
    // Limpa também o localStorage
    localStorage.removeItem(CONFIG.storageKey);
}
