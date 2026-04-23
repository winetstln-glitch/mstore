@extends('layouts.app')

@section('title', __('Messenger Internal'))

@push('styles')
<style>
    :root {
        --chat-bg: #f0f2f5;
        --chat-bubble-me: #dcf8c6;
        --chat-bubble-other: #ffffff;
    }

    /* Layout Container */
    .chat-wrapper {
        height: calc(100vh - 120px);
        min-height: 500px;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    }

    /* Sidebar Thread List */
    .chat-sidebar {
        border-right: 1px solid #eaeaea;
        display: flex;
        flex-direction: column;
        background: #fff;
    }

    .thread-scroll {
        flex: 1;
        overflow-y: auto;
    }

    .chat-thread-item {
        padding: 12px 16px;
        border-bottom: 1px solid #f8f9fa;
        transition: all 0.2s ease;
        border-left: 4px solid transparent !important;
        cursor: pointer;
    }

    .chat-thread-item:hover {
        background-color: #f8f9fa;
    }

    .chat-thread-item.active {
        background-color: #e7f1ff;
        border-left-color: #0d6efd !important;
    }

    /* Message Area */
    .chat-main {
        display: flex;
        flex-direction: column;
        background-color: var(--chat-bg);
        position: relative;
    }

    .chat-header {
        background: #fff;
        padding: 10px 20px;
        border-bottom: 1px solid #eaeaea;
        z-index: 10;
    }

    .chat-message-list {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* Custom Chat Bubbles */
    .chat-bubble {
        max-width: 75%;
        padding: 8px 12px;
        font-size: 0.95rem;
        position: relative;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
    }

    .chat-bubble.mine {
        align-self: flex-end;
        background-color: #0d6efd;
        color: #fff;
        border-radius: 15px 15px 2px 15px;
    }

    .chat-bubble.other {
        align-self: flex-start;
        background-color: #fff;
        color: #333;
        border-radius: 15px 15px 15px 2px;
        border: 1px solid #efefef;
    }

    .chat-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #495057;
        flex-shrink: 0;
    }

    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }

    /* Composer Area */
    .chat-footer {
        background: #fff;
        padding: 15px 20px;
        border-top: 1px solid #eaeaea;
    }

    .chat-input {
        border-radius: 20px !important;
        padding-left: 20px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
    }

    .chat-input:focus {
        background-color: #fff;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .chat-wrapper {
            height: calc(100vh - 80px);
            border-radius: 0;
        }
        .chat-sidebar {
            display: {{ $selectedThread ? 'none' : 'flex' }};
        }
        .chat-main {
            display: {{ $selectedThread ? 'flex' : 'none' }};
        }
    }
</style>
@endpush

@section('content')
@php $currentUserId = (int) Auth::id(); @endphp
@php
    $otherParticipantId = null;
    if ($selectedThread) {
        $otherParticipantId = (int) ($selectedThread->user_one_id === $currentUserId ? $selectedThread->user_two_id : $selectedThread->user_one_id);
    }
@endphp

<div class="container py-2">
    <div class="chat-wrapper row g-0" id="chatApp"
        data-current-user-id="{{ $currentUserId }}"
        data-thread-id="{{ $selectedThread?->id }}"
        data-other-user-id="{{ $otherParticipantId }}"
        data-messages-endpoint-template="{{ route('chat.messages', ['chat' => '__THREAD__']) }}"
        data-presence-endpoint-template="{{ route('chat.presence', ['chat' => '__THREAD__']) }}"
        data-typing-endpoint-template="{{ route('chat.typing', ['chat' => '__THREAD__']) }}"
        data-send-endpoint="{{ route('chat.store') }}"
        data-start-endpoint="{{ route('chat.start') }}"
    >
        <!-- Sidebar -->
        <div class="col-md-4 chat-sidebar">
            <div class="p-3 border-bottom">
                <h6 class="fw-bold mb-3">{{ __('Messenger Internal') }}</h6>
                <div class="input-group input-group-sm">
                    <select id="chatRecipient" class="form-select border-end-0">
                        <option value="">{{ __('Cari Kontak...') }}</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-primary" id="chatStartBtn">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
            </div>

            <div class="thread-scroll">
                @forelse($threads as $thread)
                    @php
                        $other = $thread->otherParticipant($currentUserId);
                        $latest = $thread->latestMessage;
                        $isActive = $selectedThread && (int) $selectedThread->id === (int) $thread->id;
                    @endphp
                    <a href="{{ route('chat.index', ['thread' => $thread->id]) }}" 
                       class="text-decoration-none chat-thread-item d-flex align-items-center gap-3 {{ $isActive ? 'active' : '' }}">
                        <div class="chat-avatar bg-primary text-white">
                            {{ strtoupper(substr($other?->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold text-dark small truncate">{{ $other?->name ?? 'User' }}</span>
                                <span class="text-muted tiny" style="font-size: 0.7rem;">
                                    {{ optional($latest?->created_at)->diffForHumans(null, true) }}
                                </span>
                            </div>
                            <div class="text-muted small text-truncate">
                                {{ $latest?->body ?? __('Belum ada pesan') }}
                            </div>
                        </div>
                        @if(($thread->unread_count ?? 0) > 0)
                            <span class="badge rounded-pill bg-danger">{{ $thread->unread_count }}</span>
                        @endif
                    </a>
                @empty
                    <div class="p-4 text-center text-muted">
                        <p class="small">{{ __('Belum ada percakapan') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="col-md-8 chat-main">
            @if($selectedThread)
                @php $otherParticipant = $selectedThread->otherParticipant($currentUserId); @endphp
                
                <!-- Chat Header -->
                <div class="chat-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <a href="{{ route('chat.index') }}" class="d-md-none text-dark me-2">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <div class="chat-avatar">
                            {{ strtoupper(substr($otherParticipant?->name ?? 'U', 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-bold text-dark h6 mb-0">{{ $otherParticipant?->name }}</div>
                            <div class="small d-flex align-items-center">
                                <span id="chatPresenceBadge" class="status-dot bg-secondary"></span>
                                <span id="chatLastSeenStatus" class="text-muted">{{ __('Offline') }}</span>
                            </div>
                        </div>
                    </div>
                    <div id="chatTypingStatus" class="small text-primary fw-italic d-none animate-pulse">
                        {{ __('sedang mengetik...') }}
                    </div>
                </div>

                <!-- Messages -->
                <div id="chatMessageList" class="chat-message-list">
                    @foreach($messages as $message)
                        @php $mine = (int) $message->sender_id === $currentUserId; @endphp
                        <div class="chat-bubble {{ $mine ? 'mine' : 'other' }}" data-message-id="{{ $message->id }}">
                            @if(!$mine)
                                <div class="fw-bold tiny mb-1" style="font-size: 0.75rem;">{{ $message->sender->name }}</div>
                            @endif
                            <div class="message-text">{{ $message->body }}</div>
                            <div class="d-flex justify-content-end align-items-center gap-1 mt-1" style="font-size: 0.65rem; opacity: 0.8;">
                                <span>{{ optional($message->created_at)->format('H:i') }}</span>
                                @if($mine)
                                    <i class="fa-solid fa-check-double {{ $message->read_at ? 'text-info' : '' }}" data-message-status-for="{{ $message->id }}"></i>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Footer -->
                <div class="chat-footer">
                    <form id="chatSendForm" class="input-group">
                        <input type="hidden" id="chatThreadId" value="{{ $selectedThread->id }}">
                        <input type="text" id="chatMessageInput" class="form-control chat-input" 
                               placeholder="{{ __('Tulis pesan...') }}" autocomplete="off">
                        <button type="submit" id="chatSendBtn" class="btn btn-primary rounded-pill ms-2 px-4">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            @else
                <!-- Empty State -->
                <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                    <div class="bg-light p-4 rounded-circle mb-3">
                        <i class="fa-regular fa-comments fs-1"></i>
                    </div>
                    <h5 class="fw-bold">{{ __('MStore Messenger') }}</h5>
                    <p class="small px-4 text-center">{{ __('Pilih salah satu kontak di samping untuk memulai koordinasi internal.') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const app = document.getElementById('chatApp');
    if (!app) return;

    const currentUserId = Number(app.dataset.currentUserId || 0);
    const threadId = Number(app.dataset.threadId || 0);
    const otherUserId = Number(app.dataset.otherUserId || 0);
    const messagesEndpointTemplate = app.dataset.messagesEndpointTemplate || '';
    const presenceEndpointTemplate = app.dataset.presenceEndpointTemplate || '';
    const typingEndpointTemplate = app.dataset.typingEndpointTemplate || '';
    const sendEndpoint = app.dataset.sendEndpoint || '';
    const startEndpoint = app.dataset.startEndpoint || '';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const messageListEl = document.getElementById('chatMessageList');
    const sendForm = document.getElementById('chatSendForm');
    const sendBtn = document.getElementById('chatSendBtn');
    const messageInput = document.getElementById('chatMessageInput');
    const presenceDot = document.getElementById('chatPresenceBadge');
    const lastSeenStatus = document.getElementById('chatLastSeenStatus');
    const typingStatus = document.getElementById('chatTypingStatus');
    const startBtn = document.getElementById('chatStartBtn');
    const recipientSelect = document.getElementById('chatRecipient');

    let lastMessageId = 0;
    let pollingTimer = null;
    let lastTypingPingAt = 0;
    let typingTimeoutHandle = null;

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const scrollToBottom = () => {
        if (!messageListEl) return;
        messageListEl.scrollTop = messageListEl.scrollHeight;
    };

    const findLastMessageId = () => {
        if (!messageListEl) return 0;
        const nodes = messageListEl.querySelectorAll('[data-message-id]');
        if (!nodes.length) return 0;
        return Number(nodes[nodes.length - 1].getAttribute('data-message-id') || 0);
    };

    const updateSeenStatus = (messageIds) => {
        if (!Array.isArray(messageIds)) return;
        messageIds.forEach((id) => {
            const icon = document.querySelector('[data-message-status-for="' + String(id) + '"]');
            if (icon) icon.classList.add('text-info');
        });
    };

    const appendMessage = (message) => {
        if (!messageListEl || !message || !message.id) return;
        if (messageListEl.querySelector('[data-message-id="' + message.id + '"]')) return;

        const mine = Number(message.sender_id) === currentUserId;
        const wrapper = document.createElement('div');
        wrapper.className = 'chat-bubble ' + (mine ? 'mine' : 'other');
        wrapper.setAttribute('data-message-id', String(message.id));

        const senderHtml = mine
            ? ''
            : '<div class="fw-bold tiny mb-1" style="font-size: 0.75rem;">' + escapeHtml(message.sender_name || 'User') + '</div>';
        const clock = escapeHtml((message.created_at || '').slice(11, 16) || '');
        const readClass = mine && message.read_at ? 'text-info' : '';
        const readIcon = mine
            ? '<i class="fa-solid fa-check-double ' + readClass + '" data-message-status-for="' + String(message.id) + '"></i>'
            : '';

        wrapper.innerHTML = ''
            + senderHtml
            + '<div class="message-text">' + escapeHtml(message.body || '') + '</div>'
            + '<div class="d-flex justify-content-end align-items-center gap-1 mt-1" style="font-size: 0.65rem; opacity: 0.8;">'
            + '<span>' + clock + '</span>'
            + readIcon
            + '</div>';

        messageListEl.appendChild(wrapper);
        lastMessageId = Math.max(lastMessageId, Number(message.id));
        scrollToBottom();
    };

    const applyPresenceState = (online, lastSeenAt) => {
        if (presenceDot) {
            presenceDot.className = 'status-dot ' + (online ? 'bg-success' : 'bg-secondary');
        }
        if (lastSeenStatus) {
            lastSeenStatus.textContent = online ? 'Online' : (lastSeenAt ? ('Terakhir aktif: ' + lastSeenAt) : 'Offline');
        }
    };

    const showTyping = () => {
        if (!typingStatus) return;
        typingStatus.classList.remove('d-none');
        if (typingTimeoutHandle) clearTimeout(typingTimeoutHandle);
        typingTimeoutHandle = setTimeout(() => typingStatus.classList.add('d-none'), 3000);
    };

    const pollMessages = async () => {
        if (!threadId || !messagesEndpointTemplate) return;
        const endpoint = messagesEndpointTemplate.replace('__THREAD__', String(threadId));
        const query = lastMessageId > 0 ? ('?after_id=' + encodeURIComponent(lastMessageId)) : '';
        try {
            const response = await fetch(endpoint + query, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const payload = await response.json();
            const messages = Array.isArray(payload.messages) ? payload.messages : [];
            messages.forEach(appendMessage);
        } catch (error) {
            console.warn('Polling messages failed:', error);
        }
    };

    const pollPresence = async () => {
        if (!threadId || !presenceEndpointTemplate) return;
        const endpoint = presenceEndpointTemplate.replace('__THREAD__', String(threadId));
        try {
            const response = await fetch(endpoint, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const payload = await response.json();
            applyPresenceState(payload.online === true, payload.last_seen_at || null);
            if (payload.typing === true) showTyping();
        } catch (error) {
            console.warn('Polling presence failed:', error);
        }
    };

    const pingTyping = async () => {
        if (!threadId || !typingEndpointTemplate) return;
        const nowTs = Date.now();
        if (nowTs - lastTypingPingAt < 1800) return;
        lastTypingPingAt = nowTs;
        const endpoint = typingEndpointTemplate.replace('__THREAD__', String(threadId));
        try {
            await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ typing: true }),
            });
        } catch (_) {
            // ignore
        }
    };

    if (messageListEl) {
        lastMessageId = findLastMessageId();
        scrollToBottom();
    }

    if (threadId > 0) {
        pollPresence();
        pollingTimer = setInterval(() => {
            pollMessages();
            pollPresence();
        }, 2500);

        if (window.Echo) {
            window.Echo.private('chat.thread.' + threadId)
                .listen('.chat.message.sent', (event) => {
                    if (event?.message) appendMessage(event.message);
                })
                .listen('.chat.typing', (event) => {
                    if (Number(event?.senderId || 0) !== currentUserId) showTyping();
                })
                .listen('.chat.messages.read', (event) => {
                    if (Number(event?.readerId || 0) !== currentUserId) {
                        updateSeenStatus(event?.messageIds || []);
                    }
                });
        }

        if (window.Echo && otherUserId > 0) {
            window.Echo.private('presence.user.' + otherUserId)
                .listen('.presence.updated', (event) => {
                    applyPresenceState(event?.online === true, event?.lastSeenAt || null);
                });
        }
    }

    if (sendForm && messageInput && sendBtn) {
        messageInput.addEventListener('input', pingTyping);
        sendForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const body = messageInput.value.trim();
            if (!body || !threadId) return;

            sendBtn.disabled = true;
            try {
                const response = await fetch(sendEndpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ thread_id: threadId, body }),
                });
                if (!response.ok) throw new Error('Send failed');
                const payload = await response.json();
                if (payload.message) appendMessage(payload.message);
                messageInput.value = '';
            } catch (error) {
                alert('{{ __('Gagal mengirim pesan.') }}');
            } finally {
                sendBtn.disabled = false;
            }
        });
    }

    if (startBtn && recipientSelect) {
        startBtn.addEventListener('click', async () => {
            const recipientId = Number(recipientSelect.value || 0);
            if (!recipientId) {
                alert('{{ __('Pilih akun terlebih dahulu.') }}');
                return;
            }

            startBtn.disabled = true;
            try {
                const response = await fetch(startEndpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ recipient_id: recipientId }),
                });
                if (!response.ok) throw new Error('Start thread failed');
                const payload = await response.json();
                if (payload.url) window.location.href = payload.url;
            } catch (error) {
                alert('{{ __('Gagal membuat percakapan baru.') }}');
            } finally {
                startBtn.disabled = false;
            }
        });
    }

    window.addEventListener('beforeunload', function () {
        if (pollingTimer) clearInterval(pollingTimer);
    });
});
</script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const app = document.getElementById('chatApp');
    if (!app) return;

    const currentUserId = Number(app.dataset.currentUserId || 0);
    const threadId = Number(app.dataset.threadId || 0);
    const otherUserId = Number(app.dataset.otherUserId || 0);
    const messagesEndpointTemplate = app.dataset.messagesEndpointTemplate || '';
    const presenceEndpointTemplate = app.dataset.presenceEndpointTemplate || '';
    const typingEndpointTemplate = app.dataset.typingEndpointTemplate || '';
    const sendEndpoint = app.dataset.sendEndpoint || '';
    const startEndpoint = app.dataset.startEndpoint || '';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const messageListEl = document.getElementById('chatMessageList');
    const sendForm = document.getElementById('chatSendForm');
    const sendBtn = document.getElementById('chatSendBtn');
    const messageInput = document.getElementById('chatMessageInput');
    const presenceDot = document.getElementById('chatPresenceBadge');
    const lastSeenStatus = document.getElementById('chatLastSeenStatus');
    const typingStatus = document.getElementById('chatTypingStatus');
    const startBtn = document.getElementById('chatStartBtn');
    const recipientSelect = document.getElementById('chatRecipient');

    let lastMessageId = 0;
    let pollingTimer = null;
    let lastTypingPingAt = 0;
    let typingTimeoutHandle = null;

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const scrollToBottom = () => {
        if (!messageListEl) return;
        messageListEl.scrollTop = messageListEl.scrollHeight;
    };

    const findLastMessageId = () => {
        if (!messageListEl) return 0;
        const nodes = messageListEl.querySelectorAll('[data-message-id]');
        if (!nodes.length) return 0;
        return Number(nodes[nodes.length - 1].getAttribute('data-message-id') || 0);
    };

    const updateSeenStatus = (messageIds) => {
        if (!Array.isArray(messageIds)) return;
        messageIds.forEach((id) => {
            const icon = document.querySelector('[data-message-status-for="' + String(id) + '"]');
            if (icon) {
                icon.classList.add('text-info');
            }
        });
    };

    const appendMessage = (message) => {
        if (!messageListEl || !message || !message.id) return;
        if (messageListEl.querySelector('[data-message-id="' + message.id + '"]')) return;

        const mine = Number(message.sender_id) === currentUserId;
        const wrap = document.createElement('div');
        wrap.className = 'chat-bubble ' + (mine ? 'mine' : 'other');
        wrap.setAttribute('data-message-id', String(message.id));

        const senderHtml = mine
            ? ''
            : '<div class="fw-bold tiny mb-1" style="font-size: 0.75rem;">' + escapeHtml(message.sender_name || 'User') + '</div>';
        const timeLabel = escapeHtml((message.created_at || '').slice(11, 16) || '');
        const readClass = mine && message.read_at ? 'text-info' : '';
        const readIconHtml = mine
            ? '<i class="fa-solid fa-check-double ' + readClass + '" data-message-status-for="' + String(message.id) + '"></i>'
            : '';

        wrap.innerHTML = ''
            + senderHtml
            + '<div class="message-text">' + escapeHtml(message.body || '') + '</div>'
            + '<div class="d-flex justify-content-end align-items-center gap-1 mt-1" style="font-size: 0.65rem; opacity: 0.8;">'
            + '<span>' + timeLabel + '</span>'
            + readIconHtml
            + '</div>';

        messageListEl.appendChild(wrap);
        lastMessageId = Math.max(lastMessageId, Number(message.id));
        scrollToBottom();
    };

    const applyPresenceState = (online, lastSeenAt) => {
        if (presenceDot) {
            presenceDot.className = 'status-dot ' + (online ? 'bg-success' : 'bg-secondary');
        }
        if (lastSeenStatus) {
            if (online) {
                lastSeenStatus.textContent = 'Online';
            } else {
                lastSeenStatus.textContent = lastSeenAt ? ('Terakhir aktif: ' + lastSeenAt) : 'Offline';
            }
        }
    };

    const showTyping = () => {
        if (!typingStatus) return;
        typingStatus.classList.remove('d-none');
        if (typingTimeoutHandle) clearTimeout(typingTimeoutHandle);
        typingTimeoutHandle = setTimeout(() => {
            typingStatus.classList.add('d-none');
        }, 3000);
    };

    const pollMessages = async () => {
        if (!threadId || !messagesEndpointTemplate) return;
        const endpoint = messagesEndpointTemplate.replace('__THREAD__', String(threadId));
        const query = lastMessageId > 0 ? ('?after_id=' + encodeURIComponent(lastMessageId)) : '';

        try {
            const response = await fetch(endpoint + query, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const payload = await response.json();
            const messages = Array.isArray(payload.messages) ? payload.messages : [];
            messages.forEach(appendMessage);
        } catch (error) {
            console.warn('Polling messages failed:', error);
        }
    };

    const pollPresence = async () => {
        if (!threadId || !presenceEndpointTemplate) return;
        const endpoint = presenceEndpointTemplate.replace('__THREAD__', String(threadId));
        try {
            const response = await fetch(endpoint, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const payload = await response.json();
            applyPresenceState(payload.online === true, payload.last_seen_at || null);
            if (payload.typing === true) {
                showTyping();
            }
        } catch (error) {
            console.warn('Polling presence failed:', error);
        }
    };

    const pingTyping = async () => {
        if (!threadId || !typingEndpointTemplate) return;
        const nowTs = Date.now();
        if (nowTs - lastTypingPingAt < 1800) return;
        lastTypingPingAt = nowTs;
        const endpoint = typingEndpointTemplate.replace('__THREAD__', String(threadId));

        try {
            await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ typing: true }),
            });
        } catch (_) {
            // ignore
        }
    };

    if (messageListEl) {
        lastMessageId = findLastMessageId();
        scrollToBottom();
    }

    if (threadId > 0) {
        pollPresence();
        pollingTimer = setInterval(() => {
            pollMessages();
            pollPresence();
        }, 2500);

        if (window.Echo) {
            window.Echo.private('chat.thread.' + threadId)
                .listen('.chat.message.sent', (event) => {
                    if (event?.message) {
                        appendMessage(event.message);
                    }
                })
                .listen('.chat.typing', (event) => {
                    if (Number(event?.senderId || 0) !== currentUserId) {
                        showTyping();
                    }
                })
                .listen('.chat.messages.read', (event) => {
                    if (Number(event?.readerId || 0) !== currentUserId) {
                        updateSeenStatus(event?.messageIds || []);
                    }
                });
        }

        if (window.Echo && otherUserId > 0) {
            window.Echo.private('presence.user.' + otherUserId)
                .listen('.presence.updated', (event) => {
                    applyPresenceState(event?.online === true, event?.lastSeenAt || null);
                });
        }
    }

    if (sendForm && messageInput && sendBtn) {
        messageInput.addEventListener('input', pingTyping);
        sendForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const body = messageInput.value.trim();
            if (!body || !threadId) return;

            sendBtn.disabled = true;
            try {
                const response = await fetch(sendEndpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        thread_id: threadId,
                        body: body,
                    }),
                });
                if (!response.ok) throw new Error('Send failed');
                const payload = await response.json();
                if (payload.message) {
                    appendMessage(payload.message);
                }
                messageInput.value = '';
            } catch (error) {
                alert('{{ __('Gagal mengirim pesan.') }}');
            } finally {
                sendBtn.disabled = false;
            }
        });
    }

    if (startBtn && recipientSelect) {
        startBtn.addEventListener('click', async () => {
            const recipientId = Number(recipientSelect.value || 0);
            if (!recipientId) {
                alert('{{ __('Pilih akun terlebih dahulu.') }}');
                return;
            }

            startBtn.disabled = true;
            try {
                const response = await fetch(startEndpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ recipient_id: recipientId }),
                });
                if (!response.ok) throw new Error('Start thread failed');
                const payload = await response.json();
                if (payload.url) {
                    window.location.href = payload.url;
                }
            } catch (error) {
                alert('{{ __('Gagal membuat percakapan baru.') }}');
            } finally {
                startBtn.disabled = false;
            }
        });
    }

    window.addEventListener('beforeunload', function () {
        if (pollingTimer) clearInterval(pollingTimer);
    });
});
</script>
@endpush
