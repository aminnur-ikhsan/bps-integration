@props([
    'rows',
    'columns' => [],
    'empty' => 'Belum ada data.',
])

<div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-neutral-200 dark:border-neutral-700">
            <tr>
                <th class="px-4 py-3 font-medium">{{ __('No.') }}</th>
                @foreach ($columns as $column)
                    <th class="px-4 py-3 font-medium">{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="border-b border-neutral-100 last:border-0 dark:border-neutral-800">
                    <td class="px-4 py-3 text-neutral-500">{{ $rows->firstItem() + $loop->index }}</td>
                    @foreach ($columns as $column)
                        @php ($value = data_get($row, $column['field']))
                        <td class="px-4 py-3">
                            @if (($column['date'] ?? false) && $value)
                                {{ $value->locale('id')->translatedFormat('d F Y, H:i') }}
                            @elseif ($value === null || $value === '')
                                <span class="text-neutral-500">—</span>
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="px-4 py-6 text-center">
                        {{ $empty }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $rows->links() }}
