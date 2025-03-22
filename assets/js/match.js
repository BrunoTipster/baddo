/**
 * Sistema de Matching
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 16:17:20
 */

'use strict';

// Verificar se App global existe
if (typeof App === 'undefined') {
    throw new Error('App global não encontrado');
}

const Match = {
    // ... [todo o código anterior permanece igual até showMatchAnimation] ...

    showMatchAnimation(match) {
        const modal = document.createElement('div');
        modal.className = 'match-modal';
        modal.innerHTML = `
            <div class="match-modal-content">
                <div class="match-header">
                    <i class="bi bi-heart-fill text-danger"></i>
                    <h2>É um Match!</h2>
                </div>
                <div class="match-users">
                    <div class="match-user">
                        <img src="${App.config.siteUrl}/assets/images/avatars/${App.config.userAvatar}" 
                             alt="Seu avatar">
                    </div>
                    <div class="match-user">
                        <img src="${match.avatar}" 
                             alt="${App.helpers.escapeHtml(match.name)}">
                    </div>
                </div>
                <div class="match-message">
                    <p>Você e ${App.helpers.escapeHtml(match.name)} se curtiram!</p>
                </div>
                <div class="match-actions">
                    <button type="button" class="btn btn-lg btn-primary" data-action="chat">
                        <i class="bi bi-chat-heart"></i> Começar Conversa
                    </button>
                    <button type="button" class="btn btn-lg btn-outline-secondary" data-action="continue">
                        Continuar Explorando
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Adicionar event listeners
        modal.querySelector('[data-action="chat"]').addEventListener('click', () => {
            window.location.href = `${App.config.siteUrl}/chat.php?id=${match.id}`;
        });

        modal.querySelector('[data-action="continue"]').addEventListener('click', () => {
            modal.classList.add('fade-out');
            setTimeout(() => modal.remove(), 300);
        });

        // Animar entrada
        requestAnimationFrame(() => modal.classList.add('show'));
    },

    // Funções auxiliares que faltavam
    getCurrentCard() {
        return this.elements.cards.querySelector('.match-card');
    },

    nextCard() {
        this.state.currentIndex++;
        
        // Carregar mais cards se necessário
        if (this.state.currentIndex >= this.state.users.length - 3) {
            this.loadMoreUsers();
        }

        this.checkEmptyState();
    },

    checkEmptyState() {
        if (this.state.currentIndex >= this.state.users.length) {
            this.elements.noMoreCards?.classList.add('show');
            this.elements.cards.classList.add('empty');
        } else {
            this.elements.noMoreCards?.classList.remove('show');
            this.elements.cards.classList.remove('empty');
        }
    },

    updateUndoButton() {
        if (this.elements.undoButton) {
            this.elements.undoButton.disabled = this.state.lastAction.length === 0;
        }
    },

    preloadImages() {
        // Pré-carregar próximas imagens
        this.state.users.slice(this.state.currentIndex, this.state.currentIndex + 3)
            .forEach(user => {
                if (user.photos && user.photos.length > 0) {
                    const img = new Image();
                    img.src = user.photos[0];
                }
            });
    },

    // Método de destruição para limpeza
    destroy() {
        // Remover event listeners
        document.removeEventListener('keydown', this.handleKeydown);
        
        this.elements.likeButton?.removeEventListener('click', this.like);
        this.elements.dislikeButton?.removeEventListener('click', this.dislike);
        this.elements.undoButton?.removeEventListener('click', this.undo);
        
        if (this.elements.cards) {
            this.elements.cards.removeEventListener('touchstart', this.handleTouchStart);
            this.elements.cards.removeEventListener('touchmove', this.handleTouchMove);
            this.elements.cards.removeEventListener('touchend', this.handleTouchEnd);
        }

        // Limpar estado
        this.state = {
            users: [],
            currentIndex: 0,
            initialX: 0,
            currentX: 0,
            xDiff: 0,
            animating: false,
            touching: false,
            loadingMore: false,
            lastAction: []
        };

        // Limpar elementos
        this.elements = {
            container: null,
            cards: null,
            likeButton: null,
            dislikeButton: null,
            undoButton: null,
            noMoreCards: null
        };
    }
};

// Exportar módulo
export default Match;