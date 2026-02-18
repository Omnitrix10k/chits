@extends('layouts.app-custom')

@section('title', __('Edit Editor'))

@section('header', __('Edit Editor'))

@section('content')
    <div class="max-w-2xl rounded-lg bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.editors.update', $editor) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">{{ __('Name') }} *</label>
                <input id="name" name="name" type="text" value="{{ old('name', $editor->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="profile_image" class="block text-sm font-medium text-gray-700">{{ __('Profile Image (Optional)') }}</label>
                <div class="mt-2 flex items-center gap-4">
                    <img src="{{ $editor->profile_image_url }}" alt="{{ $editor->name }}" class="h-20 w-20 rounded-full border border-gray-200 object-cover">
                    <div class="w-full">
                        <input id="profile_image" name="profile_image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-700">
                        <p class="mt-1 text-xs text-gray-500">{{ __('JPG, PNG, or WEBP. Max 2MB. Auto-optimized for faster loading.') }}</p>

                        @if ($editor->profile_image_path)
                            <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="remove_profile_image" value="1" @checked(old('remove_profile_image')) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>{{ __('Remove current image') }}</span>
                            </label>
                        @endif
                    </div>
                </div>
                @error('profile_image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('remove_profile_image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">{{ __('Email') }} *</label>
                <input id="email" name="email" type="email" value="{{ old('email', $editor->email) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="mobile_number" class="block text-sm font-medium text-gray-700">{{ __('Mobile Number') }} *</label>
                <input id="mobile_number" name="mobile_number" type="text" value="{{ old('mobile_number', $editor->mobile_number) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @error('mobile_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">{{ __('New Password (optional)') }}</label>
                <input id="password" name="password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">{{ __('Confirm Password') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Update Editor') }}</button>
                <a href="{{ route('admin.editors.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
