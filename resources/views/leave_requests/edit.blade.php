@extends('layouts.app')

@section('title', 'Edit Pengajuan Cuti/Izin')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <div class="d-flex align-items-center">
                        <a href="{{ Auth::user()->hasPermission('leave.manage') ? route('admin.leave-requests') : route('employee.leave-requests') }}" class="btn btn-outline-secondary btn-sm rounded-circle me-3">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="h4 mb-0 fw-bold">Edit Pengajuan Cuti/Izin</h1>
                            <p class="text-body-secondary small mb-0">Perbarui detail pengajuan izin atau cuti Anda.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('leave-requests.update-request', $leaveRequest->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Kategori Izin</label>
                            <div class="row g-2">
                                @php
                                    $currentCategory = 'lainnya';
                                    $reason = $leaveRequest->reason;
                                    if (str_starts_with($reason, '[')) {
                                        $pos = strpos($reason, ']');
                                        if ($pos !== false) {
                                            $badge = substr($reason, 1, $pos - 1);
                                            $reason = trim(substr($reason, $pos + 1));
                                            
                                            $map = [
                                                'Cuti' => 'cuti',
                                                'Sakit' => 'sakit',
                                                'Keperluan Mendadak' => 'mendadak',
                                                'Keperluan Keluarga' => 'keluarga',
                                                'Izin Lainnya' => 'lainnya',
                                            ];
                                            $currentCategory = $map[$badge] ?? 'lainnya';
                                        }
                                    }
                                @endphp
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="category" id="cat_cuti" value="cuti" {{ $currentCategory == 'cuti' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary w-100 rounded-3 py-2" for="cat_cuti">
                                        <i class="fa-solid fa-umbrella-beach d-block mb-1"></i>Cuti
                                    </label>
                                </div>
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="category" id="cat_sakit" value="sakit" {{ $currentCategory == 'sakit' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-success w-100 rounded-3 py-2" for="cat_sakit">
                                        <i class="fa-solid fa-house-medical d-block mb-1"></i>Sakit
                                    </label>
                                </div>
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="category" id="cat_mendadak" value="mendadak" {{ $currentCategory == 'mendadak' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-danger w-100 rounded-3 py-2" for="cat_mendadak">
                                        <i class="fa-solid fa-bolt d-block mb-1"></i>Mendadak
                                    </label>
                                </div>
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="category" id="cat_keluarga" value="keluarga" {{ $currentCategory == 'keluarga' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-info w-100 rounded-3 py-2" for="cat_keluarga">
                                        <i class="fa-solid fa-people-roof d-block mb-1"></i>Keluarga
                                    </label>
                                </div>
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="category" id="cat_lainnya" value="lainnya" {{ $currentCategory == 'lainnya' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary w-100 rounded-3 py-2" for="cat_lainnya">
                                        <i class="fa-solid fa-ellipsis d-block mb-1"></i>Lainnya
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label fw-semibold">Tanggal Mulai</label>
                                <input type="date" class="form-control rounded-3" id="start_date" name="start_date" value="{{ $leaveRequest->start_date->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label fw-semibold">Tanggal Selesai</label>
                                <input type="date" class="form-control rounded-3" id="end_date" name="end_date" value="{{ $leaveRequest->end_date->format('Y-m-d') }}" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="reason" class="form-label fw-semibold">Alasan / Keterangan</label>
                            <textarea class="form-control rounded-3" id="reason" name="reason" rows="4" placeholder="Jelaskan alasan pengajuan Anda..." required>{{ $reason }}</textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 rounded-3 fw-bold">
                                <i class="fa-solid fa-save me-1"></i>Simpan Perubahan
                            </button>
                            <a href="{{ Auth::user()->hasPermission('leave.manage') ? route('admin.leave-requests') : route('employee.leave-requests') }}" class="btn btn-link text-decoration-none text-body-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
