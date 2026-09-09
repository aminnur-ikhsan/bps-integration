<?php
// resources/views/pages/bps/dynamic-data/⚡vertical-variables.blade.php

use App\Models\BpsDomain;
use App\Models\BpsVariable;
use App\Models\BpsVerticalVariable;
use App\Services\Bps\VerticalVariableSync;
use App\Services\Bps\BpsApiException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Vertical Variables')] class extends Component {
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
            $result = app(VerticalVariableSync::class)->sync($this->domainId, $parsedVarId, auth()->id());
            $this->message = "Berhasil menyimpan {$result->count} vertical variable untuk domain {$this->domainId}.";
        } catch (BpsApiException $e) {
            $this->isError = true;
            $this->message = "Gagal: {$e->getMessage()}";
        }

        $this->resetPage();
    }

    public function with(): array
    {
        $query = BpsVerticalVariable::where('domain_id', $this->domainId);

        if ($this->varId) {
            $query->where('var_id', $this->varId);
        }

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('vervar', 'ilike', "%{$term}%")
                  ->orWhere('name_group_ver_id', 'ilike', "%{$term}%");
                if (ctype_digit($term)) {
                    $q->orWhere('vervar_id', (int) $term);
                }
            });
        }

        return [
            'domains'   => BpsDomain::orderBy('domain_id')->get(),
            'variables' => BpsVariable::where('domain_id', $this->domainId)->orderBy('title')->get(),
            'vervars'   => $query->orderBy('vervar_id')->paginate(20),
            'columns'   => [
                ['label' => 'Vervar ID', 'field' => 'vervar_id'],
                ['label' => 'Nama Vervar', 'field' => 'vervar'],
                ['label' => 'Grup', 'field' => 'name_group_ver_id'],
                ['label' => 'Sync terakhir', 'field' => 'last_synced_at', 'date' => true],
            ],
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Vertical Variables</flux:heading>

        {{-- Tombol Fetch Data disembunyikan sementara: sinkronisasi vertical variable belum siap. --}}
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

    <x-data-table :rows="$vervars" :columns="$columns" empty="Belum ada data." />
</div>
