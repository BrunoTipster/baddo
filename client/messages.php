<?php
/**
 * Página de Mensagens
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/utils/helpers.php';
require_once BASE_PATH . '/utils/Database.php';

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();

// Buscar todas as conversas
$conversations = $db->all(
    "SELECT 
        mt.id as match_id,
        u.id as user_id,
        u.name,
        u.username,
        u.avatar,
        u.is_online,
        u.last_active,
        (
            SELECT message 
            FROM messages 
            WHERE match_id = mt.id 
            ORDER BY created_at DESC 
            LIMIT 1
        ) as last_message,
        (
            SELECT created_at 
            FROM messages 
            WHERE match_id = mt.id 
            ORDER BY created_at DESC 
            LIMIT 1
        ) as last_message_date,
        (
            SELECT COUNT(*) 
            FROM messages 
            WHERE match_id = mt.id 
            AND sender_id != ? 
            AND is_read = 0
        ) as unread_count,
        (
            SELECT COUNT(*) 
            FROM messages 
            WHERE match_id = mt.id
        ) as total_messages
    FROM matches mt
    INNER JOIN users u ON (
        CASE 
            WHEN mt.user1_id = ? THEN mt.user2_id = u.id
            ELSE mt.user1_id = u.id
        END
    )
    WHERE 
        (mt.user1_id = ? OR mt.user2_id = ?)
        AND mt.status = 'matched'
        AND u.status = 'active'
    ORDER BY COALESCE(last_message_date, mt.created_at) DESC",
    [$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]
);

// Buscar total de mensagens não lidas
$total_unread = $db->single(
    "SELECT COUNT(*) as count
     FROM messages m
     INNER JOIN matches mt ON m.match_id = mt.id
     WHERE (mt.user1_id = ? OR mt.user2_id = ?)
     AND m.sender_id != ?
     AND m.is_read = 0",
    [$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]
)['count'];

// Buscar dados do usuário atual
$current_user = $db->single(
    "SELECT * FROM users WHERE id = ?",
    [$_SESSION['user_id']]
);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagens - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #5C6BC0;
            --secondary-color: #3F51B5;
            --accent-color: #FF4081;
            --success-color: #4CAF50;
            --warning-color: #FFC107;
            --danger-color: #F44336;
            --gray-color: #757575;
            --light-gray: #f5f5f5;
            --dark-gray: #212121;
        }

        body {
            background-color: var(--light-gray);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }

        .nav-link {
            position: relative;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            transform: translateY(-2px);
        }

        .nav-link .badge {
            position: absolute;
            top: 0;
            right: 0;
            transform: translate(50%, -50%);
        }

        .messages-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-top: 2rem;
            height: calc(100vh - 100px);
            overflow: hidden;
        }

        .conversation-list {
            border-right: 1px solid #dee2e6;
            height: 100%;
            overflow-y: auto;
        }

        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .conversation-item:hover {
            background-color: var(--light-gray);
            transform: translateY(-2px);
        }

        .conversation-item.active {
            background-color: #e3f2fd;
            border-left: 4px solid var(--primary-color);
        }

        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .online-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--success-color);
            display: inline-block;
            margin-right: 5px;
            box-shadow: 0 0 0 2px white;
        }

        .offline-indicator {
            background-color: var(--gray-color);
        }

        .unread-badge {
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .message-preview {
            color: var(--gray-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
            font-size: 0.875rem;
        }

        .chat-container {
            height: 100%;
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
        }

        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            background-color: white;
            z-index: 1;
        }

        .chat-messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .chat-input {
            padding: 1rem;
            background-color: white;
            border-top: 1px solid #dee2e6;
        }

        .message-bubble {
            max-width: 75%;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
            position: relative;
            word-wrap: break-word;
        }

        .message-sent {
            background-color: var(--primary-color);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 0.25rem;
        }

        .message-received {
            background-color: white;
            margin-right: auto;
            border-bottom-left-radius: 0.25rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .message-time {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            opacity: 0.8;
        }

        .message-status {
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray-color);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .input-message {
            border-radius: 20px;
            padding-right: 100px;
        }

        .btn-send {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            border-radius: 0 20px 20px 0;
            padding: 0 1.5rem;
        }

        .typing-indicator {
            display: none;
            padding: 0.5rem;
            color: var(--gray-color);
            font-size: 0.875rem;
        }

        .typing-indicator.active {
            display: block;
        }

        /* Scrollbar personalizada */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-bubble {
            animation: fadeIn 0.3s ease;
        }

        .conversation-item {
            animation: fadeIn 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-chat-heart-fill"></i> <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="matches.php">
                            <i class="bi bi-heart"></i> Matches
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="messages.php">
                            <i class="bi bi-chat"></i> Mensagens
                            <?php if ($total_unread > 0): ?>
                                <span class="badge bg-danger"><?php echo $total_unread; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <img src="<?php echo !empty($current_user['avatar']) ? '../uploads/avatars/' . $current_user['avatar'] : '../assets/images/default-avatar.jpg'; ?>" 
                                 class="rounded-circle me-2" 
                                 style="width: 30px; height: 30px; object-fit: cover;">
                            <?php echo htmlspecialchars($current_user['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="edit-profile.php">
                                    <i class="bi bi-person"></i> Editar Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="bi bi-gear"></i> Configurações
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="../logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Sair
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="messages-container">
            <div class="row g-0">
                <!-- Lista de Conversas -->
                <div class="col-md-4 conversation-list">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-chat-dots"></i> Conversas
                        </h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="markAllAsRead()">
                                <i class="bi bi-check-all"></i> Marcar todas como lidas
                            </button>
                        </div>
                    </div>
                    
                    <?php if (empty($conversations)): ?>
                        <div class="empty-state">
                            <i class="bi bi-chat-dots"></i>
                            <h5>Nenhuma conversa ainda</h5>
                            <p class="mb-3">Faça matches para começar a conversar</p>
                            <a href="matches.php" class="btn btn-primary">
                                <i class="bi bi-heart"></i> Encontrar Matches
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <div class="conversation-item" 
                                 onclick="loadChat(<?php echo $conv['match_id']; ?>)"
                                 data-match-id="<?php echo $conv['match_id']; ?>">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative">
                                        <img src="<?php echo !empty($conv['avatar']) ? '../uploads/avatars/' . $conv['avatar'] : '../assets/images/default-avatar.jpg'; ?>" 
                                             class="avatar" alt="Avatar">
                                        <?php if ($conv['is_online']): ?>
                                            <span class="online-indicator position-absolute bottom-0 end-0"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0">
                                                <?php echo htmlspecialchars($conv['name']); ?>
                                            </h6>
                                            <?php if ($conv['unread_count'] > 0): ?>
                                                <span class="unread-badge">
                                                    <?php echo $conv['unread_count']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-preview">
                                            <?php if ($conv['last_message']): ?>
                                                <?php echo htmlspecialchars($conv['last_message']); ?>
                                                <small class="text-muted d-block">
                                                    <?php echo timeAgo($conv['last_message_date']); ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">Iniciar conversa</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Área do Chat -->
                <div class="col-md-8">
                    <div class="chat-container">
                        <div class="chat-header d-none" id="chatHeader"></div>
                        <div class="chat-messages d-none" id="chatMessages">
                            <div class="empty-state" id="chatPlaceholder">
                                <i class="bi bi-chat-dots"></i>
                                <h5>Selecione uma conversa</h5>
                                <p>Escolha uma conversa para começar a trocar mensagens</p>
                            </div>
                        </div>
                        <div class="typing-indicator d-none" id="typingIndicator">
                            <i class="bi bi-three-dots"></i> Digitando...
                        </div>
                        <div class="chat-input d-none" id="chatInput">
                            <form id="messageForm" onsubmit="sendMessage(event)">
                                <div class="input-group">
                                    <input type="text" class="form-control input-message" 
                                           placeholder="Digite sua mensagem..." 
                                           id="messageText"
                                           autocomplete="off">
                                    <button class="btn btn-primary btn-send" type="submit">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentMatchId = null;
        let lastMessageId = 0;
        let typingTimeout = null;
        
        // Carregar chat
        function loadChat(matchId) {
            currentMatchId = matchId;
            
            // Atualizar item ativo
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
                if (item.dataset.matchId == matchId) {
                    item.classList.add('active');
                }
            });
            
            // Ativar área do chat
            document.getElementById('chatHeader').classList.remove('d-none');
            document.getElementById('chatMessages').classList.remove('d-none');
            document.getElementById('chatInput').classList.remove('d-none');
            document.getElementById('chatPlaceholder').classList.add('d-none');
            
            // Carregar mensagens
            fetch(`get_messages.php?match_id=${matchId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Atualizar header
                        document.getElementById('chatHeader').innerHTML = `
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <img src="${data.user.avatar}" class="avatar me-3" alt="Avatar">
                                    <div>
                                        <h6 class="mb-0">${data.user.name}</h6>
                                        <small class="text-muted">
                                            ${data.user.is_online ? 
                                                '<span class="online-indicator"></span>Online' : 
                                                `Visto por último ${data.user.last_active}`}
                                        </small>
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="clearChat(${matchId})">
                                                <i class="bi bi-trash"></i> Limpar conversa
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="blockUser(${data.user.id})">
                                                <i class="bi bi-shield-x"></i> Bloquear usuário
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" onclick="reportUser(${data.user.id})">
                                                <i class="bi bi-exclamation-triangle"></i> Reportar
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        `;
                        
                        // Atualizar mensagens
                        const messagesHtml = data.messages.map(msg => `
                            <div class="message-bubble ${msg.sender_id == <?php echo $_SESSION['user_id']; ?> ? 'message-sent' : 'message-received'}">
                                ${msg.message}
                                <div class="message-time">
                                    ${msg.created_at}
                                    ${msg.sender_id == <?php echo $_SESSION['user_id']; ?> ? 
                                        `<span class="message-status">
                                            ${msg.is_read ? 
                                                '<i class="bi bi-check2-all"></i>' : 
                                                '<i class="bi bi-check2"></i>'}
                                        </span>` : ''}
                                </div>
                            </div>
                        `).join('');
                        
                        document.getElementById('chatMessages').innerHTML = messagesHtml;
                        scrollToBottom();
                    }
                });
        }
        
        // Enviar mensagem
        function sendMessage(event) {
            event.preventDefault();
            
            const messageText = document.getElementById('messageText').value.trim();
            if (!messageText) return;
            
            const messageElement = document.getElementById('messageText');
            const submitButton = event.target.querySelector('button');
            
            // Desabilitar input e botão
            messageElement.disabled = true;
            submitButton.disabled = true;
            
            fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    match_id: currentMatchId,
                    message: messageText
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('messageText').value = '';
                    loadChat(currentMatchId);
                } else {
                    alert(data.error || 'Erro ao enviar mensagem');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao enviar mensagem');
            })
            .finally(() => {
                // Reabilitar input e botão
                messageElement.disabled = false;
                submitButton.disabled = false;
                messageElement.focus();
            });
        }
        
        // Rolar para última mensagem
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Marcar todas como lidas
        function markAllAsRead() {
            fetch('mark_all_read.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.unread-badge').forEach(badge => {
                        badge.remove();
                    });
                }
            });
        }
        
        // Indicador de digitação
        function showTypingIndicator() {
            clearTimeout(typingTimeout);
            const indicator = document.getElementById('typingIndicator');
            indicator.classList.remove('d-none');
            
            typingTimeout = setTimeout(() => {
                indicator.classList.add('d-none');
            }, 3000);
        }
        
        // Event Listeners
        document.getElementById('messageText').addEventListener('input', showTypingIndicator);
        
        // Atualizar mensagens periodicamente
        setInterval(() => {
            if (currentMatchId) {
                loadChat(currentMatchId);
            }
        }, 5000);
        
        // Verificar novas mensagens
        setInterval(() => {
            fetch('check_new_messages.php')
                .then(response => response.json())
                .then(data => {
                    if (data.has_new) {
                        // Atualizar badges e conversas
                    }
                });
        }, 10000);
    </script>
</body>
</html>