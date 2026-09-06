<?php
// resources/views/pages/bps/dynamic-data/⚡variables.blade.php

use App\Models\BpsDomain;
use App\Models\BpsVariable;
use App\Services\Bps\VariableSync;
use App\Services\Bps\BpsApiException;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $domainId = '3200';
    public string $message = '';
    public bool $isError = false;

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
        return [
            'domains'   => BpsDomain::orderBy('domain_name')->get(),
            'variables' => BpsVariable::where('domain_id', $this->domainId)
                ->orderBy('title')
                ->paginate(20),
        ];
    }
}; ?>

<div>
        <flux:heading size="xl">Variables</flux:heading>
        <flux:subheading>Daftar variabel dari BPS untuk domain terpilih.</flux:subheading>

        <div class="mt-6 flex items-end gap-4">
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
                        <th class="py-2 pr-4">Var ID</th>
                        <th class="py-2 pr-4">Judul</th>
                        <th class="py-2 pr-4">Subjek</th>
                        <th class="py-2 pr-4">Satuan</th>
                        <th class="py-2">Terakhir Disinkron</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($variables as $var)
                        <tr class="border-b hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="py-2 pr-4 font-mono">{{ $var->var_id }}</td>
                            <td class="py-2 pr-4">{{ $var->title }}</td>
                            <td class="py-2 pr-4">{{ $var->sub_name }}</td>
                            <td class="py-2 pr-4">{{ $var->unit }}</td>
                            <td class="py-2 text-zinc-500">{{ $var->last_synced_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-zinc-500">
                                Belum ada data. Pilih domain dan tekan Fetch Data.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $variables->links() }}
        </div>
</div>
