@extends('layouts.app-custom')

@section('title', __('Dashboard'))

@section('header', __('Dashboard'))

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 flex items-center justify-between gap-4">
            <span>{{ __("You're logged in!") }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>
    </div>
@endsection
