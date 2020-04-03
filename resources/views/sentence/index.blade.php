@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <form class="form-inline">
                    <div class="form-group">
                        <input type="text" name="search" class="form-control mr-1" autocomplete="off"
                               value="{{ request()->input('search') }}"/>
                    </div>

                    <button type="submit" class="btn btn-primary">{{__('sentence.index.search.submit')}}</button>
                </form>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-sm-12">
                <table class="table table-striped table-hover table-sm sentences-table">
                    <thead>
                    <tr>
                        <th scope="col">{{ __('sentence.index.table.th.sentence') }}</th>
                        <th scope="col" class="sentences-table__note">{{ __('sentence.index.table.th.note') }}</th>
                        <th scope="col" class="sentences-table__group">{{ __('sentence.index.table.th.group') }}</th>
                        <th scope="col" class="sentences-table__author">{{ __('sentence.index.table.th.author') }}</th>
                        <th scope="col"
                            class="sentences-table__created_at">{{ __('sentence.index.table.th.created_at') }}</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($sentences as $sentence)
                        @php
                            /** @var $sentence \App\Models\Sentence */
                        @endphp

                        <tr>
                            <td>{{ $sentence->sentence }}</td>
                            <td class="sentences-table__note">{{ $sentence->note }}</td>
                            <td class="sentences-table__group"><a
                                    href="{{ route('sentence.index', ['group' => $sentence->group->id]) }}">{{ $sentence->group->name }}</a>
                            </td>
                            <td class="sentences-table__author"><a
                                    href="{{ route('sentence.index', ['author' => $sentence->user->id]) }}">{{ $sentence->user->name }}</a>
                            </td>
                            <td class="sentences-table__created_at">{{ $sentence->created_at }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-sm-12">
                <form method="post" action="{{ route('sentence.add.do') }}" class="form">
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
        </div>
    </div>
@endsection
