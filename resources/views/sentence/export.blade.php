@extends('layouts.app')

@section('content')
    <div class="container">
        <form method="get">
            <div class="form-row">
                <fieldset class="form-group col-md-6">
                    <legend>{{ __('sentence.export.groups.title') }}</legend>

                    @foreach($groups as $group)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="groups[]" value="{{ $group->id }}"
                                   id="sentenceExportGroups{{ ucwords($group->name) }}">
                            <label class="form-check-label" for="sentenceExportGroups{{ ucwords($group->name) }}">
                                {{ $group->name }}
                            </label>
                        </div>
                    @endforeach
                </fieldset>
            </div>

            <button type="submit" class="btn btn-primary">{{ __('sentence.export.submit') }}</button>
        </form>

        <div class="row mt-5">
            <div class="col bg-secondary text-white">
                <samp>{{ $sentences->pluck('sentence')->unique()->implode(', ') }}</samp>
            </div>
        </div>
    </div>
@endsection
