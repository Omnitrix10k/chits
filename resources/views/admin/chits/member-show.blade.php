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
                            <p class="member-grid-value">{{ $member?->referred_by_name ?: 'No One' }}</p>
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

    </section>
@endsection
