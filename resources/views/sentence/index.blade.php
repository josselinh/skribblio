@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <form class="form-inline">
                    <div class="form-group mx-sm-3 mb-2">
                        <input type="text" name="search" class="form-control" autocomplete="off"
                               value="{{ request()->input('search') }}"/>
                    </div>

                    <button type="submit" class="btn btn-primary mb-2">{{__('sentence.index.search.submit')}}</button>
                </form>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-sm-12">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm sentences-table">
                        <thead>
                        <tr>
                            <th scope="col">{{ __('sentence.index.table.th.sentence') }}</th>
                            <th scope="col" class="sentences-table__note">{{ __('sentence.index.table.th.note') }}</th>
                            <th scope="col"
                                class="sentences-table__group">{{ __('sentence.index.table.th.group') }}</th>
                            <th scope="col"
                                class="sentences-table__author">{{ __('sentence.index.table.th.author') }}</th>
                            <th scope="col"
                                class="sentences-table__created_at">{{ __('sentence.index.table.th.created_at') }}</th>
                            <th scope="col"
                                class="sentences-table__actions">{{ __('sentence.index.table.th.actions') }}</th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($sentences as $sentence)
                            @php
                                /** @var $sentence \App\Models\Sentence */
                            @endphp

                            <tr>
                                <td>{{ $sentence->sentence }}</td>
                                <td class="sentences-table__note">
                                    {{ $sentence->note }}
                                </td>
                                <td class="sentences-table__group"><a
                                        href="{{ route('sentence.index', ['group' => $sentence->group->id]) }}">{{ $sentence->group->name }}</a>
                                </td>
                                <td class="sentences-table__author"><a
                                        href="{{ route('sentence.index', ['author' => $sentence->user->id]) }}">{{ $sentence->user->name }}</a>
                                </td>
                                <td class="sentences-table__created_at">{{ $sentence->created_at }}</td>
                                <td class="sentences-table__actions">
                                    <x-sentence-vote-buttons :sentence="$sentence"/>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    {{ $sentences->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
