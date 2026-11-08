@if(session('alert-success') || session('status'))
    <div class="mb-4 rounded-md bg-green-50 p-4 border border-green-200 text-sm font-medium text-green-800">
        {{ session('alert-success') ?? session('status') }}
    </div>
@endif

@if(session('alert-danger') || session('error'))
    <div class="mb-4 rounded-md bg-red-50 p-4 border border-red-200 text-sm font-medium text-red-800">
        {{ session('alert-danger') ?? session('error') }}
    </div>
@endif

@if(session('alert-warning'))
    <div class="mb-4 rounded-md bg-yellow-50 p-4 border border-yellow-200 text-sm font-medium text-yellow-800">
        {{ session('alert-warning') }}
    </div>
@endif

@if(session('alert-info') || session('alert-primary') || session('alert-secondary'))
    <div class="mb-4 rounded-md bg-blue-50 p-4 border border-blue-200 text-sm font-medium text-blue-800">
        {{ session('alert-info') ?? session('alert-primary') ?? session('alert-secondary') }}
    </div>
@endif
