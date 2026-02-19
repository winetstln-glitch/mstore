<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncCustomersToUsers extends Command
{
    protected $signature = 'customers:sync-users {--dry-run}';
    protected $description = 'Sinkronkan customers ke users dan tautkan customers.user_id';

    public function handle(): int
    {
        $dry = (bool)$this->option('dry-run');
        $created = 0;
        $linked = 0;

        $customers = Customer::query()->get();
        foreach ($customers as $customer) {
            $user = null;

            if ($customer->user_id) {
                $user = User::find($customer->user_id);
            }

            if (!$user) {
                $email = 'customer'.$customer->id.'@local.test';
                $user = User::where('email', $email)->first();
                if (!$user && !$dry) {
                    $password = Str::password(12);
                    $user = User::create([
                        'name' => $customer->name ?: 'Customer '.$customer->id,
                        'email' => $email,
                        'password' => $password,
                        'is_active' => true,
                    ]);
                    $created++;
                    $this->line("created user: {$user->email}");
                } else {
                    $this->line("user exists: {$email}");
                }
            }

            if ($user && $customer->user_id !== $user->id) {
                if (!$dry) {
                    $customer->user_id = $user->id;
                    $customer->save();
                }
                $linked++;
                $this->line("linked customer #{$customer->id} -> user #{$user->id}");
            }
        }

        $this->info("done. created={$created}, linked={$linked}, dry={$dry}");
        return self::SUCCESS;
    }
}
