<?php

use App\Models\BpsDomain;
use App\Models\BpsSubjectCategory;
use App\Models\BpsSubject;
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
    public function categoryCount(): int
    {
        return BpsSubjectCategory::count();
    }

    #[Computed]
    public function subjectCount(): int
    {
        return BpsSubject::count();
    }

    #[Computed]
    public function lastSyncedAt(): ?string
    {
        $domainSync = BpsDomain::max('last_synced_at');
        $catSync = BpsSubjectCategory::max('last_synced_at');
        $subSync = BpsSubject::max('last_synced_at');

        $max = max(array_filter([$domainSync, $catSync, $subSync]));

        if (empty($max)) {
            return null;
        }

        return Date::parse($max)->locale('id')->translatedFormat('d F Y, H:i');
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>

    <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-48 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:text class="text-sm">{{ __('Domain tersimpan') }}</flux:text>
            <flux:heading size="lg" class="mt-1">{{ $this->domainCount }}</flux:heading>
        </div>

        <div class="flex-1 min-w-48 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:text class="text-sm">{{ __('Kategori Subjek') }}</flux:text>
            <flux:heading size="lg" class="mt-1">{{ $this->categoryCount }}</flux:heading>
        </div>

        <div class="flex-1 min-w-48 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:text class="text-sm">{{ __('Subjek tersimpan') }}</flux:text>
            <flux:heading size="lg" class="mt-1">{{ $this->subjectCount }}</flux:heading>
        </div>

        <div class="flex-1 min-w-48 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:text class="text-sm">{{ __('Sync terakhir') }}</flux:text>
            <flux:heading size="lg" class="mt-1">{{ $this->lastSyncedAt ?? '—' }}</flux:heading>
        </div>
    </div>
</div>

