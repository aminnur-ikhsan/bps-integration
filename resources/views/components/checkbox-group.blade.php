@props([
    'options' => [],
    'empty' => 'Belum ada data.',
])

@if (count($options) === 0)
    <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ $empty }}</p>
@else
    <flux:checkbox.group {{ $attributes }}>
        @foreach ($options as $value => $label)
            <flux:checkbox value="{{ $value }}" label="{{ $label }}" />
        @endforeach
    </flux:checkbox.group>
@endif
