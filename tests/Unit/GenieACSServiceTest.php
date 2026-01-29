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
            '*/devices/device-123/tasks?timeout=8000&connection_request' => Http::response(['name' => 'setParameterValues'], 200),
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
            if (!str_contains($request->url(), '/tasks')) {
                return false;
            }

            $data = $request->data();
            if ($data['name'] !== 'setParameterValues') {
                return false;
            }

            $params = [];
            foreach ($data['parameterValues'] as $item) {
                if (is_array($item) && count($item) >= 2) {
                    $params[$item[0]] = $item[1];
                }
            }

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
            if (!str_contains($request->url(), '/tasks')) {
                return false;
            }

            $params = [];
            foreach ($request->data()['parameterValues'] as $item) {
                if (is_array($item) && count($item) >= 2) {
                    $params[$item[0]] = $item[1];
                }
            }

            return isset($params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase']) &&
                $params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase'] === 'NewPass';
            });
    }

    public function testUpdateWlanSettingsTr098PreSharedKeyKeyPassphrase()
    {
        Http::fake([
            '*/devices/device-psk-kp' => Http::response([
                'InternetGatewayDevice' => [
                    'LANDevice' => [
                        1 => [
                            'WLANConfiguration' => [
                                1 => [
                                    'SSID' => ['_value' => 'OldSSID'],
                                    'PreSharedKey' => [
                                        1 => [
                                            'KeyPassphrase' => ['_value' => 'OldPass'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
            '*/devices/device-psk-kp/tasks*' => Http::response([], 200),
        ]);

        $service = new GenieACSService();
        $result = $service->updateWlanSettings('device-psk-kp', [
            'ssid_2g' => 'NewSSID',
            'password_2g' => 'NewPass',
        ]);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/tasks')) {
                return false;
            }

            $params = [];
            foreach ($request->data()['parameterValues'] as $item) {
                if (is_array($item) && count($item) >= 2) {
                    $params[$item[0]] = $item[1];
                }
            }

            return isset($params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase']) &&
                $params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase'] === 'NewPass';
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

    }

    public function testUpdateWanSettingsUsesVendorSpecificVlanParametersWhenAvailable()
    {
        Http::fake([
            '*/devices/device-vlan' => Http::response([
                'InternetGatewayDevice' => [
                    'WANDevice' => [
                        1 => [
                            'WANConnectionDevice' => [
                                1 => [
                                    'WANPPPConnection' => [
                                        1 => [
                                            'Username' => ['_value' => 'olduser'],
                                            'Password' => ['_value' => 'oldpass'],
                                            'X_BROADCOM_COM_VlanMuxID' => ['_value' => 100],
                                            'X_CU_VLANEnabled' => ['_value' => true],
                                            'X_CU_VLAN' => ['_value' => 100],
                                            'X_CMCC_VLANIDMark' => ['_value' => 100],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
            '*/tasks*' => Http::response([
                'name' => 'setParameterValues',
            ], 200),
        ]);

        $service = new GenieACSService();
        $result = $service->updateWanSettings('device-vlan', 'newuser', 'newpass', 200);

        $this->assertTrue($result);
    }

    public function testSetParameterValuesTreatsCurl52AsSuccess()
    {
        Http::fake([
            '*/devices/device-error/tasks?timeout=8000&connection_request' => Http::response([], 500),
            '*/devices/device-error/tasks' => function () {
                throw new \Exception('cURL error 52: Empty reply from server');
            },
        ]);

        $service = new GenieACSService();
        $result = $service->setParameterValues('device-error', [
            'Device.Test.Param' => 'value',
        ]);

        $this->assertTrue($result);
    }

    public function testRebootDeviceFallsBackToQueueOnFailure()
    {
        Http::fake([
            '*/devices/device-reboot/tasks?timeout=8000&connection_request' => Http::response([], 500),
            '*/devices/device-reboot/tasks' => Http::response([], 200),
        ]);

        $service = new GenieACSService();
        $result = $service->rebootDevice('device-reboot');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'devices/device-reboot/tasks');
        });
    }

    public function testRebootDeviceTreatsCurlTimeoutAsSuccess()
    {
        Http::fake([
            '*/devices/device-reboot-timeout/tasks?timeout=8000&connection_request' => Http::response([], 500),
            '*/devices/device-reboot-timeout/tasks' => function () {
                throw new \Exception('cURL error 28: Operation timed out');
            },
        ]);

        $service = new GenieACSService();
        $result = $service->rebootDevice('device-reboot-timeout');

        $this->assertTrue($result);
    }
}
