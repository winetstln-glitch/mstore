@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Create New Ticket') }}</h5>
                <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to List') }}
                </a>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="{{ route('tickets.store') }}">
                    @csrf

                    <div class="row g-3 mb-4">
                        <!-- Type (Moved to top for better flow) -->
                        <div class="col-md-12">
                            <label for="type" class="form-label">{{ __('Ticket Type') }}</label>
                            <select name="type" id="type" required class="form-select @error('type') is-invalid @enderror">
                                <option value="gangguan" {{ old('type') == 'gangguan' ? 'selected' : '' }}>{{ __('Gangguan') }} (Issue)</option>
                                <option value="pasang_baru" {{ old('type') == 'pasang_baru' ? 'selected' : '' }}>{{ __('Pasang Baru') }} (New Install)</option>
                                <option value="maintenance" {{ old('type') == 'maintenance' ? 'selected' : '' }}>{{ __('Maintenance') }}</option>
                                <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Customer Selection (Existing) -->
                        <div class="col-12" id="existing-customer-section">
                            <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
                            <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                                <option value="">{{ __('Select Customer') }}</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" 
                                        data-lat="{{ $customer->latitude }}" 
                                        data-lng="{{ $customer->longitude }}"
                                        data-address="{{ $customer->address }}"
                                        {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }} - {{ $customer->address }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- New Customer Form (Hidden by default) -->
                        <div id="new-customer-section" class="col-12" style="display: none;">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-user-plus me-1"></i> {{ __('New Customer Details') }}</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="new_customer_name" class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="new_customer_name" name="new_customer_name" value="{{ old('new_customer_name') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="new_customer_phone" class="form-label">{{ __('Phone') }}</label>
                                            <input type="text" class="form-control" id="new_customer_phone" name="new_customer_phone" value="{{ old('new_customer_phone') }}">
                                        </div>
                                        <div class="col-12">
                                            <label for="new_customer_address" class="form-label">{{ __('Address') }} <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="new_customer_address" name="new_customer_address" rows="2">{{ old('new_customer_address') }}</textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="new_customer_lat" class="form-label">{{ __('Latitude') }}</label>
                                            <input type="text" class="form-control" id="new_customer_lat" name="new_customer_lat" value="{{ old('new_customer_lat') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="new_customer_lng" class="form-label">{{ __('Longitude') }}</label>
                                            <input type="text" class="form-control" id="new_customer_lng" name="new_customer_lng" value="{{ old('new_customer_lng') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Technician Selection -->
                        <div class="col-12">
                            <label for="technicians" class="form-label">{{ __('Assign Technicians') }}</label>
                            <select name="technicians[]" id="technicians" class="form-select @error('technicians') is-invalid @enderror" multiple>
                                @foreach($technicians as $technician)
                                    <option value="{{ $technician->id }}" {{ (collect(old('technicians'))->contains($technician->id)) ? 'selected' : '' }}>
                                        {{ $technician->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted">{{ __('Select multiple technicians by clicking on them. Only technicians present today and without active assignments are shown.') }}</div>
                            @error('technicians')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Subject -->
                        <div class="col-12">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required placeholder="Brief summary of the issue" class="form-control @error('subject') is-invalid @enderror">
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Priority -->
                        <div class="col-md-12">
                            <label for="priority" class="form-label">Priority</label>
                            <select name="priority" id="priority" required class="form-select @error('priority') is-invalid @enderror">
                                <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ODP & Coordinator -->
                        <div class="col-md-6">
                            <label for="odp_id" class="form-label">ODP</label>
                            <select name="odp_id" id="odp_id" class="form-select @error('odp_id') is-invalid @enderror">
                                <option value="">Select ODP</option>
                                @foreach($odps as $odp)
                                    <option value="{{ $odp->id }}" {{ old('odp_id') == $odp->id ? 'selected' : '' }}>
                                        {{ $odp->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('odp_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="coordinator_id" class="form-label">Coordinator</label>
                            <select name="coordinator_id" id="coordinator_id" class="form-select @error('coordinator_id') is-invalid @enderror">
                                <option value="">Select Coordinator</option>
                                @foreach($coordinators as $coordinator)
                                    <option value="{{ $coordinator->id }}" {{ old('coordinator_id') == $coordinator->id ? 'selected' : '' }}>
                                        {{ $coordinator->name }} ({{ $coordinator->region->name ?? 'No Region' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('coordinator_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Location -->
                        <div class="col-12">
                            <label for="location" class="form-label">Location (Optional)</label>
                            <div class="input-group">
                                <input type="text" name="location" id="location" value="{{ old('location') }}" placeholder="Specific coordinates or notes" class="form-control @error('location') is-invalid @enderror">
                                <a href="#" id="view-map-link" target="_blank" class="btn btn-outline-secondary" style="display: none;" title="View on Google Maps">
                                    <i class="fa-solid fa-map-location-dot"></i>
                                </a>
                            </div>
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-4">
                        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Create Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('#technicians').select2({
            theme: 'bootstrap-5',
            placeholder: "Select Technicians",
            allowClear: true,
            width: '100%'
        });

        const typeSelect = $('#type');
        const existingCustomerSection = $('#existing-customer-section');
        const newCustomerSection = $('#new-customer-section');
        const customerSelect = $('#customer_id');
        const newCustomerInputs = $('#new_customer_name, #new_customer_address');
        const locationInput = document.getElementById('location');
        const mapLink = document.getElementById('view-map-link');

        function toggleCustomerForm() {
            if (typeSelect.val() === 'pasang_baru') {
                existingCustomerSection.hide();
                newCustomerSection.show();
                customerSelect.prop('required', false);
                newCustomerInputs.prop('required', true);
            } else {
                existingCustomerSection.show();
                newCustomerSection.hide();
                customerSelect.prop('required', true);
                newCustomerInputs.prop('required', false);
            }
        }

        // Initial check
        toggleCustomerForm();

        // On Type change
        typeSelect.on('change', toggleCustomerForm);

        // Existing Customer Location Logic
        function updateMapLink() {
            const val = locationInput.value;
            if (val && mapLink) {
                mapLink.href = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(val)}`;
                mapLink.style.display = 'inline-block';
            } else if (mapLink) {
                mapLink.style.display = 'none';
            }
        }
        
        if (locationInput) {
            updateMapLink();
            locationInput.addEventListener('input', updateMapLink);
        }

        customerSelect.on('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const lat = selectedOption.getAttribute('data-lat');
            const lng = selectedOption.getAttribute('data-lng');
            const address = selectedOption.getAttribute('data-address');

            if (locationInput) {
                if (lat && lng) {
                    locationInput.value = `${lat}, ${lng}`;
                } else if (address) {
                    locationInput.value = address;
                } else {
                    locationInput.value = '';
                }
                updateMapLink();
            }
        });
        
        // New Customer Location Logic (Auto-fill location from lat/lng inputs)
        $('#new_customer_lat, #new_customer_lng').on('input', function() {
            const lat = $('#new_customer_lat').val();
            const lng = $('#new_customer_lng').val();
            if (lat && lng && locationInput) {
                locationInput.value = `${lat}, ${lng}`;
                updateMapLink();
            }
        });
    });
</script>
@endpush
@endsection
