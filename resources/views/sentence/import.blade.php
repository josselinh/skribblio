@extends('layouts.app')

@section('content')
    <div class="container">
        <form method="post" action="{{ route('sentence.import.do') }}">
            @csrf

            <div class="form-group">
                <label for="sentenceImport">{{ __('sentence.import.label') }}</label>
                <textarea name="sentences" class="form-control @error('sentences') is-invalid @enderror"
                          id="sentenceImport"></textarea>
                @error('sentences')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="sentenceImportGroup">{{ __('sentence.import.group.label') }}</label>
                <select name="group" id="sentenceImportGroup" class="form-control @error('group') is-invalid @enderror">
                    <option disabled selected>{{ __('sentence.import.group.placeholder') }}</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
                @error('group')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">{{ __('sentence.import.submit') }}</button>
        </form>
    </div>
@endsection
