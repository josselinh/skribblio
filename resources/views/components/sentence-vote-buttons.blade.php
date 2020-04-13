<form method="post" action="{{ route('sentence.vote.do') }}" class="form-inline">
    @csrf

    <input type="hidden" name="sentence_id" value="{{ $sentence->id }}"/>

    <div class="btn-group btn-group-toggle inline" data-toggle="buttons">
        @if($negativeButton)
            <button name="note" value="-1" type="submit"
                    class="btn btn-sm text-danger fa fa-thumbs-down">
            </button>
        @endif

        @if($positiveButton)
            <button name="note" value="+1" type="submit"
                    class="btn btn-sm text-success fa fa-thumbs-up">
            </button>
        @endif
    </div>
</form>
