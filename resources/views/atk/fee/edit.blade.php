@extends('layouts.app')

@section('title', __('Edit Fee Profile ATK'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3">
                <h5 class="mb-0 fw-bold">{{ __('Edit Fee Profile ATK') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('atk.fee.update', $profile->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3 mb-4 pb-3 border-bottom">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-medium">{{ __('Nama Profile') }}</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $profile->name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="transaction_type" class="form-label fw-medium">{{ __('Tipe Transaksi') }}</label>
                            <select class="form-select" id="transaction_type" name="transaction_type" required>
                                @foreach($transactionTypes as $key => $value)
                                    <option value="{{ $key }}" {{ $profile->transaction_type === $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="fee_mode" class="form-label fw-medium">{{ __('Mode Fee') }}</label>
                            <select class="form-select" id="fee_mode" name="fee_mode" required>
                                @foreach($feeModes as $key => $value)
                                    <option value="{{ $key }}" {{ $profile->fee_mode === $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3 mt-4">
                                <div class="form-check">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ $profile->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">{{ __('Aktif') }}</label>
                                </div>
                                <div class="form-check">
                                    <input type="hidden" name="allow_override" value="0">
                                    <input type="checkbox" class="form-check-input" id="allow_override" name="allow_override" value="1" {{ $profile->allow_override ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_override">{{ __('Izinkan Override Manual') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="fixed-fields" class="fee-fields mb-4 pb-3 border-bottom">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-coins me-1"></i> {{ __('Fixed Fee') }}
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="fixed_fee" class="form-label fw-medium">{{ __('Jumlah Fee Tetap') }}</label>
                                <input type="number" class="form-control" id="fixed_fee" name="tiers[0][fee_value]" value="{{ $profile->tiers->first()->fee_value ?? 0 }}">
                            </div>
                        </div>
                    </div>

                    <div id="percentage-fields" class="fee-fields mb-4 pb-3 border-bottom d-none">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-percent me-1"></i> {{ __('Percentage Fee') }}
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="percentage_fee" class="form-label fw-medium">{{ __('Persentase Fee') }}</label>
                                <input type="number" step="0.01" class="form-control" id="percentage_fee" name="tiers[0][fee_value]" value="{{ $profile->tiers->first()->fee_value ?? 0 }}">
                            </div>
                        </div>
                    </div>

                    <div id="fixed-percentage-fields" class="fee-fields mb-4 pb-3 border-bottom d-none">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-percent me-1"></i> {{ __('Fixed + Percentage') }}
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="fixed_percentage_fixed" class="form-label fw-medium">{{ __('Fee Tetap') }}</label>
                                <input type="number" class="form-control" id="fixed_percentage_fixed" name="tiers[0][fixed_value]" value="{{ $profile->tiers->first()->fixed_value ?? 0 }}">
                            </div>
                            <div class="col-md-6">
                                <label for="fixed_percentage_percent" class="form-label fw-medium">{{ __('Persentase') }}</label>
                                <input type="number" step="0.01" class="form-control" id="fixed_percentage_percent" name="tiers[0][fee_value]" value="{{ $profile->tiers->first()->fee_value ?? 0 }}">
                            </div>
                        </div>
                    </div>

                    <div id="tier-fields" class="fee-fields mb-4 pb-3 border-bottom d-none">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-layer-group me-1"></i> {{ __('Tier Fee') }}
                        </h6>
                        <div id="tiers-container" class="mb-3">
                            @foreach($profile->tiers as $index => $tier)
                            <div class="tier-item border rounded p-3 mb-3" data-index="{{ $index }}">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold">Tier {{ $index + 1 }}</span>
                                    @if($index > 0)
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-tier-btn">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-medium">{{ __('Min Amount') }}</label>
                                        <input type="number" class="form-control" name="tiers[{{ $index }}][min_amount]" value="{{ $tier->min_amount }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-medium">{{ __('Max Amount') }}</label>
                                        <input type="number" class="form-control" name="tiers[{{ $index }}][max_amount]" value="{{ $tier->max_amount }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-medium">{{ __('Tipe Fee') }}</label>
                                        <select class="form-select" name="tiers[{{ $index }}][fee_type]">
                                            <option value="fixed" {{ $tier->fee_type === 'fixed' ? 'selected' : '' }}>Fixed</option>
                                            <option value="percentage" {{ $tier->fee_type === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                            <option value="fixed_percentage" {{ $tier->fee_type === 'fixed_percentage' ? 'selected' : '' }}>Fixed + Percentage</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-medium">{{ __('Nilai Fee') }}</label>
                                        <input type="number" step="0.01" class="form-control" name="tiers[{{ $index }}][fee_value]" value="{{ $tier->fee_value }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-medium">{{ __('Fee Tetap (Jika Fixed+Percentage)') }}</label>
                                        <input type="number" class="form-control" name="tiers[{{ $index }}][fixed_value]" value="{{ $tier->fixed_value ?? 0 }}">
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" id="add-tier-btn" class="btn btn-outline-primary btn-sm">
                            <i class="fa-solid fa-plus me-1"></i> {{ __('Tambah Tier') }}
                        </button>
                    </div>

                    <div id="cost-plus-fields" class="fee-fields mb-4 pb-3 border-bottom d-none">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-calculator me-1"></i> {{ __('Cost Plus Markup') }}
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="cost_price" class="form-label fw-medium">{{ __('Harga Cost') }}</label>
                                <input type="number" class="form-control" id="cost_price" name="cost_price" value="{{ $profile->cost_price ?? 0 }}">
                            </div>
                            <div class="col-md-3">
                                <label for="markup_type" class="form-label fw-medium">{{ __('Tipe Markup') }}</label>
                                <select class="form-select" id="markup_type" name="markup_type">
                                    <option value="fixed" {{ $profile->markup_type === 'fixed' ? 'selected' : '' }}>Fixed</option>
                                    <option value="percentage" {{ $profile->markup_type === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="markup_value" class="form-label fw-medium">{{ __('Nilai Markup') }}</label>
                                <input type="number" step="0.01" class="form-control" id="markup_value" name="markup_value" value="{{ $profile->markup_value ?? 0 }}">
                            </div>
                        </div>
                    </div>

                    <div id="custom-fields" class="fee-fields mb-4 pb-3 border-bottom d-none">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-code me-1"></i> {{ __('Custom Formula') }}
                        </h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="custom_formula" class="form-label fw-medium">{{ __('Formula') }} (gunakan 'amount' sebagai variabel nominal)</label>
                                <input type="text" class="form-control" id="custom_formula" name="custom_formula" value="{{ $profile->custom_formula }}" placeholder="(amount * 0.5 / 100) + 3000">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end pt-3 gap-3">
                        <a href="{{ route('atk.fee.index') }}" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Kembali') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Simpan') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const feeModeSelect = document.getElementById('fee_mode');
    const allFields = document.querySelectorAll('.fee-fields');
    
    function updateFields() {
        const selectedMode = feeModeSelect.value;
        allFields.forEach(field => field.classList.add('d-none'));
        
        if (selectedMode === 'fixed') {
            document.getElementById('fixed-fields').classList.remove('d-none');
        } else if (selectedMode === 'percentage') {
            document.getElementById('percentage-fields').classList.remove('d-none');
        } else if (selectedMode === 'fixed_percentage') {
            document.getElementById('fixed-percentage-fields').classList.remove('d-none');
        } else if (selectedMode === 'tier') {
            document.getElementById('tier-fields').classList.remove('d-none');
        } else if (selectedMode === 'cost_plus') {
            document.getElementById('cost-plus-fields').classList.remove('d-none');
        } else if (selectedMode === 'custom') {
            document.getElementById('custom-fields').classList.remove('d-none');
        }
    }
    
    feeModeSelect.addEventListener('change', updateFields);
    updateFields();

    let tierIndex = {{ $profile->tiers->count() }};
    const addTierBtn = document.getElementById('add-tier-btn');
    const tiersContainer = document.getElementById('tiers-container');
    
    addTierBtn.addEventListener('click', function() {
        const newTier = `
            <div class="tier-item border rounded p-3 mb-3" data-index="${tierIndex}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold">Tier ${tierIndex + 1}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-tier-btn">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-medium">{{ __('Min Amount') }}</label>
                        <input type="number" class="form-control" name="tiers[${tierIndex}][min_amount]" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">{{ __('Max Amount') }}</label>
                        <input type="number" class="form-control" name="tiers[${tierIndex}][max_amount]">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">{{ __('Tipe Fee') }}</label>
                        <select class="form-select" name="tiers[${tierIndex}][fee_type]">
                            <option value="fixed">Fixed</option>
                            <option value="percentage">Percentage</option>
                            <option value="fixed_percentage">Fixed + Percentage</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">{{ __('Nilai Fee') }}</label>
                        <input type="number" step="0.01" class="form-control" name="tiers[${tierIndex}][fee_value]" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">{{ __('Fee Tetap (Jika Fixed+Percentage)') }}</label>
                        <input type="number" class="form-control" name="tiers[${tierIndex}][fixed_value]" value="0">
                    </div>
                </div>
            </div>
        `;
        tiersContainer.insertAdjacentHTML('beforeend', newTier);
        tierIndex++;
    });

    tiersContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-tier-btn')) {
            e.target.closest('.tier-item').remove();
        }
    });
});
</script>
@endsection
