/**
 * HU-029: Sistema de Chat - JavaScript
 * Lógica del frontend tipo Microsoft Teams
 */
(function($) {
    'use strict';

    // =============================== CONFIGURACIÓN ===============================
    const POLLING_INTERVAL = 5000; // 5 segundos
    const CONVERSATION_POLL_INTERVAL = 10000; // 10 segundos
    const SEARCH_DEBOUNCE = 350;
    const BASE_URL = window.CHAT_BASE_URL || '';

    // =============================== ESTADO ===============================
    let state = {
        currentConversationId: null,
        lastMessageId: 0,
        pollingTimer: null,
        convPollingTimer: null,
        searchTimeout: null,
        isSending: false,
        conversations: []
    };

    // =============================== COLORES DE ROL ===============================
    const ROL_COLORS = {
        1: '#dc3545',  // Admin
        2: '#1a3a5c',  // Gestor
        3: '#fd7e14',  // CTFG
        4: '#198754',  // Estudiante
        5: '#6f42c1'   // Asesor
    };

    const ROL_CLASSES = {
        1: 'rol-badge-admin',
        2: 'rol-badge-gestor',
        3: 'rol-badge-ctfg',
        4: 'rol-badge-estudiante',
        5: 'rol-badge-asesor'
    };

    // =============================== INICIALIZACIÓN ===============================
    $(document).ready(function() {
        initChat();
    });

    function initChat() {
        loadConversations();
        bindEvents();
        startConversationPolling();
    }

    // =============================== EVENTOS ===============================
    function bindEvents() {
        // Búsqueda de usuarios
        $('#chatSearchInput').on('input', function() {
            clearTimeout(state.searchTimeout);
            const term = $(this).val().trim();
            if (term.length < 1) {
                hideSearchResults();
                return;
            }
            state.searchTimeout = setTimeout(() => searchUsers(term), SEARCH_DEBOUNCE);
        });

        // Cerrar búsqueda al hacer clic fuera
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.chat-search-container').length) {
                hideSearchResults();
            }
        });

        // Enviar mensaje
        $('#chatSendBtn').on('click', sendCurrentMessage);
        
        // Enter para enviar (Shift+Enter para nueva línea)
        $('#chatMessageInput').on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendCurrentMessage();
            }
        });

        // Auto-resize del textarea
        $('#chatMessageInput').on('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // Botón atrás (móvil)
        $('#chatBackBtn').on('click', function() {
            closeChat();
        });

        // Botón ver miembros del grupo
        $(document).on('click', '#chatGroupMembersBtn', function() {
            $('#chatMembersModal').modal('show');
        });
    }

    // =============================== BÚSQUEDA DE USUARIOS ===============================
    function searchUsers(term) {
        $.ajax({
            url: BASE_URL + 'chat_search_users.php',
            data: { term: term },
            dataType: 'json',
            success: function(resp) {
                if (resp.success && resp.users.length > 0) {
                    showSearchResults(resp.users);
                } else {
                    showNoSearchResults();
                }
            },
            error: function(xhr, status, error) {
                console.error('Chat: Error búsqueda:', status, error, xhr.responseText);
                hideSearchResults();
            }
        });
    }

    function showSearchResults(users) {
        const $results = $('#chatSearchResults');
        $results.empty();
        
        users.forEach(function(user) {
            const color = ROL_COLORS[user.id_roll] || '#6c757d';
            const initials = getInitials(user.nombre);
            const rolClass = ROL_CLASSES[user.id_roll] || '';
            const emailInfo = user.email ? `<div class="user-email"><i class="bi bi-envelope"></i> ${escapeHtml(user.email)}</div>` : '';
            const idInfo = `<span class="user-id-tag"><i class="bi bi-person-badge"></i> ${escapeHtml(user.id)}</span>`;
            
            const $item = $(`
                <div class="chat-search-result-item" data-user-id="${escapeHtml(user.id)}" data-user-name="${escapeHtml(user.nombre)}">
                    <div class="user-avatar" style="background: ${color}">${initials}</div>
                    <div class="user-info">
                        <div class="user-name">${escapeHtml(user.nombre)}</div>
                        <div class="user-details-row">
                            <span class="rol-badge ${rolClass}">${escapeHtml(user.roll_name)}</span>
                            ${idInfo}
                        </div>
                        ${emailInfo}
                    </div>
                </div>
            `);
            
            $item.on('click', function() {
                startConversation(user.id, user.nombre, user.id_roll);
                hideSearchResults();
                $('#chatSearchInput').val('');
            });
            
            $results.append($item);
        });
        
        $results.addClass('show');
    }

    function showNoSearchResults() {
        const $results = $('#chatSearchResults');
        $results.html('<div class="chat-search-no-results"><i class="bi bi-search me-2"></i>No se encontraron usuarios</div>');
        $results.addClass('show');
    }

    function hideSearchResults() {
        $('#chatSearchResults').removeClass('show');
    }

    // =============================== CONVERSACIONES ===============================
    function loadConversations() {
        $.ajax({
            url: BASE_URL + 'chat_get_conversations.php',
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    state.conversations = resp.conversations;
                    renderConversationsList(resp.conversations);
                    updateHeaderBadge(resp.total_unread);
                } else {
                    console.error('Chat: Error cargando conversaciones:', resp.error);
                    $('#chatConversationsList').html(
                        '<div class="chat-empty-state" style="padding: 2rem 1rem;">' +
                        '<p style="font-size: 0.85rem; color: #999;">No hay conversaciones aún. Busca a alguien para chatear.</p></div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('Chat: AJAX error en conversaciones:', status, error);
                $('#chatConversationsList').html(
                    '<div class="chat-empty-state" style="padding: 2rem 1rem;">' +
                    '<p style="font-size: 0.85rem; color: #999;">No hay conversaciones aún. Busca a alguien para chatear.</p></div>'
                );
            }
        });
    }

    function renderConversationsList(conversations) {
        const $list = $('#chatConversationsList');
        $list.empty();
        
        if (conversations.length === 0) {
            $list.html(`
                <div class="chat-empty-state" style="padding: 2rem 1rem;">
                    <i class="bi bi-chat-dots"></i>
                    <p style="font-size: 0.85rem; color: #999;">No hay conversaciones aún. Busca a alguien para chatear.</p>
                </div>
            `);
            return;
        }
        
        conversations.forEach(function(conv) {
            const isGroup = conv.conversation_type === 'group';
            const isActive = state.currentConversationId === parseInt(conv.id);
            const hasUnread = conv.unread_count > 0;
            const color = isGroup ? '' : (conv.other_user ? (ROL_COLORS[conv.other_user.id_roll] || '#6c757d') : '#6c757d');
            const initials = isGroup ? '' : getInitials(conv.display_name);
            const lastTime = conv.last_message_at ? formatTime(conv.last_message_at) : '';
            const lastMsg = conv.last_message || (isGroup ? 'Chat de proyecto' : 'Iniciar conversación');
            
            let avatarHtml;
            if (isGroup) {
                avatarHtml = `<div class="conv-avatar group-avatar"><i class="bi bi-people-fill"></i></div>`;
            } else {
                avatarHtml = `<div class="conv-avatar" style="background: ${color}">${initials}</div>`;
            }
            
            const unreadHtml = hasUnread 
                ? `<span class="conv-unread-badge">${conv.unread_count > 99 ? '99+' : conv.unread_count}</span>` 
                : '';
            
            const participantCount = isGroup && conv.participant_count 
                ? `<span style="font-size:0.72rem;color:#aaa;"> · ${conv.participant_count} miembros</span>` 
                : '';
            
            const $item = $(`
                <div class="chat-conversation-item ${isActive ? 'active' : ''} ${hasUnread ? 'has-unread' : ''}" 
                     data-conversation-id="${conv.id}">
                    ${avatarHtml}
                    <div class="conv-info">
                        <div class="conv-name">${escapeHtml(conv.display_name)}${participantCount}</div>
                        <div class="conv-last-message">${escapeHtml(lastMsg)}</div>
                    </div>
                    <div class="conv-meta">
                        <div class="conv-time">${lastTime}</div>
                        ${unreadHtml}
                    </div>
                </div>
            `);
            
            $item.on('click', function() {
                openConversation(parseInt(conv.id));
            });
            
            $list.append($item);
        });
    }

    function startConversation(userId, userName, userRol) {
        // Verificar si ya hay una conversación individual con este usuario
        const existing = state.conversations.find(c => 
            c.conversation_type === 'individual' && 
            c.other_user && c.other_user.id === userId
        );
        
        if (existing) {
            openConversation(parseInt(existing.id));
            return;
        }
        
        // Crear nueva conversación enviando un objeto temporal
        state.currentConversationId = null;
        state.lastMessageId = 0;
        stopMessagePolling();
        
        const color = ROL_COLORS[userRol] || '#6c757d';
        const rolClass = ROL_CLASSES[userRol] || '';
        const rolNames = { 1: 'Administrador', 2: 'Gestor Académico', 3: 'CTFG', 4: 'Estudiante', 5: 'Asesor' };
        
        // Mostrar header del chat
        const headerHtml = `
            <div class="header-avatar" style="background: ${color}">${getInitials(userName)}</div>
            <div>
                <div class="header-name">${escapeHtml(userName)}</div>
                <span class="rol-badge ${rolClass}">${rolNames[userRol] || 'Usuario'}</span>
            </div>
        `;
        $('#chatHeaderInfo').html(headerHtml);
        $('#chatGroupMembersBtn').hide();
        
        // Limpiar mensajes y mostrar estado vacío
        $('#chatMessagesArea').html(`
            <div class="chat-no-messages">
                <i class="bi bi-chat-heart"></i>
                <p>Envía el primer mensaje para iniciar la conversación</p>
            </div>
        `);
        
        // Habilitar input con user_id pendiente
        enableInput();
        $('#chatMessageInput').data('pending-user-id', userId);
        $('#chatMessageInput').focus();
        
        // Mostrar panel de chat (móvil)
        $('.chat-container').addClass('chat-open');
    }

    function openConversation(conversationId) {
        if (state.currentConversationId === conversationId) return;
        
        state.currentConversationId = conversationId;
        state.lastMessageId = 0;
        stopMessagePolling();
        
        // Marcar como activa en la lista
        $('.chat-conversation-item').removeClass('active');
        $(`.chat-conversation-item[data-conversation-id="${conversationId}"]`).addClass('active');
        
        // Mostrar panel de chat (móvil)
        $('.chat-container').addClass('chat-open');
        
        // Cargar mensajes
        loadMessages(conversationId, false);
        
        // Marcar como leída
        markAsRead(conversationId);
        
        // Iniciar polling
        startMessagePolling();
        
        // Habilitar input
        enableInput();
        $('#chatMessageInput').removeData('pending-user-id');
        $('#chatMessageInput').focus();
    }

    function closeChat() {
        state.currentConversationId = null;
        stopMessagePolling();
        $('.chat-container').removeClass('chat-open');
        $('.chat-conversation-item').removeClass('active');
        disableInput();
        
        // Restaurar estado vacío
        showEmptyChat();
    }

    // =============================== MENSAJES ===============================
    function loadMessages(conversationId, isPolling) {
        const params = { conversation_id: conversationId };
        if (isPolling && state.lastMessageId > 0) {
            params.after_id = state.lastMessageId;
        }
        
        $.ajax({
            url: BASE_URL + 'chat_get_messages.php',
            data: params,
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    if (!isPolling) {
                        renderFullMessages(resp.messages, resp.conversation);
                        updateChatHeader(resp.conversation);
                    } else if (resp.messages.length > 0) {
                        appendNewMessages(resp.messages);
                    }
                    
                    // Actualizar lastMessageId
                    if (resp.messages.length > 0) {
                        state.lastMessageId = Math.max(
                            state.lastMessageId, 
                            ...resp.messages.map(m => parseInt(m.id))
                        );
                    }
                }
            }
        });
    }

    function updateChatHeader(conversation) {
        if (!conversation) return;
        
        const isGroup = conversation.type === 'group';
        let headerHtml = '';
        
        if (isGroup) {
            headerHtml = `
                <div class="header-avatar group-avatar" style="background: linear-gradient(135deg, #667eea, #764ba2)">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="header-name">${escapeHtml(conversation.display_name)}</div>
                    <div class="header-subtitle">Chat de Proyecto</div>
                </div>
            `;
            $('#chatGroupMembersBtn').show();
            // Cargar miembros si es grupo
            loadGroupMembers(conversation.id);
        } else {
            // Buscar la conversación en el estado para obtener info del otro usuario
            const conv = state.conversations.find(c => parseInt(c.id) === parseInt(conversation.id));
            if (conv && conv.other_user) {
                const color = ROL_COLORS[conv.other_user.id_roll] || '#6c757d';
                const rolClass = ROL_CLASSES[conv.other_user.id_roll] || '';
                headerHtml = `
                    <div class="header-avatar" style="background: ${color}">${getInitials(conv.other_user.nombre)}</div>
                    <div>
                        <div class="header-name">${escapeHtml(conv.other_user.nombre)}</div>
                        <span class="rol-badge ${rolClass}">${escapeHtml(conv.other_user.roll_name)}</span>
                    </div>
                `;
            } else {
                headerHtml = `
                    <div class="header-avatar" style="background: #6c757d"><i class="bi bi-person"></i></div>
                    <div><div class="header-name">${escapeHtml(conversation.display_name)}</div></div>
                `;
            }
            $('#chatGroupMembersBtn').hide();
        }
        
        $('#chatHeaderInfo').html(headerHtml);
    }

    function renderFullMessages(messages, conversation) {
        const $area = $('#chatMessagesArea');
        $area.empty();
        
        if (messages.length === 0) {
            $area.html(`
                <div class="chat-no-messages">
                    <i class="bi bi-chat-heart"></i>
                    <p>No hay mensajes aún. ¡Inicia la conversación!</p>
                </div>
            `);
            return;
        }
        
        let lastDate = '';
        messages.forEach(function(msg) {
            const msgDate = formatDate(msg.sent_at);
            if (msgDate !== lastDate) {
                $area.append(`<div class="chat-date-separator"><span>${msgDate}</span></div>`);
                lastDate = msgDate;
            }
            $area.append(createMessageHtml(msg));
        });
        
        scrollToBottom();
    }

    function appendNewMessages(messages) {
        const $area = $('#chatMessagesArea');
        
        // Remover estado vacío si existe
        $area.find('.chat-no-messages').remove();
        
        messages.forEach(function(msg) {
            $area.append(createMessageHtml(msg));
        });
        
        scrollToBottom();
    }

    function createMessageHtml(msg) {
        const isOwn = msg.is_own;
        const color = ROL_COLORS[msg.sender_roll_id] || '#6c757d';
        const rolClass = ROL_CLASSES[msg.sender_roll_id] || '';
        const initials = getInitials(msg.sender_name);
        const time = formatMessageTime(msg.sent_at);
        
        return `
            <div class="chat-message ${isOwn ? 'own-message' : 'other-message'}" data-msg-id="${msg.id}">
                <div class="msg-avatar" style="background: ${color}">${initials}</div>
                <div class="msg-content">
                    <div class="msg-header">
                        <span class="msg-sender-name">${escapeHtml(msg.sender_name)}</span>
                        <span class="rol-badge ${rolClass}">${escapeHtml(msg.sender_roll)}</span>
                        <span class="msg-time">${time}</span>
                    </div>
                    <div class="msg-bubble">${escapeHtml(msg.message_text)}</div>
                </div>
            </div>
        `;
    }

    function sendCurrentMessage() {
        if (state.isSending) return;
        
        const $input = $('#chatMessageInput');
        const messageText = $input.val().trim();
        
        if (messageText.length === 0) return;
        if (messageText.length > 5000) {
            Swal.fire('Mensaje muy largo', 'El mensaje no puede exceder 5000 caracteres.', 'warning');
            return;
        }
        
        state.isSending = true;
        $('#chatSendBtn').prop('disabled', true);
        
        const data = { message_text: messageText };
        
        // Si hay un user_id pendiente (nueva conversación), enviarlo
        const pendingUserId = $input.data('pending-user-id');
        if (pendingUserId) {
            data.user_id = pendingUserId;
        } else if (state.currentConversationId) {
            data.conversation_id = state.currentConversationId;
        } else {
            state.isSending = false;
            $('#chatSendBtn').prop('disabled', false);
            return;
        }
        
        $.ajax({
            url: BASE_URL + 'chat_send_message.php',
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    $input.val('');
                    $input.css('height', 'auto');
                    
                    // Si era una nueva conversación, actualizar el ID
                    if (pendingUserId && resp.conversation_id) {
                        state.currentConversationId = resp.conversation_id;
                        $input.removeData('pending-user-id');
                        startMessagePolling();
                    }
                    
                    // Agregar el mensaje a la vista
                    appendNewMessages([resp.message]);
                    
                    // Actualizar la última ID
                    state.lastMessageId = Math.max(state.lastMessageId, parseInt(resp.message.id));
                    
                    // Recargar lista de conversaciones
                    loadConversations();
                } else {
                    Swal.fire('Error', resp.error || 'No se pudo enviar el mensaje', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de conexión al enviar el mensaje', 'error');
            },
            complete: function() {
                state.isSending = false;
                $('#chatSendBtn').prop('disabled', false);
                $input.focus();
            }
        });
    }

    // =============================== MIEMBROS DEL GRUPO ===============================
    function loadGroupMembers(conversationId) {
        $.ajax({
            url: BASE_URL + 'chat_get_messages.php',
            data: { conversation_id: conversationId, limit: 1 },
            dataType: 'json',
            success: function(resp) {
                if (resp.success && resp.conversation && resp.conversation.type === 'group') {
                    // Cargar miembros via mensajes endpoint (la info viene en conversation)
                    // Necesitamos un endpoint dedicado, pero usamos la info disponible
                }
            }
        });
    }

    // =============================== MARK AS READ ===============================
    function markAsRead(conversationId) {
        $.ajax({
            url: BASE_URL + 'chat_mark_read.php',
            method: 'POST',
            data: { conversation_id: conversationId },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    updateHeaderBadge(resp.total_unread);
                    // Actualizar badge en la lista
                    const $item = $(`.chat-conversation-item[data-conversation-id="${conversationId}"]`);
                    $item.removeClass('has-unread');
                    $item.find('.conv-unread-badge').remove();
                }
            }
        });
    }

    // =============================== POLLING ===============================
    function startMessagePolling() {
        stopMessagePolling();
        state.pollingTimer = setInterval(function() {
            if (state.currentConversationId) {
                loadMessages(state.currentConversationId, true);
            }
        }, POLLING_INTERVAL);
    }

    function stopMessagePolling() {
        if (state.pollingTimer) {
            clearInterval(state.pollingTimer);
            state.pollingTimer = null;
        }
    }

    function startConversationPolling() {
        state.convPollingTimer = setInterval(function() {
            loadConversations();
        }, CONVERSATION_POLL_INTERVAL);
    }

    // =============================== HEADER BADGE ===============================
    function updateHeaderBadge(count) {
        const $badge = $('#chatHeaderBadge');
        if (count > 0) {
            $badge.text(count > 99 ? '99+' : count).show();
        } else {
            $badge.hide();
        }
    }

    // =============================== UI HELPERS ===============================
    function enableInput() {
        $('#chatMessageInput').prop('disabled', false);
        $('#chatSendBtn').prop('disabled', false);
        $('#chatInputArea').show();
    }

    function disableInput() {
        $('#chatMessageInput').prop('disabled', true);
        $('#chatSendBtn').prop('disabled', true);
    }

    function showEmptyChat() {
        $('#chatHeaderInfo').html('');
        $('#chatGroupMembersBtn').hide();
        $('#chatMessagesArea').html(`
            <div class="chat-empty-state">
                <i class="bi bi-chat-left-text"></i>
                <h4>Bienvenido al Chat</h4>
                <p>Busca a un usuario por su nombre para iniciar una conversación, o selecciona un chat existente.</p>
            </div>
        `);
        disableInput();
    }

    function scrollToBottom() {
        const $area = $('#chatMessagesArea');
        $area.scrollTop($area[0].scrollHeight);
    }

    // =============================== UTILIDADES ===============================
    function getInitials(name) {
        if (!name) return '?';
        const parts = name.trim().split(/\s+/);
        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }
        return parts[0].substring(0, 2).toUpperCase();
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatTime(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr.replace(' ', 'T'));
        const now = new Date();
        const diff = now - date;
        
        // Hoy
        if (date.toDateString() === now.toDateString()) {
            return date.toLocaleTimeString('es-CR', { hour: '2-digit', minute: '2-digit' });
        }
        
        // Ayer
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);
        if (date.toDateString() === yesterday.toDateString()) {
            return 'Ayer';
        }
        
        // Esta semana
        if (diff < 7 * 24 * 60 * 60 * 1000) {
            const days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
            return days[date.getDay()];
        }
        
        // Más antiguo
        return date.toLocaleDateString('es-CR', { day: '2-digit', month: '2-digit' });
    }

    function formatMessageTime(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr.replace(' ', 'T'));
        return date.toLocaleTimeString('es-CR', { hour: '2-digit', minute: '2-digit' });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr.replace(' ', 'T'));
        const now = new Date();
        
        if (date.toDateString() === now.toDateString()) {
            return 'Hoy';
        }
        
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);
        if (date.toDateString() === yesterday.toDateString()) {
            return 'Ayer';
        }
        
        return date.toLocaleDateString('es-CR', { 
            weekday: 'long', 
            day: 'numeric', 
            month: 'long',
            year: 'numeric'
        });
    }

})(jQuery);
