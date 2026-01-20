@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Add Investor') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('investors.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="coordinator_id" class="form-label">{{ __('Coordinator') }}</label>
                            <select class="form-select @error('coordinator_id') is-invalid @enderror" id="coordinator_id" name="coordinator_id" required>
                                <option value="">{{ __('Select Coordinator') }}</option>
                                @foreach($coordinators as $coordinator)
                                    <option value="{{ $coordinator->id }}" {{ old('coordinator_id') == $coordinator->id ? 'selected' : '' }}>
                                        {{ $coordinator->name }} ({{ $coordinator->region->name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('coordinator_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Mode') }}</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="mode_new" value="new" {{ old('mode', 'new') === 'new' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mode_new">
                                    {{ __('Create New Investor') }}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="mode_select" value="select" {{ old('mode') === 'select' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mode_select">
                                    {{ __('Select Existing Investor') }}
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="existingInvestorWrapper">
                            <label for="source_investor_id" class="form-label">{{ __('Existing Investors') }}</label>
                            <select class="form-select @error('source_investor_id') is-invalid @enderror" id="source_investor_id" name="source_investor_id">
                                <option value="">{{ __('Select Investor') }}</option>
                                @foreach($existingInvestors as $investor)
                                    <option value="{{ $investor->id }}" {{ old('source_investor_id') == $investor->id ? 'selected' : '' }}>
                                        {{ $investor->name }} @if($investor->phone) ({{ $investor->phone }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('source_investor_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                {{ __('Use this to reuse an existing investor for another coordinator.') }}
                            </div>
                        </div>

                        <div id="newInvestorFields">
                            <div class="mb-3">
                                <label for="name" class="form-label">{{ __('Name') }}</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">{{ __('Phone') }}</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">{{ __('Description') }}</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">{{ __('Save Investor') }}</button>
                            <a href="{{ route('investors.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        function updateMode() {
            var mode = document.querySelector('input[name="mode"]:checked')?.value || 'new';
            var existingWrapper = document.getElementById('existingInvestorWrapper');
            var newFields = document.getElementById('newInvestorFields');

            if (mode === 'select') {
                existingWrapper.style.display = '';
                newFields.style.display = 'none';
            } else {
                existingWrapper.style.display = 'none';
                newFields.style.display = '';
            }
        }

        document.getElementById('mode_new').addEventListener('change', updateMode);
        document.getElementById('mode_select').addEventListener('change', updateMode);

        updateMode();
    })();
</script>
@endsection
