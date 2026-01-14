<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GenieACSService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GenieACSServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock Log to avoid cluttering output and verify logging
        Log::shouldReceive('info')->byDefault();
        Log::shouldReceive('error')->byDefault();
        Log::shouldReceive('warning')->byDefault();
    }

    public function testUpdateWlanSettingsTr098DualBand()
    {
        Http::fake([
            // Mock getDeviceDetails
            '*/devices/device-123' => Http::response([
                'InternetGatewayDevice' => [
                    'LANDevice' => [
                        1 => [
                            'WLANConfiguration' => [
                                1 => [
                                    'SSID' => ['_value' => 'OldSSID2G'],
                                    'PreSharedKey' => [1 => ['PreSharedKey' => ['_value' => 'OldPass2G']]]
                                ],
                                2 => [
                                    'SSID' => ['_value' => 'OldSSID5G'],
                                    'PreSharedKey' => [1 => ['PreSharedKey' => ['_value' => 'OldPass5G']]]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
            
            // Mock setParameterValues
            '*/devices/device-123/tasks?timeout=3000&connection_request' => Http::response(['name' => 'setParameterValues'], 200),
        ]);

        $service = new GenieACSService();
        $result = $service->updateWlanSettings('device-123', [
            'ssid_2g' => 'NewSSID2G',
            'password_2g' => 'NewPass2G',
            'ssid_5g' => 'NewSSID5G',
            'password_5g' => 'NewPass5G',
        ]);

        $this->assertTrue($result);

        // Verify the request sent to GenieACS
        Http::assertSent(function ($request) {
            // Check if it's the tasks endpoint
            if (!str_contains($request->url(), '/tasks')) {
                return false;
            }

            $data = $request->data();
            if ($data['name'] !== 'setParameterValues') return false;
            
            $params = collect($data['parameterValues'])->pluck('value', 'name')->toArray();
            
            // dump($request->url());
            // dump($params);

            return $params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID'] === 'NewSSID2G' &&
                    $params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.PreSharedKey'] === 'NewPass2G' &&
                    $params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.SSID'] === 'NewSSID5G' &&
                    $params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.PreSharedKey.1.PreSharedKey'] === 'NewPass5G';
        });
    }

    public function testUpdateWlanSettingsTr098KeyPassphrase()
    {
        Http::fake([
            '*/devices/device-kp' => Http::response([
                'InternetGatewayDevice' => [
                    'LANDevice' => [
                        1 => [
                            'WLANConfiguration' => [
                                1 => [
                                    'SSID' => ['_value' => 'OldSSID'],
                                    'KeyPassphrase' => ['_value' => 'OldPass']
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
            '*/devices/device-kp/tasks*' => Http::response([], 200),
        ]);

        $service = new GenieACSService();
        $result = $service->updateWlanSettings('device-kp', [
            'ssid_2g' => 'NewSSID',
            'password_2g' => 'NewPass',
        ]);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/tasks')) {
                $params = collect($request->data()['parameterValues'])->pluck('value', 'name')->toArray();
                return isset($params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase']) &&
                       $params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase'] === 'NewPass';
            }
            return false;
        });
    }

    public function testUpdateWlanSettingsTr181()
    {
        Http::fake([
            '*/devices/device-181' => Http::response([
                'Device' => [
                    'WiFi' => [
                        'SSID' => [
                            1 => ['SSID' => ['_value' => 'OldSSID2G']],
                            2 => ['SSID' => ['_value' => 'OldSSID5G']]
                        ],
                        'AccessPoint' => [
                            1 => ['Security' => ['KeyPassphrase' => ['_value' => 'OldPass2G']]],
                            2 => ['Security' => ['KeyPassphrase' => ['_value' => 'OldPass5G']]]
                        ]
                    ]
                ]
            ], 200),
            '*/tasks*' => Http::response([], 200),
        ]);

        $service = new GenieACSService();
        $result = $service->updateWlanSettings('device-181', [
            'ssid_2g' => 'NewSSID2G',
            'password_2g' => 'NewPass2G',
            'ssid_5g' => 'NewSSID5G',
            'password_5g' => 'NewPass5G',
        ]);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/tasks')) {
                $params = collect($request->data()['parameterValues'])->pluck('value', 'name')->toArray();
                return $params['Device.WiFi.SSID.1.SSID'] === 'NewSSID2G' &&
                       $params['Device.WiFi.AccessPoint.1.Security.KeyPassphrase'] === 'NewPass2G' &&
                       $params['Device.WiFi.SSID.2.SSID'] === 'NewSSID5G' &&
                       $params['Device.WiFi.AccessPoint.2.Security.KeyPassphrase'] === 'NewPass5G';
            }
            return false;
        });
    }

    public function testUpdateWlanSettingsMissing5G()
    {
        Http::fake([
            '*/devices/device-no-5g' => Http::response([
                'InternetGatewayDevice' => [
                    'LANDevice' => [
                        1 => [
                            'WLANConfiguration' => [
                                1 => ['SSID' => ['_value' => 'OldSSID']]
                            ]
                        ]
                    ]
                ]
            ], 200),
            '*/tasks*' => Http::response([], 200),
        ]);

        $service = new GenieACSService();
        $result = $service->updateWlanSettings('device-no-5g', [
            'ssid_2g' => 'NewSSID',
            'ssid_5g' => 'NewSSID5G', // Should be ignored
        ]);

        $this->assertTrue($result); // Should succeed for 2.4G

        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/tasks')) {
                $params = collect($request->data()['parameterValues'])->pluck('value', 'name')->toArray();
                // 5G should NOT be in params
                return isset($params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID']) &&
                       !isset($params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.SSID']);
            }
            return false;
        });
    }
}
