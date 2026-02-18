@extends('layouts.app-custom')

@section('title', 'Chits')

@section('content')
    <div class="max-w-5xl mx-auto rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-slate-800">Chits</h1>
        <p class="mt-2 text-sm text-slate-600">Chit management workspace placeholder. You can wire listing and filters here.</p>
        <a href="{{ route('dashboard') }}" class="inline-block mt-5 rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Back to Dashboard</a>
    </div>
@endsection
