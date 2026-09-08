<?php
// resources/views/pages/bps/dynamic-data/⚡derived-variables.blade.php

use App\Models\BpsDomain;
use App\Models\BpsVariable;
use App\Models\BpsDerivedVariable;
use App\Services\Bps\DerivedVariableSync;
use App\Services\Bps\BpsApiException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Derived Variables')] class extends Component {
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
            $result = app(DerivedVariableSync::class)->sync($this->domainId, $parsedVarId, auth()->id());
            $this->message = "Berhasil menyimpan {$result->count} derived variable untuk domain {$this->domainId}.";
        } catch (BpsApiException $e) {
            $this->isError = true;
            $this->message = "Gagal: {$e->getMessage()}";
        }

        $this->resetPage();
    }

    public function with(): array
    {
        $query = BpsDerivedVariable::where('domain_id', $this->domainId);

        if ($this->varId) {
            $query->where('var_id', $this->varId);
        }

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('turvar', 'ilike', "%{$term}%")
                  ->orWhere('name_group_turvar', 'ilike', "%{$term}%");
                if (ctype_digit($term)) {
                    $q->orWhere('turvar_id', (int) $term);
                }
            });
        }

        return [
            'domains'   => BpsDomain::orderBy('domain_id')->get(),
            'variables' => BpsVariable::where('domain_id', $this->domainId)->orderBy('title')->get(),
            'turvars'   => $query->orderBy('turvar_id')->paginate(20),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Derived Variables</flux:heading>

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
        <div class="w-64">
            <flux:select wire:model.live="varId" placeholder="Semua Variabel">
                <flux:select.option value="">-- Semua Variabel --</flux:select.option>
                @foreach ($variables as $v)
                    <flux:select.option value="{{ $v->var_id }}">
                        {{ $v->var_id }} — {{ $v->title }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Cari ID, nama, atau grup')" icon="magnifying-glass" />
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-neutral-200 dark:border-neutral-700">
                <tr>
                    <th class="px-4 py-3 font-medium">No.</th>
                    <th class="px-4 py-3 font-medium">Turvar ID</th>
                    <th class="px-4 py-3 font-medium">Nama Turvar</th>
                    <th class="px-4 py-3 font-medium">Grup</th>
                    <th class="px-4 py-3 font-medium">Sync terakhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($turvars as $turvar)
                    <tr class="border-b border-neutral-100 last:border-0 dark:border-neutral-800">
                        <td class="px-4 py-3 text-neutral-500">{{ $turvars->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">{{ $turvar->turvar_id }}</td>
                        <td class="px-4 py-3">{{ $turvar->turvar }}</td>
                        <td class="px-4 py-3">{{ $turvar->name_group_turvar }}</td>
                        <td class="px-4 py-3">{{ $turvar->last_synced_at?->locale('id')->translatedFormat('d F Y, H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center">
                            Belum ada data.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $turvars->links() }}
</div>
