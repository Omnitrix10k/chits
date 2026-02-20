@extends('layouts.app-custom')

@section('title', __('Member Chit Details'))

@section('header', __('Member Chit Details'))

@php
    $chitDetailCssVersion = file_exists(public_path('css/chit-detail.css'))
        ? filemtime(public_path('css/chit-detail.css'))
        : time();

    $statusClassMap = [
        'not_paid' => 'status-not-paid',
        'due' => 'status-due',
        'paid' => 'status-paid',
    ];

    $latestStatusClass = $statusClassMap[$latestSnapshot['status_key']] ?? 'status-not-paid';
    $memberName = trim((string) ($member?->name ?? 'Member'));
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

    <section class="section chit-member-page">
        <div class="card chit-shell-card mb-3">
            <div class="card-body p-3 p-lg-4">
                <div class="chit-shell-head">
                    <div>
                        <p class="chit-shell-kicker mb-1">Member Slot</p>
                        <h4 class="chit-shell-title mb-1">{{ $displayName }}</h4>
                        <p class="chit-shell-subtitle mb-0">
                            {{ $chit->plan_label }} • Month {{ $currentMonth }} of {{ (int) $chit->duration_months }}
                        </p>
                    </div>
                    <a href="{{ route('admin.chits.show', ['chit' => $chit->id, 'tab' => 'overview']) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back To Overview
                    </a>
                </div>

                <div class="member-profile-grid mt-3">
                    <div class="member-profile-card">
                        <img
                            src="{{ $member?->profile_image_url ?? asset('images/default-avatar.svg') }}"
                            alt="{{ $memberName }}"
                            class="member-profile-avatar"
                        >
                        <div>
                            <h5 class="member-profile-name mb-1">{{ $memberName }}</h5>
                            <p class="member-profile-meta mb-0">Phone: {{ $member?->mobile_number ?: ($member?->primary_phone ?: 'Not available') }}</p>
                        </div>
                    </div>
                    <div class="member-profile-info">
                        <div>
                            <span class="member-grid-label">Age</span>
                            <p class="member-grid-value">{{ $slot->age ?: 'Not available' }}</p>
                        </div>
                        <div>
                            <span class="member-grid-label">Referred By</span>
                            <p class="member-grid-value">{{ $slot->referred_by_name ?: 'Not provided' }}</p>
                        </div>
                        <div>
                            <span class="member-grid-label">Current Status</span>
                            <p class="member-grid-value">
                                <span class="member-status-badge {{ $latestStatusClass }}">{{ $latestSnapshot['status_label'] }}</span>
                            </p>
                        </div>
                        <div>
                            <span class="member-grid-label">Current Due</span>
                            <p class="member-grid-value text-danger">Rs {{ number_format((int) $latestSnapshot['due_amount']) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card payment-history-card mb-3">
            <div class="card-body">
                <h5 class="card-title mb-3">Payment History</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Expected</th>
                                <th>Paid</th>
                                <th>Due</th>
                                <th>Extra</th>
                                <th>Status</th>
                                <th>Paid?</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $entry)
                                @php
                                    $entryStatusClass = $statusClassMap[$entry['status_key']] ?? 'status-not-paid';
                                @endphp
                                <tr>
                                    <td>Month {{ $entry['month_number'] }}</td>
                                    <td>Rs {{ number_format((int) $entry['expected_amount']) }}</td>
                                    <td>Rs {{ number_format((int) $entry['paid_amount']) }}</td>
                                    <td>Rs {{ number_format((int) $entry['due_amount']) }}</td>
                                    <td>Rs {{ number_format((int) $entry['extra_paid_amount']) }}</td>
                                    <td><span class="member-status-badge {{ $entryStatusClass }}">{{ $entry['status_label'] }}</span></td>
                                    <td>
                                        <input type="checkbox" class="form-check-input" @checked($entry['is_paid']) disabled>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <div class="card form-card" id="member-edit-section">
                    <div class="card-body">
                        <h5 class="card-title">Edit Member Details</h5>
                        <form method="POST" action="{{ route('admin.chits.members.update', [$chit, $slot]) }}">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label for="referredByName" class="form-label">Referred By Name</label>
                                <input
                                    id="referredByName"
                                    type="text"
                                    name="referred_by_name"
                                    class="form-control @error('referred_by_name') is-invalid @enderror"
                                    value="{{ old('referred_by_name', $slot->referred_by_name) }}"
                                    placeholder="Enter referred by name"
                                >
                                @error('referred_by_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="memberAge" class="form-label">Age</label>
                                <input
                                    id="memberAge"
                                    type="number"
                                    name="age"
                                    min="1"
                                    max="120"
                                    class="form-control @error('age') is-invalid @enderror"
                                    value="{{ old('age', $slot->age) }}"
                                    placeholder="Enter age"
                                >
                                @error('age')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">Save Member Details</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card form-card">
                    <div class="card-body">
                        <h5 class="card-title">Update Payment</h5>
                        <form method="POST" action="{{ route('admin.chits.members.payments.store', [$chit, $slot]) }}">
                            @csrf

                            <div class="mb-3">
                                <label for="monthNumber" class="form-label">Month</label>
                                <select id="monthNumber" name="month_number" class="form-select @error('month_number') is-invalid @enderror" required>
                                    @for ($month = 1; $month <= max(1, (int) $chit->duration_months); $month++)
                                        <option value="{{ $month }}" @selected((int) old('month_number', $currentMonth) === $month)>Month {{ $month }}</option>
                                    @endfor
                                </select>
                                @error('month_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="paymentStatus" class="form-label">Payment Status</label>
                                <select id="paymentStatus" name="payment_status" class="form-select @error('payment_status') is-invalid @enderror" required>
                                    @foreach ($statusOptions as $statusKey => $option)
                                        <option value="{{ $statusKey }}" @selected(old('payment_status', $latestSnapshot['status_key']) === $statusKey)>{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('payment_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="paidAmount" class="form-label">Paid Amount</label>
                                <input
                                    id="paidAmount"
                                    type="number"
                                    name="paid_amount"
                                    min="0"
                                    step="1"
                                    class="form-control @error('paid_amount') is-invalid @enderror"
                                    value="{{ old('paid_amount', $latestSnapshot['paid_amount']) }}"
                                    required
                                >
                                @error('paid_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 form-check">
                                <input
                                    id="markPaid"
                                    type="checkbox"
                                    name="mark_paid"
                                    value="1"
                                    class="form-check-input @error('mark_paid') is-invalid @enderror"
                                    @checked(old('mark_paid'))
                                >
                                <label for="markPaid" class="form-check-label">Mark as paid for this month</label>
                                @error('mark_paid')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="paymentNotes" class="form-label">Notes</label>
                                <textarea
                                    id="paymentNotes"
                                    name="notes"
                                    rows="3"
                                    class="form-control @error('notes') is-invalid @enderror"
                                    placeholder="Optional payment notes..."
                                >{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">Save Payment</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-card mt-3">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h6 class="mb-1">Remove Member Slot</h6>
                    <p class="text-muted mb-0">Delete this slot from current chit assignments.</p>
                </div>
                <form method="POST" action="{{ route('admin.chits.members.destroy', [$chit, $slot]) }}" onsubmit="return confirm('Delete this member slot from chit?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash me-1"></i>Delete Slot
                    </button>
                </form>
            </div>
        </div>
    </section>
@endsection
