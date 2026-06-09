@extends('layouts.app')

@section('title', 'Security & Monitoring')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">Security & Monitoring</h4>
            <div class="text-muted small">Audit, Rate Limit, Queue, Failed Jobs</div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">Audit Log (50 terakhir)</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Model</th></tr></thead>
                            <tbody>
                                @forelse($auditLogs as $log)
                                    <tr>
                                        <td>{{ $log->created_at?->format('d M H:i') }}</td>
                                        <td>{{ $log->user?->name ?? '-' }}</td>
                                        <td>{{ $log->action }}</td>
                                        <td>{{ $log->model_type }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">Tidak ada data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">Failed Jobs (50 terakhir)</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Failed At</th><th>Queue</th><th>Exception</th></tr></thead>
                            <tbody>
                                @forelse($failedJobs as $job)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($job->failed_at)->format('d M H:i') }}</td>
                                        <td>{{ $job->queue }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit((string) $job->exception, 140) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted">Tidak ada data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">Failed Notifications (50 terakhir)</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Waktu</th><th>Target</th><th>Category</th><th>Message</th></tr></thead>
                            <tbody>
                                @forelse($failedNotifications as $n)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($n->updated_at)->format('d M H:i') }}</td>
                                        <td>{{ $n->target_phone }}</td>
                                        <td>{{ $n->category }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit((string) $n->message, 120) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">Tidak ada data.</td></tr>
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

