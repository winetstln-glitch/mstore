@extends('layouts.app')

@section('title', __('Customer Management'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold text-dark">{{ __('Customer Management') }}</h5>
                <div class="d-flex flex-wrap gap-2">
                    @can('customer.delete')
                    <button type="button" class="btn btn-danger btn-sm d-none" id="bulkDeleteBtn" onclick="confirmBulkDelete()">
                        <i class="fa-solid fa-trash me-1"></i> {{ __('Delete Selected') }} (<span id="selectedCount">0</span>)
                    </button>
                    <form id="bulkDeleteForm" action="{{ route('customers.bulkDestroy') }}" method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                    @endcan
                    @can('customer.view')
                    @if(Auth::user()->hasRole('admin'))
                        <a href="{{ route('customers.export', request()->only(['search', 'status'])) }}" class="btn btn-outline-secondary btn-sm me-2">
                            <i class="fa-solid fa-file-export me-1"></i> {{ __('Export Customers') }}
                        </a>
                    @endif
                    @endcan
                    @can('customer.create')
                    @if(Auth::user()->hasRole('admin'))
                        <button type="button" class="btn btn-outline-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#importCustomersModal">
                            <i class="fa-solid fa-file-import me-1"></i> {{ __('Import Customers') }}
                        </button>
                    @endif
                    <a href="{{ route('customers.import') }}" class="btn btn-outline-success btn-sm me-2">
                        <i class="fa-solid fa-cloud-arrow-down me-1"></i> {{ __('Import from GenieACS') }}
                    </a>
                    <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus me-1"></i> {{ __('Add Customer') }}
                    </a>
                    @endcan
                </div>
            </div>
            
            <div class="card-body">
                <!-- Search and Filter -->
                <form method="GET" action="{{ route('customers.index') }}" class="row g-3 mb-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="{{ __('Search name, phone, or address...') }}">
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <select name="status" class="form-select">
                            <option value="">{{ __('All Status') }}</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                            <option value="suspend" {{ request('status') == 'suspend' ? 'selected' : '' }}>{{ __('Suspend') }}</option>
                            <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>{{ __('Terminated') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark w-100">{{ __('Filter') }}</button>
                    </div>
                </form>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
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
                                <th scope="col">{{ __('Service Info') }}</th>
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
                                        <div class="fw-bold">{{ $customer->name }}</div>
                                        <div class="small text-muted">
                                            {{ Str::limit($customer->address, 30) }}
                                            @if($customer->latitude && $customer->longitude)
                                                <a href="https://www.google.com/maps/search/?api=1&query={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank" class="text-danger ms-1" title="{{ __('View on Google Maps') }}">
                                                    <i class="fa-solid fa-map-location-dot"></i>
                                                </a>
                                            @elseif($customer->address)
                                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($customer->address) }}" target="_blank" class="text-secondary ms-1" title="{{ __('Search on Google Maps') }}">
                                                    <i class="fa-solid fa-map-location-dot"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $customer->phone }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $customer->package }}</div>
                                        <div class="small text-muted">{{ $customer->ip_address }}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2">{{ $customer->onu_serial ?? '-' }}</span>
                                            @if($customer->onu_serial)
                                                <a href="{{ route('customers.settings', $customer->id) }}" class="btn btn-sm btn-outline-secondary py-0 px-1" title="{{ __('Check Status') }}">
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
                                        <div class="btn-group">
                                            @can('customer.view')
                                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View') }}">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            @endcan
                                            @can('customer.edit')
                                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            @endcan
                                            @can('customer.delete')
                                            <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this customer?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger ms-1" title="{{ __('Delete') }}">
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

                <!-- Pagination (if using standard Laravel pagination, check provider) -->
                @if($customers instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-4">
                    {{ $customers->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@can('customer.create')
@if(Auth::user()->hasRole('admin'))
<!-- Import Customers Modal -->
<div class="modal fade" id="importCustomersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Import Customers') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="importCustomersForm" action="{{ route('customers.importFile') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="mb-3">
            <label class="form-label">{{ __('Select File (.xlsx, .csv)') }}</label>
            <input type="file" name="file" class="form-control" accept=".xlsx,.csv" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" form="importCustomersForm" class="btn btn-success btn-sm">{{ __('Import') }}</button>
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
