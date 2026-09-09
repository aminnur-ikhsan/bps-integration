@props([
    'options' => [],
    'placeholder' => 'Pilih...',
    'nullLabel' => null,
])

@php ($field = $attributes->wire('model')->value())

<div
    x-data="searchableSelect(@js($field))"
    x-on:keydown.escape="close()"
    x-on:click.outside="close()"
    class="relative"
>
    <button
        type="button"
        x-on:click="toggle()"
        class="flex h-10 w-full items-center justify-between gap-2 rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white px-3 text-base shadow-xs sm:text-sm dark:border-white/10 dark:bg-white/10 text-zinc-700 dark:text-zinc-300"
    >
        <span
            class="truncate"
            x-text="selectedLabel() || @js($placeholder)"
            x-bind:class="selectedLabel() ? '' : 'text-zinc-400'"
        ></span>
        <flux:icon.chevron-down variant="micro" class="size-4 shrink-0 text-zinc-400" />
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.origin.top
        class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-white/10 dark:bg-zinc-700"
    >
        <div class="p-2">
            <input
                x-ref="search"
                x-model="search"
                type="text"
                :placeholder="@js(__('Cari...'))"
                class="w-full rounded-md border border-zinc-200 bg-white px-2 py-1.5 text-sm text-zinc-700 dark:border-white/10 dark:bg-white/10 dark:text-zinc-300"
            />
        </div>

        <ul class="max-h-60 overflow-y-auto pb-1">
            @if ($nullLabel !== null)
                <li>
                    <button
                        type="button"
                        data-value=""
                        data-label="{{ $nullLabel }}"
                        x-show="matches($el)"
                        x-on:click="choose('')"
                        x-bind:class="isSelected('') ? 'bg-zinc-100 font-medium dark:bg-white/10' : ''"
                        class="block w-full px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-white/10"
                    >{{ $nullLabel }}</button>
                </li>
            @endif

            @foreach ($options as $value => $label)
                <li>
                    <button
                        type="button"
                        data-value="{{ $value }}"
                        data-label="{{ $label }}"
                        x-show="matches($el)"
                        x-on:click="choose(@js((string) $value))"
                        x-bind:class="isSelected(@js((string) $value)) ? 'bg-zinc-100 font-medium dark:bg-white/10' : ''"
                        class="block w-full px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-white/10"
                    >{{ $label }}</button>
                </li>
            @endforeach

            <li x-show="!hasResults()" class="px-3 py-2 text-sm text-zinc-400">
                {{ __('Tidak ada hasil') }}
            </li>
        </ul>
    </div>
</div>
