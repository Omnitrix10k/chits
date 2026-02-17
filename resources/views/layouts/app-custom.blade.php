<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900 antialiased">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="text-lg font-semibold text-gray-900">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <a href="{{ route('dashboard') }}" class="text-sm {{ request()->routeIs('dashboard') ? 'font-semibold text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
                    {{ __('Dashboard') }}
                </a>
                <a href="{{ route('profile.edit') }}" class="text-sm {{ request()->routeIs('profile.*') ? 'font-semibold text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
                    {{ __('Profile') }}
                </a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.users.index') }}" class="text-sm {{ request()->routeIs('admin.users.*') ? 'font-semibold text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
                        {{ __('Manage Users') }}
                    </a>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <span class="hidden text-sm text-gray-600 sm:block">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="py-10">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            @hasSection('header')
                <h1 class="mb-6 text-2xl font-semibold text-gray-900">@yield('header')</h1>
            @endif
            @yield('content')
        </div>
    </main>
</body>
</html>
