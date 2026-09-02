<?php

use App\Models\BpsDomain;
use Illuminate\Support\Facades\Date;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Computed]
    public function domainCount(): int
    {
        return BpsDomain::count();
    }

    #[Computed]
    public function lastSyncedAt(): ?string
    {
        // max() melewati cast Eloquent, jadi yang kembali string mentah.
        $value = BpsDomain::max('last_synced_at');

        return $value
            ? Date::parse($value)->locale('id')->translatedFormat('d F Y, H:i')
            : null;
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            <flux:text>{{ __('Domain tersimpan') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $this->domainCount }}</flux:heading>
        </div>

        <div class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            <flux:text>{{ __('Sync terakhir') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $this->lastSyncedAt ?? '—' }}</flux:heading>
        </div>
    </div>
</div>
