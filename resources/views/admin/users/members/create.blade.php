@extends('layouts.app-custom')

@section('title', __('Create Member'))

@section('header', __('Create Member'))

@section('content')
    <div class="max-w-3xl rounded-lg bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.members.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700">{{ __('First Name') }} *</label>
                    <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('first_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700">{{ __('Last Name') }} *</label>
                    <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('last_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
            </div>

            <div>
                <label for="referred_by_name" class="block text-sm font-medium text-gray-700">{{ __('Referred By (Optional)') }}</label>
                <input id="referred_by_name" name="referred_by_name" type="text" value="{{ old('referred_by_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="{{ __('Enter referrer name') }}">
                @error('referred_by_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                <label for="government_id" class="block text-sm font-medium text-gray-700">{{ __('Government ID (PDF)') }}</label>
                <input id="government_id" name="government_id" type="file" accept="application/pdf" class="mt-1 block w-full text-sm text-gray-700">
                @error('government_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-md border border-gray-200 p-4">
                <h3 class="text-sm font-semibold text-gray-800">{{ __('Surety Details') }}</h3>
                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="surety_name" class="block text-sm font-medium text-gray-700">{{ __('Surety Name') }} *</label>
                        <input id="surety_name" name="surety_name" type="text" value="{{ old('surety_name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('surety_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="surety_relation" class="block text-sm font-medium text-gray-700">{{ __('Relation') }} *</label>
                        <select id="surety_relation" name="surety_relation" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">{{ __('Select relation') }}</option>
                            @foreach ($relations as $relation)
                                <option value="{{ $relation }}" @selected(old('surety_relation') === $relation)>{{ ucfirst($relation) }}</option>
                            @endforeach
                        </select>
                        @error('surety_relation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="surety_phone_number" class="block text-sm font-medium text-gray-700">{{ __('Surety Phone Number') }} *</label>
                        <input id="surety_phone_number" name="surety_phone_number" type="text" value="{{ old('surety_phone_number') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('surety_phone_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="surety_government_id" class="block text-sm font-medium text-gray-700">{{ __('Surety Govt ID') }} *</label>
                        <input id="surety_government_id" name="surety_government_id" type="text" value="{{ old('surety_government_id') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('surety_government_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="surety_bank_name" class="block text-sm font-medium text-gray-700">{{ __('Bank Name') }} *</label>
                        <input id="surety_bank_name" name="surety_bank_name" type="text" value="{{ old('surety_bank_name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('surety_bank_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="surety_cheque_book_number" class="block text-sm font-medium text-gray-700">{{ __('Cheque Book Number') }} *</label>
                        <input id="surety_cheque_book_number" name="surety_cheque_book_number" type="text" value="{{ old('surety_cheque_book_number') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('surety_cheque_book_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label for="surety_address" class="block text-sm font-medium text-gray-700">{{ __('Surety Address') }} *</label>
                    <textarea id="surety_address" name="surety_address" rows="2" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('surety_address') }}</textarea>
                    @error('surety_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">{{ __('Password') }} *</label>
                    <input id="password" name="password" type="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">{{ __('Confirm Password') }} *</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Create Member') }}</button>
                <a href="{{ route('admin.members.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
