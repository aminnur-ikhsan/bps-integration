<?php
// resources/views/pages/bps/dynamic-data/⚡query-builder.blade.php

use App\Models\BpsSubject;
use App\Models\BpsSubjectCategory;
use App\Models\BpsVariable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Data Dinamis')] class extends Component {
    private const DOMAIN = '3200';

    public string $subcatId = '';
    public string $subId = '';
    public ?string $varId = null;

    public function updatedSubcatId(): void
    {
        $this->subId = '';
        $this->varId = null;
    }

    public function updatedSubId(): void
    {
        $this->varId = null;
    }

    #[Computed]
    public function categories()
    {
        return BpsSubjectCategory::where('domain_id', self::DOMAIN)->orderBy('title')->get();
    }

    #[Computed]
    public function subjects()
    {
        $query = BpsSubject::where('domain_id', self::DOMAIN);

        if ($this->subcatId !== '') {
            $query->where('subcat_id', $this->subcatId);
        }

        return $query->orderBy('title')->get();
    }

    #[Computed]
    public function variables()
    {
        $query = BpsVariable::where('domain_id', self::DOMAIN);

        if ($this->subId !== '') {
            $query->where('sub_id', $this->subId);
        }

        return $query->orderBy('title')->get();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <flux:heading size="xl">{{ __('Data Dinamis') }}</flux:heading>

    <div class="flex max-w-xl flex-col gap-6">
        <div>
            <flux:label>{{ __('Kategori Subjek') }}</flux:label>
            <x-searchable-select
                wire:model.live="subcatId"
                :options="$this->categories->pluck('title', 'subcat_id')"
                null-label="Semua Kategori" />
        </div>

        <div>
            <flux:label>{{ __('Subjek') }}</flux:label>
            <x-searchable-select
                wire:model.live="subId"
                :options="$this->subjects->pluck('title', 'sub_id')"
                null-label="Semua Subjek" />
        </div>

        <div>
            <flux:label>{{ __('Tabel / Indikator') }}</flux:label>
            <x-searchable-select
                wire:model.live="varId"
                :options="$this->variables->pluck('label', 'var_id')"
                placeholder="Cari tabel..." />
        </div>
    </div>
</div>
