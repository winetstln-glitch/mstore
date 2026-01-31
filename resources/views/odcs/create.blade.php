@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Add New ODC') }}</h5>
                <a href="{{ route('odcs.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('odcs.store') }}" method="POST">
                    @csrf
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="region_id" class="form-label">{{ __('Region') }}</label>
                            <select class="form-select @error('region_id') is-invalid @enderror" id="region_id" name="region_id">
                                <option value="">{{ __('Select Region') }}</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}" {{ old('region_id') == $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
                                @endforeach
                            </select>
                            @error('region_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Connection Source') }}</label>
                            <div class="mb-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="source_type" id="source_olt" value="olt" {{ old('source_type', 'olt') == 'olt' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="source_olt">Direct OLT</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="source_type" id="source_closure" value="closure" {{ old('source_type') == 'closure' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="source_closure">Via Closure</label>
                                </div>
                            </div>

                            <div id="olt_input_group" style="{{ old('source_type', 'olt') == 'olt' ? '' : 'display:none;' }}">
                                <label for="olt_id" class="form-label">{{ __('Select OLT') }}</label>
                                <select class="form-select @error('olt_id') is-invalid @enderror" id="olt_id" name="olt_id">
                                    <option value="">{{ __('Select OLT') }}</option>
                                    @foreach($olts as $olt)
                                        <option value="{{ $olt->id }}" {{ old('olt_id') == $olt->id ? 'selected' : '' }}>{{ $olt->name }}</option>
                                    @endforeach
                                </select>
                                @error('olt_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div id="closure_input_group" style="{{ old('source_type') == 'closure' ? '' : 'display:none;' }}">
                                <label for="closure_id" class="form-label">{{ __('Select Closure') }}</label>
                                <select class="form-select @error('closure_id') is-invalid @enderror" id="closure_id" name="closure_id">
                                    <option value="">{{ __('Select Closure') }}</option>
                                    @foreach($closures as $closure)
                                        <option value="{{ $closure->id }}" {{ old('closure_id') == $closure->id ? 'selected' : '' }}>{{ $closure->name }}</option>
                                    @endforeach
                                </select>
                                @error('closure_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="pon_port" class="form-label">{{ __('PON Port') }}</label>
                            <input type="text" class="form-control @error('pon_port') is-invalid @enderror" id="pon_port" name="pon_port" value="{{ old('pon_port') }}" required placeholder="e.g. PON01">
                            @error('pon_port')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="area" class="form-label">{{ __('Area') }}</label>
                            <input type="text" class="form-control @error('area') is-invalid @enderror" id="area" name="area" value="{{ old('area') }}" required placeholder="e.g. CIBADAK">
                            @error('area')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="color" class="form-label">{{ __('Tube / Fiber Color') }}</label>
                            <select class="form-select @error('color') is-invalid @enderror" id="color" name="color" required>
                                <option value="">{{ __('Select Color') }}</option>
                                <option value="BLUE" {{ old('color') == 'BLUE' ? 'selected' : '' }} data-code="B">Blue (Biru)</option>
                                <option value="ORANGE" {{ old('color') == 'ORANGE' ? 'selected' : '' }} data-code="O">Orange (Oranye)</option>
                                <option value="GREEN" {{ old('color') == 'GREEN' ? 'selected' : '' }} data-code="G">Green (Hijau)</option>
                                <option value="BROWN" {{ old('color') == 'BROWN' ? 'selected' : '' }} data-code="C">Brown (Coklat)</option>
                                <option value="SLATE" {{ old('color') == 'SLATE' ? 'selected' : '' }} data-code="S">Slate (Abu-abu)</option>
                                <option value="WHITE" {{ old('color') == 'WHITE' ? 'selected' : '' }} data-code="P">White (Putih)</option>
                                <option value="RED" {{ old('color') == 'RED' ? 'selected' : '' }} data-code="M">Red (Merah)</option>
                                <option value="BLACK" {{ old('color') == 'BLACK' ? 'selected' : '' }} data-code="H">Black (Hitam)</option>
                                <option value="YELLOW" {{ old('color') == 'YELLOW' ? 'selected' : '' }} data-code="K">Yellow (Kuning)</option>
                                <option value="VIOLET" {{ old('color') == 'VIOLET' ? 'selected' : '' }} data-code="U">Violet (Ungu)</option>
                                <option value="ROSE" {{ old('color') == 'ROSE' ? 'selected' : '' }} data-code="P">Rose (Pink)</option>
                                <option value="AQUA" {{ old('color') == 'AQUA' ? 'selected' : '' }} data-code="T">Aqua (Tosca)</option>
                            </select>
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="cable_no" class="form-label">{{ __('Cable No') }}</label>
                            <input type="text" class="form-control @error('cable_no') is-invalid @enderror" id="cable_no" name="cable_no" value="{{ old('cable_no') }}" required placeholder="e.g. 01">
                            @error('cable_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="capacity" class="form-label">{{ __('Capacity (Ports)') }}</label>
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity', 144) }}" min="0" required>
                            @error('capacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Coordinates (Latitude, Longitude)') }}</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="number" step="any" class="form-control @error('latitude') is-invalid @enderror" id="latitude" name="latitude" value="{{ old('latitude') }}" placeholder="Latitude" required>
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <input type="number" step="any" class="form-control @error('longitude') is-invalid @enderror" id="longitude" name="longitude" value="{{ old('longitude') }}" placeholder="Longitude" required>
                                @error('longitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('Description') }}</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('odcs.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Save ODC') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sourceOlt = document.getElementById('source_olt');
        const sourceClosure = document.getElementById('source_closure');
        const oltGroup = document.getElementById('olt_input_group');
        const closureGroup = document.getElementById('closure_input_group');
        const oltSelect = document.getElementById('olt_id');
        const closureSelect = document.getElementById('closure_id');

        function toggleSource() {
            if (sourceOlt.checked) {
                oltGroup.style.display = 'block';
                closureGroup.style.display = 'none';
                closureSelect.value = '';
            } else {
                oltGroup.style.display = 'none';
                closureGroup.style.display = 'block';
                oltSelect.value = '';
            }
        }

        sourceOlt.addEventListener('change', toggleSource);
        sourceClosure.addEventListener('change', toggleSource);
        
        // Run once on load
        toggleSource();
    });
</script>
@endpush
@endsection
