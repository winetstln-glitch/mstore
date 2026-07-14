<?php

namespace App\Services\Attendance;

use App\Models\TechnicianAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LocationVerificationService
{
    public const SUSPICIOUS_FLAG_NONE = 'none';
    public const SUSPICIOUS_FLAG_MOCK = 'mock_location';
    public const SUSPICIOUS_FLAG_JUMP = 'location_jump';
    public const SUSPICIOUS_FLAG_SPEED = 'impossible_speed';
    public const SUSPICIOUS_FLAG_STATIC = 'static_location';

    public function verifyLocation(User $user, float $latitude, float $longitude, ?string $deviceFingerprint = null): array
    {
        $flags = [];
        $warnings = [];

        $cacheKey = "user_location_history:{$user->id}";
        $locationHistory = Cache::get($cacheKey, []);

        if (count($locationHistory) > 0) {
            $lastLocation = end($locationHistory);
            $distance = $this->calculateDistance(
                $lastLocation['lat'],
                $lastLocation['lng'],
                $latitude,
                $longitude
            );
            $timeDiff = Carbon::now()->diffInSeconds(Carbon::parse($lastLocation['timestamp']));

            if ($timeDiff > 0) {
                $speed = ($distance / $timeDiff) * 3.6;

                if ($speed > 200) {
                    $flags[] = self::SUSPICIOUS_FLAG_SPEED;
                    $warnings[] = "Kecepatan pergerakan tidak normal: " . round($speed) . " km/jam";
                }

                if ($distance > 5000 && $timeDiff < 300) {
                    $flags[] = self::SUSPICIOUS_FLAG_JUMP;
                    $warnings[] = "Perpindahan lokasi terlalu jauh dalam waktu singkat";
                }
            }
        }

        $locationHistory[] = [
            'lat' => $latitude,
            'lng' => $longitude,
            'timestamp' => now()->toIso8601String(),
        ];

        if (count($locationHistory) > 50) {
            array_shift($locationHistory);
        }

        Cache::put($cacheKey, $locationHistory, now()->addHours(24));

        if (count($flags) > 0) {
            Log::warning('Suspicious location detected', [
                'user_id' => $user->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'flags' => $flags,
                'warnings' => $warnings,
                'device_fingerprint' => $deviceFingerprint,
            ]);
        }

        return [
            'is_suspicious' => count($flags) > 0,
            'flags' => $flags,
            'warnings' => $warnings,
        ];
    }

    public function flagAttendance(TechnicianAttendance $attendance, array $flags, array $warnings): void
    {
        $attendance->update([
            'notes' => trim(($attendance->notes ?? '') . "\n⚠️ SUSPICIOUS: " . implode(', ', $warnings)),
        ]);
    }

    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
