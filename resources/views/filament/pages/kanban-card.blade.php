<div class="p-4 space-y-1 text-sm text-gray-800 dark:text-white">
    <div class="text-base font-semibold">
        {{ $record->title }}
    </div>

    @if ($record->due_date)
        <div class="text-gray-600 dark:text-gray-300">
            📅 Due: {{ $record->due_date->format('M d, Y') }}
        </div>
    @endif

    <div class="text-gray-600 dark:text-gray-300">
        ⚡ Priority: {{ ucfirst($record->priority) }}
    </div>

    <div class="text-gray-600 dark:text-gray-300">
        👤 Assigned to: {{ $record->user?->name ?? 'Unassigned' }}
    </div>
</div>
