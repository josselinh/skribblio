@extends('layouts.app')

@section('content')
    <div class="container">
        <form method="post" action="{{ route('sentence.add.do') }}">
            @csrf

            <div class="form-group">
                <label for="sentenceIndexAddName"
                       class="mr-1">{{ __('sentence.index.add.name.label') }}</label>
                <input type="text" name="name" id="sentenceIndexAddName" class="form-control mr-1"
                       maxlength="30"
                       autocomplete="off"/>
            </div>

            <div class="form-group">
                <label for="sentenceImportAddGroup">{{ __('sentence.index.add.group.label') }}</label>
                <select name="group" id="sentenceImportAddGroup" class="form-control">
                    <option disabled selected>{{ __('sentence.import.group.placeholder') }}</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">{{ __('sentence.index.add.submit') }}</button>
        </form>
    </div>
@endsection
