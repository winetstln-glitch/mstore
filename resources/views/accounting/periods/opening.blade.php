@extends('layouts.app')
@section('title', 'Saldo Awal - '.$period->name)
@section('content')
<div class="container-fluid py-3">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <h5 class="m-0 font-weight-bold text-primary">Saldo Awal - {{ $period->name }}</h5>
            <a href="{{ route('accounting.periods.index') }}" class="btn btn-secondary btn-lg w-100 w-md-auto">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success border-left-success shadow-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger border-left-danger shadow-sm">{{ session('error') }}</div>@endif
            <form method="post" action="{{ route('accounting.periods.opening.post', $period) }}">
                @csrf
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle table-responsive-mobile" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:45%">Akun (Neraca)</th>
                                <th style="width:22%">Debit</th>
                                <th style="width:22%">Kredit</th>
                            </tr>
                        </thead>
                        <tbody id="rows-body">
                            @for($i=0;$i<10;$i++)
                            <tr>
                                <td>
                                    <select name="rows[{{ $i }}][account_id]" class="form-select form-select-lg">
                                        <option value="">-- Pilih Akun --</option>
                                        @foreach($accounts as $a)
                                        <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="rows[{{ $i }}][debit]" class="form-control form-control-lg" placeholder="0" /></td>
                                <td><input type="number" step="0.01" name="rows[{{ $i }}][credit]" class="form-control form-control-lg" placeholder="0" /></td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-primary btn-lg w-100 w-md-auto">
                        <i class="fas fa-save me-1"></i> Posting Saldo Awal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
