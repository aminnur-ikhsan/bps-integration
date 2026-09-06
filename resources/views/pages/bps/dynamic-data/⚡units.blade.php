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
    public string $message = '';
    public bool $isError = false;

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
        return [
            'domains' => BpsDomain::orderBy('domain_name')->get(),
            'units'   => BpsUnit::where('domain_id', $this->domainId)
                ->orderBy('unit_id')
                ->paginate(20),
        ];
    }
}; ?>

<div>
        <flux:heading size="xl">Units</flux:heading>
        <flux:subheading>Daftar satuan (unit) dari BPS.</flux:subheading>

        <div class="mt-6 flex flex-wrap items-end gap-4">
            <flux:select wire:model.live="domainId" label="Domain" class="w-64">
                @foreach ($domains as $domain)
                    <flux:select.option value="{{ $domain->domain_id }}">
                        {{ $domain->domain_id }} — {{ $domain->domain_name }}
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
                        <th class="py-2 pr-4">Unit ID</th>
                        <th class="py-2 pr-4">Nama Unit</th>
                        <th class="py-2">Terakhir Disinkron</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($units as $unit)
                        <tr class="border-b hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="py-2 pr-4 font-mono">{{ $unit->unit_id }}</td>
                            <td class="py-2 pr-4">{{ $unit->unit }}</td>
                            <td class="py-2 text-zinc-500">{{ $unit->last_synced_at?->diffForHumans() }}</td>
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
            {{ $units->links() }}
        </div>
</div>
