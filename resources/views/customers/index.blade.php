@extends('layouts.app')

@section('title', __('Customer Management'))

@section('content')
<style>
    /* Mobile specific tweaks */
    @media (max-width: 767.98px) {
        /* Ensure modal inputs are large enough */
        .form-control, .form-select {
            font-size: 16px; /* Prevents iOS zoom on focus */
        }
    }
</style>

<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h4 class="fw-bold text-primary mb-1">{{ __('Customer Management') }}</h4>
            <p class="text-muted small mb-0">{{ __('Manage your customers, services, and devices.') }}</p>
        </div>
        
        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-md-end align-items-center mobile-btns">
            @can('customer.delete')
            <button type="button" class="btn btn-danger w-100 w-md-auto d-none" id="bulkDeleteBtn" onclick="confirmBulkDelete()">
                <i class="fa-solid fa-trash me-1"></i> <span class="d-none d-sm-inline">{{ __('Delete Selected') }}</span> (<span id="selectedCount">0</span>)
            </button>
            <form id="bulkDeleteForm" action="{{ route('customers.bulkDestroy') }}" method="POST" class="d-none">
                @csrf
                @method('DELETE')
            </form>
            @endcan
            
            @can('customer.view')
            @if(Auth::user()->hasRole('admin'))
                <a href="{{ route('customers.export', request()->only(['search', 'status'])) }}" class="btn btn-outline-secondary" title="{{ __('Export Customers') }}">
                    <i class="fa-solid fa-file-export me-1"></i> <span class="d-none d-sm-inline">{{ __('Export') }}</span>
                </a>
            @endif
            @endcan
            
            @can('customer.create')
            @if(Auth::user()->hasRole('admin'))
                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importCustomersModal" title="{{ __('Import Customers') }}">
                    <i class="fa-solid fa-file-import me-1"></i> <span class="d-none d-sm-inline">{{ __('Import') }}</span>
                </button>
            @endif
                
                <a href="{{ route('customers.import') }}" class="btn btn-outline-success" title="{{ __('Import from GenieACS') }}">
                    <i class="fa-solid fa-cloud-arrow-down me-1"></i> <span class="d-none d-sm-inline">{{ __('Genie') }}</span>
                </a>
                
                <a href="{{ route('customers.create') }}" class="btn btn-primary flex-grow-0" title="{{ __('Add Customer') }}">
                    <i class="fa-solid fa-plus me-1"></i> <span class="d-none d-sm-inline">{{ __('Add Customer') }}</span>
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <!-- Search and Filter: Optimized for Mobile (2 columns on small screens) -->
                <form method="GET" action="{{ route('customers.index') }}" class="row g-2 g-md-3 mb-4">
                    <!-- Search: Full width on mobile for easy typing -->
                    <div class="col-12 col-md-4 col-lg-3">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="{{ __('Search...') }}">
                        </div>
                    </div>
                    
                    <!-- Status: Half width on mobile -->
                    <div class="col-6 col-md-3 col-lg-2">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">{{ __('Status') }}</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                            <option value="suspend" {{ request('status') == 'suspend' ? 'selected' : '' }}>{{ __('Suspend') }}</option>
                            <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>{{ __('Terminated') }}</option>
                        </select>
                    </div>
                    
                    <!-- Linked to User -->
                    <div class="col-6 col-md-3 col-lg-2">
                        <select name="linked" class="form-select" onchange="this.form.submit()">
                            <option value="">{{ __('User Link') }}</option>
                            <option value="yes" {{ request('linked') === 'yes' ? 'selected' : '' }}>{{ __('Linked') }}</option>
                            <option value="no" {{ request('linked') === 'no' ? 'selected' : '' }}>{{ __('Unlinked') }}</option>
                        </select>
                    </div>
                    
                    <!-- HTB: Half width on mobile -->
                    <div class="col-6 col-md-3 col-lg-2">
                        <select name="htb_id" class="form-select" onchange="this.form.submit()">
                            <option value="">{{ __('HTB') }}</option>
                            @foreach($htbs as $htb)
                                <option value="{{ $htb->id }}" {{ request('htb_id') == $htb->id ? 'selected' : '' }}>{{ $htb->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Per Page: Half width on mobile -->
                    <div class="col-6 col-md-2 col-lg-2">
                        <select name="per_page" class="form-select" onchange="this.form.submit()">
                            <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                            <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                            <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>All</option>
                        </select>
                    </div>
                    
                    <!-- Filter Button: Half width on mobile (touches right side easily) -->
                    <div class="col-6 col-md-2 col-lg-3">
                        <button type="submit" class="btn btn-dark w-100">
                            <i class="fa-solid fa-filter me-1 d-md-none"></i> {{ __('Filter') }}
                        </button>
                    </div>
                </form>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-responsive-mobile"> 
                        <thead class="table-light">
                            <tr>
                                @can('customer.delete')
                                <th scope="col" style="width: 40px;" class="ps-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                @endcan
                                <th scope="col" class="@cannot('customer.delete') ps-3 @endcannot">{{ __('Name') }}</th>
                                <th scope="col">{{ __('Contact') }}</th>
                                <th scope="col">{{ __('User') }}</th>
                                <th scope="col">{{ __('Service') }}</th>
                                <th scope="col">{{ __('Modem') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col" class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                                <tr>
                                    @can('customer.delete')
                                    <td class="ps-3">
                                        <div class="form-check">
                                            <input class="form-check-input customer-checkbox" type="checkbox" value="{{ $customer->id }}">
                                        </div>
                                    </td>
                                    @endcan
                                    <td class="@cannot('customer.delete') ps-3 @endcannot">
                                        <div class="fw-bold text-truncate" style="max-width: 150px;">{{ $customer->name }}</div>
                                        <div class="small text-muted d-flex align-items-center">
                                            <span class="text-truncate me-1" style="max-width: 120px;">{{ Str::limit($customer->address, 20) }}</span>
                                            @if($customer->latitude && $customer->longitude)
                                                <a href="https://www.google.com/maps/search/?api=1&query={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank" class="text-danger" title="{{ __('View on Google Maps') }}">
                                                    <i class="fa-solid fa-map-location-dot"></i>
                                                </a>
                                            @elseif($customer->address)
                                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($customer->address) }}" target="_blank" class="text-secondary" title="{{ __('Search on Google Maps') }}">
                                                    <i class="fa-solid fa-map-location-dot"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 100px;">{{ $customer->phone }}</div>
                                    </td>
                                    <td>
                                        @if($customer->user)
                                            <div class="small text-truncate" style="max-width: 150px;">
                                                {{ $customer->user->name }}
                                                @if($customer->user->username) ({{ $customer->user->username }}) @endif
                                            </div>
                                            <div class="text-muted small text-truncate" style="max-width: 150px;">{{ $customer->user->email }}</div>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">{{ __('Unlinked') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 100px;">{{ $customer->package }}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 100px;">{{ $customer->ip_address }}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="me-2 text-truncate" style="max-width: 80px;">{{ $customer->onu_serial ?? '-' }}</span>
                                            @if($customer->onu_serial)
                                                <a href="{{ route('customers.settings', $customer->id) }}" class="btn btn-sm btn-outline-secondary py-0 px-2" title="{{ __('Check Status') }}">
                                                    <i class="fa-solid fa-stethoscope"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($customer->status === 'active')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Active') }}</span>
                                        @elseif($customer->status === 'suspend')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ __('Suspend') }}</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ ucfirst($customer->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end gap-1">
                                            @can('customer.view')
                                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View') }}">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            @endcan
                                            @can('customer.edit')
                                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            @if($customer->phone && $customer->user)
                                            @php
                                                $waText = 'Halo ' . $customer->name . "\nAkses Portal Pelanggan: " . route('login') . "\nUsername: " . ($customer->user->username ?: $customer->user->email) . "\nJika lupa sandi, gunakan fitur Lupa Password di halaman login.";
                                            @endphp
                                            <a href="https://wa.me/{{ preg_replace('/\D+/', '', $customer->phone) }}?text={{ rawurlencode($waText) }}" target="_blank" class="btn btn-sm btn-outline-success" title="{{ __('Send Portal Instructions') }}">
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </a>
                                            @endif
                                            @endcan
                                            @can('customer.delete')
                                            <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this customer?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-body-secondary">
                                        <div class="mb-2"><i class="fa-solid fa-users-slash fa-2x opacity-25"></i></div>
                                        {{ __('No customers found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($customers instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-4 d-flex justify-content-center">
                    {{ $customers->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@can('customer.create')
@if(Auth::user()->hasRole('admin'))
<!-- Import Customers Modal: Fullscreen on mobile -->
<div class="modal fade" id="importCustomersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Import Customers') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="importCustomersForm" action="{{ route('customers.importFile') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="mb-3">
            <label class="form-label fw-bold">{{ __('Select File (.xlsx, .csv)') }}</label>
            <div class="form-text mb-2">{{ __('Make sure your file follows the required template format.') }}</div>
            <input type="file" name="file" class="form-control form-control-lg" accept=".xlsx,.csv" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light text-muted w-100 w-md-auto" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" form="importCustomersForm" class="btn btn-success w-100 w-md-auto">{{ __('Import Data') }}</button>
      </div>
    </div>
  </div>
</div>
@endif
@endcan

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.customer-checkbox');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const selectedCount = document.getElementById('selectedCount');

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateBulkDeleteBtn();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkDeleteBtn);
        });

        function updateBulkDeleteBtn() {
            const selected = document.querySelectorAll('.customer-checkbox:checked');
            selectedCount.textContent = selected.length;
            
            if (selected.length > 0) {
                bulkDeleteBtn.classList.remove('d-none');
            } else {
                bulkDeleteBtn.classList.add('d-none');
            }
        }

        window.confirmBulkDelete = function() {
            const selected = document.querySelectorAll('.customer-checkbox:checked');
            if (selected.length === 0) return;

            if (confirm('{{ __("Are you sure you want to delete selected customers?") }}')) {
                const ids = Array.from(selected).map(cb => cb.value);
                
                const form = document.getElementById('bulkDeleteForm');
                // Remove old hidden inputs if any (except token and method)
                const oldInputs = form.querySelectorAll('input[name="ids[]"]');
                oldInputs.forEach(input => input.remove());
                
                // Add new hidden inputs
                ids.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    form.appendChild(input);
                });
                
                form.submit();
            }
        };
    });
</script>
@endsection
