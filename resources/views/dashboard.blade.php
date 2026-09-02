<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
                <flux:text>{{ __('Domain tersimpan') }}</flux:text>
                <flux:heading size="xl" class="mt-2">&mdash;</flux:heading>
            </div>

            <div class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
                <flux:text>{{ __('Sync terakhir') }}</flux:text>
                <flux:heading size="xl" class="mt-2">&mdash;</flux:heading>
            </div>
        </div>
    </div>
</x-layouts::app>
