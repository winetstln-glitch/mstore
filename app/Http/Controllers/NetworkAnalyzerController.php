<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class NetworkAnalyzerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:router.view', only: ['index', 'ping', 'networkInfo', 'speedDownload', 'speedUpload']),
        ];
    }

    public function index()
    {
        return view('network.analyzer');
    }

    public function ping(Request $request)
    {
        $validated = $request->validate([
            'target' => ['nullable', 'regex:/^[a-zA-Z0-9\.\-]+$/'],
            'count' => ['nullable', 'integer', 'min:1', 'max:6'],
        ]);

        $target = $validated['target'] ?? '1.1.1.1';
        $count = (int) ($validated['count'] ?? 4);
        $result = $this->runPing($target, $count);

        return response()->json($result);
    }

    public function networkInfo(Request $request)
    {
        $validated = $request->validate([
            'local_ip' => ['nullable', 'ip'],
            'public_ip' => ['nullable', 'ip'],
            'ssid' => ['nullable', 'string', 'max:120'],
        ]);

        $clientIp = $request->ip();
        $localIp = $validated['local_ip'] ?? null;
        $ssid = isset($validated['ssid']) ? trim((string) $validated['ssid']) : null;
        if ($ssid === '') {
            $ssid = null;
        }
        $publicIp = $validated['public_ip'] ?? $this->resolvePublicIpFromRequest($request, $clientIp);

        $macAddress = $this->detectMacAddress($localIp ?: $clientIp);
        $customerIdentity = $this->resolveCustomerIdentity($ssid, $publicIp, $localIp);
        if (! $macAddress && ! empty($customerIdentity['wan_mac'])) {
            $macAddress = $this->normalizeMacAddress((string) $customerIdentity['wan_mac']);
        }
        $vendorName = $macAddress ? $this->lookupMacVendor($macAddress) : null;
        if (! $vendorName && ! empty($customerIdentity['device_model'])) {
            $vendorName = (string) $customerIdentity['device_model'];
        }
        $ispInfo = $this->lookupIspInfo($publicIp);
        $routerIdentity = $this->resolveRouterIdentity($ssid, $publicIp, $localIp);

        return response()->json([
            'network_type' => $request->header('X-Network-Type'),
            'client_ip' => $clientIp,
            'public_ip' => $publicIp,
            'local_ip' => $localIp,
            'network_ssid' => $ssid,
            'device_mac' => $macAddress,
            'vendor' => $vendorName ?: ($ispInfo['org'] ?? null),
            'isp' => $ispInfo['isp'] ?? null,
            'asn' => $ispInfo['asn'] ?? null,
            'router_name' => $routerIdentity['name'] ?? null,
            'router_host' => $routerIdentity['host'] ?? null,
            'router_match' => $routerIdentity['match'] ?? null,
            'mac_detected' => (bool) $macAddress,
        ]);
    }

    public function speedDownload(Request $request)
    {
        $validated = $request->validate([
            'bytes' => ['nullable', 'integer', 'min:250000', 'max:5000000'],
        ]);

        $bytes = (int) ($validated['bytes'] ?? 1500000);
        $payload = random_bytes($bytes);

        return response($payload, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => (string) $bytes,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function speedUpload(Request $request)
    {
        $body = $request->getContent() ?? '';
        $size = strlen($body);

        return response()->json([
            'received_bytes' => $size,
        ]);
    }

    private function runPing(string $target, int $count): array
    {
        $timeoutMs = 1300;
        $command = PHP_OS_FAMILY === 'Windows'
            ? ['ping', '-n', (string) $count, '-w', (string) $timeoutMs, $target]
            : ['ping', '-c', (string) $count, '-W', (string) ceil($timeoutMs / 1000), $target];

        $process = new Process($command);
        $process->setTimeout(12);
        $process->run();

        $output = trim($process->getOutput().' '.$process->getErrorOutput());

        $times = [];
        if (preg_match_all('/time[=<]?\s*(\d+(?:\.\d+)?)\s*ms/i', $output, $matches)) {
            $times = array_map(static fn ($value) => (float) $value, $matches[1]);
        }

        $avg = null;
        if (! empty($times)) {
            $avg = array_sum($times) / count($times);
        }

        if ($avg === null && preg_match('/Average = (\d+)\s*ms/i', $output, $avgMatch)) {
            $avg = (float) $avgMatch[1];
        }

        if ($avg === null && preg_match('/=\s*[\d\.]+\/([\d\.]+)\/[\d\.]+\/[\d\.]+\s*ms/i', $output, $avgMatch)) {
            $avg = (float) $avgMatch[1];
        }

        $lossPercent = 100.0;
        if (preg_match('/\((\d+)%\s*loss\)/i', $output, $lossMatch)) {
            $lossPercent = (float) $lossMatch[1];
        } elseif (preg_match('/(\d+(?:\.\d+)?)%\s*packet loss/i', $output, $lossMatch)) {
            $lossPercent = (float) $lossMatch[1];
        } elseif (! empty($times)) {
            $lossPercent = max(0, 100 - (count($times) / max(1, $count)) * 100);
        }

        $jitter = null;
        if (count($times) > 1) {
            $deltas = [];
            for ($i = 1; $i < count($times); $i++) {
                $deltas[] = abs($times[$i] - $times[$i - 1]);
            }
            $jitter = array_sum($deltas) / count($deltas);
        }

        return [
            'target' => $target,
            'success' => $process->isSuccessful() || ! empty($times),
            'latency_ms' => $avg !== null ? round($avg, 2) : null,
            'jitter_ms' => $jitter !== null ? round($jitter, 2) : null,
            'loss_percent' => round($lossPercent, 2),
            'samples' => array_slice(array_map(static fn ($item) => round($item, 2), $times), -10),
        ];
    }

    private function detectMacAddress(?string $ipAddress): ?string
    {
        if (! $ipAddress || ! filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return null;
        }

        try {
            $command = PHP_OS_FAMILY === 'Windows'
                ? ['arp', '-a', $ipAddress]
                : ['arp', '-n', $ipAddress];
            $process = new Process($command);
            $process->setTimeout(5);
            $process->run();
            $output = $process->getOutput().' '.$process->getErrorOutput();
            $normalized = $this->normalizeMacAddress($output);
            if ($normalized) {
                return $normalized;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function normalizeMacAddress(?string $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        if (! preg_match('/([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}/', $value, $match)) {
            return null;
        }

        return strtoupper(Str::replace('-', ':', $match[0]));
    }

    private function lookupMacVendor(string $macAddress): ?string
    {
        try {
            $response = Http::timeout(4)->accept('text/plain')->get('https://api.macvendors.com/'.urlencode($macAddress));
            if ($response->successful()) {
                $vendor = trim((string) $response->body());

                return $vendor !== '' ? $vendor : null;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function resolvePublicIpFromRequest(Request $request, string $fallbackIp): string
    {
        $trustedCandidates = [
            $request->header('CF-Connecting-IP'),
            $request->header('True-Client-IP'),
            $request->header('X-Forwarded-For'),
            $fallbackIp,
        ];

        foreach ($trustedCandidates as $candidate) {
            if (! $candidate) {
                continue;
            }
            $first = trim(explode(',', $candidate)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }

        return $fallbackIp;
    }

    private function lookupIspInfo(string $ipAddress): array
    {
        try {
            $response = Http::timeout(5)->get("https://ipwho.is/{$ipAddress}");
            if (! $response->successful()) {
                return [];
            }
            $payload = $response->json();
            if (! is_array($payload)) {
                return [];
            }

            return [
                'isp' => data_get($payload, 'connection.isp'),
                'org' => data_get($payload, 'connection.org'),
                'asn' => data_get($payload, 'connection.asn'),
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    private function resolveRouterIdentity(?string $ssid, ?string $publicIp, ?string $localIp): ?array
    {
        $activeRouters = Router::query()->where('is_active', true);
        $routerByIp = null;

        if ($publicIp && filter_var($publicIp, FILTER_VALIDATE_IP)) {
            $routerByIp = (clone $activeRouters)->where('host', $publicIp)->first();
        }

        if (! $routerByIp && $localIp && filter_var($localIp, FILTER_VALIDATE_IP)) {
            $routerByIp = (clone $activeRouters)->where('host', $localIp)->first();
        }

        if ($routerByIp) {
            return [
                'name' => $routerByIp->name,
                'host' => $routerByIp->host,
                'match' => 'ip',
            ];
        }

        if (! $ssid) {
            return null;
        }

        $ssidKeyword = Str::limit($ssid, 80, '');
        $routerBySsid = (clone $activeRouters)
            ->where(function ($query) use ($ssidKeyword) {
                $query->where('name', 'like', "%{$ssidKeyword}%")
                    ->orWhere('host', 'like', "%{$ssidKeyword}%")
                    ->orWhere('location', 'like', "%{$ssidKeyword}%")
                    ->orWhere('description', 'like', "%{$ssidKeyword}%");
            })
            ->orderBy('name')
            ->first();

        if (! $routerBySsid) {
            return null;
        }

        return [
            'name' => $routerBySsid->name,
            'host' => $routerBySsid->host,
            'match' => 'ssid',
        ];
    }

    private function resolveCustomerIdentity(?string $ssid, ?string $publicIp, ?string $localIp): ?array
    {
        if (! Schema::hasTable('customers')) {
            return null;
        }

        $columns = array_flip(Schema::getColumnListing('customers'));

        $buildBaseQuery = function () use ($columns) {
            $query = Customer::query();
            if (isset($columns['status'])) {
                $query->where('status', 'active');
            }

            return $query;
        };

        $customer = null;
        if ($ssid && isset($columns['ssid_name'])) {
            $customer = $buildBaseQuery()
                ->where('ssid_name', $ssid)
                ->orderByDesc('id')
                ->first();
        }

        if (! $customer && $publicIp && isset($columns['ip_address'])) {
            $customer = $buildBaseQuery()
                ->where('ip_address', $publicIp)
                ->orderByDesc('id')
                ->first();
        }

        if (! $customer && $localIp && isset($columns['ip_address'])) {
            $customer = $buildBaseQuery()
                ->where('ip_address', $localIp)
                ->orderByDesc('id')
                ->first();
        }

        if (! $customer && $localIp && isset($columns['pppoe_ip_remote'])) {
            $customer = $buildBaseQuery()
                ->where('pppoe_ip_remote', $localIp)
                ->orderByDesc('id')
                ->first();
        }

        if (! $customer && $publicIp && isset($columns['pppoe_ip_remote'])) {
            $customer = $buildBaseQuery()
                ->where('pppoe_ip_remote', $publicIp)
                ->orderByDesc('id')
                ->first();
        }

        if (! $customer) {
            return null;
        }

        return [
            'wan_mac' => isset($columns['wan_mac']) ? (string) ($customer->wan_mac ?? '') : null,
            'device_model' => isset($columns['device_model']) ? (string) ($customer->device_model ?? '') : null,
        ];
    }
}
