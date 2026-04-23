<?php

use App\Models\ChatThread;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.thread.{threadId}', function ($user, $threadId) {
    return ChatThread::query()
        ->forUser((int) $user->id)
        ->whereKey((int) $threadId)
        ->exists();
});

Broadcast::channel('presence.dashboard', function ($user) {
    return method_exists($user, 'hasPermission') ? $user->hasPermission('dashboard.view') : false;
});

Broadcast::channel('presence.user.{id}', function ($user, $id) {
    return (int) $user->id > 0;
});
