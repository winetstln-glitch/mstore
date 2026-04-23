<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Events\ChatTyping;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use App\Notifications\ChatMessageNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:chat.view', only: ['index', 'show', 'messages', 'presence']),
            new Middleware('permission:chat.manage', only: ['store', 'start', 'markRead', 'typing']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentUserId = (int) Auth::id();

        $threads = ChatThread::query()
            ->forUser($currentUserId)
            ->with([
                'userOne:id,name,avatar',
                'userTwo:id,name,avatar',
                'latestMessage.sender:id,name,avatar',
            ])
            ->withCount([
                'messages as unread_count' => function ($query) use ($currentUserId) {
                    $query->whereNull('read_at')
                        ->where('sender_id', '!=', $currentUserId);
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        $selectedThreadId = (int) $request->query('thread', 0);
        if ($selectedThreadId <= 0 && $threads->isNotEmpty()) {
            $selectedThreadId = (int) $threads->first()->id;
        }

        $selectedThread = $threads->firstWhere('id', $selectedThreadId);
        if (! $selectedThread && $selectedThreadId > 0) {
            $selectedThread = ChatThread::query()
                ->forUser($currentUserId)
                ->with(['userOne:id,name,avatar', 'userTwo:id,name,avatar'])
                ->find($selectedThreadId);
        }

        $messages = collect();
        if ($selectedThread) {
            $messages = $selectedThread->messages()
                ->with('sender:id,name,avatar')
                ->latest('id')
                ->take(100)
                ->get()
                ->reverse()
                ->values();

            ChatMessage::query()
                ->where('thread_id', $selectedThread->id)
                ->where('sender_id', '!=', $currentUserId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $contacts = User::query()
            ->where('id', '!=', $currentUserId)
            ->orderBy('name')
            ->get(['id', 'name', 'avatar']);

        return view('chat.index', compact('threads', 'selectedThread', 'messages', 'contacts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $currentUserId = (int) Auth::id();
        $validated = $request->validate([
            'thread_id' => 'nullable|integer|exists:chat_threads,id',
            'recipient_id' => 'nullable|integer|exists:users,id|different:'.Auth::id(),
            'body' => 'required|string|max:5000',
        ]);

        $thread = null;
        if (! empty($validated['thread_id'])) {
            $thread = ChatThread::query()
                ->forUser($currentUserId)
                ->findOrFail((int) $validated['thread_id']);
        }

        if (! $thread && ! empty($validated['recipient_id'])) {
            $thread = ChatThread::findOrCreateBetween($currentUserId, (int) $validated['recipient_id']);
        }

        if (! $thread) {
            return response()->json([
                'message' => __('Pilih kontak atau thread terlebih dahulu.'),
            ], 422);
        }

        $message = ChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $currentUserId,
            'body' => trim((string) $validated['body']),
        ]);

        $thread->last_message_at = now();
        $thread->save();

        $message->load('sender:id,name,avatar');
        $payload = $this->formatMessage($message);

        broadcast(new ChatMessageSent((int) $thread->id, $payload))->toOthers();

        $recipientId = (int) $thread->user_one_id === $currentUserId
            ? (int) $thread->user_two_id
            : (int) $thread->user_one_id;
        $recipient = User::find($recipientId);
        if ($recipient) {
            $senderName = (string) (Auth::user()->name ?? 'Pengguna');
            $recipient->notify(new ChatMessageNotification($thread, $message, $senderName));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $payload,
                'thread_id' => $thread->id,
            ]);
        }

        return redirect()->route('chat.index', ['thread' => $thread->id]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ChatThread $chat)
    {
        $this->authorizeThreadAccess($chat);

        return redirect()->route('chat.index', ['thread' => $chat->id]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_id' => 'required|integer|exists:users,id|different:'.Auth::id(),
        ]);

        $thread = ChatThread::findOrCreateBetween((int) Auth::id(), (int) $validated['recipient_id']);

        return response()->json([
            'thread_id' => $thread->id,
            'url' => route('chat.index', ['thread' => $thread->id]),
        ]);
    }

    public function messages(Request $request, ChatThread $chat): JsonResponse
    {
        $this->authorizeThreadAccess($chat);
        $currentUserId = (int) Auth::id();
        $afterId = (int) $request->query('after_id', 0);

        $query = $chat->messages()
            ->with('sender:id,name,avatar')
            ->orderBy('id');

        if ($afterId > 0) {
            $query->where('id', '>', $afterId);
            $messages = $query->get();
        } else {
            $messages = $query->latest('id')->take(100)->get()->reverse()->values();
        }

        ChatMessage::query()
            ->where('thread_id', $chat->id)
            ->where('sender_id', '!=', $currentUserId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages->map(fn (ChatMessage $message) => $this->formatMessage($message)),
        ]);
    }

    public function markRead(ChatThread $chat): JsonResponse
    {
        $this->authorizeThreadAccess($chat);

        ChatMessage::query()
            ->where('thread_id', $chat->id)
            ->where('sender_id', '!=', (int) Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function presence(ChatThread $chat): JsonResponse
    {
        $this->authorizeThreadAccess($chat);
        $currentUserId = (int) Auth::id();
        $otherId = (int) $chat->user_one_id === $currentUserId
            ? (int) $chat->user_two_id
            : (int) $chat->user_one_id;

        $other = User::find($otherId);
        $typingKey = $this->typingCacheKey((int) $chat->id, $otherId);

        return response()->json([
            'online' => $other ? $other->isOnline() : false,
            'last_seen_at' => $other?->last_seen_at?->toDateTimeString(),
            'typing' => Cache::get($typingKey, false) === true,
        ]);
    }

    public function typing(ChatThread $chat): JsonResponse
    {
        $this->authorizeThreadAccess($chat);
        $currentUser = Auth::user();
        $key = $this->typingCacheKey((int) $chat->id, (int) Auth::id());
        Cache::put($key, true, now()->addSeconds(8));
        broadcast(new ChatTyping(
            threadId: (int) $chat->id,
            senderId: (int) Auth::id(),
            senderName: (string) ($currentUser->name ?? 'Pengguna'),
        ))->toOthers();

        return response()->json(['ok' => true]);
    }

    private function authorizeThreadAccess(ChatThread $thread): void
    {
        $currentUserId = (int) Auth::id();
        $allowed = (int) $thread->user_one_id === $currentUserId || (int) $thread->user_two_id === $currentUserId;
        abort_unless($allowed, 403);
    }

    private function formatMessage(ChatMessage $message): array
    {
        return [
            'id' => (int) $message->id,
            'thread_id' => (int) $message->thread_id,
            'sender_id' => (int) $message->sender_id,
            'sender_name' => (string) optional($message->sender)->name,
            'sender_avatar' => optional($message->sender)->avatar,
            'body' => (string) $message->body,
            'read_at' => $message->read_at?->toDateTimeString(),
            'created_at' => $message->created_at?->toDateTimeString(),
            'created_at_human' => $message->created_at?->diffForHumans(),
        ];
    }

    private function typingCacheKey(int $threadId, int $userId): string
    {
        return 'chat:typing:'.$threadId.':'.$userId;
    }
}
