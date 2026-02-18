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
                <div class="card chit-toolbar-card">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 py-3">
                        <div>
                            <h5 class="chit-toolbar-title">All Chits</h5>
                            <p class="chit-toolbar-subtitle mb-0">
                                Total Chits: <strong>{{ $totalChits }}</strong>
                                @if ($chits->total() !== $totalChits)
                                    <span class="ms-2">Filtered: <strong>{{ $chits->total() }}</strong></span>
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('admin.chits.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Create Chit
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card chit-filter-card">
                    <div class="card-body py-3">
                        <form method="GET" action="{{ route('admin.chits.index') }}" class="row g-2 align-items-end">
                            <div class="col-lg-6 col-md-12">
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

                            <div class="col-lg-2 col-md-4">
                                <label for="chitPlanFilter" class="form-label mb-1">Plan</label>
                                <select id="chitPlanFilter" name="plan" class="form-select">
                                    <option value="all" @selected($filters['plan'] === 'all')>All</option>
                                    @foreach ($planOptions as $planKey => $plan)
                                        <option value="{{ $planKey }}" @selected($filters['plan'] === $planKey)>{{ $plan['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-4">
                                <label for="chitTypeFilter" class="form-label mb-1">Type</label>
                                <select id="chitTypeFilter" name="type" class="form-select">
                                    <option value="all" @selected($filters['type'] === 'all')>All</option>
                                    @foreach ($typeOptions as $typeKey => $typeLabel)
                                        <option value="{{ $typeKey }}" @selected($filters['type'] === $typeKey)>{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-4 d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Apply</button>
                                <a href="{{ route('admin.chits.index') }}" class="btn btn-light border">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card chit-rules-card">
                    <div class="card-body py-3">
                        <h6 class="mb-2">Rules To Start A Chit</h6>
                        <ul class="mb-0">
                            <li>Each chit must have exactly 20 member slots.</li>
                            <li>A single member can be repeated at most 9 times in the same chit.</li>
                            <li>Total value is based on plan, and monthly amount is auto-calculated as total value / duration months.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            @forelse ($chits as $chit)
                @php
                    $assignedSlots = (int) ($chit->assigned_slots ?? 0);
                    $uniqueMembers = (int) ($chit->unique_members_count ?? 0);
                @endphp

                <div class="col-12 col-md-6 col-xl-4">
                    <article class="card chit-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                <div>
                                    <h5 class="chit-card-title mb-1">{{ $chit->plan_label }}</h5>
                                    <p class="text-muted small mb-0">Chit #{{ $chit->id }}</p>
                                </div>
                                <span class="badge bg-primary-subtle text-primary-emphasis chit-type-badge">{{ $chit->type_label }}</span>
                            </div>

                            <div class="chit-stats-grid">
                                <div>
                                    <span class="chit-stat-label">Total Value</span>
                                    <p class="chit-stat-value">Rs {{ number_format((int) $chit->total_amount) }}</p>
                                </div>
                                <div>
                                    <span class="chit-stat-label">Monthly Amount</span>
                                    <p class="chit-stat-value">Rs {{ number_format((int) $chit->monthly_amount) }}</p>
                                </div>
                                <div>
                                    <span class="chit-stat-label">Duration</span>
                                    <p class="chit-stat-value">{{ (int) $chit->duration_months }} months</p>
                                </div>
                                <div>
                                    <span class="chit-stat-label">Members</span>
                                    <p class="chit-stat-value">{{ $assignedSlots }}/{{ (int) $chit->member_limit }} slots</p>
                                </div>
                            </div>

                            <div class="chit-card-footer mt-3">
                                <p class="small text-muted mb-1">Unique Members: {{ $uniqueMembers }}</p>
                                <p class="small text-muted mb-0">
                                    Created by {{ $chit->creator?->name ?? 'Admin' }} on {{ $chit->created_at->format('d M Y') }}
                                </p>
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
