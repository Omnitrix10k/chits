@extends('layouts.app-custom')

@section('title', __('Chit Details'))

@section('header', __('Chit Overview'))

@php
    use App\Models\User;

    $chitDetailCssVersion = file_exists(public_path('css/chit-detail.css'))
        ? filemtime(public_path('css/chit-detail.css'))
        : time();
    $canManageMutations = auth()->user()?->role === User::ROLE_ADMIN;

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

    @if ($canManageMutations && $errors->deleteChit->any())
        <div class="alert alert-danger" role="alert">
            Please enter the correct admin password to delete this chit.
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
                    <div class="d-flex flex-wrap gap-2">
                        @if ($canManageMutations)
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteChitModal{{ $chit->id }}">
                                <i class="bi bi-trash me-1"></i>Delete Chit
                            </button>
                        @endif
                        <a href="{{ route('admin.chits.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back To Chits
                        </a>
                    </div>
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

        @if ($canManageMutations)
            @php
                $isDeleteErrorForCurrentChit = (int) old('delete_chit_id') === (int) $chit->id && $errors->deleteChit->has('password');
            @endphp
            <div class="modal fade" id="deleteChitModal{{ $chit->id }}" tabindex="-1" aria-labelledby="deleteChitLabel{{ $chit->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.chits.destroy', $chit) }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="delete_chit_id" value="{{ $chit->id }}">

                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteChitLabel{{ $chit->id }}">Delete {{ $chit->chit_name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <p class="mb-3 text-muted">Enter your admin password to confirm deletion. This action permanently removes the chit and related records.</p>
                                <label for="deleteChitPassword{{ $chit->id }}" class="form-label">Admin Password</label>
                                <input
                                    id="deleteChitPassword{{ $chit->id }}"
                                    type="password"
                                    name="password"
                                    class="form-control @if ($isDeleteErrorForCurrentChit) is-invalid @endif"
                                    required
                                >
                                @if ($isDeleteErrorForCurrentChit)
                                    <div class="invalid-feedback d-block">{{ $errors->deleteChit->first('password') }}</div>
                                @endif
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Delete Chit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

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
                        $overviewInvoiceUrl = route('admin.chits.months.payments.invoice', [
                            $chit,
                            (int) $memberCard['month_number'],
                            $slot,
                            'auto_print' => 1,
                        ]);
                        $overviewWhatsAppUrl = $memberCard['whatsapp_url'] ?? null;
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
                                        title="View Payment History"
                                        aria-label="View Payment History"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a
                                        href="{{ $overviewInvoiceUrl }}"
                                        class="member-action-btn member-action-pdf"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="Download Invoice PDF"
                                        aria-label="Download Invoice PDF"
                                    >
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                    @if ($overviewWhatsAppUrl)
                                        <a
                                            href="{{ $overviewWhatsAppUrl }}"
                                            class="member-action-btn member-action-whatsapp"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            title="Send WhatsApp Update"
                                            aria-label="Send WhatsApp Update"
                                        >
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                    @else
                                        <span
                                            class="member-action-btn member-action-disabled"
                                            title="Mobile number not available for WhatsApp"
                                            aria-label="Mobile number not available for WhatsApp"
                                        >
                                            <i class="bi bi-whatsapp"></i>
                                        </span>
                                    @endif
                                    @if ($canManageMutations)
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
                                    @endif
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
            @php
                $selectedMonthStatusKey = $selectedMonthRecord?->status_key ?? 'pending';
                $selectedMonthStatusLabel = $selectedMonthRecord?->status_label ?? 'Pending';
                $canManageAuction = $canManageMutations && $selectedMonth === $currentMonth && $selectedMonthStatusKey === 'open';
            @endphp

            <div class="card monthly-cycle-card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                        <div>
                            <h5 class="card-title mb-1">Monthly Payments</h5>
                            <p class="text-muted mb-0">
                                Current working month is <strong>Month {{ $currentMonth }}</strong>. You can view previous months and manage the current month.
                            </p>
                        </div>
                        <span class="member-status-badge {{ $selectedMonthStatusKey === 'closed' ? 'status-paid' : ($selectedMonthStatusKey === 'open' ? 'status-due' : 'status-not-paid') }}">
                            Month {{ $selectedMonth }} • {{ $selectedMonthStatusLabel }}
                        </span>
                    </div>

                    <div class="month-chip-grid">
                        @foreach ($monthTimeline as $monthEntry)
                            @php
                                $monthNumber = (int) $monthEntry->month_number;
                                $isAccessibleMonth = $monthNumber <= $currentMonth;
                                $monthStatusKey = $monthEntry->status_key;
                                $monthStatusClass = $monthStatusKey === 'closed' ? 'status-paid' : ($monthStatusKey === 'open' ? 'status-due' : 'status-not-paid');
                            @endphp
                            @if ($isAccessibleMonth)
                                <a href="{{ route('admin.chits.show', ['chit' => $chit->id, 'tab' => 'payments', 'month' => $monthNumber]) }}" class="month-chip {{ $selectedMonth === $monthNumber ? 'active' : '' }}">
                                    <span>Month {{ $monthNumber }}</span>
                                    <small class="{{ $monthStatusClass }}">{{ $monthEntry->status_label }}</small>
                                </a>
                            @else
                                <span class="month-chip disabled">
                                    <span>Month {{ $monthNumber }}</span>
                                    <small>Locked</small>
                                </span>
                            @endif
                        @endforeach
                    </div>

                    @if ($canManageMutations && $canInitializeMonth)
                        <form method="POST" action="{{ route('admin.chits.months.initialize', [$chit, $selectedMonth]) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-play-circle me-1"></i>Initialize Month {{ $selectedMonth }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="toast-container position-fixed top-0 end-0 p-3">
                <div id="auctionStatusToast" class="toast border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <strong class="me-auto">Auction Update</strong>
                        <small>Month {{ $selectedMonth }}</small>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        {{ $auctionToastMessage }}
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6 col-xl-4">
                    <article class="card payment-summary-card">
                        <div class="card-body">
                            <span class="summary-label">Total Members</span>
                            <p class="summary-value mb-0">{{ $paymentSummary['members_count'] }}</p>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <article class="card payment-summary-card">
                        <div class="card-body">
                            <span class="summary-label">Expected Collection</span>
                            <p class="summary-value mb-0">Rs {{ number_format((int) $paymentSummary['expected_collection']) }}</p>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <article class="card payment-summary-card">
                        <div class="card-body">
                            <span class="summary-label">Amount Collected</span>
                            <p class="summary-value text-success mb-0">Rs {{ number_format((int) $paymentSummary['collected_amount']) }}</p>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <article class="card payment-summary-card payment-summary-due">
                        <div class="card-body">
                            <span class="summary-label">Pending Amount</span>
                            <p class="summary-value text-warning mb-0">Rs {{ number_format((int) $paymentSummary['pending_amount']) }}</p>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <article class="card payment-summary-card payment-summary-paid">
                        <div class="card-body">
                            <span class="summary-label">Paid Members</span>
                            <p class="summary-value text-success mb-0">{{ $paymentSummary['paid_members_count'] }}</p>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <article class="card payment-summary-card payment-summary-not-paid">
                        <div class="card-body">
                            <span class="summary-label">Pending Members</span>
                            <p class="summary-value text-danger mb-0">{{ $paymentSummary['pending_members_count'] }}</p>
                        </div>
                    </article>
                </div>
            </div>

            <div class="card monthly-auction-card mb-3">
                <div class="card-body">
                    <div class="auction-shell">
                        <div class="auction-shell-head">
                            <div>
                                <h5 class="card-title mb-1">Auction Management</h5>
                                <p class="text-muted mb-0">
                                    Capture monthly auction amount and winner. Winners from previous months are locked automatically.
                                </p>
                            </div>
                            <div class="auction-limit-badges">
                                <span class="auction-limit-pill">Max 30%: Rs {{ number_format((int) $maxAuctionAmount) }}</span>
                                <span class="auction-limit-pill auction-limit-pill-soft">Committee 4%: Rs {{ number_format((int) $auctionInsights['committee_interest']) }}</span>
                            </div>
                        </div>

                        @if ($canManageMutations)
                            <form method="POST" action="{{ route('admin.chits.months.auction', [$chit, $selectedMonth]) }}" class="auction-form-grid mt-3">
                                @csrf
                                @method('PATCH')

                                <div class="auction-input-group">
                                    <label for="auctionAmount" class="form-label mb-1">Auction Amount</label>
                                    <input
                                        id="auctionAmount"
                                        type="number"
                                        min="{{ (int) $auctionInsights['committee_interest'] }}"
                                        max="{{ (int) $maxAuctionAmount }}"
                                        name="auction_amount"
                                        value="{{ old('auction_amount', (int) ($selectedMonthRecord?->auction_amount ?? 0)) }}"
                                        class="form-control @error('auction_amount') is-invalid @enderror"
                                        required
                                        @disabled(! $canManageAuction)
                                    >
                                    @error('auction_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="auction-input-group">
                                    <label for="winnerSlotId" class="form-label mb-1">Select Winner</label>
                                    <select
                                        id="winnerSlotId"
                                        name="winner_slot_id"
                                        class="form-select @error('winner_slot_id') is-invalid @enderror"
                                        required
                                        @disabled(! $canManageAuction)
                                    >
                                        <option value="">Choose member slot</option>
                                        @foreach ($availableWinnerRows as $row)
                                            <option value="{{ $row['slot']->id }}" @selected((int) old('winner_slot_id', (int) ($selectedMonthRecord?->auction_winner_slot_id ?? 0)) === (int) $row['slot']->id)>
                                                {{ $row['display_name'] }} ({{ $row['phone'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('winner_slot_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary auction-save-btn" @disabled(! $canManageAuction)>Save Auction</button>
                                </div>
                            </form>
                        @else
                            <p class="text-muted mt-3 mb-0">Auction can be updated by admin only.</p>
                        @endif

                        @if ($auctionInsights['is_available'])
                            <div class="auction-result-grid mt-3">
                                <article class="auction-result-item auction-result-winner">
                                    <span class="summary-label">Winner</span>
                                    <p class="summary-value mb-0">{{ $auctionInsights['winner_name'] }}</p>
                                </article>
                                <article class="auction-result-item">
                                    <span class="summary-label">Auction Amount</span>
                                    <p class="summary-value mb-0">Rs {{ number_format((int) $auctionInsights['auction_amount']) }}</p>
                                </article>
                                <article class="auction-result-item">
                                    <span class="summary-label">Winner Claimed</span>
                                    <p class="summary-value text-primary mb-0">Rs {{ number_format((int) $auctionInsights['winner_claim_amount']) }}</p>
                                </article>
                                <article class="auction-result-item">
                                    <span class="summary-label">Committee Interest (4%)</span>
                                    <p class="summary-value mb-0">Rs {{ number_format((int) $auctionInsights['committee_interest']) }}</p>
                                </article>
                                <article class="auction-result-item">
                                    <span class="summary-label">Interest Per Person</span>
                                    <p class="summary-value text-success mb-0">Rs {{ number_format((int) $auctionInsights['interest_per_member']) }}</p>
                                </article>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card chit-member-filter-card mb-3">
                <div class="card-body py-3">
                    <form method="GET" action="{{ route('admin.chits.show', $chit) }}" class="row g-2 align-items-end">
                        <input type="hidden" name="tab" value="payments">
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">

                        <div class="col-lg-4 col-md-12">
                            <label for="paymentSearch" class="form-label mb-1">Search</label>
                            <input
                                id="paymentSearch"
                                type="text"
                                name="payment_search"
                                value="{{ $filters['payment_search'] }}"
                                class="form-control"
                                placeholder="Search by member name or phone..."
                            >
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label for="paymentFilter" class="form-label mb-1">Filter</label>
                            <select id="paymentFilter" name="payment_filter" class="form-select">
                                <option value="all" @selected($filters['payment_filter'] === 'all')>Show All</option>
                                <option value="pending" @selected($filters['payment_filter'] === 'pending')>Pending</option>
                                <option value="paid" @selected($filters['payment_filter'] === 'paid')>Paid</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6 d-grid">
                            <button type="submit" class="btn btn-primary">Apply</button>
                        </div>

                        <div class="col-lg-2 col-md-6 d-grid">
                            <a href="{{ route('admin.chits.show', ['chit' => $chit->id, 'tab' => 'payments', 'month' => $selectedMonth]) }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>

                    @if ($canManageMutations)
                        <div class="bulk-action-row mt-3">
                            <form method="POST" action="{{ route('admin.chits.months.mark-all-paid', [$chit, $selectedMonth]) }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm w-100" @disabled(! $canMarkAllPaid)>Mark All Paid</button>
                            </form>
                            <form method="POST" action="{{ route('admin.chits.months.reset', [$chit, $selectedMonth]) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning btn-sm w-100" @disabled(! $canResetMonth)>Reset Month</button>
                            </form>
                            <form method="POST" action="{{ route('admin.chits.months.close', [$chit, $selectedMonth]) }}" onsubmit="return confirm('Close Month {{ $selectedMonth }}?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100" @disabled(! $canCloseMonth)>Close Month</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card payment-list-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h5 class="card-title mb-0">Month {{ $selectedMonth }} Payments</h5>
                        <div class="bulk-selection-toolbar">
                            <span id="bulkSelectedCount" class="bulk-selected-count">0 selected</span>
                            <button type="button" id="bulkMarkPaidButton" class="btn btn-success btn-sm" disabled @disabled(! $canManageAuction)>Mark Selected Paid</button>
                            <button type="button" id="bulkMarkNotPaidButton" class="btn btn-outline-danger btn-sm" disabled @disabled(! $canManageAuction)>Mark Selected Not Paid</button>
                        </div>
                    </div>

                    <form id="bulkSelectionRowsForm" method="POST" action="{{ route('admin.chits.months.bulk-status', [$chit, $selectedMonth]) }}">
                        @csrf
                        <input type="hidden" id="bulkStatusInputRows" name="bulk_status" value="">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th width="36">
                                            <input id="selectAllPaymentRows" type="checkbox" class="form-check-input" @disabled(! $canManageAuction)>
                                        </th>
                                        <th>S.No</th>
                                        <th>Name</th>
                                        <th>Amount Due</th>
                                        <th>Interest / Person</th>
                                        <th>Amount Paid</th>
                                        <th>Payment Date</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($paymentRows as $row)
                                        @php
                                            $slot = $row['slot'];
                                            $rowStatusClass = $statusClassMap[$row['status_key']] ?? 'status-not-paid';
                                            $modalId = 'editPaymentModal'.$slot->id;
                                        @endphp
                                        <tr>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input payment-row-selector"
                                                    name="selected_slots[]"
                                                    value="{{ $slot->id }}"
                                                    @disabled(! $canManageAuction)
                                                >
                                            </td>
                                            <td>{{ $row['serial'] }}</td>
                                            <td>
                                                <strong>{{ $row['display_name'] }}</strong>
                                                <div class="small text-muted">{{ $row['phone'] }}</div>
                                            </td>
                                            <td>Rs {{ number_format((int) $row['due_amount']) }}</td>
                                            <td>
                                                @if ($auctionInsights['is_available'])
                                                    <span class="text-success fw-semibold">Rs {{ number_format((int) $auctionInsights['interest_per_member']) }}</span>
                                                @else
                                                    <span class="text-muted">Not calculated</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="amount-paid-highlight {{ (int) $row['paid_amount'] > 0 ? 'is-positive' : 'is-zero' }}">
                                                    Rs {{ number_format((int) $row['paid_amount']) }}
                                                </span>
                                            </td>
                                            <td>{{ $row['payment_date'] ? $row['payment_date']->format('d M Y') : 'Not paid' }}</td>
                                            <td><span class="member-status-badge {{ $rowStatusClass }}">{{ $row['status_label'] }}</span></td>
                                            <td class="text-end">
                                                <div class="payment-action-group justify-content-end">
                                                    @if ($canManageMutations)
                                                        <button
                                                            type="button"
                                                            class="payment-action-btn payment-action-edit"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#{{ $modalId }}"
                                                            title="Edit Payment"
                                                            aria-label="Edit Payment"
                                                        >
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                    @endif
                                                    <a
                                                        href="{{ route('admin.chits.members.show', [$chit, $slot]) }}"
                                                        class="payment-action-btn payment-action-view"
                                                        title="View Member Details"
                                                        aria-label="View Member Details"
                                                    >
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a
                                                        href="{{ route('admin.chits.months.payments.invoice', [$chit, $selectedMonth, $slot, 'auto_print' => 1]) }}"
                                                        class="payment-action-btn payment-action-pdf"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        title="Download Invoice PDF"
                                                        aria-label="Download Invoice PDF"
                                                    >
                                                        <i class="bi bi-file-earmark-pdf"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">No member rows available for this month and filter.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>

                    @if ($canManageMutations)
                        @foreach ($paymentRows as $row)
                            @php
                                $slot = $row['slot'];
                                $modalId = 'editPaymentModal'.$slot->id;
                            @endphp
                            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Payment - {{ $row['display_name'] }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" action="{{ route('admin.chits.months.payments.update', [$chit, $selectedMonth, $slot]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Payment Status</label>
                                                    <select name="payment_status" class="form-select" required>
                                                        @foreach ($statusOptions as $statusKey => $option)
                                                            <option value="{{ $statusKey }}" @selected($row['status_key'] === $statusKey)>{{ $option['label'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Paid Amount</label>
                                                    <input type="number" name="paid_amount" min="0" step="1" value="{{ (int) $row['paid_amount'] }}" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canManageMutations = @json($canManageMutations);
            if (!canManageMutations) {
                return;
            }

            const failedDeleteChitId = @json((int) old('delete_chit_id'));
            if (failedDeleteChitId > 0) {
                const failedModal = document.getElementById(`deleteChitModal${failedDeleteChitId}`);
                if (failedModal && window.bootstrap?.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(failedModal).show();
                }
            }
        });
    </script>

    @if ($activeTab === 'payments')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toastElement = document.getElementById('auctionStatusToast');
                if (toastElement && typeof bootstrap !== 'undefined' && bootstrap.Toast) {
                    const toast = new bootstrap.Toast(toastElement, { delay: 3200 });
                    toast.show();
                }

                const rowsForm = document.getElementById('bulkSelectionRowsForm');
                const statusInput = document.getElementById('bulkStatusInputRows');
                const selectAllCheckbox = document.getElementById('selectAllPaymentRows');
                const rowCheckboxes = Array.from(document.querySelectorAll('.payment-row-selector'));
                const selectedCountNode = document.getElementById('bulkSelectedCount');
                const markPaidButton = document.getElementById('bulkMarkPaidButton');
                const markNotPaidButton = document.getElementById('bulkMarkNotPaidButton');
                const bulkActionsAllowed = {{ $canManageAuction ? 'true' : 'false' }};

                if (!rowsForm || !statusInput || !selectAllCheckbox || rowCheckboxes.length === 0 || !selectedCountNode || !markPaidButton || !markNotPaidButton) {
                    return;
                }

                if (!bulkActionsAllowed) {
                    selectedCountNode.textContent = 'Month locked';
                    return;
                }

                const syncSelection = function () {
                    const selected = rowCheckboxes.filter(function (checkbox) {
                        return checkbox.checked;
                    });
                    const selectedCount = selected.length;

                    selectedCountNode.textContent = selectedCount + ' selected';
                    markPaidButton.disabled = selectedCount === 0;
                    markNotPaidButton.disabled = selectedCount === 0;

                    selectAllCheckbox.checked = selectedCount > 0 && selectedCount === rowCheckboxes.length;
                    selectAllCheckbox.indeterminate = selectedCount > 0 && selectedCount < rowCheckboxes.length;
                };

                selectAllCheckbox.addEventListener('change', function () {
                    rowCheckboxes.forEach(function (checkbox) {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                    syncSelection();
                });

                rowCheckboxes.forEach(function (checkbox) {
                    checkbox.addEventListener('change', syncSelection);
                });

                markPaidButton.addEventListener('click', function () {
                    statusInput.value = 'paid';
                    rowsForm.submit();
                });

                markNotPaidButton.addEventListener('click', function () {
                    statusInput.value = 'not_paid';
                    rowsForm.submit();
                });

                syncSelection();
            });
        </script>
    @endif
@endpush
