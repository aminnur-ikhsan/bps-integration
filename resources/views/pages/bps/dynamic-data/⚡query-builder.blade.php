<?php
// resources/views/pages/bps/dynamic-data/⚡query-builder.blade.php

use App\Models\BpsDerivedPeriod;
use App\Models\BpsDerivedVariable;
use App\Models\BpsPeriod;
use App\Models\BpsSubject;
use App\Models\BpsSubjectCategory;
use App\Models\BpsVariable;
use App\Models\BpsVerticalVariable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Data Dinamis')] class extends Component {
    private const DOMAIN = '3200';

    public string $subcatId = '';
    public string $subId = '';
    public ?string $varId = null;

    public array $years = [];
    public array $turyears = [];
    public array $characteristics = [];
    public array $vervars = [];

    public function updatedSubcatId(): void
    {
        $this->subId = '';
        $this->resetTableForm();
    }

    public function updatedSubId(): void
    {
        $this->resetTableForm();
    }

    public function updatedVarId(): void
    {
        $this->years = [];
        $this->turyears = [];
        $this->characteristics = [];
        $this->vervars = [];

        if (! $this->varId) {
            return;
        }

        // Default: semua Judul Baris tercentang, sama seperti situs asli.
        foreach ($this->vervarOptions as $vervar) {
            $this->vervars[] = (string) $vervar->vervar_id;
        }
    }

    private function resetTableForm(): void
    {
        $this->varId = null;
        $this->years = [];
        $this->turyears = [];
        $this->characteristics = [];
        $this->vervars = [];
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

    #[Computed]
    public function yearOptions()
    {
        if (! $this->varId) {
            return collect();
        }

        return BpsPeriod::where('domain_id', self::DOMAIN)->where('var_id', $this->varId)->orderBy('th_id')->get();
    }

    #[Computed]
    public function turyearOptions()
    {
        if (! $this->varId) {
            return collect();
        }

        return BpsDerivedPeriod::where('domain_id', self::DOMAIN)->where('var_id', $this->varId)->orderBy('turth_id')->get();
    }

    #[Computed]
    public function characteristicOptions()
    {
        if (! $this->varId) {
            return collect();
        }

        return BpsDerivedVariable::where('domain_id', self::DOMAIN)->where('var_id', $this->varId)->orderBy('turvar_id')->get();
    }

    #[Computed]
    public function vervarOptions()
    {
        if (! $this->varId) {
            return collect();
        }

        return BpsVerticalVariable::where('domain_id', self::DOMAIN)->where('var_id', $this->varId)->orderBy('vervar_id')->get();
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

        @if ($varId)
            <div>
                <flux:label>{{ __('Tahun') }}</flux:label>
                <x-checkbox-group
                    wire:model.live="years"
                    :options="$this->yearOptions->pluck('th', 'th_id')"
                    empty="Belum ada data tahun untuk tabel ini." />
            </div>

            <div>
                <flux:label>{{ __('Turunan Tahun') }}</flux:label>
                <x-checkbox-group
                    wire:model.live="turyears"
                    :options="$this->turyearOptions->pluck('turth', 'turth_id')"
                    empty="Belum ada data turunan tahun untuk tabel ini." />
            </div>

            @if ($this->characteristicOptions->isNotEmpty())
                <div>
                    <flux:label>{{ __('Karakteristik') }}</flux:label>
                    <x-checkbox-group
                        wire:model.live="characteristics"
                        :options="$this->characteristicOptions->pluck('turvar', 'turvar_id')" />
                </div>
            @endif

            <div>
                <flux:label>{{ __('Judul Baris') }}</flux:label>
                <x-checkbox-group
                    wire:model.live="vervars"
                    :options="$this->vervarOptions->pluck('vervar', 'vervar_id')"
                    empty="Belum ada data judul baris untuk tabel ini." />
            </div>
        @endif
    </div>
</div>
