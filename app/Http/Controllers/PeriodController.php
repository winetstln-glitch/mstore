<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Period;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingPoster;

class PeriodController extends Controller
{
    protected AccountingPoster $poster;
    public function __construct(AccountingPoster $poster)
    {
        $this->poster = $poster;
    }

    public function index()
    {
        $periods = Period::orderByDesc('start_date')->get();
        return view('accounting.periods.index', compact('periods'));
    }

    public function create()
    {
        return view('accounting.periods.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:20|unique:periods,name',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        Period::create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'open',
        ]);

        return redirect()->route('accounting.periods.index')->with('success', 'Periode berhasil dibuat');
    }

    public function openingForm(Period $period)
    {
        $accounts = Account::whereIn('type',['asset','liability','equity'])->orderBy('code')->get();
        return view('accounting.periods.opening', compact('period','accounts'));
    }

    public function postOpening(Request $request, Period $period)
    {
        if ($period->status !== 'open') {
            return back()->with('error','Periode harus OPEN untuk input saldo awal');
        }
        $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.account_id' => 'required|exists:accounts,id',
            'rows.*.debit' => 'nullable|numeric|min:0',
            'rows.*.credit' => 'nullable|numeric|min:0',
        ]);
        $lines = [];
        foreach ($request->rows as $r) {
            $d = (float)($r['debit'] ?? 0);
            $c = (float)($r['credit'] ?? 0);
            if ($d == 0 && $c == 0) continue;
            $lines[] = [
                'account_id' => (int)$r['account_id'],
                'debit' => $d,
                'credit' => $c,
                'memo' => 'Saldo Awal',
            ];
        }
        if (empty($lines)) {
            return back()->with('error','Tidak ada baris saldo awal yang diisi');
        }
        $date = $period->start_date;
        $journalNo = 'OB-'.$period->name;
        $this->poster->post($journalNo, $date, 'Saldo Awal '.$period->name, $lines, $period->id, 'opening', null);
        return redirect()->route('accounting.periods.index')->with('success','Saldo awal diposting');
    }

    public function close(Period $period)
    {
        if ($period->status === 'closed') {
            return back()->with('error', 'Periode sudah ditutup');
        }
        // Hitung penutupan pendapatan dan beban
        $base = DB::table('journal_entries')
            ->join('journals','journal_entries.journal_id','=','journals.id')
            ->join('accounts','journal_entries.account_id','=','accounts.id')
            ->whereDate('journals.date','>=',$period->start_date)
            ->whereDate('journals.date','<=',$period->end_date);
        $revenues = (clone $base)->where('accounts.type','revenue')
            ->select('accounts.id','accounts.code','accounts.name', DB::raw('SUM(journal_entries.credit - journal_entries.debit) as amount'))
            ->groupBy('accounts.id','accounts.code','accounts.name')->get();
        $expenses = (clone $base)->where('accounts.type','expense')
            ->select('accounts.id','accounts.code','accounts.name', DB::raw('SUM(journal_entries.debit - journal_entries.credit) as amount'))
            ->groupBy('accounts.id','accounts.code','accounts.name')->get();
        $configuredId = Setting::getValue('accounting_retained_earnings_account_id');
        $equityEarnings = null;
        if ($configuredId) {
            $equityEarnings = Account::find($configuredId);
        }
        if (!$equityEarnings) {
            $equityEarnings = Account::where('code','3201')->first() ?? Account::where('type','equity')->orderBy('code')->first();
        }
        if (!$equityEarnings) {
            return back()->with('error','Akun ekuitas untuk laba berjalan tidak ditemukan');
        }
        $lines = [];
        foreach ($revenues as $r) {
            $amt = (float)$r->amount;
            if (abs($amt) < 0.0001) continue;
            $lines[] = ['account_id'=>$r->id,'debit'=>$amt,'credit'=>0,'memo'=>'Tutup Pendapatan'];
            $lines[] = ['account_id'=>$equityEarnings->id,'debit'=>0,'credit'=>$amt,'memo'=>'Tutup Pendapatan'];
        }
        foreach ($expenses as $e) {
            $amt = (float)$e->amount;
            if (abs($amt) < 0.0001) continue;
            $lines[] = ['account_id'=>$e->id,'debit'=>0,'credit'=>$amt,'memo'=>'Tutup Beban'];
            $lines[] = ['account_id'=>$equityEarnings->id,'debit'=>$amt,'credit'=>0,'memo'=>'Tutup Beban'];
        }
        if (!empty($lines)) {
            $this->poster->post('CL-'.$period->name, $period->end_date, 'Tutup Periode '.$period->name, $lines, $period->id, 'closing', null);
        }
        // Tandai closed
        $period->status = 'closed';
        $period->save();
        // Buat periode berikut + saldo awal otomatis untuk akun neraca
        $nextStart = \Carbon\Carbon::parse($period->start_date)->addMonthNoOverflow()->startOfMonth();
        $nextName = $nextStart->format('Y-m');
        $next = Period::firstOrCreate(
            ['name'=>$nextName],
            ['start_date'=>$nextStart->copy()->startOfMonth()->toDateString(),'end_date'=>$nextStart->copy()->endOfMonth()->toDateString(),'status'=>'open']
        );
        // Hitung saldo akhir akun Neraca sampai akhir periode
        $balances = DB::table('journal_entries')
            ->join('journals','journal_entries.journal_id','=','journals.id')
            ->join('accounts','journal_entries.account_id','=','accounts.id')
            ->whereDate('journals.date','<=',$period->end_date)
            ->whereIn('accounts.type',['asset','liability','equity'])
            ->select('accounts.id','accounts.type', DB::raw('SUM(journal_entries.debit) as d'), DB::raw('SUM(journal_entries.credit) as c'))
            ->groupBy('accounts.id','accounts.type')->get();
        $obLines = [];
        foreach ($balances as $b) {
            $balance = ($b->type === 'asset') ? ($b->d - $b->c) : ($b->c - $b->d);
            if (abs($balance) < 0.0001) continue;
            if ($b->type === 'asset') {
                $obLines[] = ['account_id'=>$b->id,'debit'=>max($balance,0),'credit'=>max(-$balance,0),'memo'=>'Saldo Awal'];
            } else {
                $obLines[] = ['account_id'=>$b->id,'debit'=>max(-$balance,0),'credit'=>max($balance,0),'memo'=>'Saldo Awal'];
            }
        }
        if (!empty($obLines)) {
            $this->poster->post('OB-'.$next->name, $next->start_date, 'Saldo Awal '.$next->name, $obLines, $next->id, 'opening', null);
        }
        return back()->with('success', 'Periode ditutup dan saldo awal periode berikut dibuat');
    }
}
