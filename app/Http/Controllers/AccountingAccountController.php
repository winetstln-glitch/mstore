<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Http\Request;

class AccountingAccountController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));

        $accounts = Account::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%');
                });
            })
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->with('parent')
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('accounting.accounts.index', [
            'accounts' => $accounts,
            'types' => $this->types(),
            'search' => $search,
            'selectedType' => $type,
        ]);
    }

    public function create()
    {
        return view('accounting.accounts.create', [
            'parents' => Account::orderBy('code')->get(['id', 'code', 'name']),
            'types' => $this->types(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:accounts,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|exists:accounts,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Account::create($validated);

        return redirect()->route('accounting.accounts.index')->with('success', 'Akun berhasil ditambahkan');
    }

    public function edit(Account $account)
    {
        return view('accounting.accounts.edit', [
            'account' => $account,
            'parents' => Account::where('id', '!=', $account->id)->orderBy('code')->get(['id', 'code', 'name']),
            'types' => $this->types(),
        ]);
    }

    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:accounts,code,'.$account->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|exists:accounts,id|not_in:'.$account->id,
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', false);

        $account->update($validated);

        return redirect()->route('accounting.accounts.index')->with('success', 'Akun berhasil diperbarui');
    }

    public function destroy(Account $account)
    {
        $hasEntries = JournalEntry::where('account_id', $account->id)->exists();
        $hasChildren = Account::where('parent_id', $account->id)->exists();

        if ($hasEntries || $hasChildren) {
            return back()->with('error', 'Akun tidak bisa dihapus karena sudah dipakai');
        }

        $account->delete();

        return redirect()->route('accounting.accounts.index')->with('success', 'Akun berhasil dihapus');
    }

    private function types(): array
    {
        return [
            'asset' => 'Aset',
            'liability' => 'Liabilitas',
            'equity' => 'Ekuitas',
            'revenue' => 'Pendapatan',
            'expense' => 'Beban',
        ];
    }
}
