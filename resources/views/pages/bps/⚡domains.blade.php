<?php

use App\Models\BpsDomain;
use App\Services\Bps\BpsApiException;
use App\Services\Bps\DomainSync;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Domain BPS')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $message = '';

    public bool $failed = false;

    public function fetchData(DomainSync $sync): void
    {
        $this->message = '';
        $this->failed = false;

        try {
            $result = $sync->sync('all', auth()->id());
            $this->message = "{$result->count} domain tersimpan.";
        } catch (BpsApiException $e) {
            $this->message = $e->getMessage();
            $this->failed = true;
        }

        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function domains()
    {
        $query = BpsDomain::query();

        if ($this->search !== '') {
            $query->where('domain_name', 'ilike', '%'.$this->search.'%')
                ->orWhere('domain_id', 'ilike', '%'.$this->search.'%');
        }

        return $query->orderBy('domain_id')->paginate(25);
    }

    public function with(): array
    {
        return [
            'columns' => [
                ['label' => 'Kode', 'field' => 'domain_id'],
                ['label' => 'Nama', 'field' => 'domain_name'],
                ['label' => 'URL', 'field' => 'domain_url'],
                ['label' => 'Sync terakhir', 'field' => 'last_synced_at', 'date' => true],
            ],
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Domain BPS') }}</flux:heading>

        <flux:button wire:click="fetchData" wire:loading.attr="disabled" variant="primary" icon="arrow-down-tray">
            <span wire:loading.remove wire:target="fetchData">{{ __('Fetch Data') }}</span>
            <span wire:loading wire:target="fetchData">{{ __('Mengambil...') }}</span>
        </flux:button>
    </div>

    @if ($message)
        <flux:callout :variant="$failed ? 'danger' : 'success'">
            <flux:callout.text>{{ $message }}</flux:callout.text>
        </flux:callout>
    @endif

    <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Cari kode atau nama domain')" icon="magnifying-glass" />

    <x-data-table :rows="$this->domains" :columns="$columns" empty="Belum ada data. Klik Fetch Data untuk mengambil dari BPS." />
</div>
