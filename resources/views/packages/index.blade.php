@extends('layouts.app')

@section('title', __('Packages'))

@section('content')
@php
    $labels = [
        'page_title' => 'Daftar Paket Internet',
        'add_button' => 'Tambah Paket',
        'tab_pppoe' => 'PPPoE / Rumahan',
        'tab_hotspot' => 'Hotspot / Member',
        'col_name' => 'Nama Paket',
        'col_type' => 'Tipe Paket',
        'col_price' => 'Harga',
        'col_speed' => 'Kecepatan',
        'col_devices' => 'Jumlah Perangkat',
        'col_promo' => 'Promo',
        'col_status' => 'Status',
        'col_actions' => 'Aksi',
        'status_active' => 'Aktif',
        'status_inactive' => 'Nonaktif',
        'empty_pppoe' => 'Belum ada paket PPPoE / Rumahan.',
        'empty_hotspot' => 'Belum ada paket Hotspot / Member.',
        'confirm_delete' => 'Yakin ingin menghapus paket ini?',
        'edit_aria' => 'Ubah paket',
        'delete_aria' => 'Hapus paket',
    ];

    $inferPackageType = function ($package) {
        $explicitType = \Illuminate\Support\Str::lower((string) ($package->package_type ?? ''));
        if (in_array($explicitType, ['pppoe', 'hotspot'], true)) {
            return $explicitType;
        }

        $haystack = \Illuminate\Support\Str::lower(trim(
            $package->name.' '.$package->speed.' '.($package->description ?? '')
        ));

        return \Illuminate\Support\Str::contains($haystack, ['hotspot', 'member', 'voucher'])
            ? 'hotspot'
            : 'pppoe';
    };

    $hotspotPackages = $packages->filter(fn ($package) => $inferPackageType($package) === 'hotspot')->values();
    $pppoePackages = $packages->filter(fn ($package) => $inferPackageType($package) === 'pppoe')->values();

    $renderPackageRows = function ($rows, $emptyText) use ($labels, $inferPackageType) {
        if ($rows->isEmpty()) {
            return new \Illuminate\Support\HtmlString('<tr><td colspan="8" class="text-center text-muted py-4">'.$emptyText.'</td></tr>');
        }

        $html = '';
        foreach ($rows as $package) {
            $packageTypeText = $inferPackageType($package) === 'hotspot' ? 'Hotspot / Member' : 'PPPoE / Rumahan';
            $devicesText = is_null($package->devices_limit) ? 'Tanpa Batas' : ((int) $package->devices_limit.' Perangkat');
            $statusBadge = $package->is_active
                ? '<span class="badge bg-success-subtle text-success">'.$labels['status_active'].'</span>'
                : '<span class="badge bg-secondary-subtle text-secondary">'.$labels['status_inactive'].'</span>';
            $promoBadge = ($package->is_promo_enabled ?? true)
                ? '<span class="badge bg-primary-subtle text-primary">ON</span>'
                : '<span class="badge bg-secondary-subtle text-secondary">OFF</span>';

            $html .= '
                <tr>
                    <td>'.e($package->name).'</td>
                    <td>'.e($packageTypeText).'</td>
                    <td>'.e(number_format($package->price, 0, ',', '.')).'</td>
                    <td>'.e($package->speed).'</td>
                    <td>'.e($devicesText).'</td>
                    <td>'.$promoBadge.'</td>
                    <td>'.$statusBadge.'</td>
                    <td class="text-end">
                        <a href="'.e(route('packages.edit', $package)).'" class="btn btn-sm btn-warning text-white" aria-label="'.$labels['edit_aria'].'">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <form action="'.e(route('packages.destroy', $package)).'" method="POST" class="d-inline" onsubmit="return confirm(\''.$labels['confirm_delete'].'\')">
                            '.csrf_field().'
                            '.method_field('DELETE').'
                            <button type="submit" class="btn btn-sm btn-danger" aria-label="'.$labels['delete_aria'].'">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            ';
        }

        return new \Illuminate\Support\HtmlString($html);
    };
@endphp
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ $labels['page_title'] }}</h5>
                <a href="{{ route('packages.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-1"></i> {{ $labels['add_button'] }}
                </a>
            </div>
            <div class="card-body">
                <ul class="nav nav-pills mb-3 gap-2" id="packageTypeTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pppoe-tab" data-bs-toggle="tab" data-bs-target="#pppoe-pane" type="button" role="tab" aria-controls="pppoe-pane" aria-selected="true">
                            <i class="fa-solid fa-house-signal me-1"></i> {{ $labels['tab_pppoe'] }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="hotspot-tab" data-bs-toggle="tab" data-bs-target="#hotspot-pane" type="button" role="tab" aria-controls="hotspot-pane" aria-selected="false">
                            <i class="fa-solid fa-wifi me-1"></i> {{ $labels['tab_hotspot'] }}
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="packageTypeTabContent">
                    <div class="tab-pane fade show active" id="pppoe-pane" role="tabpanel" aria-labelledby="pppoe-tab" tabindex="0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-responsive-mobile">
                                <thead>
                                    <tr>
                                        <th>{{ $labels['col_name'] }}</th>
                                        <th>{{ $labels['col_type'] }}</th>
                                        <th>{{ $labels['col_price'] }}</th>
                                        <th>{{ $labels['col_speed'] }}</th>
                                        <th>{{ $labels['col_devices'] }}</th>
                                        <th>{{ $labels['col_promo'] }}</th>
                                        <th>{{ $labels['col_status'] }}</th>
                                        <th class="text-end">{{ $labels['col_actions'] }}</th>
                                    </tr>
                                </thead>
                                <tbody>{!! $renderPackageRows($pppoePackages, $labels['empty_pppoe']) !!}</tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="hotspot-pane" role="tabpanel" aria-labelledby="hotspot-tab" tabindex="0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-responsive-mobile">
                                <thead>
                                    <tr>
                                        <th>{{ $labels['col_name'] }}</th>
                                        <th>{{ $labels['col_type'] }}</th>
                                        <th>{{ $labels['col_price'] }}</th>
                                        <th>{{ $labels['col_speed'] }}</th>
                                        <th>{{ $labels['col_devices'] }}</th>
                                        <th>{{ $labels['col_promo'] }}</th>
                                        <th>{{ $labels['col_status'] }}</th>
                                        <th class="text-end">{{ $labels['col_actions'] }}</th>
                                    </tr>
                                </thead>
                                <tbody>{!! $renderPackageRows($hotspotPackages, $labels['empty_hotspot']) !!}</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
