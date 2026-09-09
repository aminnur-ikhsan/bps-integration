<?php
// resources/views/pages/bps/dynamic-data/⚡derived-periods.blade.php

use App\Models\BpsDomain;
use App\Models\BpsVariable;
use App\Models\BpsDerivedPeriod;
use App\Services\Bps\DerivedPeriodSync;
use App\Services\Bps\BpsApiException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Derived Periods')] class extends Component {
    use WithPagination;

    public string $domainId = '3200';
    public ?string $varId = null;
    public string $search = '';
    public string $message = '';
    public bool $isError = false;

    public function updatedDomainId(): void
    {
        $this->resetPage();
        $this->varId = null;
        $this->search = '';
        $this->message = '';
        $this->isError = false;
    }

    public function updatedVarId(): void
    {
        $this->resetPage();
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
            $parsedVarId = $this->varId ? (int) $this->varId : null;
            $result = app(DerivedPeriodSync::class)->sync($this->domainId, $parsedVarId, auth()->id());
            $this->message = "Berhasil menyimpan {$result->count} derived period untuk domain {$this->domainId}.";
        } catch (BpsApiException $e) {
            $this->isError = true;
            $this->message = "Gagal: {$e->getMessage()}";
        }

        $this->resetPage();
    }

    public function with(): array
    {
        $query = BpsDerivedPeriod::where('domain_id', $this->domainId);

        if ($this->varId) {
            $query->where('var_id', $this->varId);
        }

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('turth', 'ilike', "%{$term}%")
                  ->orWhere('name_group_turth', 'ilike', "%{$term}%");
                if (ctype_digit($term)) {
                    $q->orWhere('turth_id', (int) $term);
                }
            });
        }

        return [
            'domains'   => BpsDomain::orderBy('domain_id')->get(),
            'variables' => BpsVariable::where('domain_id', $this->domainId)->orderBy('title')->get(),
            'turths'    => $query->orderBy('turth_id')->paginate(20),
            'columns'   => [
                ['label' => 'Turth ID', 'field' => 'turth_id'],
                ['label' => 'Nama Turth', 'field' => 'turth'],
                ['label' => 'Grup', 'field' => 'name_group_turth'],
                ['label' => 'Sync terakhir', 'field' => 'last_synced_at', 'date' => true],
            ],
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Derived Periods</flux:heading>

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
        <div class="w-64">
            <x-searchable-select
                wire:model.live="varId"
                :options="$variables->pluck('label', 'var_id')"
                null-label="-- Semua Variabel --" />
        </div>
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Cari ID, nama, atau grup')" icon="magnifying-glass" />
        </div>
    </div>

    <x-data-table :rows="$turths" :columns="$columns" empty="Belum ada data." />
</div>
