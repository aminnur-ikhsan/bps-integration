<?php

use App\Models\BpsDomain;
use App\Models\BpsSubject;
use App\Services\Bps\BpsApiException;
use App\Services\Bps\SubjectSync;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Subjek BPS')] class extends Component {
    use WithPagination;

    public string $domainId = '3200';
    public $subcatId = '';
    public string $search = '';

    public string $message = '';
    public bool $failed = false;

    public function fetchData(SubjectSync $sync): void
    {
        $this->message = '';
        $this->failed = false;

        try {
            $result = $sync->sync($this->domainId, auth()->id());
            $this->message = "{$result->count} subjek tersimpan.";
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
        $this->subcatId = '';
        $this->search = '';
    }

    public function updatedSubcatId(): void
    {
        $this->resetPage();
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
        return \App\Models\BpsSubjectCategory::where('domain_id', $this->domainId)
            ->orderBy('title')
            ->get();
    }

    #[Computed]
    public function subjects()
    {
        $query = BpsSubject::with('category')->where('domain_id', $this->domainId);

        if ($this->subcatId !== '') {
            $query->where('subcat_id', $this->subcatId);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'ilike', '%'.$this->search.'%')
                  ->orWhere('sub_id', 'ilike', '%'.$this->search.'%')
                  ->orWhereHas('category', function ($qc) {
                      $qc->where('title', 'ilike', '%'.$this->search.'%');
                  });
            });
        }

        return $query->orderBy('sub_id')->paginate(25);
    }

    public function with(): array
    {
        return [
            'columns' => [
                ['label' => 'ID Subjek', 'field' => 'sub_id'],
                ['label' => 'Judul', 'field' => 'title'],
                ['label' => 'Kategori', 'field' => 'category.title'],
                ['label' => 'Sync terakhir', 'field' => 'last_synced_at', 'date' => true],
            ],
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Subjek BPS') }}</flux:heading>

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
        <div class="w-64">
            <flux:select wire:model.live="subcatId" placeholder="Semua Kategori">
                <flux:select.option value="">Semua Kategori</flux:select.option>
                @foreach ($this->categories as $category)
                    <flux:select.option value="{{ $category->subcat_id }}">{{ $category->title }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Cari ID atau judul subjek')" icon="magnifying-glass" />
        </div>
    </div>

    <x-data-table :rows="$this->subjects" :columns="$columns" empty="Belum ada data untuk wilayah ini. Klik Fetch Data untuk mengambil dari BPS." />
</div>
