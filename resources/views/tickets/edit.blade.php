@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Ubah Tiket') }} #{{ $ticket->ticket_number }}</h5>
                <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Kembali ke Detail') }}
                </a>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="{{ route('tickets.update', $ticket) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-4">
                        @php
                            $ticketTypeOptions = [
                                'gangguan' => __('Gangguan'),
                                'pasang_baru' => __('Pasang Baru'),
                                'pasang_odc' => __('Pasang ODC'),
                                'tarik_jalur' => __('Tarik Jalur'),
                                'maintenance' => __('Maintenance'),
                                'pergantian_onu' => __('Pergantian ONU'),
                                'ganti_onu' => __('Ganti ONU'),
                                'other' => __('Lainnya'),
                            ];
                            $currentTicketType = old('type', $ticket->type);
                            if (! array_key_exists($currentTicketType, $ticketTypeOptions) && filled($currentTicketType)) {
                                $ticketTypeOptions[$currentTicketType] = __(ucfirst(str_replace('_', ' ', $currentTicketType)));
                            }
                        @endphp
                        <!-- Jenis Tiket -->
                        <div class="col-md-6">
                            <label for="type" class="form-label">{{ __('Jenis Tiket') }}</label>
                            <select name="type" id="type" required class="form-select @error('type') is-invalid @enderror">
                                @foreach($ticketTypeOptions as $ticketTypeValue => $ticketTypeLabel)
                                    <option value="{{ $ticketTypeValue }}" {{ $currentTicketType === $ticketTypeValue ? 'selected' : '' }}>
                                        {{ $ticketTypeLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Subjek -->
                        <div class="col-md-6">
                            <label for="subject" class="form-label">{{ __('Subjek') }}</label>
                            <input type="text" name="subject" id="subject" value="{{ old('subject', $ticket->subject) }}" required class="form-control @error('subject') is-invalid @enderror">
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label for="status" class="form-label">{{ __('Status') }}</label>
                            <select name="status" id="status" required class="form-select @error('status') is-invalid @enderror">
                                <option value="open" {{ old('status', $ticket->status) == 'open' ? 'selected' : '' }}>{{ __('Buka') }}</option>
                                <option value="assigned" {{ old('status', $ticket->status) == 'assigned' ? 'selected' : '' }}>{{ __('Ditugaskan') }}</option>
                                <option value="in_progress" {{ old('status', $ticket->status) == 'in_progress' ? 'selected' : '' }}>{{ __('Sedang Dikerjakan') }}</option>
                                <option value="pending" {{ old('status', $ticket->status) == 'pending' ? 'selected' : '' }}>{{ __('Tertunda') }}</option>
                                <option value="solved" {{ old('status', $ticket->status) == 'solved' ? 'selected' : '' }}>{{ __('Selesai') }}</option>
                                <option value="closed" {{ old('status', $ticket->status) == 'closed' ? 'selected' : '' }}>{{ __('Tutup') }}</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Prioritas -->
                        <div class="col-md-6">
                            <label for="priority" class="form-label">{{ __('Prioritas') }}</label>
                            <select name="priority" id="priority" required class="form-select @error('priority') is-invalid @enderror">
                                <option value="low" {{ old('priority', $ticket->priority) == 'low' ? 'selected' : '' }}>{{ __('Rendah') }}</option>
                                <option value="medium" {{ old('priority', $ticket->priority) == 'medium' ? 'selected' : '' }}>{{ __('Sedang') }}</option>
                                <option value="high" {{ old('priority', $ticket->priority) == 'high' ? 'selected' : '' }}>{{ __('Tinggi') }}</option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Penugasan teknisi -->
                        <div class="col-12">
                            <label for="technicians" class="form-label">{{ __('Tugaskan Teknisi') }}</label>
                            <select name="technicians[]" id="technicians" class="form-select @error('technicians') is-invalid @enderror" multiple>
                                @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}" {{ collect(old('technicians', $ticket->technicians->pluck('id')))->contains($tech->id) ? 'selected' : '' }}>
                                        {{ $tech->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('Tahan Ctrl (Windows) atau Command (Mac) untuk memilih beberapa teknisi. Hanya teknisi yang hadir hari ini dan tidak memiliki tugas aktif yang ditampilkan. Teknisi yang sudah ditugaskan tetap terlihat.') }}</div>
                            @error('technicians')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ODP dan Pengurus -->
                        <div class="col-md-6">
                            <label for="odp_id" class="form-label">{{ __('ODP') }}</label>
                            <select name="odp_id" id="odp_id" class="form-select @error('odp_id') is-invalid @enderror">
                                <option value="">{{ __('Pilih ODP') }}</option>
                                @foreach($odps as $odp)
                                    <option value="{{ $odp->id }}" {{ old('odp_id', $ticket->odp_id) == $odp->id ? 'selected' : '' }}>
                                        {{ $odp->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('odp_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="coordinator_id" class="form-label">{{ __('Pengurus') }}</label>
                            <select name="coordinator_id" id="coordinator_id" class="form-select @error('coordinator_id') is-invalid @enderror">
                                <option value="">{{ __('Pilih Pengurus') }}</option>
                                @foreach($coordinators as $coordinator)
                                    <option value="{{ $coordinator->id }}" {{ old('coordinator_id', $ticket->coordinator_id) == $coordinator->id ? 'selected' : '' }}>
                                        {{ $coordinator->name }} ({{ $coordinator->region->name ?? __('Tanpa Wilayah') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('coordinator_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Deskripsi -->
                        <div class="col-12">
                            <label for="description" class="form-label">{{ __('Deskripsi') }}</label>
                            <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $ticket->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Lokasi -->
                        <div class="col-12">
                            <label for="location" class="form-label">{{ __('Lokasi (Opsional)') }}</label>
                            <div class="input-group">
                                <input type="text" name="location" id="location" value="{{ old('location', $ticket->location) }}" class="form-control @error('location') is-invalid @enderror">
                                <a href="#" id="view-map-link" target="_blank" class="btn btn-outline-secondary" style="display: none;" title="{{ __('Lihat di Google Maps') }}">
                                    <i class="fa-solid fa-map-location-dot"></i>
                                </a>
                            </div>
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-4">
                        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">{{ __('Batal') }}</a>
                        <button type="submit" class="btn btn-primary px-4">{{ __('Perbarui Tiket') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const locationInput = document.getElementById('location');
        const mapLink = document.getElementById('view-map-link');

        function updateMapLink() {
            const val = locationInput.value;
            if (val && mapLink) {
                mapLink.href = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(val)}`;
                mapLink.style.display = 'inline-block';
            } else if (mapLink) {
                mapLink.style.display = 'none';
            }
        }

        // Cek awal
        updateMapLink();

        // Saat input berubah
        locationInput.addEventListener('input', updateMapLink);
    });
</script>
@endsection
