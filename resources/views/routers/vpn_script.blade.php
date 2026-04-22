@extends('layouts.app')

@section('title', 'Script Router VPN')

@section('content')
<div class="container py-3">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Script VPN untuk {{ $router->name }}</strong>
                <span class="badge bg-secondary ms-2 text-uppercase">{{ $protocol }}</span>
            </div>
            <div>
                <a href="{{ route('routers.show', $router) }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Server</label>
                <div class="form-control" disabled>
                    {{ $account->server->name }} ({{ $account->server->ip_public }})
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="form-control" disabled>{{ $account->username }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Script</label>
                <textarea class="form-control" rows="10" id="scriptText" readonly>{{ $script }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" onclick="copyScript()">Salin Script</button>
                <a class="btn btn-outline-primary" href="{{ route('routers.vpn.script', [$router, 'protocol'=>'l2tp']) }}">L2TP</a>
                <a class="btn btn-outline-primary" href="{{ route('routers.vpn.script', [$router, 'protocol'=>'pptp']) }}">PPTP</a>
                <a class="btn btn-outline-primary" href="{{ route('routers.vpn.script', [$router, 'protocol'=>'sstp']) }}">SSTP</a>
                <a class="btn btn-outline-primary" href="{{ route('routers.vpn.script', [$router, 'protocol'=>'openvpn']) }}">OpenVPN</a>
            </div>
            <div class="mt-3 text-muted">
                Tempelkan script di Winbox Terminal. Pastikan interface muncul dan status R.
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyScript(){
    var el=document.getElementById('scriptText');
    el.select();
    document.execCommand('copy');
    if(window.Swal){Swal.fire({icon:'success',title:'Tersalin','text':'Script berhasil disalin'});}else{alert('Script berhasil disalin');}
}
</script>
@endpush
