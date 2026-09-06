<?php
// resources/views/pages/bps/dynamic-data/⚡derived-periods.blade.php

use App\Models\BpsDomain;
use App\Models\BpsVariable;
use App\Models\BpsDerivedPeriod;
use App\Services\Bps\DerivedPeriodSync;
use App\Services\Bps\BpsApiException;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $domainId = '3200';
    public ?string $varId = null;
    public string $message = '';
    public bool $isError = false;

    public function updatedDomainId()
    {
        $this->varId = null;
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

        return [
            'domains'   => BpsDomain::orderBy('domain_name')->get(),
            'variables' => BpsVariable::where('domain_id', $this->domainId)->orderBy('title')->get(),
            'turths'    => $query->orderBy('turth_id')->paginate(20),
        ];
    }
}; ?>

<div>
        <flux:heading size="xl">Derived Periods</flux:heading>
        <flux:subheading>Daftar periode turunan (misal kuartal, bulan) dari BPS.</flux:subheading>

        <div class="mt-6 flex flex-wrap items-end gap-4">
            <flux:select wire:model.live="domainId" label="Domain" class="w-64">
                @foreach ($domains as $domain)
                    <flux:select.option value="{{ $domain->domain_id }}">
                        {{ $domain->domain_id }} — {{ $domain->domain_name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="varId" label="Variabel (Opsional)" class="w-80">
                <flux:select.option value="">-- Semua Variabel --</flux:select.option>
                @foreach ($variables as $v)
                    <flux:select.option value="{{ $v->var_id }}">
                        {{ $v->var_id }} — {{ $v->title }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:button wire:click="fetch" wire:loading.attr="disabled" variant="primary">
                <span wire:loading.remove wire:target="fetch">Fetch Data</span>
                <span wire:loading wire:target="fetch">Mengambil...</span>
            </flux:button>
        </div>

        @if ($message)
            <flux:callout class="mt-4" :variant="$isError ? 'danger' : 'success'" :dismissible="true">
                {{ $message }}
            </flux:callout>
        @endif

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="py-2 pr-4">Turth ID</th>
                        <th class="py-2 pr-4">Nama Turth</th>
                        <th class="py-2 pr-4">Grup</th>
                        <th class="py-2">Terakhir Disinkron</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($turths as $turth)
                        <tr class="border-b hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="py-2 pr-4 font-mono">{{ $turth->turth_id }}</td>
                            <td class="py-2 pr-4">{{ $turth->turth }}</td>
                            <td class="py-2 pr-4">{{ $turth->name_group_turth }}</td>
                            <td class="py-2 text-zinc-500">{{ $turth->last_synced_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-zinc-500">
                                Belum ada data.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $turths->links() }}
        </div>
</div>
