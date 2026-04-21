<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register Pelanggan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-7 col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h5 mb-0">Register Pelanggan Baru</h1>
                    </div>
                    <div class="card-body p-4">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger mb-3">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('customers.public.register.store') }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="name" class="form-label">Nama Lengkap</label>
                                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                                </div>

                                <div class="col-12">
                                    <label for="package_id" class="form-label">Paket</label>
                                    <select id="package_id" name="package_id" class="form-select">
                                        <option value="">Pilih Paket</option>
                                        @foreach(($packages ?? collect()) as $package)
                                            <option value="{{ $package->id }}" {{ old('package_id') == $package->id ? 'selected' : '' }}>
                                                {{ $package->name }} @if($package->price) - Rp {{ number_format($package->price, 0, ',', '.') }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="coordinator_id" class="form-label">Pengurus</label>
                                    <select id="coordinator_id" name="coordinator_id" class="form-select">
                                        <option value="">Pilih Pengurus</option>
                                        @foreach(($coordinators ?? collect()) as $coordinator)
                                            <option value="{{ $coordinator->id }}" {{ old('coordinator_id') == $coordinator->id ? 'selected' : '' }}>
                                                {{ $coordinator->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Nomor HP</label>
                                    <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="0812xxxxxx" required>
                                    <div class="form-text">Nomor disimpan dalam format 62xxxxxxxxxx.</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="nama@email.com">
                                </div>

                                <div class="col-12">
                                    <label for="address" class="form-label">Alamat</label>
                                    <textarea id="address" name="address" rows="2" class="form-control" placeholder="Alamat pelanggan">{{ old('address') }}</textarea>
                                </div>

                                <div class="col-md-6">
                                    <label for="ssid_name" class="form-label">Nama WiFi</label>
                                    <input type="text" id="ssid_name" name="ssid_name" class="form-control" value="{{ old('ssid_name') }}" placeholder="Contoh: MStoreHome">
                                </div>

                                <div class="col-md-6">
                                    <label for="ssid_password" class="form-label">Password WiFi</label>
                                    <input type="text" id="ssid_password" name="ssid_password" class="form-control" value="{{ old('ssid_password') }}" placeholder="Password WiFi">
                                </div>

                                <div class="col-md-6">
                                    <label for="latitude" class="form-label">Koordinator (Latitude)</label>
                                    <input type="text" id="latitude" name="latitude" class="form-control" value="{{ old('latitude') }}" placeholder="-6.200000">
                                </div>

                                <div class="col-md-6">
                                    <label for="longitude" class="form-label">Koordinator (Longitude)</label>
                                    <input type="text" id="longitude" name="longitude" class="form-control" value="{{ old('longitude') }}" placeholder="106.816666">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Simpan Registrasi</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
