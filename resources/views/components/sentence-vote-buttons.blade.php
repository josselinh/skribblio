<form method="post" action="{{ route('sentence.vote.do') }}" class="inline-flex items-center space-x-1">
    @csrf

    <input type="hidden" name="sentence_id" value="{{ $sentence->id }}"/>

    <div class="inline-flex items-center space-x-1" role="group">
        @if($negativeButton)
            <button name="note" value="-1" type="submit" title="-1"
                    class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                    <path d="M18.9 11.75a1.25 1.25 0 1 1-2.5 0v-7.5a1.25 1.25 0 1 1 2.5 0v7.5ZM8.9 17v1.3c0 .94.76 1.7 1.7 1.7h.6c.45 0 .88-.18 1.2-.5l3-3c.32-.32.5-.75.5-1.2V6.7a1.7 1.7 0 0 0-1.7-1.7H7c-.76 0-1.43.51-1.63 1.24l-1.6 6c-.26.98.48 1.96 1.5 1.96h2.33c-.45.8-.7 1.76-.7 2.8Z" />
                </svg>
            </button>
        @endif

        @if($positiveButton)
            <button name="note" value="+1" type="submit" title="+1"
                    class="p-1.5 text-green-500 hover:text-green-700 hover:bg-green-50 rounded transition focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                    <path d="M1 8.25a1.25 1.25 0 1 1 2.5 0v7.5a1.25 1.25 0 1 1-2.5 0v-7.5ZM11 3V1.7c0-.94-.76-1.7-1.7-1.7h-.6a1.7 1.7 0 0 0-1.2.5l-3 3A1.7 1.7 0 0 0 4 4.7v8.6a1.7 1.7 0 0 0 1.7 1.7h7.2c.76 0 1.43-.51 1.63-1.24l1.6-6c.26-.98-.48-1.96-1.5-1.96H12.3c.45-.8.7-1.76.7-2.8Z" />
                </svg>
            </button>
        @endif
    </div>
</form>
