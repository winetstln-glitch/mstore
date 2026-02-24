<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Htb;
use App\Models\Odp;
use Illuminate\Console\Command;
use OpenSpout\Reader\CSV\Reader as CSVReader;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;

class MixradiusImportUsers extends Command
{
    protected $signature = 'mixradius:import-users 
        {--file= : Path file CSV/XLSX export dari MixRADIUS}
        {--update-existing : Update jika PPPoE User/Name sudah ada}
        {--dry-run : Simulasi tanpa menyimpan perubahan}';

    protected $description = 'Import massal user dari MixRADIUS (CSV/XLSX) ke Customers';

    public function handle(): int
    {
        $file = (string) $this->option('file');
        $update = (bool) $this->option('update-existing');
        $dry = (bool) $this->option('dry-run');

        if (! $file || ! is_file($file)) {
            $this->error('File tidak ditemukan. Gunakan --file=path/to/file.csv');

            return self::INVALID;
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $reader = $ext === 'csv' ? new CSVReader : new XLSXReader;

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        try {
            $reader->open($file);
            $header = null;
            $rowNumber = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;
                    try {
                        $values = [];
                        foreach ($row->getCells() as $cell) {
                            $values[] = $cell->getValue();
                        }
                        if ($header === null) {
                            $header = array_map(function ($v) {
                                return strtolower(trim((string) $v));
                            }, $values);
                            if (! in_array('login', $header, true) && ! in_array('pppoe_user', $header, true) && ! in_array('name', $header, true)) {
                                $this->error('Header minimal harus memuat kolom Login atau PPPoE_User atau Name');

                                return self::INVALID;
                            }

                            continue;
                        }

                        if (count($values) > count($header)) {
                            $values = array_slice($values, 0, count($header));
                        } elseif (count($values) < count($header)) {
                            $values = array_pad($values, count($header), null);
                        }
                        $rowMap = array_combine($header, $values);
                        if (! $rowMap) {
                            $skipped++;

                            continue;
                        }

                        $get = function ($key) use ($rowMap) {
                            $val = $rowMap[$key] ?? null;
                            if (is_string($val)) {
                                $val = trim($val);
                            }

                            return $val === '' ? null : $val;
                        };

                        $pppoeUser = $get('login') ?? $get('pppoe_user');
                        $name = $get('fullname') ?? $get('full_name') ?? $get('name') ?? $pppoeUser;
                        if (! $name && ! $pppoeUser) {
                            $skipped++;

                            continue;
                        }

                        $odpName = $get('odp');
                        $odpId = null;
                        if ($odpName) {
                            $o = Odp::where('name', $odpName)->first();
                            if ($o) {
                                $odpId = $o->id;
                            }
                        }

                        $htbId = null;
                        $htbName = $get('htb');
                        if ($htbName) {
                            $h = Htb::with('odp')->where('name', $htbName)->first();
                            if ($h) {
                                $htbId = $h->id;
                                $odpId = $h->odp_id;
                                $odpName = $h->odp->name ?? $odpName;
                            }
                        }

                        $data = [
                            'name' => $name,
                            'address' => $get('address'),
                            'phone' => $get('phone'),
                            'package' => $get('plan') ?? $get('package'),
                            'ip_address' => $get('ipaddress') ?? $get('ip_address'),
                            'vlan' => $get('vlan'),
                            'odp' => $odpName,
                            'odp_id' => $odpId,
                            'htb_id' => $htbId,
                            'status' => strtolower($get('status') ?? 'active'),
                            'pppoe_user' => $pppoeUser,
                            'pppoe_password' => $get('password') ?? $get('pppoe_password'),
                            'onu_serial' => $get('onu_serial'),
                            'device_model' => $get('device_model'),
                            'ssid_name' => $get('ssid_name'),
                            'ssid_password' => $get('ssid_password'),
                            'latitude' => $get('latitude'),
                            'longitude' => $get('longitude'),
                            'genieacs_device_id' => $get('genieacs_device_id'),
                        ];

                        if (! in_array($data['status'], ['active', 'suspend', 'terminated'])) {
                            $data['status'] = 'active';
                        }

                        $existing = null;
                        if (! empty($data['pppoe_user'])) {
                            $existing = Customer::where('pppoe_user', $data['pppoe_user'])->first();
                        }
                        if (! $existing && ! empty($name)) {
                            $existing = Customer::where('name', $name)->first();
                        }

                        if ($existing) {
                            if ($update) {
                                if ($dry) {
                                    $this->line("DRY: update #{$existing->id} ({$existing->name})");
                                } else {
                                    $existing->update($data);
                                }
                                $updated++;
                            } else {
                                $skipped++;
                            }
                        } else {
                            if ($dry) {
                                $this->line("DRY: create {$data['name']} ({$data['pppoe_user']})");
                            } else {
                                Customer::create($data);
                            }
                            $created++;
                        }
                    } catch (\Throwable $e) {
                        $failed++;
                        $this->warn("Row {$rowNumber}: {$e->getMessage()}");
                    }
                }
            }
            $reader->close();

            $this->info("Selesai. created={$created}, updated={$updated}, skipped={$skipped}, failed={$failed}, dry={$dry}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal import: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
