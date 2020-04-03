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

                    <button type="submit" class="btn btn-primary">{{ __('group.index.search.submit') }}</button>
                </form>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-sm-12">
                <table class="table table-striped table-hover table-sm groups-table">
                    <thead>
                    <tr>
                        <th scope="col">{{ __('group.index.table.th.name') }}</th>
                        <th scope="col"
                            class="groups-table__sentences">{{ __('group.index.table.th.go_to_sentences') }}</th>
                        <th scope="col" class="groups-table__author">{{ __('group.index.table.th.author') }}</th>
                        <th scope="col"
                            class="groups-table__created_at">{{ __('group.index.table.th.created_at') }}</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($groups as $group)
                        @php
                            /** @var $group \App\Models\Group */
                        @endphp

                        <tr>
                            <td>{{$group->name}}</td>
                            <td class="groups-table__sentences"><a
                                    href="{{ route('sentence.index', ['group' => $group->id]) }}">
                                    <svg class="bi bi-link" width="1em" height="1em" viewBox="0 0 16 16"
                                         fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M6.354 5.5H4a3 3 0 000 6h3a3 3 0 002.83-4H9c-.086 0-.17.01-.25.031A2 2 0 017 10.5H4a2 2 0 110-4h1.535c.218-.376.495-.714.82-1z"/>
                                        <path
                                            d="M6.764 6.5H7c.364 0 .706.097 1 .268A1.99 1.99 0 019 6.5h.236A3.004 3.004 0 008 5.67a3 3 0 00-1.236.83z"/>
                                        <path
                                            d="M9 5.5a3 3 0 00-2.83 4h1.098A2 2 0 019 6.5h3a2 2 0 110 4h-1.535a4.02 4.02 0 01-.82 1H12a3 3 0 100-6H9z"/>
                                        <path
                                            d="M8 11.33a3.01 3.01 0 001.236-.83H9a1.99 1.99 0 01-1-.268 1.99 1.99 0 01-1 .268h-.236c.332.371.756.66 1.236.83z"/>
                                    </svg>
                                </a></td>
                            <td class="sentences-table__author"><a
                                    href="{{ route('group.index', ['author' => $group->user->id]) }}">{{ $group->user->name }}</a>
                            </td>
                            <td class="sentences-table__created_at">{{ $group->created_at }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-sm-12">
                <form method="post" action="{{ route('group.add.do') }}" class="form-inline">
                    @csrf

                    <div class="form-group">
                        <label for="groupIndexAddName" class="mr-1">{{ __('group.index.add.label') }}</label>
                        <input type="text" name="name" id="groupIndexAddName" class="form-control mr-1"
                               autocomplete="off"/>
                    </div>

                    <button type="submit" class="btn btn-primary">{{ __('group.index.add.submit') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection
