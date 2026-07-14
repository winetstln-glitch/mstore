@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex align-items-center">
            <h5 class="mb-0">{{ __('Portal MixRADIUS') }}</h5>
            <a href="{{ $mixradiusUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary ms-auto">
                <i class="fa-solid fa-up-right-from-square"></i> {{ __('Buka di Tab Baru') }}
            </a>
        </div>
        <div class="card-body p-0" style="height: calc(100vh - 220px);">
            <iframe src="{{ $mixradiusUrl }}/" title="MixRADIUS Portal" style="width:100%; height:100%; border:0;" referrerpolicy="no-referrer" sandbox="allow-forms allow-same-origin allow-scripts allow-popups allow-popups-to-escape-sandbox"></iframe>
        </div>
    </div>
</div>
@endsection
