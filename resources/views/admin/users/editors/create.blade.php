@extends('layouts.app-custom')

@section('title', __('Create Editor'))

@section('header', __('Create Editor'))

@section('content')
    <div class="max-w-2xl rounded-lg bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.editors.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">{{ __('Name') }} *</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="profile_image" class="block text-sm font-medium text-gray-700">{{ __('Profile Image (Optional)') }}</label>
                <div class="mt-2 flex items-center gap-4">
                    <img src="{{ asset('images/default-avatar.svg') }}" alt="{{ __('Default profile image') }}" class="h-20 w-20 rounded-full border border-gray-200 object-cover">
                    <div class="w-full">
                        <input id="profile_image" name="profile_image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-700">
                        <p class="mt-1 text-xs text-gray-500">{{ __('JPG, PNG, or WEBP. Max 2MB. Auto-optimized for faster loading.') }}</p>
                    </div>
                </div>
                @error('profile_image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">{{ __('Email') }} *</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="mobile_number" class="block text-sm font-medium text-gray-700">{{ __('Mobile Number') }} *</label>
                <input id="mobile_number" name="mobile_number" type="text" value="{{ old('mobile_number') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @error('mobile_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">{{ __('Password') }} *</label>
                <input id="password" name="password" type="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">{{ __('Confirm Password') }} *</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Create Editor') }}</button>
                <a href="{{ route('admin.editors.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
