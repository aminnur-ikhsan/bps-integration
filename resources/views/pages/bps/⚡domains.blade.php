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
        return BpsDomain::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('domain_name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('domain_id', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('domain_id')
            ->paginate(25);
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

        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-neutral-200 dark:border-neutral-700">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Kode') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Nama') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('URL') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Sync terakhir') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->domains as $domain)
                        <tr class="border-b border-neutral-100 last:border-0 dark:border-neutral-800">
                            <td class="px-4 py-3">{{ $domain->domain_id }}</td>
                            <td class="px-4 py-3">{{ $domain->domain_name }}</td>
                            <td class="px-4 py-3">{{ $domain->domain_url }}</td>
                            <td class="px-4 py-3">{{ $domain->last_synced_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center">
                                {{ __('Belum ada data. Klik Fetch Data untuk mengambil dari BPS.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $this->domains->links() }}
    </div>
