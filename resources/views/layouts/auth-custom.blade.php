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
    <main class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-md">
            @yield('content')
        </div>
    </main>
</body>
</html>
