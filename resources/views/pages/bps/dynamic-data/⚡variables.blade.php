<?php
// resources/views/pages/bps/dynamic-data/⚡variables.blade.php

use App\Models\BpsDomain;
use App\Models\BpsVariable;
use App\Services\Bps\VariableSync;
use App\Services\Bps\BpsApiException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Variables')] class extends Component {
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
            $result = app(VariableSync::class)->sync($this->domainId, auth()->id());
            $this->message = "Berhasil menyimpan {$result->count} variable untuk domain {$this->domainId}.";
        } catch (BpsApiException $e) {
            $this->isError = true;
            $this->message = "Gagal: {$e->getMessage()}";
        }

        $this->resetPage();
    }

    public function with(): array
    {
        $query = BpsVariable::where('domain_id', $this->domainId);

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'ilike', "%{$term}%")
                  ->orWhere('sub_name', 'ilike', "%{$term}%");
                if (ctype_digit($term)) {
                    $q->orWhere('var_id', (int) $term);
                }
            });
        }

        return [
            'domains'   => BpsDomain::orderBy('domain_id')->get(),
            'variables' => $query->orderBy('title')->paginate(20),
            'columns'   => [
                ['label' => 'Var ID', 'field' => 'var_id'],
                ['label' => 'Judul', 'field' => 'title'],
                ['label' => 'Subjek', 'field' => 'sub_name'],
                ['label' => 'Satuan', 'field' => 'unit'],
                ['label' => 'Sync terakhir', 'field' => 'last_synced_at', 'date' => true],
            ],
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Variables</flux:heading>

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
            <flux:select wire:model.live="domainId" placeholder="Pilih Wilayah...">
                @foreach ($domains as $domain)
                    <flux:select.option value="{{ $domain->domain_id }}">
                        {{ $domain->domain_id }} — {{ $domain->domain_name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Cari ID, judul, atau subjek')" icon="magnifying-glass" />
        </div>
    </div>

    <x-data-table :rows="$variables" :columns="$columns" empty="Belum ada data. Pilih domain dan tekan Fetch Data." />
</div>
