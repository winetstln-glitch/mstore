<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AtkProduct;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use App\Models\WashService;
use App\Services\BillingService;
use App\Services\MidtransService;
use App\Services\MixRadiusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HotspotPortalController extends Controller
{
    public function createPayment(Request $request, MidtransService $midtrans)
    {
        $payload = $request->validate([
            'gateway' => 'nullable|string|max:20',
            'amount' => 'required|integer|min:1000',
            'user' => 'nullable|string|max:120',
            'package_code' => 'nullable|string|max:80',
        ]);

        $user = $this->resolveUserFromIdentity($payload['user'] ?? null);
        if (! $user) {
            $seed = trim((string) ($payload['user'] ?? 'guest'));
            $slug = Str::of($seed)->lower()->replaceMatches('/[^a-z0-9]+/', '.')->trim('.')->value();
            if ($slug === '') {
                $slug = 'guest';
            }
            $email = 'hotspot+'.$slug.'.'.Str::lower(Str::random(6)).'@mstore.local';
            $username = User::generateUniqueUsername($seed ?: 'guest', $email);
            $user = User::create([
                'name' => $seed !== '' ? $seed : 'Guest',
                'email' => $email,
                'username' => $username,
                'password' => Str::random(32),
                'is_active' => true,
            ]);
        }

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'code' => 'HS-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4)),
            'amount' => (int) $payload['amount'],
            'due_date' => now()->toDateString(),
            'status' => 'pending',
            'meta' => [
                'source' => 'hotspot',
                'gateway' => strtolower((string) ($payload['gateway'] ?? 'midtrans')),
                'package_code' => $payload['package_code'] ?? null,
                'display_user' => $payload['user'] ?? $user->name,
            ],
        ]);

        $snapToken = $midtrans->createSnapToken($invoice);
        $isProd = filter_var(config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION', false)), FILTER_VALIDATE_BOOL);
        $paymentUrl = ($isProd ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com').'/snap/v2/vtweb/'.$snapToken;

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'transaction_id' => $invoice->code,
                'status' => 'pending',
                'payment_url' => $paymentUrl,
                'expired_at' => optional($invoice->created_at)->addMinutes(30)?->toIso8601String(),
            ],
        ]);
    }

    public function paymentStatus(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|string|max:120',
        ]);

        $trx = (string) $request->query('transaction_id');
        $invoice = Invoice::where('code', $trx)
            ->orWhere('midtrans_order_id', $trx)
            ->latest('id')
            ->first();

        if (! $invoice) {
            return response()->json([
                'success' => false,
                'message' => 'transaction not found',
            ], 404);
        }

        $voucherCode = null;
        if ($invoice->status === 'paid') {
            $meta = is_array($invoice->meta) ? $invoice->meta : [];
            if (empty($meta['voucher_code'])) {
                $meta['voucher_code'] = $this->buildVoucherCode($invoice);
                $invoice->meta = $meta;
                $invoice->save();
            }
            $voucherCode = (string) $meta['voucher_code'];
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'transaction_id' => $invoice->code,
                'status' => $invoice->status,
                'voucher_code' => $voucherCode,
            ],
        ]);
    }

    public function voucherStatus(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:80',
        ]);

        $code = strtoupper(trim((string) $request->query('code')));
        $paidInvoices = Invoice::where('status', 'paid')->latest('id')->limit(500)->get();
        $matched = $paidInvoices->first(function (Invoice $invoice) use ($code) {
            $meta = is_array($invoice->meta) ? $invoice->meta : [];
            $voucher = strtoupper((string) ($meta['voucher_code'] ?? ''));

            return $voucher !== '' && $voucher === $code;
        });

        if (! $matched) {
            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => [
                    'found' => false,
                    'used' => false,
                    'status' => 'not_found',
                    'message' => 'Voucher tidak ditemukan',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'found' => true,
                'used' => false,
                'status' => 'ready',
                'message' => 'Voucher tersedia dan bisa digunakan',
            ],
        ]);
    }

    public function billingMonthly(Request $request, BillingService $billing, MixRadiusService $mix)
    {
        $request->validate([
            'customer_id' => 'required|string|max:120',
        ]);

        $identity = trim((string) $request->query('customer_id'));
        $user = $this->resolveUserFromIdentity($identity);

        if ($user) {
            $billing->syncFromMixRadius($user, $mix);
        }

        $invoice = null;
        if ($user) {
            $invoice = $user->invoices()
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->orderBy('due_date')
                ->latest('id')
                ->first();
        }
        if (! $invoice) {
            $invoice = Invoice::where('code', $identity)->latest('id')->first();
        }

        if (! $invoice) {
            return response()->json([
                'success' => false,
                'message' => 'billing not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'invoice_code' => $invoice->code,
                'amount' => (int) $invoice->amount,
                'due_date' => optional($invoice->due_date)->toDateString(),
                'status' => $invoice->status,
            ],
        ]);
    }

    public function productAds(Request $request)
    {
        $limit = max(1, min((int) $request->query('limit', 8), 20));

        $packages = Package::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->limit($limit)
            ->get(['name', 'price']);

        $atk = AtkProduct::query()
            ->where('stock', '>', 0)
            ->latest('id')
            ->limit($limit)
            ->get(['name', 'price', 'category']);

        $items = [];
        foreach ($packages as $pkg) {
            $items[] = [
                'title' => (string) $pkg->name,
                'subtitle' => 'Paket Internet',
                'price' => (int) ($pkg->price ?? 0),
                'kind' => 'package',
            ];
        }
        foreach ($atk as $row) {
            $items[] = [
                'title' => (string) $row->name,
                'subtitle' => (string) ($row->category ?: 'Produk'),
                'price' => (int) ($row->price ?? 0),
                'kind' => 'product',
            ];
        }

        $washServices = WashService::query()
            ->where('is_active', true)
            ->whereIn('service_category', ['addon', 'skincare', 'main'])
            ->with(['priceRules' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
            }])
            ->orderBy('service_category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'price', 'service_category']);

        foreach ($washServices as $service) {
            $rules = [];
            foreach ($service->priceRules->take(6) as $rule) {
                $rules[] = [
                    'label' => (string) $rule->label,
                    'price' => (int) $rule->price,
                ];
            }

            $items[] = [
                'title' => (string) $service->name,
                'subtitle' => (string) ($service->service_category === 'addon' ? 'Addon Wash' : ($service->service_category === 'skincare' ? 'Skincare Wash' : 'Layanan Wash')),
                'price' => (int) ($service->price ?? 0),
                'kind' => 'wash',
                'rules' => $rules,
            ];
        }
        $items = array_slice($items, 0, $limit);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'items' => $items,
                'landing_url' => url('/'),
            ],
        ]);
    }

    public function health(MixRadiusService $mix)
    {
        $dbOk = true;
        try {
            DB::select('select 1 as ok');
        } catch (\Throwable $e) {
            $dbOk = false;
        }

        $mixHealth = $mix->health();
        $midtransReady = (string) config('services.midtrans.server_key', '') !== '';
        $ready = $dbOk && $midtransReady;

        return response()->json([
            'success' => $ready,
            'message' => $ready ? 'ok' : 'partial',
            'data' => [
                'ready' => $ready,
                'db_ok' => $dbOk,
                'midtrans_ready' => $midtransReady,
                'mixradius_ok' => (bool) ($mixHealth['ok'] ?? false),
                'server_time' => now()->toIso8601String(),
            ],
        ], $dbOk ? 200 : 503);
    }

    protected function resolveUserFromIdentity(?string $identity): ?User
    {
        $id = trim((string) $identity);
        if ($id === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $id) ?? '';
        $phoneCandidates = [];
        if ($digits !== '') {
            $phoneCandidates[] = $digits;
            if (str_starts_with($digits, '0')) {
                $phoneCandidates[] = '62'.substr($digits, 1);
            } elseif (str_starts_with($digits, '62')) {
                $phoneCandidates[] = '0'.substr($digits, 2);
            }
        }
        $phoneCandidates = array_values(array_unique(array_filter($phoneCandidates)));

        $query = User::query();
        if (ctype_digit($id)) {
            $query->orWhere('id', (int) $id);
        }

        foreach ($phoneCandidates as $candidate) {
            $query->orWhere('phone', $candidate);
        }

        return $query
            ->orWhere('username', $id)
            ->orWhere('email', $id)
            ->orWhere('radius_username', $id)
            ->first();
    }

    protected function buildVoucherCode(Invoice $invoice): string
    {
        $tail = strtoupper(substr(md5((string) $invoice->code), 0, 4));

        return 'MS'.str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT).$tail;
    }
}
