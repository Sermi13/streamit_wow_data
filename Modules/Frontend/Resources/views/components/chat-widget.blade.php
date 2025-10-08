<div class="chat-widget">
    <button class="chat-button" onclick="toggleChat()">
        <div class="chat-button-icon">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </button>

    <div class="chat-container" id="chatContainer">
        <div class="chat-header">
            <div class="chat-header-top">
                <span class="header-avatar"></span>
                <h3 class="header-title">Assistente IA</h3>
                <button class="close-btn" onclick="toggleChat()">×</button>
            </div>
            <p class="header-subtitle">Pronto para ajudar</p>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="message bot">
                <div class="message-avatar"></div>
                <div class="message-content">
                    <div class="message-bubble">
                        Olá 👋 Sou o assistente para te guiar e tirar duvidas sobre os nossos vídeos, qual seria a sua pergunta?
                    </div>
                    <div class="message-time">{{ date('H:i') }}</div>
                </div>
            </div>
        </div>

        <div class="chat-input-area">
            <div class="input-wrapper">
                <textarea
                    class="chat-input"
                    id="chatInput"
                    placeholder="Digite sua mensagem..."
                    rows="1"
                    oninput="adjustTextareaHeight(this)"
                    onkeydown="handleKeyDown(event)"
                ></textarea>
            </div>
            <button class="send-btn" id="sendBtn" onclick="sendMessage()">
                <svg viewBox="0 0 24 24">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </div>
    </div>
</div>

@push('after-scripts')
<script>
    // Configuração dinâmica com dados do Laravel
    CONFIG.userId = '{{ Auth::check() ? Auth::id() : '0' }}';
    CONFIG.contentId = '{{ $contentId ?? '0' }}';
    CONFIG.contentType = '{{ $contentType ?? 'unknown' }}';
    CONFIG.apiUrl = '{{ route('api.chat') }}';
</script>
@endpush
