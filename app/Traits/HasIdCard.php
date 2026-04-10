<?php

namespace App\Traits;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

trait HasIdCard
{
    protected function resolveUserBrand($user): array
    {
        $scope = strtolower(trim((string) ($user->role?->label ?: $user->role?->name ?: '')));
        $defaultLogo = (string) (Setting::getValue('store_logo') ?: '');
        $defaultSlogan = 'Solusi Digital Cepat dan Terpercaya';

        if (str_contains($scope, 'wash')) {
            $name = (string) (Setting::getValue('brand_gtwash_name') ?: 'GTWASH');
            $logo = (string) (Setting::getValue('brand_gtwash_logo') ?: $defaultLogo);
            $slogan = (string) (Setting::getValue('brand_gtwash_slogan') ?: $defaultSlogan);

            return [strtoupper($name), $this->brandLogoUrl($logo), $slogan, 'gtwash'];
        }

        if (str_contains($scope, 'net') || str_contains($scope, 'network') || str_contains($scope, 'internet')) {
            $name = (string) (Setting::getValue('brand_mstorenet_name') ?: 'MSTORE.NET');
            $logo = (string) (Setting::getValue('brand_mstorenet_logo') ?: $defaultLogo);
            $slogan = (string) (Setting::getValue('brand_mstorenet_slogan') ?: $defaultSlogan);

            return [strtoupper($name), $this->brandLogoUrl($logo), $slogan, 'mstorenet'];
        }

        $name = (string) (Setting::getValue('brand_mstore_name') ?: Setting::getValue('store_name') ?: 'MSTORE');
        $logo = (string) (Setting::getValue('brand_mstore_logo') ?: $defaultLogo);
        $slogan = (string) (Setting::getValue('brand_mstore_slogan') ?: $defaultSlogan);

        return [strtoupper($name), $this->brandLogoUrl($logo), $slogan, 'mstore'];
    }

    protected function brandLogoUrl(string $logo): string
    {
        if (! $logo) {
            return asset('img/logo.png');
        }

        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            try {
                $hash = md5($logo);
                $ext = pathinfo(parse_url($logo, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'png';
                $ext = strtolower($ext);
                if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'], true)) {
                    $ext = 'png';
                }
                $path = "brand-logos/{$hash}.{$ext}";
                if (! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                    $response = \Illuminate\Support\Facades\Http::timeout(10)->withHeaders([
                        'User-Agent' => 'MStore-IDCard-LogoFetcher/1.0',
                    ])->get($logo);
                    if ($response->successful()) {
                        \Illuminate\Support\Facades\Storage::disk('public')->put($path, $response->body());
                    } else {
                        return $logo; // Fallback to URL
                    }
                }

                return asset('storage/'.$path);
            } catch (\Throwable $e) {
                return $logo; // Fallback to URL
            }
        }

        return asset($logo);
    }

    protected function userIdCardCode($user): string
    {
        if (Schema::hasColumn('users', 'attendance_card_code') && ! empty($user->attendance_card_code)) {
            return (string) $user->attendance_card_code;
        }

        return str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);
    }
}
