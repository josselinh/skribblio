@extends('layouts.app')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6">{{ __('menu.groups.add') }}</h2>

            <form method="post" action="{{ route('group.add.do') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="groupAddName" class="block text-sm font-medium text-gray-700">
                        {{ __('group.add.name.label') }}
                    </label>
                    <input type="text" name="name" id="groupAddName" value="{{ old('name') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-300 @enderror"
                           autocomplete="off"/>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="groupAddVisibility" class="block text-sm font-medium text-gray-700">
                        {{ __('group.add.visibility.label') }}
                    </label>
                    <select name="visibility" id="groupAddVisibility"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('visibility') border-red-300 @enderror">
                        @foreach($visibilities as $visibilityId => $visibilityLabel)
                            <option value="{{ $visibilityId }}" @if(old('visibility') == $visibilityId) selected @endif>{{ $visibilityLabel }}</option>
                        @endforeach
                    </select>
                    @error('visibility')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none transition ease-in-out duration-150">
                        {{ __('group.add.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
