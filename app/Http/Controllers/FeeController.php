<?php

namespace App\Http\Controllers;

use App\Models\FeeProfile;
use App\Models\FeeTier;
use App\Services\FeeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class FeeController extends Controller implements HasMiddleware
{
    protected $feeService;

    public function __construct(FeeCalculationService $feeService)
    {
        $this->feeService = $feeService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:atk.manage'),
        ];
    }

    public function index()
    {
        $profiles = FeeProfile::where('module', 'atk')->with('tiers')->orderBy('created_at', 'desc')->get();
        return view('atk.fee.index', compact('profiles'));
    }

    public function create()
    {
        $transactionTypes = [
            'bank' => 'Transfer Bank',
            'cash_out' => 'Tarik Tunai',
            'top_up' => 'Top Up',
            'ppob' => 'PPOB',
            'qris' => 'QRIS',
            'custom' => 'Custom',
        ];

        $feeModes = [
            'fixed' => 'Fixed Fee',
            'percentage' => 'Percentage Fee',
            'fixed_percentage' => 'Fixed + Percentage',
            'tier' => 'Tier Fee',
            'cost_plus' => 'Cost Plus Markup',
            'custom' => 'Custom Formula',
        ];

        return view('atk.fee.create', compact('transactionTypes', 'feeModes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'transaction_type' => 'required|string',
            'fee_mode' => 'required|string',
            'custom_formula' => 'nullable|string',
            'cost_price' => 'nullable|numeric',
            'markup_value' => 'nullable|numeric',
            'markup_type' => 'nullable|string',
            'is_active' => 'boolean',
            'allow_override' => 'boolean',
            'tiers' => 'nullable|array',
            'tiers.*.min_amount' => 'numeric',
            'tiers.*.max_amount' => 'nullable|numeric',
            'tiers.*.fee_type' => 'nullable|string',
            'tiers.*.fee_value' => 'nullable|numeric',
            'tiers.*.fixed_value' => 'nullable|numeric',
        ]);

        $validated['module'] = 'atk';
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['allow_override'] = $request->boolean('allow_override', false);

        $profile = FeeProfile::create($validated);

        if ($request->has('tiers') && is_array($request->tiers)) {
            foreach ($request->tiers as $index => $tier) {
                $profile->tiers()->create([
                    'min_amount' => $tier['min_amount'] ?? 0,
                    'max_amount' => $tier['max_amount'] ?? null,
                    'fee_type' => $tier['fee_type'] ?? 'fixed',
                    'fee_value' => $tier['fee_value'] ?? 0,
                    'fixed_value' => $tier['fixed_value'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()->route('atk.fee.index')->with('success', 'Fee profile created successfully');
    }

    public function show($id)
    {
        $profile = FeeProfile::with('tiers')->findOrFail($id);
        return view('atk.fee.show', compact('profile'));
    }

    public function edit($id)
    {
        $profile = FeeProfile::with('tiers')->findOrFail($id);
        
        $transactionTypes = [
            'bank' => 'Transfer Bank',
            'cash_out' => 'Tarik Tunai',
            'top_up' => 'Top Up',
            'ppob' => 'PPOB',
            'qris' => 'QRIS',
            'custom' => 'Custom',
        ];

        $feeModes = [
            'fixed' => 'Fixed Fee',
            'percentage' => 'Percentage Fee',
            'fixed_percentage' => 'Fixed + Percentage',
            'tier' => 'Tier Fee',
            'cost_plus' => 'Cost Plus Markup',
            'custom' => 'Custom Formula',
        ];

        return view('atk.fee.edit', compact('profile', 'transactionTypes', 'feeModes'));
    }

    public function update(Request $request, $id)
    {
        $profile = FeeProfile::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'transaction_type' => 'required|string',
            'fee_mode' => 'required|string',
            'custom_formula' => 'nullable|string',
            'cost_price' => 'nullable|numeric',
            'markup_value' => 'nullable|numeric',
            'markup_type' => 'nullable|string',
            'is_active' => 'boolean',
            'allow_override' => 'boolean',
            'tiers' => 'nullable|array',
            'tiers.*.min_amount' => 'numeric',
            'tiers.*.max_amount' => 'nullable|numeric',
            'tiers.*.fee_type' => 'nullable|string',
            'tiers.*.fee_value' => 'nullable|numeric',
            'tiers.*.fixed_value' => 'nullable|numeric',
        ]);

        $validated['updated_by'] = Auth::id();
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['allow_override'] = $request->boolean('allow_override', false);

        $profile->update($validated);

        // Update tiers
        $profile->tiers()->delete();
        if ($request->has('tiers') && is_array($request->tiers)) {
            foreach ($request->tiers as $index => $tier) {
                $profile->tiers()->create([
                    'min_amount' => $tier['min_amount'] ?? 0,
                    'max_amount' => $tier['max_amount'] ?? null,
                    'fee_type' => $tier['fee_type'] ?? 'fixed',
                    'fee_value' => $tier['fee_value'] ?? 0,
                    'fixed_value' => $tier['fixed_value'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()->route('atk.fee.index')->with('success', 'Fee profile updated successfully');
    }

    public function destroy($id)
    {
        $profile = FeeProfile::findOrFail($id);
        $profile->delete();

        return redirect()->route('atk.fee.index')->with('success', 'Fee profile deleted successfully');
    }

    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'transaction_type' => 'required|string',
            'nominal' => 'required|numeric',
            'module' => 'nullable|string',
        ]);

        $result = $this->feeService->calculateFee(
            $validated['transaction_type'],
            (float) $validated['nominal'],
            $validated['module'] ?? 'atk'
        );

        return response()->json($result);
    }
}
