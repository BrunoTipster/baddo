/**
 * Funcionalidades do Chat
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 16:14:27
 */

'use strict';

const Chat = {
    config: {
        messageLimit: 50,
        pollInterval: 5000,
        typingTimeout: 3000,
        soundEnabled: true
    },

    elements: {
        container: null,
        messages: null,
        input: null,
        form: null,
        typing: null
    },

    state: {
        matchId: null,
        lastMessageId: 0,
        isTyping: false,
        typingTimer: null,
        pollingTimer: null,
        unreadCount: 0
    },

    sounds: {
        message: new Audio('/assets/sounds/message.mp3'),
        send: new Audio('/assets/sounds/send.mp3')
    },

    init(matchId) {
        this.state.matchId = matchId;
        this.cacheElements();
        this.setupEventListeners();
        this.startPolling();
        this.scrollToBottom();
        this.markAsRead();
    },

    cacheElements() {
        this.elements.container = document.querySelector('.chat-container');
        this.elements.messages = document.querySelector('.chat-messages');
        this.elements.input = document.querySelector('.chat-input');
        this.elements.form = document.querySelector('.chat-form');
        this.elements.typing = document.querySelector('.chat-typing');

        if (!this.elements.container || !this.elements.messages || 
            !this.elements.input || !this.elements.form) {
            throw new Error('Elementos do chat não encontrados');
        }
    },

    setupEventListeners() {
        // Form submit
        this.elements.form.addEventListener('submit', this.handleSubmit.bind(this));

        // Digitando
        this.elements.input.addEventListener('input', this.handleTyping.bind(this));

        // Teclas especiais
        this.elements.input.addEventListener('keydown', this.handleKeydown.bind(this));

        // Auto-resize textarea
        this.elements.input.addEventListener('input', this.autoResizeInput.bind(this));

        // Scroll para detectar mensagens antigas
        this.elements.messages.addEventListener('scroll', this.handleScroll.bind(this));
    },

    async handleSubmit(event) {
        event.preventDefault();

        const message = this.elements.input.value.trim();
        if (!message) return;

        try {
            const response = await this.sendMessage(message);
            
            if (response.success) {
                this.elements.input.value = '';
                this.autoResizeInput();
                this.appendMessage(response.message);
                this.scrollToBottom();
                this.playSound('send');
            }
        } catch (error) {
            App.handleError(error);
        }
    },

    handleTyping() {
        if (!this.state.isTyping) {
            this.state.isTyping = true;
            this.sendTypingStatus(true);
        }

        clearTimeout(this.state.typingTimer);
        this.state.typingTimer = setTimeout(() => {
            this.state.isTyping = false;
            this.sendTypingStatus(false);
        }, this.config.typingTimeout);
    },

    handleKeydown(event) {
        // Enter para enviar (Shift+Enter para nova linha)
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            this.elements.form.dispatchEvent(new Event('submit'));
        }
    },

    handleScroll() {
        // Carregar mensagens antigas quando chegar ao topo
        if (this.elements.messages.scrollTop === 0) {
            this.loadOlderMessages();
        }
    },

    async sendMessage(message) {
        const response = await fetch(`${App.config.apiUrl}/messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                match_id: this.state.matchId,
                message: message
            })
        });

        if (!response.ok) {
            throw new Error('Erro ao enviar mensagem');
        }

        return response.json();
    },

    async sendTypingStatus(isTyping) {
        try {
            await fetch(`${App.config.apiUrl}/messages/typing`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    match_id: this.state.matchId,
                    is_typing: isTyping
                })
            });
        } catch (error) {
            console.error('Erro ao enviar status de digitação:', error);
        }
    },

    async loadOlderMessages() {
        const firstMessage = this.elements.messages.firstElementChild;
        const oldScrollHeight = this.elements.messages.scrollHeight;

        try {
            const response = await fetch(
                `${App.config.apiUrl}/messages?match_id=${this.state.matchId}&before=${this.state.lastMessageId}&limit=${this.config.messageLimit}`
            );

            if (!response.ok) throw new Error('Erro ao carregar mensagens');

            const data = await response.json();
            
            if (data.messages.length > 0) {
                data.messages.reverse().forEach(message => {
                    this.prependMessage(message);
                });

                // Manter posição do scroll
                const newScrollHeight = this.elements.messages.scrollHeight;
                this.elements.messages.scrollTop = newScrollHeight - oldScrollHeight;
            }
        } catch (error) {
            App.handleError(error);
        }
    },

    startPolling() {
        this.pollNewMessages();
        this.state.pollingTimer = setInterval(
            this.pollNewMessages.bind(this),
            this.config.pollInterval
        );
    },

    async pollNewMessages() {
        if (!App.state.isOnline) return;

        try {
            const response = await fetch(
                `${App.config.apiUrl}/messages?match_id=${this.state.matchId}&after=${this.state.lastMessageId}`
            );

            if (!response.ok) throw new Error('Erro ao buscar novas mensagens');

            const data = await response.json();
            
            if (data.messages.length > 0) {
                data.messages.forEach(message => {
                    this.appendMessage(message);
                });

                // Se estiver próximo do fim, rolar para baixo
                const isNearBottom = this.elements.messages.scrollHeight - 
                    this.elements.messages.scrollTop - 
                    this.elements.messages.clientHeight < 100;

                if (isNearBottom) {
                    this.scrollToBottom();
                }

                if (data.typing) {
                    this.showTypingIndicator();
                } else {
                    this.hideTypingIndicator();
                }

                this.playSound('message');
            }
        } catch (error) {
            console.error('Erro ao buscar novas mensagens:', error);
        }
    },

    appendMessage(message) {
        const html = this.createMessageHTML(message);
        this.elements.messages.insertAdjacentHTML('beforeend', html);
        this.state.lastMessageId = Math.max(this.state.lastMessageId, message.id);
    },

    prependMessage(message) {
        const html = this.createMessageHTML(message);
        this.elements.messages.insertAdjacentHTML('afterbegin', html);
    },

    createMessageHTML(message) {
        const isMine = message.sender_id === parseInt(App.config.userId);
        const time = App.helpers.formatDate(message.created_at, 'HH:mm');
        
        return `
            <div class="chat-message ${isMine ? 'mine' : 'theirs'}" data-id="${message.id}">
                ${!isMine ? `
                    <img src="${App.helpers.escapeHtml(message.sender_avatar)}" 
                         class="message-avatar" 
                         alt="Avatar">
                ` : ''}
                <div class="message-content">
                    <div class="message-bubble">
                        ${App.helpers.escapeHtml(message.message)}
                    </div>
                    <small class="message-time">
                        ${time}
                        ${isMine ? `
                            <i class="bi bi-check${message.is_read ? '2-all' : '2'}" 
                               title="${message.is_read ? 'Lida' : 'Enviada'}">
                            </i>
                        ` : ''}
                    </small>
                </div>
            </div>
        `;
    },

    showTypingIndicator() {
        if (!this.elements.typing.classList.contains('show')) {
            this.elements.typing.classList.add('show');
        }
    },

    hideTypingIndicator() {
        this.elements.typing.classList.remove('show');
    },

    autoResizeInput() {
        const input = this.elements.input;
        input.style.height = 'auto';
        input.style.height = (input.scrollHeight) + 'px';
    },

    scrollToBottom() {
        this.elements.messages.scrollTop = this.elements.messages.scrollHeight;
    },

    async markAsRead() {
        try {
            await fetch(`${App.config.apiUrl}/messages/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    match_id: this.state.matchId
                })
            });
        } catch (error) {
            console.error('Erro ao marcar mensagens como lidas:', error);
        }
    },

    playSound(type) {
        if (!this.config.soundEnabled) return;
        
        const sound = this.sounds[type];
        if (sound) {
            sound.currentTime = 0;
            sound.play().catch(() => {});
        }
    },

    destroy() {
        clearInterval(this.state.pollingTimer);
        clearTimeout(this.state.typingTimer);
        
        // Remover event listeners
        this.elements.form.removeEventListener('submit', this.handleSubmit);
        this.elements.input.removeEventListener('input', this.handleTyping);
        this.elements.input.removeEventListener('keydown', this.handleKeydown);
        this.elements.messages.removeEventListener('scroll', this.handleScroll);
    }
};

// Exportar módulo
export default Chat;