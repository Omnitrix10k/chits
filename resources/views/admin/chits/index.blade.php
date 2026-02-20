@extends('layouts.app-custom')

@section('title', __('Chits'))

@section('header', __('Chits'))

@php
    $chitsCssVersion = file_exists(public_path('css/chits.css'))
        ? filemtime(public_path('css/chits.css'))
        : time();
@endphp

@push('styles')
    <link href="{{ asset('css/chits.css') }}?v={{ $chitsCssVersion }}" rel="stylesheet">
@endpush

@section('content')
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ __(ucfirst(str_replace('-', ' ', session('status')))) }}
        </div>
    @endif

    <section class="section chits-page">
        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="card chit-overview-card">
                    <div class="card-body p-3 p-lg-4">
                        <div class="chit-overview-head">
                            <div class="chit-overview-copy">
                                <p class="chit-overview-kicker mb-1">Chit Management</p>
                                <h5 class="chit-overview-title mb-1">All Chits</h5>
                                <p class="chit-overview-subtitle mb-0">
                                    Create new chits, filter active cycles, and monitor slot utilization from a single workspace.
                                </p>
                            </div>
                            <a href="{{ route('admin.chits.create') }}" class="btn btn-primary chit-overview-cta">
                                <i class="bi bi-plus-circle me-1"></i>Create Chit
                            </a>
                        </div>

                        <div class="chit-overview-metrics">
                            <div class="chit-overview-metric">
                                <span class="metric-label">Total Chits</span>
                                <strong class="metric-value">{{ $totalChits }}</strong>
                            </div>
                            <div class="chit-overview-metric">
                                <span class="metric-label">Visible Results</span>
                                <strong class="metric-value">{{ $chits->total() }}</strong>
                            </div>
                            <div class="chit-overview-metric">
                                <span class="metric-label">Slot Rule</span>
                                <strong class="metric-value">Dynamic Members</strong>
                            </div>
                            <div class="chit-overview-metric">
                                <span class="metric-label">Repeat Limit</span>
                                <strong class="metric-value">9 per Member</strong>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('admin.chits.index') }}" class="row g-2 g-lg-3 align-items-end chit-filter-grid">
                            <div class="col-xl-5 col-lg-12">
                                <label for="chitSearch" class="form-label mb-1">Search</label>
                                <input
                                    id="chitSearch"
                                    type="text"
                                    name="search"
                                    value="{{ $filters['search'] }}"
                                    class="form-control"
                                    placeholder="Search by chit id, plan, or type..."
                                >
                            </div>

                            <div class="col-xl-2 col-lg-4 col-md-6">
                                <label for="chitPlanFilter" class="form-label mb-1">Plan</label>
                                <select id="chitPlanFilter" name="plan" class="form-select">
                                    <option value="all" @selected($filters['plan'] === 'all')>All</option>
                                    @foreach ($planOptions as $planKey => $plan)
                                        <option value="{{ $planKey }}" @selected($filters['plan'] === $planKey)>{{ $plan['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xl-2 col-lg-4 col-md-6">
                                <label for="chitTypeFilter" class="form-label mb-1">Type</label>
                                <select id="chitTypeFilter" name="type" class="form-select">
                                    <option value="all" @selected($filters['type'] === 'all')>All</option>
                                    @foreach ($typeOptions as $typeKey => $typeLabel)
                                        <option value="{{ $typeKey }}" @selected($filters['type'] === $typeKey)>{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xl-3 col-lg-4 col-md-12">
                                <div class="chit-filter-actions">
                                    <button type="submit" class="btn btn-primary">Apply</button>
                                    <a href="{{ route('admin.chits.index') }}" class="btn btn-outline-secondary">Reset</a>
                                </div>
                            </div>
                        </form>

                        <div class="chit-overview-rules">
                            <div class="chit-rule-item">
                                <i class="bi bi-people"></i>
                                <span>Each chit must have exactly the member slot count configured at creation.</span>
                            </div>
                            <div class="chit-rule-item">
                                <i class="bi bi-arrow-repeat"></i>
                                <span>A member can be repeated at most 9 times in a single chit.</span>
                            </div>
                            <div class="chit-rule-item">
                                <i class="bi bi-calculator"></i>
                                <span>Monthly amount is auto-calculated as total value divided by total members.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            @forelse ($chits as $chit)
                @php
                    $assignedSlots = (int) ($chit->assigned_slots ?? 0);
                    $uniqueMembers = (int) ($chit->unique_members_count ?? 0);
                    $memberLimit = (int) $chit->member_limit;
                    $fillPercentage = $memberLimit > 0
                        ? min(100, (int) round(($assignedSlots / $memberLimit) * 100))
                        : 0;
                @endphp

                <div class="col-12 col-md-6 col-xl-4">
                    <article
                        class="card chit-card h-100 chit-card-clickable"
                        data-href="{{ route('admin.chits.show', $chit) }}"
                        role="link"
                        tabindex="0"
                        aria-label="Open {{ $chit->plan_label }} details"
                    >
                        <div class="card-body">
                            <div class="chit-card-head">
                                <div class="chit-card-head-left">
                                    <p class="chit-id-text">Chit #{{ $chit->id }}</p>
                                    <h5 class="chit-card-title mb-0">{{ $chit->plan_label }}</h5>
                                </div>
                                <div class="chit-card-head-right">
                                    <span class="badge bg-primary-subtle text-primary-emphasis chit-type-badge">{{ $chit->type_label }}</span>
                                    <span class="chit-status-chip">{{ $chit->status_label }}</span>
                                </div>
                            </div>

                            <div class="chit-stats-grid">
                                <div class="chit-stat-box">
                                    <span class="chit-stat-label">Total Value</span>
                                    <p class="chit-stat-value">Rs {{ number_format((int) $chit->total_amount) }}</p>
                                </div>
                                <div class="chit-stat-box">
                                    <span class="chit-stat-label">Monthly Amount</span>
                                    <p class="chit-stat-value">Rs {{ number_format((int) $chit->monthly_amount) }}</p>
                                </div>
                                <div class="chit-stat-box">
                                    <span class="chit-stat-label">Duration</span>
                                    <p class="chit-stat-value">{{ (int) $chit->duration_months }} months</p>
                                </div>
                                <div class="chit-stat-box">
                                    <span class="chit-stat-label">Members</span>
                                    <p class="chit-stat-value">{{ $assignedSlots }}/{{ $memberLimit }} slots</p>
                                </div>
                            </div>

                            <div class="chit-progress-wrap">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="chit-progress-label">Slot Utilization</span>
                                    <span class="chit-progress-value">{{ $fillPercentage }}%</span>
                                </div>
                                <div class="progress chit-progress-bar">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $fillPercentage }}%" aria-valuenow="{{ $fillPercentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>

                            <div class="chit-card-footer mt-3">
                                <div class="chit-meta-line">
                                    <span class="chit-meta-label">Unique Members</span>
                                    <strong class="chit-meta-value">{{ $uniqueMembers }}</strong>
                                </div>
                                <div class="chit-meta-line">
                                    <span class="chit-meta-label">Created By</span>
                                    <strong class="chit-meta-value">{{ $chit->creator?->name ?? 'Admin' }}</strong>
                                </div>
                                <div class="chit-meta-line">
                                    <span class="chit-meta-label">Created On</span>
                                    <strong class="chit-meta-value">{{ $chit->created_at->format('d M Y') }}</strong>
                                </div>
                                <div class="chit-card-open-row">
                                    <span class="chit-card-open-chip">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Open Chit
                                    </span>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body py-5 text-center text-muted">
                            No chits found. Create your first chit to start collections.
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($chits->hasPages())
            <div class="members-pagination-wrap mt-3">
                <p class="small text-muted mb-0">
                    Showing {{ $chits->firstItem() }} to {{ $chits->lastItem() }} of {{ $chits->total() }} chits
                </p>
                {{ $chits->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cards = Array.from(document.querySelectorAll('.chit-card-clickable'));
            cards.forEach(function (card) {
                const href = card.dataset.href;
                if (!href) {
                    return;
                }

                card.addEventListener('click', function (event) {
                    if (event.target.closest('a, button, form, input, select, textarea, label')) {
                        return;
                    }
                    window.location.href = href;
                });

                card.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        window.location.href = href;
                    }
                });
            });
        });
    </script>
@endpush
