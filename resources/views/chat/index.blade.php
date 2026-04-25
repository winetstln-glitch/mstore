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
        min-height: 0;
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
        height: 100%;
        min-height: 0;
    }

    .chat-sidebar {
        height: 100%;
        min-height: 0;
    }

    .chat-header {
        background: #fff;
        padding: 10px 20px;
        border-bottom: 1px solid #eaeaea;
        z-index: 10;
    }

    .chat-message-list {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 20px;
        padding-bottom: 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        overscroll-behavior: contain;
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
        flex-shrink: 0;
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

    .chat-send-btn {
        min-width: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .chat-attachment-image {
        max-width: 220px;
        max-height: 220px;
        border-radius: 10px;
        display: block;
        margin-top: 6px;
        object-fit: cover;
        border: 1px solid rgba(0, 0, 0, 0.12);
    }

    .chat-attachment-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 6px;
        font-size: 0.85rem;
        text-decoration: none;
    }

    .chat-bubble.mine .chat-attachment-link {
        color: #fff;
    }

    .chat-bubble.other .chat-attachment-link {
        color: #0d6efd;
    }

    .chat-file-hint {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 6px;
        display: none;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .chat-page-container {
            padding-left: 0;
            padding-right: 0;
        }
        .chat-wrapper {
            height: calc(100dvh - 76px - var(--nav-height, 65px) - env(safe-area-inset-bottom));
            min-height: calc(100dvh - 76px - var(--nav-height, 65px) - env(safe-area-inset-bottom));
            border-radius: 0;
        }
        .chat-sidebar {
            display: {{ $selectedThread ? 'none' : 'flex' }};
        }
        .chat-main {
            display: {{ $selectedThread ? 'flex' : 'none' }};
            overflow: hidden;
        }
        .chat-message-list {
            padding: 12px;
        }
        .chat-footer {
            position: sticky;
            bottom: 0;
            z-index: 20;
            padding: 10px 12px calc(10px + env(safe-area-inset-bottom));
        }
        .chat-footer .input-group {
            flex-wrap: nowrap;
            align-items: center;
        }
        .chat-input {
            min-width: 0;
            padding-left: 14px;
            padding-right: 14px;
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

<div class="container py-2 chat-page-container">
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
                                @if(!empty($latest?->body))
                                    {{ $latest->body }}
                                @elseif(!empty($latest?->attachment_name))
                                    📎 {{ $latest->attachment_name }}
                                @else
                                    {{ __('Belum ada pesan') }}
                                @endif
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
                            @if($message->body !== '')
                                <div class="message-text">{{ $message->body }}</div>
                            @endif
                            @if($message->attachment_path)
                                @php
                                    $attachmentUrl = Storage::disk((string) ($message->attachment_disk ?: 'public'))->url((string) $message->attachment_path);
                                    $attachmentMime = (string) ($message->attachment_mime ?? '');
                                    $isImageAttachment = str_starts_with($attachmentMime, 'image/');
                                @endphp
                                @if($isImageAttachment)
                                    <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer">
                                        <img src="{{ $attachmentUrl }}" alt="{{ $message->attachment_name ?? 'image' }}" class="chat-attachment-image">
                                    </a>
                                    <a href="{{ route('chat.attachments.download', ['message' => $message->id]) }}" class="chat-attachment-link" data-chat-download="1" data-filename="{{ $message->attachment_name ?? 'image' }}">
                                        <i class="fa-solid fa-download"></i>
                                        <span>{{ __('Download gambar') }}</span>
                                    </a>
                                @else
                                    <a href="{{ route('chat.attachments.download', ['message' => $message->id]) }}" class="chat-attachment-link" data-chat-download="1" data-filename="{{ $message->attachment_name ?? 'file' }}">
                                        <i class="fa-solid fa-download"></i>
                                        <span>{{ $message->attachment_name ?? __('File') }}</span>
                                    </a>
                                @endif
                            @endif
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
                        <input type="file" id="chatAttachmentInput" class="d-none" accept="image/*,.pdf,.txt,.zip,.doc,.docx,.xls,.xlsx">
                        <button type="button" id="chatAttachBtn" class="btn btn-outline-secondary rounded-pill me-2" title="{{ __('Lampirkan file') }}">
                            <i class="fa-solid fa-paperclip"></i>
                        </button>
                        <input type="text" id="chatMessageInput" class="form-control chat-input" 
                               placeholder="{{ __('Tulis pesan...') }}" autocomplete="off">
                        <button type="submit" id="chatSendBtn" class="btn btn-primary rounded-pill ms-2 px-3 chat-send-btn">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                    <div id="chatAttachmentHint" class="chat-file-hint"></div>
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
    const attachmentInput = document.getElementById('chatAttachmentInput');
    const attachmentHint = document.getElementById('chatAttachmentHint');
    const attachBtn = document.getElementById('chatAttachBtn');
    const presenceDot = document.getElementById('chatPresenceBadge');
    const lastSeenStatus = document.getElementById('chatLastSeenStatus');
    const typingStatus = document.getElementById('chatTypingStatus');
    const startBtn = document.getElementById('chatStartBtn');
    const recipientSelect = document.getElementById('chatRecipient');

    let lastMessageId = 0;
    let pollingTimer = null;
    let lastTypingPingAt = 0;
    let typingTimeoutHandle = null;

    const parseResponsePayload = async (response) => {
        const contentType = String(response.headers.get('content-type') || '').toLowerCase();
        if (contentType.includes('application/json')) {
            try {
                return await response.json();
            } catch (_) {
                return null;
            }
        }
        try {
            const text = await response.text();
            return { message: text ? text.slice(0, 300) : null };
        } catch (_) {
            return null;
        }
    };

    const extractErrorMessage = (payload, fallbackMessage) => {
        if (payload && typeof payload.message === 'string' && payload.message.trim() !== '') {
            return payload.message;
        }
        if (payload && payload.errors && typeof payload.errors === 'object') {
            const firstError = Object.values(payload.errors).flat()[0];
            if (typeof firstError === 'string' && firstError.trim() !== '') {
                return firstError;
            }
        }
        return fallbackMessage;
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const formatBytes = (size) => {
        const bytes = Number(size || 0);
        if (!bytes || bytes < 1024) return bytes + ' B';
        const kb = bytes / 1024;
        if (kb < 1024) return kb.toFixed(1) + ' KB';
        const mb = kb / 1024;
        return mb.toFixed(1) + ' MB';
    };

    const renderAttachmentHtml = (message) => {
        if (!message || !message.has_attachment) return '';
        const downloadUrl = escapeHtml(message.attachment_download_url || message.attachment_url || '');
        if (!downloadUrl) return '';
        const fileName = escapeHtml(message.attachment_name || 'File');
        const fileSize = message.attachment_size ? (' (' + escapeHtml(formatBytes(message.attachment_size)) + ')') : '';
        if (message.is_image_attachment) {
            return ''
                + '<a href="' + escapeHtml(message.attachment_url) + '" target="_blank" rel="noopener noreferrer">'
                + '<img src="' + escapeHtml(message.attachment_url) + '" alt="' + fileName + '" class="chat-attachment-image">'
                + '</a>'
                + '<a href="' + downloadUrl + '" class="chat-attachment-link" data-chat-download="1" data-filename="' + fileName + '">'
                + '<i class="fa-solid fa-download"></i>'
                + '<span>Download gambar</span>'
                + '</a>';
        }
        return ''
            + '<a href="' + downloadUrl + '" class="chat-attachment-link" data-chat-download="1" data-filename="' + fileName + '">'
            + '<i class="fa-solid fa-download"></i>'
            + '<span>' + fileName + fileSize + '</span>'
            + '</a>';
    };

    const extractFileNameFromDisposition = (disposition) => {
        const value = String(disposition || '');
        if (!value) return '';
        const utf8 = value.match(/filename\*=UTF-8''([^;]+)/i);
        if (utf8 && utf8[1]) {
            try {
                return decodeURIComponent(utf8[1]);
            } catch (_) {
                return utf8[1];
            }
        }
        const ascii = value.match(/filename="?([^"]+)"?/i);
        return ascii?.[1] || '';
    };

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
            + (String(message.body || '') !== '' ? ('<div class="message-text">' + escapeHtml(message.body || '') + '</div>') : '')
            + renderAttachmentHtml(message)
            + '<div class="d-flex justify-content-end align-items-center gap-1 mt-1" style="font-size: 0.65rem; opacity: 0.8;">'
            + '<span>' + clock + '</span>'
            + readIcon
            + '</div>';

        messageListEl.appendChild(wrapper);
        lastMessageId = Math.max(lastMessageId, Number(message.id));
        scrollToBottom();
    };

    const appendPendingMineMessage = (body, fileName) => {
        if (!messageListEl) return null;

        const wrapper = document.createElement('div');
        wrapper.className = 'chat-bubble mine';
        wrapper.setAttribute('data-pending-message', '1');
        wrapper.style.opacity = '0.7';

        wrapper.innerHTML = ''
            + '<div class="message-text">' + escapeHtml(body || '') + '</div>'
            + (fileName ? '<div class="small mt-1"><i class="fa-solid fa-paperclip me-1"></i>' + escapeHtml(fileName) + '</div>' : '')
            + '<div class="d-flex justify-content-end align-items-center gap-1 mt-1" style="font-size: 0.65rem; opacity: 0.8;">'
            + '<span>Mengirim...</span>'
            + '</div>';

        messageListEl.appendChild(wrapper);
        scrollToBottom();
        return wrapper;
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
        messageListEl.addEventListener('click', async (event) => {
            const link = event.target.closest('a[data-chat-download="1"]');
            if (!link) return;
            event.preventDefault();
            if (link.dataset.downloading === '1') return;

            link.dataset.downloading = '1';
            try {
                const response = await fetch(link.href, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': '*/*',
                    },
                });
                if (!response.ok) {
                    throw new Error('Gagal mengunduh file.');
                }

                const blob = await response.blob();
                const disposition = response.headers.get('content-disposition');
                const serverFileName = extractFileNameFromDisposition(disposition);
                const fallbackName = link.getAttribute('data-filename') || 'attachment';
                const fileName = serverFileName || fallbackName;

                const objectUrl = URL.createObjectURL(blob);
                const anchor = document.createElement('a');
                anchor.href = objectUrl;
                anchor.download = fileName;
                document.body.appendChild(anchor);
                anchor.click();
                anchor.remove();
                setTimeout(() => URL.revokeObjectURL(objectUrl), 2000);
            } catch (error) {
                alert(error?.message || 'Gagal mengunduh file.');
            } finally {
                link.dataset.downloading = '0';
            }
        });
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

    if (attachmentInput && attachmentHint) {
        attachmentInput.addEventListener('change', function () {
            const file = attachmentInput.files?.[0] || null;
            if (!file) {
                attachmentHint.style.display = 'none';
                attachmentHint.textContent = '';
                return;
            }
            attachmentHint.style.display = 'block';
            attachmentHint.textContent = 'Lampiran: ' + file.name + ' (' + formatBytes(file.size) + ')';
        });
    }

    if (attachBtn && attachmentInput) {
        attachBtn.addEventListener('click', function () {
            attachmentInput.click();
        });
    }

    if (sendForm && messageInput && sendBtn) {
        messageInput.addEventListener('input', pingTyping);
        sendForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const body = messageInput.value.trim();
            const file = attachmentInput?.files?.[0] || null;
            if ((!body && !file) || !threadId) return;
            if (sendBtn.disabled) return;

            sendBtn.disabled = true;
            messageInput.value = '';
            const pendingEl = appendPendingMineMessage(body, file?.name || '');
            if (attachmentInput) attachmentInput.value = '';
            if (attachmentHint) {
                attachmentHint.style.display = 'none';
                attachmentHint.textContent = '';
            }

            try {
                const formData = new FormData();
                formData.append('thread_id', String(threadId));
                formData.append('body', body);
                if (otherUserId > 0) formData.append('recipient_id', String(otherUserId));
                if (file) formData.append('attachment', file);

                const response = await fetch(sendEndpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                    body: formData,
                });

                const payload = await parseResponsePayload(response);
                if (!response.ok) {
                    if (response.status === 401 || response.status === 419) {
                        throw new Error('Sesi login habis. Silakan refresh halaman lalu coba kirim lagi.');
                    }
                    throw new Error(extractErrorMessage(payload, 'Gagal mengirim pesan.'));
                }

                if (pendingEl) pendingEl.remove();
                if (payload.message) appendMessage(payload.message);
            } catch (error) {
                if (pendingEl) pendingEl.remove();
                messageInput.value = body;
                messageInput.focus();
                if (file && attachmentHint) {
                    attachmentHint.style.display = 'block';
                    attachmentHint.textContent = 'Lampiran: ' + file.name + ' (' + formatBytes(file.size) + ')';
                }
                alert(error?.message || '{{ __('Gagal mengirim pesan.') }}');
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

                const payload = await parseResponsePayload(response);
                if (!response.ok) {
                    if (response.status === 401 || response.status === 419) {
                        throw new Error('Sesi login habis. Silakan refresh halaman lalu coba lagi.');
                    }
                    throw new Error(extractErrorMessage(payload, 'Gagal membuat percakapan baru.'));
                }

                if (payload.url) window.location.href = payload.url;
            } catch (error) {
                alert(error?.message || '{{ __('Gagal membuat percakapan baru.') }}');
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
