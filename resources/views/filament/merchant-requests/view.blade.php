@php($requester = $record->user)
@php($status = $record->status ?? 'pending')

<div class="w-full max-w-none">
<div class="space-y-6">

    {{-- HEADER ROW --}}
    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">
                Merchant Requests
            </h3>
            <p class="text-sm text-slate-500">
                ID #{{ $record->id }} · {{ $record->created_at?->format('M j, Y g:i A') }}
            </p>
        </div>

        <span @class([
            'text-xs font-medium px-3 py-1 rounded-full',
            'bg-amber-100 text-amber-700' => $status === 'pending',
            'bg-green-100 text-green-700' => $status === 'approved',
            'bg-red-100 text-red-700'     => $status === 'rejected',
        ])>
            {{ ucfirst($status) }}
        </span>
    </div>

    {{-- ROW: REQUESTER + BACKEND --}}
    <div class="grid grid-cols-2 gap-6">

        {{-- REQUESTER --}}
        <div class="space-y-3 min-w-0">
            <h4 class="text-xs font-semibold text-slate-400 uppercase">
                Requester
            </h4>

            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-slate-200 flex items-center justify-center font-semibold">
                    {{ strtoupper(substr($requester->name, 0, 1)) }}
                </div>

                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ $requester->name }}
                    </p>
                    <p class="text-xs text-slate-500">
                        {{ $requester->email }}
                    </p>
                </div>
            </div>

            <p class="text-xs text-slate-500">
                User ID #{{ $requester->id }}
            </p>
        </div>

        {{-- BACKEND --}}
        <div class="space-y-3 min-w-0">
            <h4 class="text-xs font-semibold text-slate-400 uppercase">
                Backend
            </h4>

            <div>
                <p class="text-xs text-slate-400">Account</p>
                <p class="text-sm text-slate-900">
                    {{ $record->backendUser?->email ?? '—' }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-slate-400">Handled By</p>
                    <p class="text-sm text-slate-900">
                        {{ $record->handledBy?->name ?? '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-slate-400">Handled At</p>
                    <p class="text-sm text-slate-900">
                        {{ $record->handled_at?->format('M j, Y g:i A') ?? '—' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ROW: MESSAGE --}}
    <div>
        <h4 class="text-xs font-semibold text-slate-400 uppercase mb-2">
            Request Message
        </h4>
        <div class="bg-slate-50 rounded-lg p-3 text-sm text-slate-700">
            {{ $record->message }}
        </div>
    </div>

    {{-- ROW: DECISION --}}
    <div>
        <h4 class="text-xs font-semibold text-slate-400 uppercase mb-2">
            Decision
        </h4>

        <p class="text-sm text-slate-700">
            {{ $record->decision_note ?? 'No decision provided.' }}
        </p>

        @if ($status === 'pending')
            <p class="text-xs text-amber-600 mt-2">
                Awaiting review
            </p>
        @endif
    </div>

</div>
