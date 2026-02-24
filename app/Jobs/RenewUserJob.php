<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\MixRadiusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RenewUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;

    public string $reason;

    public function __construct(int $userId, string $reason)
    {
        $this->userId = $userId;
        $this->reason = $reason;
    }

    public function handle(MixRadiusService $mix): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }
        $mix->renewUser($user, $this->reason);
    }
}
