@extends('layouts.app-custom')

@section('title', __('Chits'))

@section('header', __('Chits'))

@php
    use App\Models\User;

    $chitsCssVersion = file_exists(public_path('css/chits.css'))
        ? filemtime(public_path('css/chits.css'))
        : time();
    $canManageMutations = auth()->user()?->role === User::ROLE_ADMIN;
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

    @if ($canManageMutations && $errors->deleteChit->any())
        <div class="alert alert-danger" role="alert">
            Please enter the correct admin password to delete a chit.
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
                            </div>
                            @if ($canManageMutations)
                                <a href="{{ route('admin.chits.create') }}" class="btn btn-primary chit-overview-cta">
                                    <i class="bi bi-plus-circle me-1"></i>Create Chit
                                </a>
                            @endif
                        </div>

                        <div class="chit-overview-metrics chit-overview-metrics-single">
                            <div class="chit-overview-metric">
                                <span class="metric-label">Total Chits</span>
                                <strong class="metric-value">{{ $totalChits }}</strong>
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
                                    <a
                                        href="{{ route('admin.chits.show', $chit) }}"
                                        class="chit-card-action-btn chit-card-action-open"
                                        aria-label="Open chit"
                                    >
                                        <i class="bi bi-box-arrow-up-right"></i>
                                        <span>Open</span>
                                    </a>
                                    @if ($canManageMutations)
                                        <a
                                            href="{{ route('admin.chits.edit', $chit) }}"
                                            class="chit-card-action-btn chit-card-action-edit"
                                            aria-label="Edit chit"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                            <span>Edit</span>
                                        </a>
                                        <button
                                            type="button"
                                            class="chit-card-action-btn chit-card-action-delete"
                                            aria-label="Delete chit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteChitModal{{ $chit->id }}"
                                        >
                                            <i class="bi bi-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>

                    @if ($canManageMutations)
                        @php
                            $isDeleteErrorForChit = (int) old('delete_chit_id') === (int) $chit->id && $errors->deleteChit->has('password');
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
                                                class="form-control @if ($isDeleteErrorForChit) is-invalid @endif"
                                                required
                                            >
                                            @if ($isDeleteErrorForChit)
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
@endpush
