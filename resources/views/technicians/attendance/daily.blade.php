@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 bg-white">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark">{{ __('Kehadiran Karyawan Harian') }}</h5>
                        <a href="{{ route('attendance.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fa-solid fa-list-check me-1"></i> {{ __('Kembali ke Rekap') }}
                        </a>
                    </div>

                    <!-- Filter Form yang Lebih Konsisten -->
                    <form action="{{ route('attendance.daily') }}" method="GET" class="w-100">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md">
                                <label class="form-label small fw-bold text-muted">{{ __('Filter Bulan') }}</label>
                                <input type="month" name="month" value="{{ request('month') }}" class="form-control" onchange="this.form.submit()">
                            </div>
                            <div class="col-12 col-md">
                                <label class="form-label small fw-bold text-muted">{{ __('Mulai Tanggal') }}</label>
                                <input type="date" name="start_date" value="{{ $startDate }}" class="form-control" onchange="this.form.submit()">
                            </div>
                            <div class="col-12 col-md">
                                <label class="form-label small fw-bold text-muted">{{ __('Tanggal Selesai') }}</label>
                                <input type="date" name="end_date" value="{{ $endDate }}" class="form-control" onchange="this.form.submit()">
                            </div>
                            <div class="col-12 col-md">
                                <label class="form-label small fw-bold text-muted">{{ __('Status') }}</label>
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">{{ __('Semua Status') }}</option>
                                    @foreach(['present' => 'Hadir', 'late' => 'Terlambat', 'leave' => 'Cuti', 'permit' => 'Izin', 'sick' => 'Sakit', 'alpha' => 'Alpha', 'off' => 'Off', 'belum_absen' => 'Belum Absen'] as $val => $label)
                                        <option value="{{ $val }}" {{ ($status ?? '') === $val ? 'selected' : '' }}>{{ __($label) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md">
                                <label class="form-label small fw-bold text-muted">{{ __('Cari Nama') }}</label>
                                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, username, atau email..." class="form-control" onchange="this.form.submit()">
                            </div>
                            <div class="col-12 col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="fa-solid fa-filter me-1"></i> {{ __('Terapkan') }}
                                </button>
                                <a href="{{ route('attendance.daily') }}" class="btn btn-outline-secondary flex-fill">
                                    <i class="fa-solid fa-rotate me-1"></i> {{ __('Reset') }}
                                </a>
                                <a href="{{ route('attendance.excel', array_merge(request()->query(), ['scope' => 'daily'])) }}" target="_blank" class="btn btn-success flex-fill">
                                    <i class="fa-solid fa-file-excel me-1"></i> {{ __('Excel') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body">
                <!-- Panduan Penggunaan -->
                <div class="alert alert-info py-2 mb-4">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    <strong>Panduan Penggunaan:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Halaman ini menampilkan <strong>status kehadiran SELURUH karyawan</strong> untuk periode yang dipilih, termasuk yang belum absen dan yang OFF.</li>
                        <li>Gunakan filter di atas untuk memilih bulan, rentang tanggal, atau status kehadiran tertentu.</li>
                        <li>Klik <strong>"Excel"</strong> untuk mengunduh seluruh data kehadiran dalam format Excel.</li>
                        <li>Klik accordion untuk membuka/menutup detail kehadiran per tanggal.</li>
                        <li>Klik <strong>"Kembali ke Rekap"</strong> untuk kembali ke halaman riwayat absensi.</li>
                        <li>Admin dapat mengedit atau membuat absensi baru via tombol di kolom <strong>Aksi</strong>.</li>
                    </ul>
                </div>

                <div class="alert alert-info py-2 mb-4 d-flex align-items-center">
                    <i class="fa-solid fa-circle-info me-2 fs-5"></i>
                    <div>
                        {{ __('Menampilkan status kehadiran seluruh karyawan untuk periode:') }} 
                        <strong>{{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }}</strong> - <strong>{{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}</strong>
                    </div>
                </div>

                <!-- Accordion untuk Menghemat Ruang Vertikal -->
                <div class="accordion" id="accordionAttendance">
                    @foreach($dates as $index => $currentDate)
                        @php
                            $attendances = $attendancesByDate->get($currentDate, collect());
                            
                            // Filter koleksi user sebelum looping render untuk mendeteksi empty state
                            $filteredUsers = $users->filter(function($user) use ($attendances, $currentDate, $status, $isOffByUserAndDate) {
                                $isOff = $isOffByUserAndDate[$user->id][$currentDate] ?? false;
                                $attendance = $attendances->get($user->id);
                                
                                if ($status === '') return true;
                                if ($status === 'belum_absen') return ! $attendance && ! $isOff;
                                if ($status === 'off') return $isOff;
                                return $attendance && $attendance->status === $status;
                            });
                        @endphp

                        <div class="accordion-item mb-2 border rounded shadow-sm overflow-hidden">
                            <h2 class="accordion-header" id="heading-{{ $index }}">
                                <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }} fw-bold text-primary py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $index }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="collapse-{{ $index }}">
                                    <i class="fa-regular fa-calendar me-2"></i>
                                    {{ \Carbon\Carbon::parse($currentDate)->translatedFormat('l, d F Y') }}
                                    <span class="badge bg-secondary ms-2 small">{{ $filteredUsers->count() }} {{ __('Karyawan') }}</span>
                                </button>
                            </h2>
                            
                            <div id="collapse-{{ $index }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" aria-labelledby="heading-{{ $index }}" data-bs-parent="#accordionAttendance">
                                <div class="accordion-body p-0">
                                    @if($filteredUsers->isEmpty())
                                        <div class="p-4 text-center text-muted">
                                            <i class="fa-solid fa-folder-open d-block fs-3 mb-2"></i>
                                            {{ __('Tidak ada data kehadiran yang sesuai dengan filter pada tanggal ini.') }}
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    @php
                                                        $isAdminOrHrd = Auth::user()->hasAnyRole(['admin', 'direktur', App\Models\Role::HRD_MANAGER, 'owner', 'owner pendiri']);
                                                    @endphp
                                                    <tr>
                                                        <th style="width: 50px;" class="ps-3">#</th>
                                                        <th>{{ __('Karyawan') }}</th>
                                                        <th>{{ __('Peran') }}</th>
                                                        <th>{{ __('Jam Masuk') }}</th>
                                                        <th>{{ __('Jam Keluar') }}</th>
                                                        <th>{{ __('Status') }}</th>
                                                        <th class="pe-3">{{ __('Keterangan') }}</th>
                                                        @if($isAdminOrHrd)
                                                            <th class="text-end pe-3">{{ __('Aksi') }}</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $i = 1; @endphp
                                                    @foreach($filteredUsers as $user)
                                                        @php
                                                            $attendance = $attendances->get($user->id);
                                                            $isOff = $isOffByUserAndDate[$user->id][$currentDate] ?? false;
                                                        @endphp
                                                        <tr>
                                                            <td class="ps-3">{{ $i++ }}</td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    @if($user->avatar)
                                                                        <img src="{{ Storage::url($user->avatar) }}" class="rounded-circle me-2 object-fit-cover" width="32" height="32" alt="">
                                                                    @else
                                                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                                                        </div>
                                                                    @endif
                                                                    <div>
                                                                        <div class="fw-bold text-dark lh-sm">{{ $user->name }}</div>
                                                                        <div class="small text-muted" style="font-size: 0.75rem;">{{ $user->username ?? $user->email }}</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light text-dark border" style="font-size: 0.75rem;">{{ $user->role->name ?? '-' }}</span>
                                                            </td>
                                                            <td>
                                                                @if($isOff)
                                                                    <span class="text-muted">--:--</span>
                                                                @elseif($attendance && $attendance->clock_in)
                                                                    <div class="fw-bold">{{ $attendance->clock_in->format('H:i') }}</div>
                                                                    @if($attendance->lat_clock_in && $attendance->lng_clock_in)
                                                                        <a href="https://maps.google.com/?q={{ $attendance->lat_clock_in }},{{ $attendance->lng_clock_in }}" target="_blank" class="text-decoration-none x-small d-block">
                                                                            <i class="fa-solid fa-location-dot me-1"></i>{{ __('Lokasi') }}
                                                                        </a>
                                                                    @endif
                                                                @else
                                                                    <span class="text-muted">--:--</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if($isOff)
                                                                    <span class="text-muted">--:--</span>
                                                                @elseif($attendance && $attendance->clock_out)
                                                                    <div class="fw-bold">{{ $attendance->clock_out->format('H:i') }}</div>
                                                                    @if($attendance->lat_clock_out && $attendance->lng_clock_out)
                                                                        <a href="https://maps.google.com/?q={{ $attendance->lat_clock_out }},{{ $attendance->lng_clock_out }}" target="_blank" class="text-decoration-none x-small d-block">
                                                                            <i class="fa-solid fa-location-dot me-1"></i>{{ __('Lokasi') }}
                                                                        </a>
                                                                    @endif
                                                                @else
                                                                    <span class="text-muted">--:--</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if($isOff)
                                                                    <span class="badge bg-secondary">{{ __('OFF') }}</span>
                                                                @elseif($attendance)
                                                                    @php
                                                                        $statusClass = match($attendance->status) {
                                                                            'present' => 'bg-success',
                                                                            'late' => 'bg-warning text-dark',
                                                                            'leave', 'permit', 'sick' => 'bg-info',
                                                                            'alpha' => 'bg-danger',
                                                                            default => 'bg-secondary'
                                                                        };
                                                                    @endphp
                                                                    <span class="badge {{ $statusClass }}">
                                                                        {{ __(ucfirst($attendance->status)) }}
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                                        {{ __('Belum Absen') }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="pe-3">
                                                                <small class="text-muted d-block text-truncate" style="max-width: 200px;" title="{{ $attendance->notes ?? '' }}">
                                                                    {{ $attendance->notes ?? '-' }}
                                                                </small>
                                                            </td>
                                                            @if($isAdminOrHrd)
                                                                <td class="text-end pe-3">
                                                                    <div class="d-inline-flex gap-1">
                                                                        @if($attendance)
                                                                            <button type="button" class="btn btn-outline-primary btn-sm" title="Edit" data-bs-toggle="modal" data-bs-target="#editModalDaily-{{ $user->id }}-{{ $currentDate }}">
                                                                                <i class="fas fa-edit"></i>
                                                                            </button>
                                                                        @elseif(!$isOff)
                                                                            <button type="button" class="btn btn-outline-success btn-sm" title="Buat Absensi" data-bs-toggle="modal" data-bs-target="#createModalDaily-{{ $user->id }}-{{ $currentDate }}">
                                                                                <i class="fas fa-plus"></i>
                                                                            </button>
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals for Edit and Create -->
@foreach($dates as $index => $currentDate)
    @php
        $attendances = $attendancesByDate->get($currentDate, collect());
        $filteredUsersForModal = $users->filter(function($user) use ($attendances, $currentDate, $status, $isOffByUserAndDate) {
            $isOff = $isOffByUserAndDate[$user->id][$currentDate] ?? false;
            $attendance = $attendances->get($user->id);
            if ($status === '') return true;
            if ($status === 'belum_absen') return ! $attendance && ! $isOff;
            if ($status === 'off') return $isOff;
            return $attendance && $attendance->status === $status;
        });
    @endphp
    @foreach($filteredUsersForModal as $user)
        @php
            $attendance = $attendances->get($user->id);
            $isOff = $isOffByUserAndDate[$user->id][$currentDate] ?? false;
        @endphp
        @if($attendance)
            <!-- Edit Modal -->
            <div class="modal fade" id="editModalDaily-{{ $user->id }}-{{ $currentDate }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">{{ __('Edit Absensi') }} - {{ $user->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('attendance.updateManual', $attendance->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-medium">{{ __('Tanggal') }}</label>
                                    <input type="date" name="date" class="form-control" required value="{{ $currentDate }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-medium">{{ __('Status') }}</label>
                                    <select name="status" class="form-select" required>
                                        <option value="present" {{ $attendance->status === 'present' ? 'selected' : '' }}>{{ __('Hadir') }}</option>
                                        <option value="leave" {{ $attendance->status === 'leave' ? 'selected' : '' }}>{{ __('Cuti') }}</option>
                                        <option value="permit" {{ $attendance->status === 'permit' ? 'selected' : '' }}>{{ __('Izin') }}</option>
                                        <option value="sick" {{ $attendance->status === 'sick' ? 'selected' : '' }}>{{ __('Sakit') }}</option>
                                        <option value="late" {{ $attendance->status === 'late' ? 'selected' : '' }}>{{ __('Terlambat') }}</option>
                                        <option value="alpha" {{ $attendance->status === 'alpha' ? 'selected' : '' }}>{{ __('Alpha (Tanpa Keterangan)') }}</option>
                                    </select>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-medium">{{ __('Jam Masuk') }}</label>
                                        <input type="time" name="clock_in" class="form-control" value="{{ $attendance->clock_in?->format('H:i') }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-medium">{{ __('Jam Keluar') }}</label>
                                        <input type="time" name="clock_out" class="form-control" value="{{ $attendance->clock_out?->format('H:i') }}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-medium">{{ __('Catatan') }}</label>
                                    <textarea name="notes" class="form-control" rows="2">{{ $attendance->notes }}</textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Tutup') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('Simpan Perubahan') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @elseif(!$isOff)
            <!-- Create Modal -->
            <div class="modal fade" id="createModalDaily-{{ $user->id }}-{{ $currentDate }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">{{ __('Buat Absensi Baru') }} - {{ $user->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('attendance.storeManual') }}" method="POST">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-medium">{{ __('Tanggal') }}</label>
                                    <input type="date" name="date" class="form-control" required value="{{ $currentDate }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-medium">{{ __('Status') }}</label>
                                    <select name="status" class="form-select" required>
                                        <option value="present">{{ __('Hadir') }}</option>
                                        <option value="leave">{{ __('Cuti') }}</option>
                                        <option value="permit">{{ __('Izin') }}</option>
                                        <option value="sick">{{ __('Sakit') }}</option>
                                        <option value="late">{{ __('Terlambat') }}</option>
                                        <option value="alpha">{{ __('Alpha (Tanpa Keterangan)') }}</option>
                                    </select>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-medium">{{ __('Jam Masuk') }}</label>
                                        <input type="time" name="clock_in_create" class="form-control">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-medium">{{ __('Jam Keluar') }}</label>
                                        <input type="time" name="clock_out_create" class="form-control">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-medium">{{ __('Catatan') }}</label>
                                    <textarea name="notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Tutup') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('Simpan Data') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endforeach
@endsection

@push('styles')
<style>
    .x-small { font-size: 0.72rem; }
    .accordion-button:not(.collapsed) {
        background-color: rgba(13, 110, 253, 0.05);
        color: #0d6efd;
        box-shadow: inset 0 -1px 0 rgba(0,0,0,.125);
    }
    .accordion-button::after {
        background-size: 1rem;
    }
</style>
@endpush