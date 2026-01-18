@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ __('Add New OLT') }}</h5>
                <a href="{{ route('olt.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to List') }}
                </a>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="{{ route('olt.store') }}">
                    @csrf

                    <div class="row g-3 mb-4">
                        <!-- Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label">{{ __('OLT Name') }}</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Host -->
                        <div class="col-md-6">
                            <label for="host" class="form-label">{{ __('Host / IP Address') }}</label>
                            <input type="text" name="host" id="host" value="{{ old('host') }}" class="form-control @error('host') is-invalid @enderror" required>
                            @error('host')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="port" class="form-label">{{ __('Port') }}</label>
                            <input type="number" name="port" id="port" value="{{ old('port', 23) }}" class="form-control @error('port') is-invalid @enderror" required>
                            <div class="form-text">{{ __('Default: 23 (Telnet), 22 (SSH)') }}</div>
                            @error('port')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="brand" class="form-label">Brand</label>
                            <select name="brand" id="brand" class="form-select @error('brand') is-invalid @enderror" required>
                                <option value="zte" {{ old('brand') == 'zte' ? 'selected' : '' }}>ZTE</option>
                                <option value="huawei" {{ old('brand') == 'huawei' ? 'selected' : '' }}>Huawei</option>
                                <option value="hsgq" {{ old('brand') == 'hsgq' ? 'selected' : '' }}>HSGQ</option>
                                <option value="cdata" {{ old('brand') == 'cdata' ? 'selected' : '' }}>C-Data</option>
                                <option value="vsol" {{ old('brand') == 'vsol' ? 'selected' : '' }}>VSOL</option>
                            </select>
                            @error('brand')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Type -->
                        <div class="col-md-6">
                            <label for="type" class="form-label">{{ __('Type') }}</label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="epon" {{ old('type') == 'epon' ? 'selected' : '' }}>EPON</option>
                                <option value="gpon" {{ old('type') == 'gpon' ? 'selected' : '' }}>GPON</option>
                                <option value="xpon" {{ old('type') == 'xpon' ? 'selected' : '' }}>XPON</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="username" class="form-label">{{ __('Username') }}</label>
                            <input type="text" name="username" id="username" value="{{ old('username') }}" class="form-control @error('username') is-invalid @enderror" required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">{{ __('Password') }}</label>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="snmp_port" class="form-label">SNMP Port</label>
                            <input type="number" name="snmp_port" id="snmp_port" value="{{ old('snmp_port', 161) }}" class="form-control @error('snmp_port') is-invalid @enderror">
                            @error('snmp_port')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="snmp_community" class="form-label">SNMP Community</label>
                            <input type="text" name="snmp_community" id="snmp_community" value="{{ old('snmp_community') }}" class="form-control @error('snmp_community') is-invalid @enderror">
                            @error('snmp_community')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 d-flex align-items-center pt-4">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                            </div>
                            @error('is_active')
                                <div class="text-danger small ms-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-4">
                        <button type="button" onclick="testConnection()" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-plug me-1"></i> Test Connection
                        </button>
                        <button type="submit" class="btn btn-primary px-4">Save OLT</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function testConnection() {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> {{ __('Testing...') }}';
        btn.disabled = true;

        const host = document.getElementById('host').value;
        const port = document.getElementById('port').value;
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const brand = document.getElementById('brand').value;

        if (!host || !port) {
            alert('Please fill Host and Port fields.');
            btn.innerHTML = originalText;
            btn.disabled = false;
            return;
        }

        fetch('{{ route('olt.test_connection') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ 
                host: host, 
                port: port,
                username: username,
                password: password,
                brand: brand
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('{{ __('Success!') }} ' + data.message);
            } else {
                alert('{{ __('Error!') }} ' + data.message);
            }
        })
        .catch(error => {
            alert('{{ __('Error!') }} ' + error);
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
</script>
@endsection
