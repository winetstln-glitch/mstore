@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Installation Details #{{ $installation->id }}</h5>
                    <div class="btn-group">
                        <a href="{{ route('installations.edit', $installation) }}" class="btn btn-warning btn-sm text-white">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </a>
                        <a href="{{ route('installations.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Customer Information</h6>
                            <div class="p-3 bg-light rounded border dark:bg-dark dark:border-secondary">
                                <p class="mb-2">
                                    <span class="fw-bold">Name:</span> 
                                    <a href="{{ route('customers.show', $installation->customer) }}" class="text-decoration-none">
                                        {{ $installation->customer->name }}
                                    </a>
                                </p>
                                <p class="mb-2">
                                    <span class="fw-bold">Address:</span> 
                                    {{ $installation->customer->address }}
                                </p>
                                <p class="mb-2">
                                    <span class="fw-bold">Phone:</span> 
                                    {{ $installation->customer->phone }}
                                </p>
                                <p class="mb-0">
                                    <span class="fw-bold">Package:</span> 
                                    {{ $installation->customer->package }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Installation Status</h6>
                            <div class="p-3 bg-light rounded border dark:bg-dark dark:border-secondary">
                                <p class="mb-2">
                                    <span class="fw-bold">Status:</span> 
                                    <span class="badge 
                                        @if($installation->status === 'completed') text-bg-success 
                                        @elseif($installation->status === 'cancelled') text-bg-danger 
                                        @elseif($installation->status === 'installation') text-bg-primary
                                        @elseif($installation->status === 'survey') text-bg-warning
                                        @else text-bg-secondary @endif">
                                        {{ ucfirst($installation->status) }}
                                    </span>
                                </p>
                                <p class="mb-2">
                                    <span class="fw-bold">Plan Date:</span> 
                                    {{ $installation->plan_date ? $installation->plan_date->format('Y-m-d') : 'Not Set' }}
                                </p>
                                <p class="mb-2">
                                    <span class="fw-bold">Technician:</span> 
                                    {{ $installation->technician ? $installation->technician->name : 'Unassigned' }}
                                </p>
                                <p class="mb-0">
                                    <span class="fw-bold">Coordinates:</span> 
                                    {{ $installation->coordinates ?? 'Not Set' }}
                                </p>
                            </div>
                        </div>

                        <div class="col-12">
                            <h6 class="fw-bold mb-3">Notes</h6>
                            <div class="p-3 bg-light rounded border dark:bg-dark dark:border-secondary">
                                <p class="mb-0 text-break" style="white-space: pre-line;">{{ $installation->notes ?? 'No notes available.' }}</p>
                            </div>
                        </div>
                        
                        @if($installation->photo_before || $installation->photo_after)
                        <div class="col-12">
                            <h6 class="fw-bold mb-3">Photos</h6>
                            <div class="row g-3">
                                @if($installation->photo_before)
                                <div class="col-md-6">
                                    <p class="fw-bold mb-2">Before:</p>
                                    <img src="{{ asset('storage/' . $installation->photo_before) }}" alt="Before Installation" class="img-fluid rounded shadow-sm border">
                                </div>
                                @endif
                                
                                @if($installation->photo_after)
                                <div class="col-md-6">
                                    <p class="fw-bold mb-2">After:</p>
                                    <img src="{{ asset('storage/' . $installation->photo_after) }}" alt="After Installation" class="img-fluid rounded shadow-sm border">
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
