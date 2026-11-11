@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <form method="GET" class="flex flex-col sm:flex-row gap-2 sm:items-center">
                @if(request()->filled('group'))
                    <input type="hidden" name="group" value="{{ request('group') }}"/>
                @endif
                @if(request()->filled('author'))
                    <input type="hidden" name="author" value="{{ request('author') }}"/>
                @endif
                <input type="text" name="search"
                       class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm max-w-sm"
                       autocomplete="off"
                       placeholder="{{ __('sentence.index.search.submit') }}..."
                       value="{{ request()->input('search') }}"/>

                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none transition ease-in-out duration-150 w-full sm:w-auto">
                    {{ __('sentence.index.search.submit') }}
                </button>
            </form>

            @if(request()->filled('group'))
                <div>
                    <a href="{{ route('sentence.add', ['group' => request('group')]) }}"
                       class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none transition ease-in-out duration-150 w-full sm:w-auto">
                        {{ __('sentence.index.add.submit') }}
                    </a>
                </div>
            @endif
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 font-medium">
                    <tr>
                        <th scope="col" class="px-6 py-3">{{ __('sentence.index.table.th.sentence') }}</th>
                        <th scope="col" class="px-4 py-3 w-16 text-center">{{ __('sentence.index.table.th.note') }}</th>
                        <th scope="col" class="px-6 py-3 w-48">{{ __('sentence.index.table.th.group') }}</th>
                        <th scope="col" class="px-6 py-3 w-44">{{ __('sentence.index.table.th.author') }}</th>
                        <th scope="col" class="px-6 py-3 w-40">{{ __('sentence.index.table.th.created_at') }}</th>
                        <th scope="col" class="px-4 py-3 w-20 text-center">{{ __('sentence.index.table.th.actions') }}</th>
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($sentences as $sentence)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900 break-words">{{ $sentence->sentence }}</td>
                            <td class="px-4 py-4 text-center text-gray-600 font-semibold">{{ $sentence->note }}</td>
                            <td class="px-6 py-4 text-gray-600">
                                <a href="{{ route('sentence.index', ['group' => $sentence->group->id]) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                    {{ $sentence->group->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                <a href="{{ route('sentence.index', ['author' => $sentence->user->id]) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                    {{ $sentence->user->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">{{ $sentence->created_at }}</td>
                            <td class="px-4 py-4 text-center">
                                <x-sentence-vote-buttons :sentence="$sentence"/>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                Aucune phrase trouvée.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($sentences->hasPages())
                <div class="p-4 border-t border-gray-200">
                    {{ $sentences->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
