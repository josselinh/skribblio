@extends('layouts.app')

@section('content')
    <div class="container">
        <form method="post" action="{{ route('sentence.add.do') }}">
            @csrf

            <div class="form-group">
                <label for="sentenceIndexAddName"
                       class="mr-1">{{ __('sentence.index.add.name.label') }}</label>
                <input type="text" name="name" value="{{ old('name') }}" id="sentenceIndexAddName" class="form-control mr-1 @error('name') is-invalid @enderror"
                       maxlength="30"
                       autocomplete="off"/>
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="sentenceImportAddGroup">{{ __('sentence.index.add.group.label') }}</label>
                <select name="group" id="sentenceImportAddGroup" class="form-control @error('group') is-invalid @enderror">
                    <option disabled @if(is_null(old('group'))) selected @endif>{{ __('sentence.import.group.placeholder') }}</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" @if(old('group') == $group->id) selected @endif>{{ $group->name }}</option>
                    @endforeach
                </select>
                @error('group')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">{{ __('sentence.index.add.submit') }}</button>
        </form>
    </div>
@endsection
