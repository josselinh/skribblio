@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6">{{ __('menu.sentences.export') }}</h2>

            <form method="get" class="space-y-6">
                <div>
                    <legend class="text-sm font-medium text-gray-700 mb-3">{{ __('sentence.export.groups.title') }}</legend>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($groups as $group)
                            <label class="relative flex items-start p-2 rounded-lg hover:bg-gray-50 border border-gray-200 cursor-pointer">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" name="groups[]" value="{{ $group->id }}"
                                           id="sentenceExportGroups{{ ucwords($group->name) }}"
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                           @if(in_array($group->id, (array)request()->input('groups', []))) checked @endif>
                                </div>
                                <div class="ms-3 text-sm">
                                    <span class="font-medium text-gray-700">{{ $group->name }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-start">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none transition ease-in-out duration-150">
                        {{ __('sentence.export.submit') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-gray-800 text-gray-100 rounded-lg shadow-sm p-6 overflow-x-auto">
            <div class="text-xs uppercase font-semibold text-gray-400 mb-2">Résultat :</div>
            <p class="font-mono text-sm leading-relaxed select-all">
                {{ $sentences->pluck('sentence')->unique()->implode(', ') }}
            </p>
        </div>
    </div>
@endsection
