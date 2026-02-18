@extends('layouts.app-custom')

@section('title', __('Editors'))

@section('header', __('Editors'))

@php
    $membersCssVersion = file_exists(public_path('niceadmin/assets/css/goud-members.css'))
        ? filemtime(public_path('niceadmin/assets/css/goud-members.css'))
        : time();
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
                            <h5 class="members-toolbar-title">All Editors</h5>
                            <p class="members-toolbar-subtitle">
                                Total Editors: <strong>{{ $totalEditors }}</strong>
                                @if ($editors->total() !== $totalEditors)
                                    <span class="ms-2">Filtered: <strong>{{ $editors->total() }}</strong></span>
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('admin.editors.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add Editor
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card members-filter-card">
                    <div class="card-body py-3">
                        <form method="GET" action="{{ route('admin.editors.index') }}" class="row g-2 align-items-end">
                            <div class="col-lg-6 col-md-12">
                                <label for="editorSearch" class="form-label mb-1">Search</label>
                                <input
                                    id="editorSearch"
                                    type="text"
                                    name="search"
                                    value="{{ $filters['search'] }}"
                                    class="form-control"
                                    placeholder="Search name, email, phone..."
                                >
                            </div>

                            <div class="col-lg-2 col-md-4">
                                <label for="profileFilter" class="form-label mb-1">Profile</label>
                                <select id="profileFilter" name="profile_image" class="form-select">
                                    <option value="all" @selected($filters['profile_image'] === 'all')>All</option>
                                    <option value="uploaded" @selected($filters['profile_image'] === 'uploaded')>Image Uploaded</option>
                                    <option value="missing" @selected($filters['profile_image'] === 'missing')>No Image</option>
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-4">
                                <label for="editorSort" class="form-label mb-1">Sort</label>
                                <select id="editorSort" name="sort" class="form-select">
                                    <option value="latest" @selected($filters['sort'] === 'latest')>Newest</option>
                                    <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest</option>
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-4 d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Apply</button>
                                <a href="{{ route('admin.editors.index') }}" class="btn btn-light border">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="member-list">
            @forelse ($editors as $editor)
                <article class="card member-card">
                    <div class="member-card-body">
                        <img src="{{ $editor->profile_image_url }}" alt="{{ $editor->name }}" class="member-avatar">

                        <div class="member-main">
                            <div class="member-top">
                                <div>
                                    <h5 class="member-name">{{ $editor->name }}</h5>
                                    <span class="member-role-badge">Editor</span>
                                </div>
                            </div>

                            <div class="member-meta-grid">
                                <div class="member-meta-item">
                                    <span class="member-meta-label"><i class="bi bi-envelope"></i>Email</span>
                                    <p class="member-meta-value">{{ $editor->email }}</p>
                                </div>

                                <div class="member-meta-item">
                                    <span class="member-meta-label"><i class="bi bi-telephone"></i>Phone</span>
                                    <p class="member-meta-value">{{ $editor->mobile_number }}</p>
                                </div>

                                <div class="member-meta-item member-meta-wide">
                                    <span class="member-meta-label"><i class="bi bi-image"></i>Profile Image</span>
                                    <p class="member-meta-value">
                                        @if ($editor->profile_image_path)
                                            <span class="member-pdf-link" style="color:#1e7f3e;">
                                                <i class="bi bi-check-circle"></i>{{ __('Uploaded') }}
                                            </span>
                                        @else
                                            <span class="member-pdf-missing">{{ __('Not uploaded') }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="member-actions">
                            <a
                                href="{{ route('admin.editors.edit', $editor) }}"
                                class="member-icon-btn"
                                title="{{ __('Edit Editor') }}"
                                aria-label="{{ __('Edit Editor') }}"
                            >
                                <i class="bi bi-pencil-square"></i>
                            </a>

                            <form method="POST" action="{{ route('admin.editors.destroy', $editor) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="member-icon-btn member-delete"
                                    title="{{ __('Delete Editor') }}"
                                    aria-label="{{ __('Delete Editor') }}"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="card members-empty">
                    <div class="card-body py-4">
                        <p class="text-muted mb-0">{{ __('No editors found.') }}</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($editors->hasPages())
            <div class="members-pagination-wrap">
                <p class="small text-muted mb-0">
                    Showing {{ $editors->firstItem() }} to {{ $editors->lastItem() }} of {{ $editors->total() }} editors
                </p>
                {{ $editors->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>
@endsection
