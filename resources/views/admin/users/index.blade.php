@extends('layouts.app-custom')

@section('title', __('Manage Members & Editors'))

@section('header', __('Manage Members & Editors'))

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ __(ucfirst(str_replace('-', ' ', session('status')))) }}
        </div>
    @endif

    <div class="mb-4 flex items-center gap-3">
        <a href="{{ route('admin.members.create') }}" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
            Add Member
        </a>
        <a href="{{ route('admin.editors.create') }}" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
            Add Editor
        </a>
    </div>

    <div id="members" class="mb-8 rounded-lg bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Members') }}</h2>
            <a href="{{ route('admin.members.create') }}" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
                {{ __('Add Member') }}
            </a>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Email') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Mobile') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Govt ID PDF') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($members as $member)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $member->profile_image_url }}" alt="{{ $member->name }}" class="h-9 w-9 rounded-full border border-gray-200 object-cover">
                                    <span>{{ $member->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $member->email }}</td>
                            <td class="px-4 py-3">{{ $member->mobile_number }}</td>
                            <td class="px-4 py-3">
                                @if ($member->government_id_path)
                                    <a href="{{ route('admin.members.government-id.download', $member) }}" class="text-blue-600 hover:text-blue-800">{{ __('Download') }}</a>
                                @else
                                    <span class="text-gray-400">{{ __('Not uploaded') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.members.edit', $member) }}" class="text-sm text-gray-700 hover:text-black">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('admin.members.destroy', $member) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">{{ __('No members found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="editors" class="rounded-lg bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Editors') }}</h2>
            <a href="{{ route('admin.editors.create') }}" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
                {{ __('Add Editor') }}
            </a>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Email') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Mobile') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($editors as $editor)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $editor->profile_image_url }}" alt="{{ $editor->name }}" class="h-9 w-9 rounded-full border border-gray-200 object-cover">
                                    <span>{{ $editor->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $editor->email }}</td>
                            <td class="px-4 py-3">{{ $editor->mobile_number }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.editors.edit', $editor) }}" class="text-sm text-gray-700 hover:text-black">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('admin.editors.destroy', $editor) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">{{ __('No editors found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
