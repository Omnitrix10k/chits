@extends('layouts.app-custom')

@section('title', __('Members'))

@section('header', __('Members'))

@php
    use App\Models\User;

    $membersCssVersion = file_exists(public_path('niceadmin/assets/css/goud-members.css'))
        ? filemtime(public_path('niceadmin/assets/css/goud-members.css'))
        : time();
    $canManageMutations = auth()->user()?->role === User::ROLE_ADMIN;
@endphp

@push('styles')
    <link href="{{ asset('niceadmin/assets/css/goud-members.css') }}?v={{ $membersCssVersion }}" rel="stylesheet">
@endpush

@section('content')
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ __(ucfirst(str_replace('-', ' ', session('status')))) }}
        </div>
    @endif

    <section class="section members-page">
        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="card members-toolbar-card">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 py-3">
                        <div>
                            <h5 class="members-toolbar-title">All Members</h5>
                            <p class="members-toolbar-subtitle">
                                Total Members: <strong>{{ $totalMembers }}</strong>
                                @if ($members->total() !== $totalMembers)
                                    <span class="ms-2">Filtered: <strong>{{ $members->total() }}</strong></span>
                                @endif
                            </p>
                        </div>
                        @if ($canManageMutations)
                            <a href="{{ route('admin.members.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i>Add Member
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card members-filter-card">
                    <div class="card-body py-3">
                        <form method="GET" action="{{ route('admin.members.index') }}" class="row g-2 align-items-end">
                            <div class="col-lg-6 col-md-12">
                                <label for="memberSearch" class="form-label mb-1">Search</label>
                                <input
                                    id="memberSearch"
                                    type="text"
                                    name="search"
                                    value="{{ $filters['search'] }}"
                                    class="form-control"
                                    placeholder="Search name, email, phone, address..."
                                >
                            </div>

                            <div class="col-lg-2 col-md-4">
                                <label for="govtIdFilter" class="form-label mb-1">Govt ID</label>
                                <select id="govtIdFilter" name="govt_id" class="form-select">
                                    <option value="all" @selected($filters['govt_id'] === 'all')>All</option>
                                    <option value="uploaded" @selected($filters['govt_id'] === 'uploaded')>Uploaded</option>
                                    <option value="missing" @selected($filters['govt_id'] === 'missing')>Not Uploaded</option>
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-4">
                                <label for="sortFilter" class="form-label mb-1">Sort</label>
                                <select id="sortFilter" name="sort" class="form-select">
                                    <option value="latest" @selected($filters['sort'] === 'latest')>Newest</option>
                                    <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest</option>
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-4 d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Apply</button>
                                <a href="{{ route('admin.members.index') }}" class="btn btn-light border">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="member-list">
            @forelse ($members as $member)
                @php
                    $displayAddress = $member->address ?: $member->family_address ?: __('Not provided');
                @endphp
                <article class="card member-card">
                    <div class="member-card-body">
                        <img src="{{ $member->profile_image_url }}" alt="{{ $member->name }}" class="member-avatar">

                        <div class="member-main">
                            <div class="member-top">
                                <div>
                                    <h5 class="member-name">{{ $member->name }}</h5>
                                    <span class="member-role-badge">Member</span>
                                </div>
                            </div>

                            <div class="member-meta-grid">
                                <div class="member-meta-item">
                                    <span class="member-meta-label"><i class="bi bi-envelope"></i>Email</span>
                                    <p class="member-meta-value">{{ $member->email }}</p>
                                </div>

                                <div class="member-meta-item">
                                    <span class="member-meta-label"><i class="bi bi-telephone"></i>Phone</span>
                                    <p class="member-meta-value">{{ $member->mobile_number }}</p>
                                </div>

                                <div class="member-meta-item member-meta-wide">
                                    <span class="member-meta-label"><i class="bi bi-geo-alt"></i>Address</span>
                                    <p class="member-meta-value">{{ $displayAddress }}</p>
                                </div>

                                <div class="member-meta-item member-meta-wide">
                                    <span class="member-meta-label"><i class="bi bi-file-earmark-pdf"></i>Government ID</span>
                                    <p class="member-meta-value">
                                        @if ($member->government_id_path)
                                            <a
                                                href="{{ route('admin.members.government-id.download', $member) }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="member-pdf-link"
                                                title="{{ __('View PDF') }}"
                                            >
                                                <i class="bi bi-box-arrow-up-right"></i>{{ __('Open PDF') }}
                                            </a>
                                        @else
                                            <span class="member-pdf-missing">{{ __('Not uploaded') }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        @if ($canManageMutations)
                            <div class="member-actions">
                                <a
                                    href="{{ route('admin.members.edit', $member) }}"
                                    class="member-icon-btn"
                                    title="{{ __('Edit Member') }}"
                                    aria-label="{{ __('Edit Member') }}"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form method="POST" action="{{ route('admin.members.destroy', $member) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="member-icon-btn member-delete"
                                        title="{{ __('Delete Member') }}"
                                        aria-label="{{ __('Delete Member') }}"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="card members-empty">
                    <div class="card-body py-4">
                        <p class="text-muted mb-0">{{ __('No members found.') }}</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($members->hasPages())
            <div class="members-pagination-wrap">
                <p class="small text-muted mb-0">
                    Showing {{ $members->firstItem() }} to {{ $members->lastItem() }} of {{ $members->total() }} members
                </p>
                {{ $members->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>
@endsection
