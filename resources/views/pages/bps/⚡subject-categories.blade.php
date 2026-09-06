<?php

use App\Models\BpsDomain;
use App\Models\BpsSubjectCategory;
use App\Services\Bps\BpsApiException;
use App\Services\Bps\SubjectCategorySync;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Kategori Subjek BPS')] class extends Component {
    use WithPagination;

    public string $domainId = '3200';
    public string $search = '';

    public string $message = '';
    public bool $failed = false;

    public function fetchData(SubjectCategorySync $sync): void
    {
        $this->message = '';
        $this->failed = false;

        try {
            $result = $sync->sync($this->domainId, auth()->id());
            $this->message = "{$result->count} kategori subjek tersimpan.";
        } catch (BpsApiException $e) {
            $this->message = $e->getMessage();
            $this->failed = true;
        }

        $this->resetPage();
    }

    public function updatedDomainId(): void
    {
        $this->resetPage();
        $this->message = '';
        $this->failed = false;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function domains()
    {
        return BpsDomain::orderBy('domain_id')->get();
    }

    #[Computed]
    public function categories()
    {
        $query = BpsSubjectCategory::where('domain_id', $this->domainId);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'ilike', '%'.$this->search.'%')
                  ->orWhere('subcat_id', 'ilike', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('subcat_id')->paginate(25);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Kategori Subjek BPS') }}</flux:heading>

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

    <div class="flex items-center gap-4">
        <div class="w-64">
            <flux:select wire:model.live="domainId" placeholder="Pilih Wilayah...">
                @foreach ($this->domains as $domain)
                    <flux:select.option value="{{ $domain->domain_id }}">{{ $domain->domain_name }} ({{ $domain->domain_id }})</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Cari ID atau judul kategori')" icon="magnifying-glass" />
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-neutral-200 dark:border-neutral-700">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('No.') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ID Kategori') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Judul') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Sync terakhir') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->categories as $category)
                    <tr class="border-b border-neutral-100 last:border-0 dark:border-neutral-800">
                        <td class="px-4 py-3 text-neutral-500">{{ $this->categories->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">{{ $category->subcat_id }}</td>
                        <td class="px-4 py-3">{{ $category->title }}</td>
                        <td class="px-4 py-3">{{ $category->last_synced_at?->locale('id')->translatedFormat('d F Y, H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center">
                            {{ __('Belum ada data untuk wilayah ini. Klik Fetch Data untuk mengambil dari BPS.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->categories->links() }}
</div>
