@extends('layouts.app-custom')

@section('title', __('Chit Details'))

@section('header', __('Chit Overview'))

@php
    $chitDetailCssVersion = file_exists(public_path('css/chit-detail.css'))
        ? filemtime(public_path('css/chit-detail.css'))
        : time();

    $statusClassMap = [
        'not_paid' => 'status-not-paid',
        'due' => 'status-due',
        'paid' => 'status-paid',
    ];

    $cardToneClassMap = [
        'not_paid' => 'chit-member-card-not-paid',
        'due' => 'chit-member-card-due',
        'paid' => 'chit-member-card-paid',
    ];
@endphp

@push('styles')
    <link href="{{ asset('css/chit-detail.css') }}?v={{ $chitDetailCssVersion }}" rel="stylesheet">
@endpush

@section('content')
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ __(ucfirst(str_replace('-', ' ', session('status')))) }}
        </div>
    @endif

    <section class="section chit-detail-page">
        <div class="card chit-shell-card mb-3">
            <div class="card-body p-3 p-lg-4">
                <div class="chit-shell-head">
                    <div>
                        <p class="chit-shell-kicker mb-1">Chit #{{ $chit->id }}</p>
                        <h4 class="chit-shell-title mb-1">{{ $chit->plan_label }}</h4>
                        <p class="chit-shell-subtitle mb-0">
                            Managed by {{ $chit->creator?->name ?? 'Admin' }} • Created on {{ $chit->created_at->format('d M Y') }}
                        </p>
                    </div>
                    <a href="{{ route('admin.chits.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back To Chits
                    </a>
                </div>

                <ul class="nav nav-pills chit-toggle-nav mt-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a
                            class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}"
                            href="{{ route('admin.chits.show', ['chit' => $chit->id, 'tab' => 'overview']) }}"
                        >
                            <i class="bi bi-grid me-1"></i>Chit Overview
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a
                            class="nav-link {{ $activeTab === 'payments' ? 'active' : '' }}"
                            href="{{ route('admin.chits.show', ['chit' => $chit->id, 'tab' => 'payments']) }}"
                        >
                            <i class="bi bi-cash-coin me-1"></i>Monthly Payments
                        </a>
                    </li>
                </ul>

                <div class="chit-overview-stats mt-3">
                    <div class="overview-stat-item">
                        <span class="overview-stat-label">Chit Type</span>
                        <strong class="overview-stat-value">{{ $chit->type_label }}</strong>
                    </div>
                    <div class="overview-stat-item">
                        <span class="overview-stat-label">Total Value</span>
                        <strong class="overview-stat-value">Rs {{ number_format((int) $chit->total_amount) }}</strong>
                    </div>
                    <div class="overview-stat-item">
                        <span class="overview-stat-label">Monthly Amount</span>
                        <strong class="overview-stat-value">Rs {{ number_format((int) $chit->monthly_amount) }}</strong>
                    </div>
                    <div class="overview-stat-item">
                        <span class="overview-stat-label">Duration</span>
                        <strong class="overview-stat-value">{{ (int) $chit->duration_months }} Months</strong>
                    </div>
                    <div class="overview-stat-item">
                        <span class="overview-stat-label">Current Month</span>
                        <strong class="overview-stat-value">Month {{ $currentMonth }}</strong>
                    </div>
                    <div class="overview-stat-item">
                        <span class="overview-stat-label">Status</span>
                        <strong class="overview-stat-value">{{ $chit->status_label }}</strong>
                    </div>
                </div>
            </div>
        </div>

        @if ($activeTab === 'overview')
            <div class="card chit-member-filter-card mb-3">
                <div class="card-body py-3">
                    <form method="GET" action="{{ route('admin.chits.show', $chit) }}" class="row g-2 align-items-end">
                        <input type="hidden" name="tab" value="overview">

                        <div class="col-lg-7 col-md-12">
                            <label for="memberSearch" class="form-label mb-1">Search Members</label>
                            <input
                                id="memberSearch"
                                type="text"
                                name="member_search"
                                value="{{ $filters['member_search'] }}"
                                class="form-control"
                                placeholder="Search by name, phone, referred by..."
                            >
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label for="memberStatus" class="form-label mb-1">Payment Status</label>
                            <select id="memberStatus" name="member_status" class="form-select">
                                <option value="all" @selected($filters['member_status'] === 'all')>All</option>
                                @foreach ($statusOptions as $key => $option)
                                    <option value="{{ $key }}" @selected($filters['member_status'] === $key)>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6 d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Apply</button>
                            <a href="{{ route('admin.chits.show', ['chit' => $chit->id, 'tab' => 'overview']) }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3">
                @forelse ($memberCards as $memberCard)
                    @php
                        $slot = $memberCard['slot'];
                        $statusKey = (string) $memberCard['status_key'];
                        $statusClass = $statusClassMap[$statusKey] ?? 'status-not-paid';
                        $cardToneClass = $cardToneClassMap[$statusKey] ?? 'chit-member-card-not-paid';
                    @endphp
                    <div class="col-12 col-md-6 col-xl-4">
                        <article class="card chit-member-card {{ $cardToneClass }} h-100">
                            <div class="card-body">
                                <div class="member-card-head">
                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                        <img
                                            src="{{ $memberCard['member']?->profile_image_url ?? asset('images/default-avatar.svg') }}"
                                            alt="{{ $memberCard['display_name'] }}"
                                            class="member-card-avatar"
                                        >
                                        <div class="min-w-0">
                                            <h6 class="member-card-name mb-0">{{ $memberCard['display_name'] }}</h6>
                                            <p class="member-card-phone mb-0">{{ $memberCard['phone'] }}</p>
                                        </div>
                                    </div>
                                    <span class="member-status-badge {{ $statusClass }}">{{ $memberCard['status_label'] }}</span>
                                </div>

                                <div class="member-card-grid mt-3">
                                    <div>
                                        <span class="member-grid-label">Referred By</span>
                                        <p class="member-grid-value">{{ $memberCard['referred_by'] }}</p>
                                    </div>
                                    <div>
                                        <span class="member-grid-label">Monthly Amount</span>
                                        <p class="member-grid-value">Rs {{ number_format((int) $memberCard['expected_amount']) }}</p>
                                    </div>
                                    <div>
                                        <span class="member-grid-label">Due Amount</span>
                                        <p class="member-grid-value text-danger">Rs {{ number_format((int) $memberCard['due_amount']) }}</p>
                                    </div>
                                    <div>
                                        <span class="member-grid-label">Extra Paid</span>
                                        <p class="member-grid-value text-success">Rs {{ number_format((int) $memberCard['extra_paid_amount']) }}</p>
                                    </div>
                                </div>

                                <div class="member-card-actions mt-3">
                                    <a
                                        href="{{ route('admin.chits.members.show', [$chit, $slot]) }}"
                                        class="member-action-btn"
                                        title="View More"
                                        aria-label="View More"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a
                                        href="{{ route('admin.chits.members.show', [$chit, $slot]) }}#member-edit-section"
                                        class="member-action-btn"
                                        title="Edit"
                                        aria-label="Edit"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.chits.members.destroy', [$chit, $slot]) }}" onsubmit="return confirm('Delete this member slot from chit?')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="member-action-btn member-action-danger"
                                            title="Delete"
                                            aria-label="Delete"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body py-5 text-center text-muted">
                                No members match the current filters for this chit.
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        @else
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <article class="card payment-summary-card payment-summary-not-paid">
                        <div class="card-body">
                            <span class="summary-label">Not Paid</span>
                            <p class="summary-value text-danger mb-0">{{ $statusSummary['not_paid'] }}</p>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-md-4">
                    <article class="card payment-summary-card payment-summary-due">
                        <div class="card-body">
                            <span class="summary-label">Due</span>
                            <p class="summary-value text-warning mb-0">{{ $statusSummary['due'] }}</p>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-md-4">
                    <article class="card payment-summary-card payment-summary-paid">
                        <div class="card-body">
                            <span class="summary-label">Paid</span>
                            <p class="summary-value text-success mb-0">{{ $statusSummary['paid'] }}</p>
                        </div>
                    </article>
                </div>
            </div>

            <div class="card payment-list-card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Month {{ $currentMonth }} Payment Snapshot</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Due</th>
                                    <th>Extra Paid</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($allMemberCards as $memberCard)
                                    @php
                                        $slot = $memberCard['slot'];
                                        $statusKey = (string) $memberCard['status_key'];
                                        $statusClass = $statusClassMap[$statusKey] ?? 'status-not-paid';
                                    @endphp
                                    <tr>
                                        <td>{{ $memberCard['display_name'] }}</td>
                                        <td>{{ $memberCard['phone'] }}</td>
                                        <td><span class="member-status-badge {{ $statusClass }}">{{ $memberCard['status_label'] }}</span></td>
                                        <td>Rs {{ number_format((int) $memberCard['due_amount']) }}</td>
                                        <td>Rs {{ number_format((int) $memberCard['extra_paid_amount']) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.chits.members.show', [$chit, $slot]) }}" class="btn btn-sm btn-outline-primary">
                                                Open
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No member slots are available for this chit.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </section>
@endsection
