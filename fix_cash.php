<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\Transaction;
use App\Models\Investor;
use Illuminate\Support\Facades\DB;

echo "Fixing Investor Cash Fund transactions...\n";

$transactions = Transaction::where('category', 'Investor Cash Fund')
    ->whereNull('investor_id')
    ->get();

echo "Found " . $transactions->count() . " transactions to fix.\n";

$count = 0;

foreach ($transactions as $transaction) {
    if (!$transaction->coordinator_id) continue;

    $investors = Investor::where('coordinator_id', $transaction->coordinator_id)->get();

    if ($investors->count() === 0) {
        echo "No investor found for coordinator " . $transaction->coordinator_id . "\n";
        continue;
    }

    if ($investors->count() === 1) {
        $transaction->update(['investor_id' => $investors->first()->id]);
        $count++;
    } else {
        // Multiple investors - split it
        $amount = $transaction->amount;
        $investorCount = $investors->count();
        $baseShare = round($amount / $investorCount, 2);
        
        DB::transaction(function() use ($transaction, $investors, $amount, $baseShare, $investorCount) {
             $allocated = 0;
             foreach ($investors as $index => $investor) {
                if ($index === $investorCount - 1) {
                    $share = $amount - $allocated;
                } else {
                    $share = $baseShare;
                    $allocated += $share;
                }
                
                Transaction::create([
                    'user_id' => $transaction->user_id,
                    'type' => $transaction->type,
                    'category' => $transaction->category,
                    'amount' => $share,
                    'transaction_date' => $transaction->transaction_date,
                    'description' => $transaction->description,
                    'coordinator_id' => $transaction->coordinator_id,
                    'investor_id' => $investor->id,
                    'reference_number' => $transaction->reference_number,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                ]);
             }
             $transaction->delete();
        });
        $count++;
    }
}

echo "Fixed $count transactions.\n";
