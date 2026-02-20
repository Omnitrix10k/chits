@extends('layouts.app-custom')

@section('title', __('Create Editor'))

@section('header', __('Create Editor'))

@php
    $userFormsCssVersion = file_exists(public_path('css/user-forms.css'))
        ? filemtime(public_path('css/user-forms.css'))
        : time();
@endphp

@push('styles')
    <link href="{{ asset('css/user-forms.css') }}?v={{ $userFormsCssVersion }}" rel="stylesheet">
@endpush

@section('content')
    <section class="section user-form-page">
        <div class="card user-form-shell">
            <div class="user-form-header">
                <h5 class="user-form-title">Add New Editor</h5>
                <p class="user-form-subtitle">Create editor access with contact information and login credentials.</p>
            </div>

            <div class="user-form-content">
                <form method="POST" action="{{ route('admin.editors.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="user-form-section">
                        <h6 class="user-form-section-title">Editor Profile</h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name <span class="required-mark">*</span></label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="mobile_number" class="form-label">Mobile Number <span class="required-mark">*</span></label>
                                <input id="mobile_number" name="mobile_number" type="text" value="{{ old('mobile_number') }}" class="form-control @error('mobile_number') is-invalid @enderror" required>
                                @error('mobile_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label">Email <span class="required-mark">*</span></label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label for="profile_image" class="form-label">Profile Image <span class="optional-mark">(Optional)</span></label>
                                <div class="profile-upload-wrap">
                                    <img
                                        id="editorProfilePreview"
                                        src="{{ asset('images/default-avatar.svg') }}"
                                        alt="Default profile image"
                                        class="profile-preview"
                                    >
                                    <div>
                                        <input id="profile_image" name="profile_image" type="file" accept="image/jpeg,image/png,image/webp" class="form-control @error('profile_image') is-invalid @enderror">
                                        <p class="profile-upload-note">JPG, PNG, or WEBP. Max 2MB. Uploaded image is optimized automatically.</p>
                                        @error('profile_image') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="user-form-section">
                        <h6 class="user-form-section-title">Login Credentials</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password <span class="required-mark">*</span></label>
                                <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" required>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirm Password <span class="required-mark">*</span></label>
                                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="user-form-actions">
                        <button type="submit" class="btn btn-primary">Create Editor</button>
                        <a href="{{ route('admin.editors.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('profile_image');
            const preview = document.getElementById('editorProfilePreview');
            if (!input || !preview) {
                return;
            }

            input.addEventListener('change', function () {
                const file = input.files && input.files[0] ? input.files[0] : null;
                if (!file) {
                    preview.src = @json(asset('images/default-avatar.svg'));
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    if (event.target && typeof event.target.result === 'string') {
                        preview.src = event.target.result;
                    }
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
@endpush
