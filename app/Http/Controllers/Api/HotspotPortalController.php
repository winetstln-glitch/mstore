<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AtkProduct;
use App\Models\HotspotProfile;
use App\Models\Invoice;
use App\Models\Router;
use App\Models\User;
use App\Models\Voucher;
use App\Models\WashMember;
use App\Models\WashMemberPackage;
use App\Models\WashMemberSubscription;
use App\Models\WashService;
use App\Services\BillingService;
use App\Services\MidtransService;
use App\Services\MikrotikService;
use Modules\Network\Services\MonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HotspotPortalController extends Controller
{
    public function packageList(Request $request)
    {
        $type = strtolower(trim((string) $request->query('type', 'all')));
        $limit = max(1, min((int) $request->query('limit', 50), 100));

        $vouchers = collect();
        if ($type === 'all' || $type === 'voucher') {
            $vouchers = HotspotProfile::query()
                ->active()
                ->vouchers()
                ->orderBy('sort_order')
                ->orderBy('price')
                ->limit($limit)
                ->get()
                ->map(function (HotspotProfile $p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => 'VOUCHER-'.$p->id,
                        'type' => 'voucher',
                        'package_type' => $p->package_type,
                        'price' => (int) round($p->price),
                        'price_formatted' => $p->formatted_price,
                        'duration_label' => $p->formatted_uptime,
                        'duration_seconds' => $p->duration_seconds,
                        'rate_limit_mbps' => $p->rate_limit_mbps ? (float) $p->rate_limit_mbps : null,
                        'quota_mb' => $p->quota_mb,
                        'shared_users' => $p->shared_users,
                        'mikrotik_profile' => $p->mikrotik_profile_name,
                        'description' => $p->description,
                        'color_badge' => $p->color_badge,
                    ];
                });
        }

        $memberPackages = collect();
        if ($type === 'all' || $type === 'member' || $type === 'membership') {
            $memberPackages = WashMemberPackage::query()
                ->active()
                ->hasWifiBenefit()
                ->orderBy('sort_order')
                ->orderBy('price')
                ->limit($limit)
                ->get()
                ->map(function (WashMemberPackage $p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->code,
                        'type' => $p->type,
                        'package_type' => 'member',
                        'price' => (int) round($p->price),
                        'price_formatted' => $p->formatted_price,
                        'duration_days' => $p->duration_days,
                        'duration_label' => $p->duration_days . ' hari',
                        'discount_percent' => (float) $p->discount_percent,
                        'rate_limit_mbps' => $p->rate_limit_mbps ? (float) $p->rate_limit_mbps : null,
                        'daily_wifi_minutes' => $p->daily_wifi_minutes,
                        'network_type' => $p->network_type,
                        'pppoe_profile' => $p->pppoe_profile,
                        'description' => $p->description,
                        'benefits' => $p->benefits,
                    ];
                });
        }

        $residential = collect();
        if ($type === 'all' || $type === 'rumah' || $type === 'residential' || $type === 'home') {
            $residential = HotspotProfile::query()
                ->active()
                ->residential()
                ->orderBy('sort_order')
                ->orderBy('price')
                ->limit($limit)
                ->get()
                ->map(function (HotspotProfile $p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => 'HOME-'.$p->id,
                        'type' => 'home',
                        'package_type' => $p->package_type,
                        'price' => (int) round($p->price),
                        'price_formatted' => $p->formatted_price,
                        'rate_limit_mbps' => $p->rate_limit_mbps ? (float) $p->rate_limit_mbps : null,
                        'quota_mb' => $p->quota_mb,
                        'shared_users' => $p->shared_users,
                        'mikrotik_profile' => $p->mikrotik_profile_name,
                        'description' => $p->description,
                        'color_badge' => $p->color_badge,
                    ];
                });
        }

        $pppoe = collect();
        if ($type === 'all' || $type === 'pppoe') {
            $pppoe = HotspotProfile::query()
                ->active()
                ->pppoe()
                ->orderBy('sort_order')
                ->orderBy('price')
                ->limit($limit)
                ->get()
                ->map(function (HotspotProfile $p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => 'PPPOE-'.$p->id,
                        'type' => 'pppoe',
                        'package_type' => $p->package_type,
                        'price' => (int) round($p->price),
                        'price_formatted' => $p->formatted_price,
                        'rate_limit_mbps' => $p->rate_limit_mbps ? (float) $p->rate_limit_mbps : null,
                        'quota_mb' => $p->quota_mb,
                        'shared_users' => $p->shared_users,
                        'mikrotik_profile' => $p->mikrotik_profile_name,
                        'description' => $p->description,
                        'color_badge' => $p->color_badge,
                    ];
                });
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'vouchers' => $vouchers,
                'member_packages' => $memberPackages,
                'residential' => $residential,
                'pppoe' => $pppoe,
            ],
        ]);
    }

    public function signupMember(Request $request)
    {
        $payload = $request->validate([
            'nama' => 'required|string|max:120',
            'alamat' => 'nullable|string|max:255',
            'nomor' => 'required|string|max:32',
            'mac' => 'nullable|string|max:32',
            'paket' => 'nullable|string|max:120',
            'username' => 'nullable|string|max:64',
            'password' => 'nullable|string|max:64',
            'package_id' => 'nullable|integer|exists:wash_member_packages,id',
            'router_id' => 'nullable|integer|exists:routers,id',
        ]);

        $phone = preg_replace('/\D+/', '', (string) $payload['nomor']);
        if (str_starts_with($phone, '0')) {
            $phoneFormatted = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phoneFormatted = '62' . $phone;
        } else {
            $phoneFormatted = $phone;
        }

        $washMember = WashMember::query()
            ->where('whatsapp', $phoneFormatted)
            ->orWhere('whatsapp', $phone)
            ->first();

        DB::beginTransaction();
        try {
            if (!$washMember) {
                $washMember = WashMember::create([
                    'member_number' => 'WM' . date('ymdHis') . rand(10, 99),
                    'name' => $payload['nama'],
                    'whatsapp' => $phoneFormatted,
                    'address' => $payload['alamat'] ?? null,
                    'joined_at' => now(),
                    'status' => 'active',
                    'total_transactions' => 0,
                    'total_visits' => 0,
                    'total_spending' => 0,
                ]);
            }

            $radiusUsername = $payload['username'] ?? ('wifi_' . strtolower(Str::random(8)));
            $radiusPassword = $payload['password'] ?? Str::random(10);

            $user = $this->resolveUserFromIdentity($payload['nomor']);
            if (!$user) {
                $email = 'wifi+' . strtolower(Str::random(8)) . '@mstore.local';
                $user = User::create([
                    'name' => $payload['nama'],
                    'email' => $email,
                    'phone' => $phoneFormatted,
                    'username' => User::generateUniqueUsername($payload['nama'], $email),
                    'password' => bcrypt($radiusPassword),
                    'radius_username' => $radiusUsername,
                    'is_active' => true,
                ]);
            } else {
                if (empty($user->radius_username)) {
                    $user->radius_username = $radiusUsername;
                    $user->save();
                }
                if (empty($user->phone)) {
                    $user->phone = $phoneFormatted;
                    $user->save();
                }
            }

            $subscription = null;
            $packageWifi = null;
            $invoice = null;
            if (!empty($payload['package_id'])) {
                $packageWifi = WashMemberPackage::find($payload['package_id']);
            } elseif (!empty($payload['paket'])) {
                $packageWifi = WashMemberPackage::query()
                    ->where('code', $payload['paket'])
                    ->orWhere('name', 'like', '%' . $payload['paket'] . '%')
                    ->active()
                    ->first();
            }

            if ($packageWifi) {
                $startDate = now();
                $endDate = $startDate->copy()->addDays($packageWifi->duration_days);
                $subscription = WashMemberSubscription::create([
                    'wash_member_id' => $washMember->id,
                    'wash_member_package_id' => $packageWifi->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'pending',
                    'paid_amount' => 0,
                    'meta' => [
                        'signup_source' => 'hotspot_portal',
                        'mac_address' => $payload['mac'] ?? null,
                        'radius_username' => $user->radius_username,
                        'router_id' => $payload['router_id'] ?? $packageWifi->router_id,
                    ],
                ]);

                $invoice = Invoice::create([
                    'user_id' => $user->id,
                    'code' => 'MBR-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4)),
                    'amount' => (int) round($packageWifi->price),
                    'due_date' => now()->toDateString(),
                    'status' => 'pending',
                    'meta' => [
                        'source' => 'hotspot_signup',
                        'package_type' => 'member_wifi',
                        'package_code' => $packageWifi->code,
                        'package_id' => $packageWifi->id,
                        'wash_member_id' => $washMember->id,
                        'subscription_id' => $subscription?->id,
                        'display_user' => $payload['nama'],
                        'customer_phone' => $phoneFormatted,
                    ],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran berhasil. Silakan selesaikan pembayaran untuk mengaktifkan paket.',
                'data' => [
                    'wash_member_id' => $washMember->id,
                    'member_number' => $washMember->member_number,
                    'user_id' => $user->id,
                    'radius_username' => $user->radius_username,
                    'subscription_id' => $subscription?->id,
                    'invoice_code' => $invoice?->code,
                    'invoice_amount' => isset($invoice) ? (int) $invoice->amount : 0,
                    'payment_required' => isset($invoice),
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Hotspot signup error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendaftarkan member: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function createPayment(Request $request, MidtransService $midtrans)
    {
        $payload = $request->validate([
            'gateway' => 'nullable|string|max:20',
            'amount' => 'required|integer|min:1000',
            'user' => 'nullable|string|max:120',
            'package_code' => 'nullable|string|max:80',
            'hotspot_profile_id' => 'nullable|integer|exists:hotspot_profiles,id',
            'package_id' => 'nullable|integer|exists:wash_member_packages,id',
            'customer_name' => 'nullable|string|max:120',
            'customer_phone' => 'nullable|string|max:32',
            'router_id' => 'nullable|integer|exists:routers,id',
        ]);

        $user = $this->resolveUserFromIdentity($payload['user'] ?? null);
        $customerPhone = $payload['customer_phone'] ?? null;
        if ($customerPhone) {
            $digits = preg_replace('/\D+/', '', (string) $customerPhone);
            if (str_starts_with($digits, '0')) {
                $customerPhone = '62' . substr($digits, 1);
            } elseif (!str_starts_with($digits, '62') && $digits !== '') {
                $customerPhone = '62' . $digits;
            }
        }

        if (!$user) {
            $seed = trim((string) ($payload['customer_name'] ?? $payload['user'] ?? 'guest'));
            $slug = Str::of($seed)->lower()->replaceMatches('/[^a-z0-9]+/', '.')->trim('.')->value();
            if ($slug === '') {
                $slug = 'guest';
            }
            $email = 'hotspot+' . $slug . '.' . Str::lower(Str::random(6)) . '@mstore.local';
            $username = User::generateUniqueUsername($seed ?: 'guest', $email);
            $user = User::create([
                'name' => $seed !== '' ? $seed : 'Guest',
                'email' => $email,
                'phone' => $customerPhone,
                'username' => $username,
                'password' => Str::random(32),
                'is_active' => true,
            ]);
        }

        $packageType = 'voucher';
        $hotspotProfile = null;
        $washPackage = null;

        if (!empty($payload['hotspot_profile_id'])) {
            $hotspotProfile = HotspotProfile::find($payload['hotspot_profile_id']);
            if ($hotspotProfile) {
                $packageType = $hotspotProfile->package_type;
            }
        }
        if (!empty($payload['package_id'])) {
            $washPackage = WashMemberPackage::find($payload['package_id']);
            if ($washPackage) {
                $packageType = 'member_' . $washPackage->type;
            }
        }

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'code' => 'HS-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4)),
            'amount' => (int) $payload['amount'],
            'due_date' => now()->toDateString(),
            'status' => 'pending',
            'meta' => [
                'source' => 'hotspot',
                'gateway' => strtolower((string) ($payload['gateway'] ?? 'midtrans')),
                'package_code' => $payload['package_code'] ?? null,
                'hotspot_profile_id' => $hotspotProfile?->id,
                'wash_member_package_id' => $washPackage?->id,
                'package_type' => $packageType,
                'display_user' => $payload['customer_name'] ?? $payload['user'] ?? $user->name,
                'customer_phone' => $customerPhone,
                'customer_name' => $payload['customer_name'] ?? null,
                'router_id' => $payload['router_id'] ?? $hotspotProfile?->router_id ?? $washPackage?->router_id,
            ],
        ]);

        $snapToken = $midtrans->createSnapToken($invoice);
        $isProd = filter_var(config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION', false)), FILTER_VALIDATE_BOOL);
        $paymentUrl = ($isProd ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com') . '/snap/v2/vtweb/' . $snapToken;

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

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'transaction not found',
            ], 404);
        }

        $voucherCode = null;
        $voucherPassword = null;
        $routerSynced = false;
        $syncError = null;

        if ($invoice->status === 'paid') {
            $meta = is_array($invoice->meta) ? $invoice->meta : [];
            if (empty($meta['voucher_code'])) {
                $meta['voucher_code'] = $this->buildVoucherCode($invoice);
            }
            $voucherCode = (string) $meta['voucher_code'];
            $packageType = $meta['package_type'] ?? 'voucher';
            $routerId = $meta['router_id'] ?? null;
            $hotspotProfileId = $meta['hotspot_profile_id'] ?? null;

            $existingVoucher = Voucher::where('invoice_id', $invoice->id)->first();
            if (!$existingVoucher && str_contains($packageType, 'voucher')) {
                $syncResult = $this->syncVoucherToRouter($invoice, $voucherCode, $routerId, $hotspotProfileId);
                $routerSynced = $syncResult['success'];
                $syncError = $syncResult['error'];
                $voucherPassword = $syncResult['password'];
            } elseif ($existingVoucher) {
                $routerSynced = (bool) $existingVoucher->synced_to_router;
                $voucherPassword = $existingVoucher->password;
            }

            if (!isset($meta['voucher_password']) && $voucherPassword) {
                $meta['voucher_password'] = $voucherPassword;
            }
            if (!isset($meta['router_synced'])) {
                $meta['router_synced'] = $routerSynced;
                $meta['router_sync_error'] = $syncError;
            }
            $invoice->meta = $meta;
            $invoice->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'transaction_id' => $invoice->code,
                'status' => $invoice->status,
                'voucher_code' => $voucherCode,
                'voucher_password' => $voucherPassword,
                'router_synced' => $routerSynced,
                'router_sync_error' => $syncError,
            ],
        ]);
    }

    public function paymentCallback(Request $request)
    {
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? $payload['transaction_id'] ?? null;
        $status = strtolower((string) ($payload['transaction_status'] ?? ''));
        $fraudStatus = strtolower((string) ($payload['fraud_status'] ?? ''));

        if (!$orderId) {
            return response()->json(['success' => false, 'message' => 'order_id required'], 400);
        }

        $invoice = Invoice::where('code', $orderId)
            ->orWhere('midtrans_order_id', $orderId)
            ->latest('id')
            ->first();

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'invoice not found'], 404);
        }

        $isPaid = in_array($status, ['capture', 'settlement', 'success'])
            || ($status === 'pending' && in_array($fraudStatus, ['accept', 'safe']));

        DB::beginTransaction();
        try {
            if ($isPaid && $invoice->status !== 'paid') {
                $invoice->status = 'paid';
                $invoice->paid_at = now();
                $invoice->midtrans_order_id = $payload['transaction_id'] ?? $invoice->midtrans_order_id;
                $invoice->save();

                $meta = is_array($invoice->meta) ? $invoice->meta : [];
                if (empty($meta['voucher_code'])) {
                    $meta['voucher_code'] = $this->buildVoucherCode($invoice);
                    $invoice->meta = $meta;
                    $invoice->save();
                }

                $packageType = $meta['package_type'] ?? 'voucher';
                if (str_contains($packageType, 'voucher')) {
                    $this->syncVoucherToRouter(
                        $invoice,
                        $meta['voucher_code'],
                        $meta['router_id'] ?? null,
                        $meta['hotspot_profile_id'] ?? null
                    );
                }

                if (!empty($meta['wash_member_package_id']) && !empty($meta['subscription_id'])) {
                    $subs = WashMemberSubscription::find($meta['subscription_id']);
                    if ($subs && $subs->status === 'pending') {
                        $pkg = WashMemberPackage::find($meta['wash_member_package_id']);
                        $subs->status = 'active';
                        $subs->paid_amount = $invoice->amount;
                        $subsMeta = is_array($subs->meta) ? $subs->meta : [];
                        $subsMeta['invoice_code'] = $invoice->code;
                        $subs->meta = $subsMeta;
                        $subs->save();

                        if ($pkg && in_array($pkg->type, ['wifi', 'both'])) {
                            $this->provisionMemberSecret($pkg, $subs, $meta);
                        }
                    }
                }
            } elseif (in_array($status, ['deny', 'cancel', 'expire', 'failure'])) {
                if ($invoice->status === 'pending') {
                    $invoice->status = 'failed';
                    $invoice->save();
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Hotspot payment callback error: ' . $e->getMessage(), [
                'payload' => $payload,
                'invoice_id' => $invoice?->id,
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'invoice_status' => $invoice->status,
        ]);
    }

    public function voucherStatus(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:80',
        ]);

        $code = strtoupper(trim((string) $request->query('code')));

        $voucher = Voucher::where('username', $code)->orWhere('password', $code)->first();
        if ($voucher) {
            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => [
                    'found' => true,
                    'status' => $voucher->status,
                    'used' => $voucher->status !== 'unused' && $voucher->status !== 'sold',
                    'profile' => $voucher->profile,
                    'formatted_uptime' => $voucher->formatted_uptime,
                    'expires_at' => optional($voucher->expires_at)->toIso8601String(),
                    'synced_to_router' => (bool) $voucher->synced_to_router,
                    'message' => $voucher->status === 'unused' || $voucher->status === 'sold'
                        ? 'Voucher tersedia dan bisa digunakan'
                        : 'Voucher sudah ' . $voucher->status_label,
                ],
            ]);
        }

        $paidInvoices = Invoice::where('status', 'paid')->latest('id')->limit(500)->get();
        $matched = $paidInvoices->first(function (Invoice $invoice) use ($code) {
            $meta = is_array($invoice->meta) ? $invoice->meta : [];
            $voucher = strtoupper((string) ($meta['voucher_code'] ?? ''));
            return $voucher !== '' && $voucher === $code;
        });

        if (!$matched) {
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

    public function billingMonthly(Request $request, BillingService $billing)
    {
        $request->validate([
            'customer_id' => 'required|string|max:120',
        ]);

        $identity = trim((string) $request->query('customer_id'));
        $user = $this->resolveUserFromIdentity($identity);

        $invoice = null;
        if ($user) {
            $invoice = $user->invoices()
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->orderBy('due_date')
                ->latest('id')
                ->first();
        }
        if (!$invoice) {
            $invoice = Invoice::where('code', $identity)->latest('id')->first();
        }

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'billing not found',
            ], 404);
        }

        $subscriptions = [];
        if ($user) {
            $washMember = WashMember::where('whatsapp', $user->phone)->first();
            if ($washMember) {
                $subscriptions = $washMember->subscriptions()
                    ->with('package')
                    ->latest('id')
                    ->limit(5)
                    ->get()
                    ->map(function ($s) {
                        return [
                            'id' => $s->id,
                            'package_name' => optional($s->package)->name,
                            'status' => $s->status,
                            'start_date' => optional($s->start_date)->toDateString(),
                            'end_date' => optional($s->end_date)->toDateString(),
                            'paid_amount' => (int) round($s->paid_amount),
                        ];
                    })
                    ->toArray();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'invoice_code' => $invoice->code,
                'amount' => (int) $invoice->amount,
                'due_date' => optional($invoice->due_date)->toDateString(),
                'status' => $invoice->status,
                'subscriptions' => $subscriptions,
            ],
        ]);
    }

    public function productAds(Request $request)
    {
        $limit = max(1, min((int) $request->query('limit', 8), 20));

        $hotspotProfiles = HotspotProfile::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->limit($limit)
            ->get(['id', 'name', 'package_type', 'price']);

        $washMemberPackages = WashMemberPackage::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->limit($limit)
            ->get(['id', 'name', 'type', 'price']);

        $atk = AtkProduct::query()
            ->where('stock', '>', 0)
            ->latest('id')
            ->limit($limit)
            ->get(['name', 'price', 'category']);

        $items = [];

        $packageTypeSubtitle = [
            'pppoe' => 'Paket PPPoE',
            'voucher' => 'Paket Voucher',
            'residential' => 'Paket Rumahan',
            'home' => 'Paket Rumahan',
            'rumahan' => 'Paket Rumahan',
            'member' => 'Paket Member',
            'membership' => 'Paket Member',
        ];

        foreach ($hotspotProfiles as $p) {
            $subtitle = $packageTypeSubtitle[$p->package_type] ?? 'Paket Internet';
            $items[] = [
                'title' => (string) $p->name,
                'subtitle' => $subtitle,
                'price' => (int) round($p->price ?? 0),
                'kind' => 'package',
                'package_type' => $p->package_type,
            ];
        }

        $washMemberTypeSubtitle = [
            'wifi' => 'Paket Member WiFi',
            'wash' => 'Paket Member Wash',
            'both' => 'Paket Member Wash+WiFi',
        ];

        foreach ($washMemberPackages as $wm) {
            $subtitle = $washMemberTypeSubtitle[$wm->type] ?? 'Paket Member';
            $items[] = [
                'title' => (string) $wm->name,
                'subtitle' => $subtitle,
                'price' => (int) round($wm->price ?? 0),
                'kind' => 'package',
                'package_type' => 'member_' . $wm->type,
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

    public function health(MonitoringService $monitoring)
    {
        $dbOk = true;
        try {
            DB::select('select 1 as ok');
        } catch (\Throwable $e) {
            $dbOk = false;
        }

        $networkHealth = $monitoring->health();
        $midtransReady = (string) config('services.midtrans.server_key', '') !== '';
        $routerCount = Router::where('is_active', true)->count();
        $ready = $dbOk && $midtransReady;

        return response()->json([
            'success' => $ready,
            'message' => $ready ? 'ok' : 'partial',
            'data' => [
                'ready' => $ready,
                'db_ok' => $dbOk,
                'midtrans_ready' => $midtransReady,
                'network_ok' => (bool) ($networkHealth['ok'] ?? false),
                'routers_online' => $routerCount,
                'server_time' => now()->toIso8601String(),
            ],
        ], $dbOk ? 200 : 503);
    }

    protected function syncVoucherToRouter(Invoice $invoice, string $voucherCode, ?int $routerId, ?int $hotspotProfileId): array
    {
        $meta = is_array($invoice->meta) ? $invoice->meta : [];
        $profile = null;
        $limitUptime = null;
        $quotaBytes = null;
        $router = null;
        $durationSeconds = null;
        $mikrotikProfileName = 'default';

        if ($hotspotProfileId) {
            $profile = HotspotProfile::find($hotspotProfileId);
            if ($profile) {
                $routerId = $routerId ?? $profile->router_id;
                $mikrotikProfileName = $profile->mikrotik_profile_name ?? 'default';
                $limitUptime = $profile->limit_uptime;
                $durationSeconds = $profile->duration_seconds;
                if ($profile->quota_mb) {
                    $quotaBytes = $profile->quota_mb * 1024 * 1024;
                }
            }
        }

        $routerId = $routerId ?? Router::where('is_active', true)->value('id');
        if ($routerId) {
            $router = Router::find($routerId);
        }

        $password = $meta['voucher_password'] ?? $voucherCode;

        $voucher = Voucher::create([
            'username' => $voucherCode,
            'password' => $password,
            'profile' => $mikrotikProfileName,
            'duration_seconds' => $durationSeconds,
            'quota_mb' => $profile?->quota_mb,
            'status' => 'sold',
            'hotspot_profile_id' => $profile?->id,
            'router_id' => $routerId,
            'invoice_id' => $invoice->id,
            'customer_name' => $meta['customer_name'] ?? null,
            'customer_phone' => $meta['customer_phone'] ?? null,
            'sold_at' => now(),
            'synced_to_router' => false,
        ]);

        if (!$router) {
            $voucher->markAsSynced('Router tidak ditemukan / tidak aktif');
            return ['success' => false, 'error' => 'Router tidak tersedia', 'password' => $password];
        }

        try {
            $mt = new MikrotikService($router);
            if (!$mt->isConnected()) {
                $voucher->markAsSynced('Gagal koneksi ke Router');
                return ['success' => false, 'error' => 'Gagal koneksi ke Router MikroTik', 'password' => $password];
            }

            $uptime = $limitUptime;
            if (!$uptime && $durationSeconds) {
                $h = floor($durationSeconds / 3600);
                $m = floor(($durationSeconds % 3600) / 60);
                if ($h > 0) {
                    $uptime = $h . 'h' . ($m > 0 ? $m . 'm' : '');
                } elseif ($m > 0) {
                    $uptime = $m . 'm';
                }
            }

            $result = $mt->createHotspotUser($voucherCode, $password, $mikrotikProfileName, $uptime, $quotaBytes);
            if ($result) {
                $voucher->markAsSynced();
                return ['success' => true, 'error' => null, 'password' => $password];
            }

            $voucher->markAsSynced('createHotspotUser gagal (user mungkin sudah ada?)');
            return ['success' => false, 'error' => 'Gagal membuat user hotspot', 'password' => $password];
        } catch (\Throwable $e) {
            Log::error('syncVoucherToRouter error: ' . $e->getMessage());
            $voucher->markAsSynced($e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'password' => $password];
        }
    }

    protected function provisionMemberSecret(WashMemberPackage $pkg, WashMemberSubscription $subs, array $meta): bool
    {
        $routerId = $pkg->router_id ?? ($meta['router_id'] ?? null);
        if (!$routerId) {
            $routerId = Router::where('is_active', true)->value('id');
        }
        if (!$routerId) {
            return false;
        }
        $router = Router::find($routerId);
        if (!$router) {
            return false;
        }

        $subsMeta = is_array($subs->meta) ? $subs->meta : [];
        $radiusUsername = $subsMeta['radius_username'] ?? null;
        if (!$radiusUsername && !empty($subs->member)) {
            $wa = preg_replace('/\D+/', '', (string) optional($subs->member)->whatsapp);
            if ($wa) {
                $radiusUsername = 'user_' . $wa;
            }
        }
        if (!$radiusUsername) {
            return false;
        }

        $password = substr(md5($radiusUsername . '|' . $subs->id), 0, 10);
        $profile = $pkg->pppoe_profile ?? 'default';

        try {
            $mt = new MikrotikService($router);
            if (!$mt->isConnected()) {
                return false;
            }
            $networkType = strtolower((string) ($pkg->network_type ?? 'pppoe'));
            if ($networkType === 'hotspot') {
                $uptime = null;
                if ($pkg->daily_wifi_minutes) {
                    $uptime = $pkg->daily_wifi_minutes . 'm';
                }
                $limitBytes = $pkg->rate_limit_mbps ? ($pkg->rate_limit_mbps * 1024 * 1024 * 1024) : null;
                return $mt->createHotspotUser($radiusUsername, $password, $profile, $uptime, $limitBytes);
            }
            return $mt->createSecret($radiusUsername, $password, $profile);
        } catch (\Throwable $e) {
            Log::error('provisionMemberSecret error: ' . $e->getMessage());
            return false;
        }
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
                $phoneCandidates[] = '62' . substr($digits, 1);
            } elseif (str_starts_with($digits, '62')) {
                $phoneCandidates[] = '0' . substr($digits, 2);
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

        return 'MS' . str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT) . $tail;
    }
}
