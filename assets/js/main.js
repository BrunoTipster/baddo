/**
 * JavaScript Global
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 16:14:27
 */

'use strict';

// Namespace global
const App = {
    config: {
        apiUrl: '/api',
        siteUrl: document.querySelector('meta[name="site-url"]')?.content || '',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
        userId: document.querySelector('meta[name="user-id"]')?.content || null,
        debug: false
    },

    // Cache de elementos DOM frequentemente usados
    elements: {},

    // Estado global da aplicação
    state: {
        isOnline: true,
        notifications: [],
        lastActivity: Date.now()
    },

    // Inicialização
    init() {
        this.setupEventListeners();
        this.setupAjaxDefaults();
        this.setupUserActivity();
        this.initializeComponents();
        this.checkOnlineStatus();
    },

    // Configurar listeners globais
    setupEventListeners() {
        // Monitorar status online/offline
        window.addEventListener('online', () => this.handleOnlineStatus(true));
        window.addEventListener('offline', () => this.handleOnlineStatus(false));

        // Interceptar todos os forms para validação
        document.addEventListener('submit', this.handleFormSubmit.bind(this));

        // Manipular cliques em notificações
        document.addEventListener('click', this.handleNotificationClick.bind(this));
    },

    // Configurar defaults para requisições AJAX
    setupAjaxDefaults() {
        // Adicionar headers padrão para todas as requisições fetch
        const originalFetch = window.fetch;
        window.fetch = async (url, options = {}) => {
            const defaultHeaders = {
                'X-CSRF-Token': this.config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            };

            options.headers = {
                ...defaultHeaders,
                ...options.headers
            };

            try {
                const response = await originalFetch(url, options);
                
                // Verificar se usuário está logado
                if (response.status === 401) {
                    window.location.href = '/login';
                    return;
                }

                return response;
            } catch (error) {
                this.handleError(error);
                throw error;
            }
        };
    },

    // Monitorar atividade do usuário
    setupUserActivity() {
        const events = ['mousedown', 'keydown', 'scroll', 'touchstart'];
        const updateActivity = () => {
            this.state.lastActivity = Date.now();
            this.updateUserStatus();
        };

        events.forEach(event => {
            document.addEventListener(event, updateActivity, { passive: true });
        });

        // Atualizar status a cada 5 minutos
        setInterval(this.updateUserStatus.bind(this), 5 * 60 * 1000);
    },

    // Inicializar componentes
    initializeComponents() {
        // Bootstrap Tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });

        // Bootstrap Popovers
        const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
        popovers.forEach(popover => {
            new bootstrap.Popover(popover);
        });
    },

    // Verificar status online
    async checkOnlineStatus() {
        try {
            const response = await fetch(`${this.config.apiUrl}/ping`);
            this.handleOnlineStatus(response.ok);
        } catch {
            this.handleOnlineStatus(false);
        }
    },

    // Manipular mudança de status online
    handleOnlineStatus(isOnline) {
        this.state.isOnline = isOnline;
        document.body.classList.toggle('is-offline', !isOnline);

        if (!isOnline) {
            this.showNotification('Você está offline. Algumas funcionalidades podem estar indisponíveis.', 'warning');
        }
    },

    // Atualizar status do usuário
    async updateUserStatus() {
        if (!this.config.userId || !this.state.isOnline) return;

        try {
            await fetch(`${this.config.apiUrl}/users/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    is_online: true,
                    last_active: new Date().toISOString()
                })
            });
        } catch (error) {
            this.handleError(error);
        }
    },

    // Manipular envio de formulários
    handleFormSubmit(event) {
        const form = event.target;
        
        // Ignorar se não for um form ou se tiver data-no-validate
        if (!form.matches('form') || form.dataset.noValidate) return;

        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            
            // Destacar campos inválidos
            Array.from(form.elements).forEach(element => {
                if (!element.validity.valid) {
                    element.classList.add('is-invalid');
                }
            });

            // Focar primeiro campo inválido
            const firstInvalid = form.querySelector(':invalid');
            firstInvalid?.focus();
        }

        form.classList.add('was-validated');
    },

    // Manipular cliques em notificações
    handleNotificationClick(event) {
        const notification = event.target.closest('.notification');
        if (!notification) return;

        const action = notification.dataset.action;
        if (action === 'dismiss') {
            notification.remove();
        }
    },

    // Mostrar notificação
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} fade show`;
        notification.innerHTML = `
            <div class="notification-body">
                ${message}
            </div>
            <button type="button" class="btn-close" data-action="dismiss"></button>
        `;

        document.getElementById('notifications')?.appendChild(notification);

        if (duration > 0) {
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }

        return notification;
    },

    // Manipular erros
    handleError(error) {
        if (this.config.debug) {
            console.error('App Error:', error);
        }

        // Mostrar mensagem amigável para o usuário
        this.showNotification(
            'Ocorreu um erro. Por favor, tente novamente.',
            'error'
        );
    },

    // Helpers
    helpers: {
        // Formatar data
        formatDate(date, format = 'DD/MM/YYYY HH:mm') {
            const d = new Date(date);
            return format
                .replace('YYYY', d.getFullYear().toString())
                .replace('MM', (d.getMonth() + 1).toString().padStart(2, '0'))
                .replace('DD', d.getDate().toString().padStart(2, '0'))
                .replace('HH', d.getHours().toString().padStart(2, '0'))
                .replace('mm', d.getMinutes().toString().padStart(2, '0'));
        },

        // Tempo relativo
        timeAgo(date) {
            const seconds = Math.floor((Date.now() - new Date(date)) / 1000);
            
            const intervals = {
                ano: 31536000,
                mês: 2592000,
                semana: 604800,
                dia: 86400,
                hora: 3600,
                minuto: 60
            };

            for (const [unit, secondsInUnit] of Object.entries(intervals)) {
                const interval = Math.floor(seconds / secondsInUnit);
                
                if (interval >= 1) {
                    return `${interval} ${unit}${interval > 1 ? 's' : ''} atrás`;
                }
            }

            return 'agora mesmo';
        },

        // Truncar texto
        truncate(text, length = 100, suffix = '...') {
            if (text.length <= length) return text;
            return text.substring(0, length).trim() + suffix;
        },

        // Escape HTML
        escapeHtml(html) {
            const div = document.createElement('div');
            div.textContent = html;
            return div.innerHTML;
        }
    }
};

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => App.init());