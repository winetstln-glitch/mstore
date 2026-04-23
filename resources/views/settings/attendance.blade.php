@extends('layouts.app')

@section('title', __('Pengaturan Absensi'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3">
                <h5 class="mb-0 fw-bold">{{ __('Pengaturan Absensi') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('settings.update') }}" method="POST">
                    @csrf

                    @forelse($settings as $group => $groupSettings)
                        <div class="mb-4 pb-3 border-bottom last:border-0">
                            <h6 class="fw-bold text-primary text-uppercase mb-3">
                                <i class="fa-solid fa-layer-group me-1"></i> {{ __(str_replace('_', ' ', (string) $group)) }} {{ __('Pengaturan') }}
                            </h6>
                            <div class="row g-3">
                                @foreach($groupSettings as $setting)
                                    <div class="{{ $setting->type === 'schedule_weekly' ? 'col-12' : 'col-md-6' }}">
                                        <label for="{{ $setting->key }}" class="form-label fw-medium">
                                            {{ $setting->label ?? ucwords(str_replace('_', ' ', (string) $setting->key)) }}
                                            @if($setting->type === 'schedule_weekly')
                                                <span class="text-muted small">({{ __('Jadwal Kerja Mingguan') }})</span>
                                            @endif
                                        </label>

                                        @if($setting->type === 'time')
                                            <input type="time" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" class="form-control">
                                        @elseif($setting->type === 'number')
                                            <input type="number" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" class="form-control">
                                        @elseif($setting->type === 'boolean')
                                            <select name="{{ $setting->key }}" id="{{ $setting->key }}" class="form-select">
                                                <option value="1" {{ (string) $setting->value === '1' ? 'selected' : '' }}>{{ __('Ya') }}</option>
                                                <option value="0" {{ (string) $setting->value === '0' ? 'selected' : '' }}>{{ __('Tidak') }}</option>
                                            </select>
                                        @elseif($setting->type === 'schedule_weekly')
                                            @php
                                                $schedule = json_decode((string) $setting->value, true) ?? [];
                                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                            @endphp
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('Hari') }}</th>
                                                            <th class="text-center" style="width: 100px">{{ __('Hari Kerja') }}</th>
                                                            <th>{{ __('Jam Mulai') }}</th>
                                                            <th>{{ __('Jam Selesai') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($days as $day)
                                                            @php
                                                                $daySettings = $schedule[$day] ?? ['enabled' => false, 'start' => '08:00', 'end' => '17:00'];
                                                            @endphp
                                                            <tr>
                                                                <td class="fw-medium">{{ $day }}</td>
                                                                <td class="text-center">
                                                                    <input type="hidden" name="{{ $setting->key }}[{{ $day }}][enabled]" value="0">
                                                                    <input class="form-check-input" type="checkbox" name="{{ $setting->key }}[{{ $day }}][enabled]" value="1" {{ !empty($daySettings['enabled']) ? 'checked' : '' }}>
                                                                </td>
                                                                <td>
                                                                    <input type="time" class="form-control form-control-sm" name="{{ $setting->key }}[{{ $day }}][start]" value="{{ $daySettings['start'] ?? '08:00' }}">
                                                                </td>
                                                                <td>
                                                                    <input type="time" class="form-control form-control-sm" name="{{ $setting->key }}[{{ $day }}][end]" value="{{ $daySettings['end'] ?? '17:00' }}">
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <input type="text" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" class="form-control">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-info mb-0">
                            {{ __('Belum ada data pengaturan absensi.') }}
                        </div>
                    @endforelse

                    <div class="d-flex justify-content-end pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Simpan Pengaturan') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
