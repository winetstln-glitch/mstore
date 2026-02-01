@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fa-solid fa-toolbox me-2"></i> {{ __('My Assets & Tools') }}
        </h1>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Items in My Custody') }}</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        {{ __('These are the assets currently assigned to you. You are responsible for their condition and safety.') }}
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0">
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
                                                <button type="submit" class="btn btn-sm btn-outline-warning">
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
@endsection
