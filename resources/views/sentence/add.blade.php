@extends('layouts.app')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6">{{ __('menu.sentences.add') }}</h2>

            <form method="post" action="{{ route('sentence.add.do') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="sentenceIndexAddName" class="block text-sm font-medium text-gray-700">
                        {{ __('sentence.index.add.name.label') }}
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" id="sentenceIndexAddName"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-300 @enderror"
                           maxlength="30"
                           autocomplete="off"/>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sentenceImportAddGroup" class="block text-sm font-medium text-gray-700">
                        {{ __('sentence.index.add.group.label') }}
                    </label>
                    <select name="group" id="sentenceImportAddGroup"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('group') border-red-300 @enderror">
                        <option disabled @if(is_null(old('group'))) selected @endif>{{ __('sentence.import.group.placeholder') }}</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @if(old('group') == $group->id) selected @endif>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    @error('group')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none transition ease-in-out duration-150">
                        {{ __('sentence.index.add.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
