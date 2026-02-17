@extends('layouts.app-custom')

@section('title', __('Manage Users'))

@section('header', __('Manage Users'))

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-gray-600">{{ __('Only admins can create users and assign roles.') }}</p>
        <a href="{{ route('admin.users.create') }}" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
            {{ __('Create User') }}
        </a>
    </div>

    @if (session('status') === 'user-created')
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ __('User created successfully.') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Email') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Mobile') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Role') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-3">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->mobile_number }}</td>
                        <td class="px-4 py-3"><span class="rounded bg-gray-100 px-2 py-1 text-xs font-semibold uppercase">{{ $user->role }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">{{ __('No users found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
@endsection
