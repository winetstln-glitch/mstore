@extends('layouts.app')

@section('title', __('Messenger Internal'))

@push('styles')
<style>
    .chat-layout {
        min-height: calc(100vh - 180px);
    }
    .chat-thread-item.active {
        background-color: rgba(13, 110, 253, 0.1);
        border-color: rgba(13, 110, 253, 0.25) !important;
    }
    .chat-message-list {
        height: calc(100vh - 360px);
        min-height: 320px;
        overflow-y: auto;
        background: var(--bs-tertiary-bg);
    }
    .chat-bubble {
        max-width: 78%;
        white-space: pre-wrap;
    }
    .chat-bubble.mine {
        margin-left: auto;
        background: var(--bs-primary);
        color: #fff;
    }
    .chat-bubble.other {
        margin-right: auto;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
    }
</style>
@endpush

@section('content')
@php $currentUserId = (int) Auth::id(); @endphp
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0 fw-bold">{{ __('Messenger Internal') }}</h5>
            <div class="text-muted small">{{ __('Komunikasi realtime antar akun MStore') }}</div>
        </div>
    </div>

    <div
        id="chatApp"
        class="row g-3 chat-layout"
        data-current-user-id="{{ $currentUserId }}"
        data-thread-id="{{ $selectedThread?->id }}"
        data-messages-endpoint-template="{{ route('chat.messages', ['chat' => '__THREAD__']) }}"
        data-send-endpoint="{{ route('chat.store') }}"
        data-start-endpoint="{{ route('chat.start') }}"
    >
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body border-bottom">
                    <label for="chatRecipient" class="form-label small fw-semibold mb-1">{{ __('Mulai Obrolan Baru') }}</label>
                    <div class="input-group">
                        <select id="chatRecipient" class="form-select">
                            <option value="">{{ __('Pilih akun') }}</option>
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="chatStartBtn" class="btn btn-primary">{{ __('Mulai') }}</button>
                    </div>
                </div>
                <div class="list-group list-group-flush overflow-auto">
                    @forelse($threads as $thread)
                        @php
                            $other = $thread->otherParticipant($currentUserId);
                            $latest = $thread->latestMessage;
                            $isActive = $selectedThread && (int) $selectedThread->id === (int) $thread->id;
                        @endphp
                        <a
                            href="{{ route('chat.index', ['thread' => $thread->id]) }}"
                            class="list-group-item list-group-item-action chat-thread-item {{ $isActive ? 'active' : '' }}"
                        >
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-truncate">{{ $other?->name ?? __('Pengguna') }}</div>
                                    <div class="small text-muted text-truncate">
                                        {{ $latest?->body ?? __('Belum ada pesan') }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    @if(($thread->unread_count ?? 0) > 0)
                                        <span class="badge text-bg-danger rounded-pill">{{ $thread->unread_count }}</span>
                                    @endif
                                    <div class="small text-muted mt-1">{{ optional($latest?->created_at)->diffForHumans() }}</div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-3 text-muted small">{{ __('Belum ada percakapan. Mulai dari daftar akun di atas.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100 d-flex flex-column">
                @if($selectedThread)
                    @php $otherParticipant = $selectedThread->otherParticipant($currentUserId); @endphp
                    <div class="card-header bg-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $otherParticipant?->name ?? __('Pengguna') }}</div>
                            <div class="small text-muted">{{ __('Thread #') }}{{ $selectedThread->id }}</div>
                        </div>
                    </div>
                    <div id="chatMessageList" class="card-body chat-message-list">
                        @foreach($messages as $message)
                            @php $mine = (int) $message->sender_id === $currentUserId; @endphp
                            <div class="mb-2 d-flex {{ $mine ? 'justify-content-end' : 'justify-content-start' }}" data-message-id="{{ $message->id }}">
                                <div class="chat-bubble {{ $mine ? 'mine' : 'other' }} rounded-3 px-3 py-2">
                                    @if(! $mine)
                                        <div class="fw-semibold small mb-1">{{ $message->sender->name ?? __('Pengguna') }}</div>
                                    @endif
                                    <div>{{ $message->body }}</div>
                                    <div class="small mt-1 {{ $mine ? 'text-white-50' : 'text-muted' }}">
                                        {{ optional($message->created_at)->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer bg-body border-top">
                        <form id="chatSendForm" class="d-flex gap-2">
                            <input type="hidden" id="chatThreadId" value="{{ $selectedThread->id }}">
                            <textarea id="chatMessageInput" class="form-control" rows="2" maxlength="5000" placeholder="{{ __('Tulis pesan...') }}" required></textarea>
                            <button type="submit" id="chatSendBtn" class="btn btn-primary px-3">{{ __('Kirim') }}</button>
                        </form>
                    </div>
                @else
                    <div class="card-body d-flex align-items-center justify-content-center text-center text-muted">
                        <div>
                            <i class="fa-regular fa-comments fs-1 mb-2"></i>
                            <div class="fw-semibold">{{ __('Pilih percakapan untuk mulai chat') }}</div>
                        </div>
                    </div>
                @endif
            </div>
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
        const messagesEndpointTemplate = app.dataset.messagesEndpointTemplate || '';
        const sendEndpoint = app.dataset.sendEndpoint || '';
        const startEndpoint = app.dataset.startEndpoint || '';
        const threadId = Number(app.dataset.threadId || 0);

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const messageListEl = document.getElementById('chatMessageList');
        const sendForm = document.getElementById('chatSendForm');
        const sendBtn = document.getElementById('chatSendBtn');
        const messageInput = document.getElementById('chatMessageInput');
        const startBtn = document.getElementById('chatStartBtn');
        const recipientSelect = document.getElementById('chatRecipient');

        let lastMessageId = 0;
        let pollingTimer = null;

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const scrollToBottom = () => {
            if (messageListEl) {
                messageListEl.scrollTop = messageListEl.scrollHeight;
            }
        };

        const findLastMessageId = () => {
            if (!messageListEl) return 0;
            const nodes = messageListEl.querySelectorAll('[data-message-id]');
            if (!nodes.length) return 0;
            return Number(nodes[nodes.length - 1].getAttribute('data-message-id') || 0);
        };

        const appendMessage = (message) => {
            if (!messageListEl || !message || !message.id) return;
            if (messageListEl.querySelector('[data-message-id="' + message.id + '"]')) return;

            const mine = Number(message.sender_id) === currentUserId;
            const wrap = document.createElement('div');
            wrap.className = 'mb-2 d-flex ' + (mine ? 'justify-content-end' : 'justify-content-start');
            wrap.setAttribute('data-message-id', String(message.id));

            const senderHtml = mine
                ? ''
                : '<div class="fw-semibold small mb-1">' + escapeHtml(message.sender_name || 'Pengguna') + '</div>';
            const timeLabel = escapeHtml(message.created_at_human || '');
            const bubbleClass = mine ? 'mine' : 'other';
            const timeClass = mine ? 'text-white-50' : 'text-muted';

            wrap.innerHTML = '' +
                '<div class="chat-bubble ' + bubbleClass + ' rounded-3 px-3 py-2">' +
                    senderHtml +
                    '<div>' + escapeHtml(message.body || '') + '</div>' +
                    '<div class="small mt-1 ' + timeClass + '">' + timeLabel + '</div>' +
                '</div>';

            messageListEl.appendChild(wrap);
            lastMessageId = Math.max(lastMessageId, Number(message.id));
        };

        const pollMessages = async () => {
            if (!threadId || !messagesEndpointTemplate) return;
            const endpoint = messagesEndpointTemplate.replace('__THREAD__', String(threadId));
            const query = lastMessageId > 0 ? ('?after_id=' + encodeURIComponent(lastMessageId)) : '';

            try {
                const response = await fetch(endpoint + query, {
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                if (!response.ok) return;

                const payload = await response.json();
                const messages = Array.isArray(payload.messages) ? payload.messages : [];
                if (!messages.length) return;
                messages.forEach(appendMessage);
                scrollToBottom();
            } catch (error) {
                console.warn('Chat polling failed:', error);
            }
        };

        if (messageListEl) {
            lastMessageId = findLastMessageId();
            scrollToBottom();
        }

        if (threadId && messagesEndpointTemplate) {
            pollingTimer = setInterval(pollMessages, 2500);
        }

        if (sendForm && messageInput && sendBtn) {
            sendForm.addEventListener('submit', async function (event) {
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
                    if (!response.ok) {
                        throw new Error('Send failed');
                    }
                    const payload = await response.json();
                    if (payload.message) {
                        appendMessage(payload.message);
                        scrollToBottom();
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
            startBtn.addEventListener('click', async function () {
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
                    if (!response.ok) {
                        throw new Error('Start thread failed');
                    }
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

