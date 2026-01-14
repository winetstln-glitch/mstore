@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3">
                <h5 class="mb-0 fw-bold">{{ __('Settings') }}</h5>
            </div>

            <div class="card-body">
                {{-- Alerts handled by SweetAlert in Layout --}}

                <form action="{{ route('settings.update') }}" method="POST">
                    @csrf
                    
                    @foreach($settings as $group => $groupSettings)
                        <div class="mb-4 pb-3 border-bottom last:border-0">
                            <h6 class="fw-bold text-primary text-uppercase mb-3">
                                <i class="fa-solid fa-layer-group me-1"></i> {{ __(str_replace('_', ' ', $group)) }} {{ __('Settings') }}
                            </h6>
                            
                            <div class="row g-3">
                                @foreach($groupSettings as $setting)
                                    <div class="{{ $setting->type == 'schedule_weekly' ? 'col-12' : 'col-md-6' }}">
                                        <label for="{{ $setting->key }}" class="form-label fw-medium">
                                            {{ $setting->label ?? ucwords(str_replace('_', ' ', $setting->key)) }}
                                        </label>
                                        
                                        @if($setting->type == 'time')
                                            <input type="time" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" class="form-control">
                                        @elseif($setting->type == 'number')
                                            <input type="number" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" class="form-control">
                                        @elseif($setting->type == 'boolean')
                                            <select name="{{ $setting->key }}" id="{{ $setting->key }}" class="form-select">
                                                <option value="1" {{ $setting->value == '1' ? 'selected' : '' }}>{{ __('Yes') }}</option>
                                                <option value="0" {{ $setting->value == '0' ? 'selected' : '' }}>{{ __('No') }}</option>
                                            </select>
                                        @elseif($setting->type == 'schedule_weekly')
                                            <div>
                                                @php
                                                    $schedule = json_decode($setting->value, true) ?? [];
                                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                                @endphp
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm align-middle">
                                                        <thead class="bg-body-tertiary">
                                                            <tr>
                                                                <th>{{ __('Day') }}</th>
                                                                <th class="text-center" style="width: 100px">{{ __('Working Day') }}</th>
                                                                <th>{{ __('Start Time') }}</th>
                                                                <th>{{ __('End Time') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($days as $day)
                                                                @php
                                                                    $daySettings = $schedule[$day] ?? ['enabled' => false, 'start' => '08:00', 'end' => '17:00'];
                                                                @endphp
                                                                <tr>
                                                                    <td class="fw-medium">{{ __($day) }}</td>
                                                                    <td class="text-center">
                                                                        <div class="form-check d-inline-block">
                                                                            <input type="hidden" name="{{ $setting->key }}[{{ $day }}][enabled]" value="0">
                                                                            <input class="form-check-input" type="checkbox" name="{{ $setting->key }}[{{ $day }}][enabled]" value="1" {{ $daySettings['enabled'] ? 'checked' : '' }}>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <input type="time" class="form-control form-control-sm" name="{{ $setting->key }}[{{ $day }}][start]" value="{{ $daySettings['start'] }}">
                                                                    </td>
                                                                    <td>
                                                                        <input type="time" class="form-control form-control-sm" name="{{ $setting->key }}[{{ $day }}][end]" value="{{ $daySettings['end'] }}">
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @elseif($setting->type == 'textarea')
                                            <textarea name="{{ $setting->key }}" id="{{ $setting->key }}" class="form-control" rows="3">{{ $setting->value }}</textarea>
                                        @else
                                            <input type="text" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" class="form-control">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="d-flex justify-content-end pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Save Settings') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

