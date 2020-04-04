@extends('layouts.app')

@section('content')
    <div class="container">
        <form method="post" action="{{ route('group.add.do') }}">
            @csrf

            <div class="form-group">
                <label for="groupAddName" class="mr-1">{{ __('group.add.name.label') }}</label>
                <input type="text" name="name" id="groupAddName" class="form-control mr-1"
                       autocomplete="off"/>
            </div>

            <div class="form-group">
                <label for="groupAddVisibility">{{ __('group.add.visibility.label') }}</label>
                <select name="visibility" id="groupAddVisibility" class="form-control">
                    @foreach($visibilities as $visibilityId => $visibilityLabel)
                        <option value="{{ $visibilityId }}">{{ $visibilityLabel }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">{{ __('group.add.submit') }}</button>
        </form>
    </div>
@endsection
