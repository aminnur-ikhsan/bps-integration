<?php
// resources/views/pages/bps/dynamic-data/⚡units.blade.php

use App\Models\BpsDomain;
use App\Models\BpsUnit;
use App\Services\Bps\UnitSync;
use App\Services\Bps\BpsApiException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Units')] class extends Component {
    use WithPagination;

    public string $domainId = '3200';
    public string $search = '';
    public string $message = '';
    public bool $isError = false;

    public function updatedDomainId(): void
    {
        $this->resetPage();
        $this->search = '';
        $this->message = '';
        $this->isError = false;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function fetch(): void
    {
        $this->message = '';
        $this->isError = false;

        try {
            $result = app(UnitSync::class)->sync($this->domainId, auth()->id());
            $this->message = "Berhasil menyimpan {$result->count} unit untuk domain {$this->domainId}.";
        } catch (BpsApiException $e) {
            $this->isError = true;
            $this->message = "Gagal: {$e->getMessage()}";
        }

        $this->resetPage();
    }

    public function with(): array
    {
        $query = BpsUnit::where('domain_id', $this->domainId);

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('unit', 'ilike', "%{$term}%");
                if (ctype_digit($term)) {
                    $q->orWhere('unit_id', (int) $term);
                }
            });
        }

        return [
            'domains' => BpsDomain::orderBy('domain_id')->get(),
            'units'   => $query->orderBy('unit_id')->paginate(20),
            'columns' => [
                ['label' => 'Unit ID', 'field' => 'unit_id'],
                ['label' => 'Nama Unit', 'field' => 'unit'],
                ['label' => 'Sync terakhir', 'field' => 'last_synced_at', 'date' => true],
            ],
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Units</flux:heading>

        <flux:button wire:click="fetch" wire:loading.attr="disabled" variant="primary" icon="arrow-down-tray">
            <span wire:loading.remove wire:target="fetch">Fetch Data</span>
            <span wire:loading wire:target="fetch">Mengambil...</span>
        </flux:button>
    </div>

    @if ($message)
        <flux:callout :variant="$isError ? 'danger' : 'success'">
            <flux:callout.text>{{ $message }}</flux:callout.text>
        </flux:callout>
    @endif

    <div class="flex items-center gap-4">
        <div class="w-64">
            <x-searchable-select
                wire:model.live="domainId"
                :options="$domains->pluck('label', 'domain_id')"
                placeholder="Pilih Wilayah..." />
        </div>
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Cari ID atau nama satuan')" icon="magnifying-glass" />
        </div>
    </div>

    <x-data-table :rows="$units" :columns="$columns" empty="Belum ada data." />
</div>
