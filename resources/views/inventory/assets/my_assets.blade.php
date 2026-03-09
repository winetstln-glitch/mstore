@extends('layouts.app')

@section('content')
<div class="container-fluid inventory-my-assets-page py-2 py-md-3">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-body">
            <i class="fa-solid fa-toolbox me-2"></i> {{ __('My Assets & Tools') }}
        </h1>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4 inventory-my-assets-panel">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Items in My Custody') }}</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        {{ __('These are the assets currently assigned to you. You are responsible for their condition and safety.') }}
                    </div>

                    <div class="table-responsive table-responsive-mobile">
                        <table class="table table-bordered table-hover align-middle mb-0 table-responsive-mobile" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>{{ __('Item Name') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Serial Number') }}</th>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Assignment Note') }}</th>
                                    <th class="text-end">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($myAssets as $asset)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $asset->item->name }}</div>
                                            <div class="small text-muted">{{ $asset->item->brand }} {{ $asset->item->model }}</div>
                                        </td>
                                        <td><span class="badge bg-secondary">{{ ucfirst($asset->item->category) }}</span></td>
                                        <td>
                                            <div class="fw-bold text-primary">{{ $asset->serial_number }}</div>
                                        </td>
                                        <td>{{ $asset->asset_code ?: '-' }}</td>
                                        <td>
                                            @if($asset->status == 'deployed')
                                                <span class="badge bg-success">{{ __('Active / In Use') }}</span>
                                            @else
                                                <span class="badge bg-warning">{{ ucfirst($asset->status) }}</span>
                                            @endif
                                            <div class="small text-muted mt-1">{{ __('Condition:') }} {{ ucfirst($asset->condition) }}</div>
                                        </td>
                                        <td>
                                            @if(isset($asset->meta_data['assignment_note']))
                                                <i class="fa-solid fa-quote-left text-muted small me-1"></i>
                                                {{ $asset->meta_data['assignment_note'] }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <form action="{{ route('inventory.assets.return', $asset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to return this asset?') }}')">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning">
                                                    <i class="fa-solid fa-rotate-left me-1"></i> {{ __('Return') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-toolbox fa-3x mb-3 opacity-25"></i>
                                            <p class="mb-0">{{ __('You currently have no assets assigned to you.') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('styles')
<style>
    .inventory-my-assets-page .inventory-my-assets-panel {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid rgba(59, 130, 246, 0.15);
        border-radius: 1.2rem;
        overflow: hidden;
    }

    .inventory-my-assets-page .inventory-my-assets-panel .card-header {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0.03) 100%);
        border-bottom: 1px solid rgba(59, 130, 246, 0.18);
    }

    .inventory-my-assets-page table thead th {
        background: rgba(148, 163, 184, 0.12);
    }

    [data-bs-theme="dark"] .inventory-my-assets-page .inventory-my-assets-panel {
        background: linear-gradient(180deg, #0f172a 0%, #0b1228 100%);
        border-color: rgba(96, 165, 250, 0.28);
    }

    [data-bs-theme="dark"] .inventory-my-assets-page .inventory-my-assets-panel .card-header {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.22) 0%, rgba(15, 23, 42, 0.3) 100%);
        border-bottom-color: rgba(96, 165, 250, 0.28);
    }

    [data-bs-theme="dark"] .inventory-my-assets-page table thead th {
        background: rgba(51, 65, 85, 0.5);
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] .inventory-my-assets-page table td {
        border-color: #334155;
    }

    @media (max-width: 767.98px) {
        .inventory-my-assets-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
        }

        .inventory-my-assets-page .inventory-my-assets-panel {
            border-radius: 1rem;
        }

        .inventory-my-assets-page .table-responsive-mobile td {
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            gap: 0.35rem;
            padding-top: 0.65rem;
            padding-bottom: 0.65rem;
        }

        .inventory-my-assets-page .table-responsive-mobile td:before {
            max-width: 100%;
            margin-right: 0;
            margin-bottom: 0.2rem;
            font-size: 0.7rem;
            letter-spacing: 0.03em;
        }

        .inventory-my-assets-page .table-responsive-mobile td.text-end {
            align-items: stretch;
        }

        .inventory-my-assets-page .table-responsive-mobile td .btn {
            width: 100%;
            min-height: 38px;
            border-radius: 0.75rem;
        }
    }
</style>
@endpush
@endsection
