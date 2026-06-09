<?php

namespace App\Services\Wash;

use App\Models\Setting;
use App\Models\WashMember;
use App\Models\WashMemberCard;
use App\Models\WashMemberLevel;
use App\Models\WashMemberVehicle;
use App\Models\WashTransaction;
use App\Services\AuditLogService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WashMembershipService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly WhatsAppService $whatsApp
    ) {
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (! is_string($digits) || $digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }
        return $digits;
    }

    public function normalizePlate(string $plate): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plate));
    }

    public function ensureMember(string $name, string $whatsapp, string $vehiclePlate, ?string $vehicleBrand = null): ?WashMember
    {
        $phone = $this->normalizePhone($whatsapp);
        $plate = $this->normalizePlate($vehiclePlate);
        $name = trim($name);

        if ($phone === '' || $plate === '' || $name === '') {
            return null;
        }

        return DB::transaction(function () use ($name, $phone, $plate, $vehicleBrand) {
            $member = WashMember::query()
                ->where('whatsapp', $phone)
                ->lockForUpdate()
                ->first();

            if (! $member) {
                $member = WashMember::create([
                    'member_number' => 'TEMP-'.Str::upper(Str::random(10)),
                    'name' => $name,
                    'whatsapp' => $phone,
                    'email' => null,
                    'address' => null,
                    'joined_at' => now(),
                    'wash_member_level_id' => $this->defaultLevel()?->id,
                    'total_transactions' => 0,
                    'total_visits' => 0,
                    'total_spending' => 0,
                    'status' => 'active',
                ]);

                $member->member_number = $this->formatMemberNumber($member->id);
                $member->save();

                $this->auditLog->logAction('wash_membership.member_created', $member, [
                    'member_number' => $member->member_number,
                ]);
            } elseif ($member->name === 'Guest' && $name !== '') {
                $member->name = $name;
                $member->save();
            }

            $this->ensureVehicle($member, $plate, $vehicleBrand);
            $this->ensureMemberCard($member);

            return $member;
        });
    }

    public function findMemberByPhone(string $whatsapp): ?WashMember
    {
        $phone = $this->normalizePhone($whatsapp);
        if ($phone === '') {
            return null;
        }
        return WashMember::query()->with(['level', 'vehicles', 'card'])->where('whatsapp', $phone)->first();
    }

    public function findMemberByPlate(string $plate): ?WashMember
    {
        $normalized = $this->normalizePlate($plate);
        if ($normalized === '') {
            return null;
        }

        $vehicle = WashMemberVehicle::query()->where('vehicle_plate', $normalized)->first();
        if (! $vehicle) {
            return null;
        }

        return WashMember::query()->with(['level', 'vehicles', 'card'])->find($vehicle->wash_member_id);
    }

    public function calculateLevelForTotalTransactions(int $totalTransactions): ?WashMemberLevel
    {
        return WashMemberLevel::query()
            ->where('is_active', true)
            ->where('min_transactions', '<=', $totalTransactions)
            ->where(function ($q) use ($totalTransactions) {
                $q->whereNull('max_transactions')->orWhere('max_transactions', '>=', $totalTransactions);
            })
            ->orderByDesc('min_transactions')
            ->first();
    }

    public function calculateDiscountPercent(?WashMember $member): float
    {
        if (! $member || $member->status !== 'active') {
            return 0;
        }

        $member->loadMissing('level');
        $pct = (float) ($member->level?->discount_percent ?? 0);
        if ($pct < 0) {
            $pct = 0;
        }
        if ($pct > 50) {
            $pct = 50;
        }
        return $pct;
    }

    public function calculateDiscountAmount(float $amount, float $discountPercent): float
    {
        if ($amount <= 0 || $discountPercent <= 0) {
            return 0;
        }
        return round($amount * ($discountPercent / 100), 2);
    }

    public function syncAfterTransaction(WashTransaction $transaction): array
    {
        $transaction->loadMissing(['member', 'items']);
        $member = $transaction->member;
        if (! $member) {
            return ['member' => null, 'level_upgraded' => false, 'old_level' => null, 'new_level' => null];
        }

        if (strtolower((string) $transaction->status) !== 'lunas') {
            return ['member' => $member, 'level_upgraded' => false, 'old_level' => null, 'new_level' => null];
        }

        if (((float) $transaction->total_amount) <= 0) {
            return ['member' => $member, 'level_upgraded' => false, 'old_level' => null, 'new_level' => null];
        }

        return DB::transaction(function () use ($transaction, $member) {
            $member = WashMember::query()->lockForUpdate()->find($member->id);
            if (! $member) {
                return ['member' => null, 'level_upgraded' => false, 'old_level' => null, 'new_level' => null];
            }

            $oldLevel = $member->level;
            $member->total_transactions = ((int) $member->total_transactions) + 1;
            $member->total_visits = ((int) $member->total_visits) + 1;
            $member->total_spending = ((float) $member->total_spending) + ((float) $transaction->total_amount);
            $member->last_transaction_at = now();

            $newLevel = $this->calculateLevelForTotalTransactions((int) $member->total_transactions);
            $levelUpgraded = false;
            if ($newLevel && (int) $member->wash_member_level_id !== (int) $newLevel->id) {
                $member->wash_member_level_id = $newLevel->id;
                $levelUpgraded = true;
            }

            $member->save();

            if ($levelUpgraded) {
                $this->auditLog->logAction('wash_membership.level_upgraded', $member, [
                    'old_level' => $oldLevel?->code,
                    'new_level' => $newLevel?->code,
                    'total_transactions' => (int) $member->total_transactions,
                ]);
            }

            return [
                'member' => $member->fresh(['level', 'vehicles', 'card']),
                'level_upgraded' => $levelUpgraded,
                'old_level' => $oldLevel,
                'new_level' => $newLevel,
            ];
        });
    }

    public function sendAfterTransactionWhatsApp(WashMember $member, array $data): void
    {
        $phone = $member->whatsapp;
        if (trim((string) $phone) === '') {
            return;
        }

        $levelName = strtoupper((string) ($member->level?->code ?? 'BRONZE')).' MEMBER';
        $progress = (int) ($data['loyalty_progress'] ?? 0);
        $target = (int) ($data['loyalty_target'] ?? 10);
        $remaining = (int) ($data['loyalty_remaining'] ?? 0);
        $voucherCode = (string) ($data['reward_voucher_code'] ?? '');

        $lines = [];
        $lines[] = "Halo {$member->name} 👋";
        $lines[] = '';
        $lines[] = 'Terima kasih telah menggunakan GT Wash.';
        $lines[] = '';
        $lines[] = "Level Member:\n{$levelName}";
        $lines[] = '';
        $lines[] = "Progress Loyalty:\n{$progress}/{$target}";
        $lines[] = "Tinggal {$remaining} kali lagi untuk mendapatkan:\n🎁 Gratis 1x Cuci";

        if ($voucherCode !== '') {
            $lines[] = '';
            $lines[] = "🎁 Selamat! Anda mendapatkan voucher gratis:\n{$voucherCode}";
        }

        $text = implode("\n", $lines);
        $this->whatsApp->sendMessage($phone, $text, 'wash_membership', null);
    }

    public function sendLevelUpWhatsApp(WashMember $member, WashMemberLevel $newLevel): void
    {
        $phone = $member->whatsapp;
        if (trim((string) $phone) === '') {
            return;
        }

        $benefits = collect($newLevel->benefits ?? [])->map(fn ($b) => '✓ '.$b)->implode("\n");
        $benefits = $benefits !== '' ? $benefits : "✓ Diskon {$newLevel->discount_percent}%";
        $text = "🎉 Selamat!\n\n"
            ."Level Membership Anda naik menjadi:\n".strtoupper($newLevel->code)." MEMBER\n\n"
            ."Benefit:\n{$benefits}\n\n"
            ."Benefit level baru aktif mulai transaksi berikutnya.";

        $this->whatsApp->sendMessage($phone, $text, 'wash_membership', null);
    }

    public function memberVerificationUrl(WashMemberCard $card): string
    {
        return url('/wash/member/verify/'.$card->verification_token);
    }

    public function ensureMemberCard(WashMember $member): WashMemberCard
    {
        $card = WashMemberCard::query()->where('wash_member_id', $member->id)->first();
        if ($card) {
            return $card;
        }

        return DB::transaction(function () use ($member) {
            $exists = WashMemberCard::query()->where('wash_member_id', $member->id)->lockForUpdate()->first();
            if ($exists) {
                return $exists;
            }

            $token = Str::random(48);
            $card = WashMemberCard::create([
                'wash_member_id' => $member->id,
                'card_number' => $member->member_number,
                'verification_token' => $token,
                'issued_at' => now(),
                'expires_at' => null,
                'status' => 'active',
                'meta' => [
                    'verification_url' => url('/wash/member/verify/'.$token),
                ],
            ]);

            $this->auditLog->logAction('wash_membership.card_generated', $card, [
                'member_id' => $member->id,
                'member_number' => $member->member_number,
            ]);

            return $card;
        });
    }

    private function ensureVehicle(WashMember $member, string $plate, ?string $vehicleBrand = null): void
    {
        $plate = $this->normalizePlate($plate);
        if ($plate === '') {
            return;
        }

        $vehicle = WashMemberVehicle::query()->where('vehicle_plate', $plate)->first();
        if ($vehicle) {
            if ((int) $vehicle->wash_member_id !== (int) $member->id) {
                return;
            }
            if ($vehicleBrand && ! $vehicle->brand) {
                $vehicle->brand = $vehicleBrand;
                $vehicle->save();
            }
            return;
        }

        WashMemberVehicle::create([
            'wash_member_id' => $member->id,
            'vehicle_plate' => $plate,
            'vehicle_type' => null,
            'brand' => $vehicleBrand ? trim((string) $vehicleBrand) : null,
            'model' => null,
            'color' => null,
            'year' => null,
            'is_active' => true,
        ]);
    }

    private function defaultLevel(): ?WashMemberLevel
    {
        $bronze = WashMemberLevel::query()->where('code', 'bronze')->first();
        if ($bronze) {
            return $bronze;
        }
        return WashMemberLevel::query()->orderBy('min_transactions')->first();
    }

    private function formatMemberNumber(int $id): string
    {
        return 'GTW-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
