@extends('layouts.app-custom')

@section('title', __('Create Member'))

@section('header', __('Create Member'))

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
                <h5 class="user-form-title">Add New Member</h5>
                <p class="user-form-subtitle">Create member profile, login credentials, and optional surety details.</p>
            </div>

            <div class="user-form-content">
                <form method="POST" action="{{ route('admin.members.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="user-form-section">
                        <h6 class="user-form-section-title">Member Profile</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name <span class="required-mark">*</span></label>
                                <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" class="form-control @error('first_name') is-invalid @enderror" required>
                                @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name <span class="required-mark">*</span></label>
                                <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" class="form-control @error('last_name') is-invalid @enderror" required>
                                @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="required-mark">*</span></label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="mobile_number" class="form-label">Mobile Number <span class="required-mark">*</span></label>
                                <input id="mobile_number" name="mobile_number" type="text" value="{{ old('mobile_number') }}" class="form-control @error('mobile_number') is-invalid @enderror" required>
                                @error('mobile_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label for="referred_by_name" class="form-label">Referred By <span class="optional-mark">(Optional)</span></label>
                                <input id="referred_by_name" name="referred_by_name" type="text" value="{{ old('referred_by_name') }}" class="form-control @error('referred_by_name') is-invalid @enderror" placeholder="Enter referrer name">
                                @error('referred_by_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label for="profile_image" class="form-label">Profile Image <span class="optional-mark">(Optional)</span></label>
                                <div class="profile-upload-wrap">
                                    <img
                                        id="memberProfilePreview"
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
                        <h6 class="user-form-section-title">Identity & Access</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="government_id" class="form-label">Government ID (PDF) <span class="optional-mark">(Optional)</span></label>
                                <input id="government_id" name="government_id" type="file" accept="application/pdf" class="form-control @error('government_id') is-invalid @enderror">
                                @error('government_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
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

                    <div class="user-form-section">
                        <h6 class="user-form-section-title">Surety Details</h6>
                        <p class="user-form-section-note">All surety fields are optional and can be filled now or later.</p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="surety_name" class="form-label">Surety Name <span class="optional-mark">(Optional)</span></label>
                                <input id="surety_name" name="surety_name" type="text" value="{{ old('surety_name') }}" class="form-control @error('surety_name') is-invalid @enderror">
                                @error('surety_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="surety_relation" class="form-label">Relation <span class="optional-mark">(Optional)</span></label>
                                <select id="surety_relation" name="surety_relation" class="form-select @error('surety_relation') is-invalid @enderror">
                                    <option value="">Select relation</option>
                                    @foreach ($relations as $relation)
                                        <option value="{{ $relation }}" @selected(old('surety_relation') === $relation)>{{ ucfirst($relation) }}</option>
                                    @endforeach
                                </select>
                                @error('surety_relation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="surety_phone_number" class="form-label">Surety Phone Number <span class="optional-mark">(Optional)</span></label>
                                <input id="surety_phone_number" name="surety_phone_number" type="text" value="{{ old('surety_phone_number') }}" class="form-control @error('surety_phone_number') is-invalid @enderror">
                                @error('surety_phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="surety_government_id" class="form-label">Surety Govt ID <span class="optional-mark">(Optional)</span></label>
                                <input id="surety_government_id" name="surety_government_id" type="text" value="{{ old('surety_government_id') }}" class="form-control @error('surety_government_id') is-invalid @enderror">
                                @error('surety_government_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="surety_bank_name" class="form-label">Bank Name <span class="optional-mark">(Optional)</span></label>
                                <input id="surety_bank_name" name="surety_bank_name" type="text" value="{{ old('surety_bank_name') }}" class="form-control @error('surety_bank_name') is-invalid @enderror">
                                @error('surety_bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="surety_cheque_book_number" class="form-label">Cheque Book Number <span class="optional-mark">(Optional)</span></label>
                                <input id="surety_cheque_book_number" name="surety_cheque_book_number" type="text" value="{{ old('surety_cheque_book_number') }}" class="form-control @error('surety_cheque_book_number') is-invalid @enderror">
                                @error('surety_cheque_book_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label for="surety_address" class="form-label">Surety Address <span class="optional-mark">(Optional)</span></label>
                                <textarea id="surety_address" name="surety_address" rows="3" class="form-control @error('surety_address') is-invalid @enderror">{{ old('surety_address') }}</textarea>
                                @error('surety_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="user-form-actions">
                        <button type="submit" class="btn btn-primary">Create Member</button>
                        <a href="{{ route('admin.members.index') }}" class="btn btn-light">Cancel</a>
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
            const preview = document.getElementById('memberProfilePreview');
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
