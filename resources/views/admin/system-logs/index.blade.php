@extends('layouts.app-custom')

@section('title', 'System Logs')

@section('header', 'System Logs')

@php
    $logsCssVersion = file_exists(public_path('niceadmin/assets/css/goud-logs.css'))
        ? filemtime(public_path('niceadmin/assets/css/goud-logs.css'))
        : time();
@endphp

@push('styles')
    <link href="{{ asset('niceadmin/assets/css/goud-logs.css') }}?v={{ $logsCssVersion }}" rel="stylesheet">
@endpush

@section('content')
    <section class="section">
        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="card logs-toolbar-card">
                    <div class="card-body py-3">
                        <h5 class="logs-toolbar-title">System Activity Logs</h5>
                        <p class="logs-toolbar-subtitle">Tracks admin/editor login, logout, edit, and delete activity.</p>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card logs-filter-card">
                    <div class="card-body py-3">
                        <form method="GET" action="{{ route('admin.system-logs.index') }}" class="row g-2 align-items-end">
                            <div class="col-lg-6 col-md-12">
                                <label for="logSearch" class="form-label mb-1">Search</label>
                                <input
                                    id="logSearch"
                                    type="text"
                                    name="search"
                                    value="{{ $filters['search'] }}"
                                    class="form-control"
                                    placeholder="Search actor, target, email, phone, or IP..."
                                >
                            </div>

                            <div class="col-lg-2 col-md-4">
                                <label for="logAction" class="form-label mb-1">Action</label>
                                <select id="logAction" name="action" class="form-select">
                                    <option value="all" @selected($filters['action'] === 'all')>All</option>
                                    @foreach ($actionLabels as $actionKey => $label)
                                        <option value="{{ $actionKey }}" @selected($filters['action'] === $actionKey)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-4">
                                <label for="logRole" class="form-label mb-1">Role</label>
                                <select id="logRole" name="role" class="form-select">
                                    <option value="all" @selected($filters['role'] === 'all')>All</option>
                                    <option value="admin" @selected($filters['role'] === 'admin')>Admin</option>
                                    <option value="editor" @selected($filters['role'] === 'editor')>Editor</option>
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-4 d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Apply</button>
                                <a href="{{ route('admin.system-logs.index') }}" class="btn btn-light border">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card logs-table-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table logs-table table-hover align-middle">
                        <thead>
                            <tr>
                                <th class="ps-3">Timestamp</th>
                                <th>Actor</th>
                                <th>Action</th>
                                <th>Target</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold">{{ $log->created_at->format('d M Y') }}</div>
                                        <div class="text-muted small">{{ $log->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td>
                                        @php
                                            $actor = $log->actor;
                                            $actorName = $actor?->name ?? ($log->actor_id ? 'User #'.$log->actor_id : 'Unknown');
                                            $actorRole = $actor?->role ?? \App\Models\SystemLog::roleName($log->actor_role_code) ?? 'unknown';
                                        @endphp
                                        <div class="fw-semibold">{{ $actorName }}</div>
                                        <span class="log-role-badge {{ $actorRole === 'admin' ? 'log-role-admin' : 'log-role-editor' }}">
                                            {{ ucfirst($actorRole) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="log-action-badge">{{ \App\Models\SystemLog::actionLabel($log->action_code) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $target = $log->targetUser;
                                        @endphp
                                        @if ($target || $log->target_user_id)
                                            <div class="fw-semibold">{{ $target?->name ?? 'User #'.$log->target_user_id }}</div>
                                            @if ($target?->role)
                                                <div class="text-muted small">{{ ucfirst($target->role) }}</div>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->ip_address ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No logs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if ($logs->hasPages())
            <div class="logs-pagination-wrap">
                <p class="small text-muted mb-0">
                    Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} logs
                </p>
                {{ $logs->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>
@endsection
