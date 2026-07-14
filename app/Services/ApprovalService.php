<?php

namespace App\Services;

use App\Events\ExpenseApproved;
use App\Models\Expense;
use App\Models\ExpenseApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public function submitForApproval(Expense $expense, User $submitter): Expense
    {
        DB::transaction(function () use ($expense, $submitter) {
            $expense->update([
                'status' => 'pending_approval',
                'updated_by' => $submitter->id,
            ]);

            $this->recordApprovalAction($expense, $submitter, 'submitted', 0, 'Diajukan untuk approval');
        });

        return $expense;
    }

    public function approve(Expense $expense, User $approver, int $level, string $notes = ''): Expense
    {
        DB::transaction(function () use ($expense, $approver, $level, $notes) {
            $this->recordApprovalAction($expense, $approver, 'approved', $level, $notes);

            $nextLevel = $level + 1;
            $isFullyApproved = $this->isFullyApproved($expense, $nextLevel);

            $expense->update([
                'status' => $isFullyApproved ? 'approved' : 'pending_approval_level_' . $nextLevel,
                'updated_by' => $approver->id,
            ]);

            if ($isFullyApproved) {
                event(new ExpenseApproved($expense));
            }
        });

        return $expense;
    }

    public function reject(Expense $expense, User $approver, int $level, string $notes = ''): Expense
    {
        DB::transaction(function () use ($expense, $approver, $level, $notes) {
            $this->recordApprovalAction($expense, $approver, 'rejected', $level, $notes);

            $expense->update([
                'status' => 'rejected',
                'updated_by' => $approver->id,
            ]);
        });

        return $expense;
    }

    protected function recordApprovalAction(
        Expense $expense,
        User $user,
        string $action,
        int $level,
        string $notes
    ): ExpenseApproval {
        return ExpenseApproval::create([
            'expense_id' => $expense->id,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'action' => $action,
            'approval_level' => $level,
            'notes' => $notes,
        ]);
    }

    protected function isFullyApproved(Expense $expense, int $nextLevel): bool
    {
        $maxLevel = $this->getMaxApprovalLevel($expense);
        return $nextLevel > $maxLevel;
    }

    protected function getMaxApprovalLevel(Expense $expense): int
    {
        $totalAmount = $expense->total_amount;

        if ($totalAmount >= 10000000) {
            return 2; // Level 0, 1, 2 → 3 approvals
        } elseif ($totalAmount >= 1000000) {
            return 1; // Level 0, 1 → 2 approvals
        }

        return 0; // Level 0 → 1 approval
    }
}