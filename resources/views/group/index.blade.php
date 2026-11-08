@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div>
            <form method="GET" class="flex flex-col sm:flex-row gap-2 sm:items-center">
                <input type="text" name="search"
                       class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm max-w-sm"
                       autocomplete="off"
                       placeholder="{{ __('group.index.search.submit') }}..."
                       value="{{ request()->input('search') }}"/>

                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none transition ease-in-out duration-150 w-full sm:w-auto">
                    {{ __('group.index.search.submit') }}
                </button>
            </form>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 font-medium">
                    <tr>
                        <th scope="col" class="px-6 py-3">{{ __('group.index.table.th.name') }}</th>
                        <th scope="col" class="px-4 py-3 w-28 text-center">{{ __('group.index.table.th.sentences') }}</th>
                        <th scope="col" class="px-6 py-3 w-56">{{ __('group.index.table.th.author') }}</th>
                        <th scope="col" class="px-6 py-3 w-48">{{ __('group.index.table.th.created_at') }}</th>
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($groups as $group)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $group->name }}</td>
                            <td class="px-4 py-4 text-center">
                                <a href="{{ route('sentence.index', ['group' => $group->id]) }}"
                                   class="inline-flex items-center text-indigo-600 hover:text-indigo-900 p-1 hover:bg-indigo-50 rounded"
                                   title="{{ __('group.index.table.th.sentences') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                <a href="{{ route('group.index', ['author' => $group->user->id]) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                    {{ $group->user->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">{{ $group->created_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                Aucun groupe trouvé.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
