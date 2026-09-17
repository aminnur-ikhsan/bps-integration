@props([
    'label',
    'field',
    'searchField',
    'options' => [],
    'selected' => [],
    'groupLabel' => null,
    'empty' => 'Belum ada data.',
])

@php
    $totalCount = count($options);
    $useList = false;
    $maxLength = 0;

    foreach ($options as $optionLabel) {
        $maxLength = max($maxLength, mb_strlen($optionLabel));
    }

    if ($totalCount > 20 || $maxLength > 25) {
        $useList = true;
    }
@endphp

<div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10">
    <div class="mb-3 flex items-start justify-between gap-2 border-b border-zinc-200 pb-2 dark:border-white/10">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium">{{ $label }}</span>
                @if ($totalCount > 0)
                    <span class="rounded bg-zinc-100 px-2 py-0.5 text-xs text-zinc-600 dark:bg-white/10 dark:text-zinc-300">
                        {{ count($selected) }} {{ __('dari') }} {{ $totalCount }}
                    </span>
                @endif
            </div>
            @if ($groupLabel)
                <p class="mt-0.5 text-xs text-zinc-400">{{ $groupLabel }}</p>
            @endif
        </div>

        @if ($totalCount > 0)
            <flux:button wire:click="toggleAll('{{ $field }}')" size="sm">{{ __('Pilih Semua') }}</flux:button>
        @endif
    </div>

    @if ($totalCount === 0)
        <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ $empty }}</p>
    @elseif ($useList)
        <flux:input
            wire:model.live.debounce.300ms="{{ $searchField }}"
            icon="magnifying-glass"
            placeholder="{{ __('Cari...') }}"
            class="mb-3" />

        <div class="grid max-h-64 grid-cols-1 gap-x-4 gap-y-2 overflow-y-auto sm:grid-cols-2">
            @foreach ($options as $value => $optionLabel)
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" wire:model.live="{{ $field }}" value="{{ $value }}" class="mt-0.5" />
                    {{ $optionLabel }}
                </label>
            @endforeach
        </div>
    @else
        <div class="flex flex-wrap gap-2">
            @foreach ($options as $value => $optionLabel)
                @php ($isSelected = in_array((string) $value, $selected, true))

                <button
                    type="button"
                    wire:click="toggle('{{ $field }}', '{{ $value }}')"
                    @class([
                        'rounded-full border px-3 py-1 text-sm',
                        'border-zinc-800 bg-zinc-800 text-white dark:border-white dark:bg-white dark:text-zinc-900' => $isSelected,
                        'border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400' => ! $isSelected,
                    ])
                >{{ $optionLabel }}</button>
            @endforeach
        </div>
    @endif
</div>
