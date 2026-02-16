@extends('layouts.app')
@section('title', 'Saldo Awal - '.$period->name)
@section('content')
<div class="container py-3">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Saldo Awal - {{ $period->name }}</h5>
            <a href="{{ route('accounting.periods.index') }}" class="btn btn-sm btn-secondary">Kembali</a>
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            <form method="post" action="{{ route('accounting.periods.opening.post', $period) }}">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
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
                                    <select name="rows[{{ $i }}][account_id]" class="form-select">
                                        <option value="">-- Pilih Akun --</option>
                                        @foreach($accounts as $a)
                                        <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="rows[{{ $i }}][debit]" class="form-control" /></td>
                                <td><input type="number" step="0.01" name="rows[{{ $i }}][credit]" class="form-control" /></td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-primary">Posting Saldo Awal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
