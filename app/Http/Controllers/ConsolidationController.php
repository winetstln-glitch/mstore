<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\GeneralTransaction;
use App\Services\ConsolidationEngineService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ConsolidationController extends Controller implements HasMiddleware
{
    protected $consolidationEngine;

    public function __construct(ConsolidationEngineService $consolidationEngine)
    {
        $this->consolidationEngine = $consolidationEngine;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:consolidation.view', only: ['index', 'generate']),
        ];
    }

    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->subMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $companies = Company::with('branches')->where('is_active', true)->get();

        $consolidatedData = $this->consolidationEngine->getConsolidatedReport($startDate, $endDate);

        return view('consolidation.index', compact('companies', 'consolidatedData', 'startDate', 'endDate'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->consolidationEngine->generateReport($request->start_date, $request->end_date);

        return redirect()->back()->with('success', 'Laporan konsolidasi berhasil dibuat!');
    }
}