<?php
// resources/views/pages/bps/dynamic-data/⚡periods.blade.php

use App\Models\BpsDomain;
use App\Models\BpsVariable;
use App\Models\BpsPeriod;
use App\Services\Bps\PeriodSync;
use App\Services\Bps\BpsApiException;
use Livewire\Volt\Component;
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
            $result = app(PeriodSync::class)->sync($this->domainId, $parsedVarId, auth()->id());
            $this->message = "Berhasil menyimpan {$result->count} period untuk domain {$this->domainId}.";
        } catch (BpsApiException $e) {
            $this->isError = true;
            $this->message = "Gagal: {$e->getMessage()}";
        }

        $this->resetPage();
    }

    public function with(): array
    {
        $query = BpsPeriod::where('domain_id', $this->domainId);
        if ($this->varId) {
            $query->where('var_id', $this->varId);
        }

        return [
            'domains'   => BpsDomain::orderBy('domain_name')->get(),
            'variables' => BpsVariable::where('domain_id', $this->domainId)->orderBy('title')->get(),
            'periods'   => $query->orderBy('th_id')->paginate(20),
        ];
    }
}; ?>

<div>
        <flux:heading size="xl">Periods</flux:heading>
        <flux:subheading>Daftar periode (tahun) dari BPS.</flux:subheading>

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
                        <th class="py-2 pr-4">Th ID</th>
                        <th class="py-2 pr-4">Tahun (Label)</th>
                        <th class="py-2">Terakhir Disinkron</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($periods as $period)
                        <tr class="border-b hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="py-2 pr-4 font-mono">{{ $period->th_id }}</td>
                            <td class="py-2 pr-4">{{ $period->th }}</td>
                            <td class="py-2 text-zinc-500">{{ $period->last_synced_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-6 text-center text-zinc-500">
                                Belum ada data.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $periods->links() }}
        </div>
</div>
