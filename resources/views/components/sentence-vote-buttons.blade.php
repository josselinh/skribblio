<form method="post" action="{{ route('sentence.vote.do') }}" class="form-inline">
    @csrf

    <input type="hidden" name="sentence_id" value="{{ $sentence->id }}"/>

    <div class="btn-group btn-group-toggle inline" data-toggle="buttons">
        @if($negativeButton)
            <button name="note" value="-1" type="submit"
                    class="btn btn-danger btn-sm">-
            </button>
        @endif

        @if($positiveButton)
            <button name="note" value="+1" type="submit"
                    class="btn btn-success btn-sm">+
            </button>
        @endif
    </div>
</form>
