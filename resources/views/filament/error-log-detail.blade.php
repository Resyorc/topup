<div class="space-y-4 text-sm">

    {{-- Meta --}}
    <div class="grid grid-cols-2 gap-3">
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Waktu</p>
            <p class="font-mono">{{ $record->occurred_at->format('d M Y, H:i:s') }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Level</p>
            <span class="inline-block rounded px-2 py-0.5 text-xs font-bold uppercase
                {{ $record->level === 'critical' ? 'bg-red-600 text-white' : ($record->level === 'error' ? 'bg-yellow-500 text-black' : 'bg-blue-500 text-white') }}">
                {{ $record->level }}
            </span>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">URL</p>
            <p class="font-mono break-all">{{ $record->url ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Method / IP</p>
            <p class="font-mono">{{ $record->method ?? '-' }} &nbsp;·&nbsp; {{ $record->ip ?? '-' }}</p>
        </div>
        @if($record->user)
        <div class="col-span-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">User</p>
            <p>{{ $record->user->name }} (ID: {{ $record->user_id }})</p>
        </div>
        @endif
    </div>

    <hr class="border-gray-700">

    {{-- Exception & Message --}}
    <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Exception Class</p>
        <p class="font-mono text-red-400">{{ $record->exception ?? '-' }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Pesan</p>
        <p class="text-gray-100">{{ $record->message }}</p>
    </div>
    @if($record->file)
    <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">File</p>
        <p class="font-mono text-xs break-all text-gray-300">{{ $record->file }}:{{ $record->line }}</p>
    </div>
    @endif

    {{-- Stack Trace --}}
    @if($record->trace)
    <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Stack Trace</p>
        <pre class="overflow-auto max-h-72 rounded bg-gray-900 p-3 text-xs text-gray-300 leading-relaxed whitespace-pre-wrap break-all">{{ $record->trace }}</pre>
    </div>
    @endif

</div>
