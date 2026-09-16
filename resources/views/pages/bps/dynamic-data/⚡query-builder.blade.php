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

    public array $selected = [];
    public ?string $resultJson = null;

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
        // Semua kotak mulai kosong — user yang pilih sendiri, tidak ada default tercentang.
        $this->years = [];
        $this->turyears = [];
        $this->characteristics = [];
        $this->vervars = [];
    }

    private function resetTableForm(): void
    {
        $this->varId = null;
        $this->years = [];
        $this->turyears = [];
        $this->characteristics = [];
        $this->vervars = [];
    }

    public function aturUlang(): void
    {
        $this->resetTableForm();
    }

    public function tambah(): void
    {
        if (! $this->canAdd) {
            return;
        }

        $this->selected[] = [
            'var_id' => $this->varId,
            'title' => BpsVariable::where('domain_id', self::DOMAIN)->where('var_id', $this->varId)->value('title'),
            'years' => $this->years,
            'turyears' => $this->turyears,
            'characteristics' => $this->characteristics,
            'vervars' => $this->vervars,
        ];

        $this->resultJson = null;
        $this->resetTableForm();
    }

    public function hapus(int $index): void
    {
        unset($this->selected[$index]);
        $this->selected = array_values($this->selected);
        $this->resultJson = null;
    }

    public function submit(): void
    {
        $params = [];

        foreach ($this->selected as $entry) {
            $params[] = $this->buildParams($entry);
        }

        $this->resultJson = json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function buildParams(array $entry): array
    {
        $params = [
            'model' => 'data',
            'domain' => self::DOMAIN,
            'lang' => 'ind',
            'var' => (int) $entry['var_id'],
            'th' => $this->joinIds($entry['years']),
            'turth' => $this->joinIds($entry['turyears']),
        ];

        if ($entry['characteristics'] !== []) {
            $params['turvar'] = $this->joinIds($entry['characteristics']);
        }

        $params['vervar'] = $this->joinIds($entry['vervars']);
        $params['key'] = '****';

        return $params;
    }

    private function joinIds(array $ids): string
    {
        $numeric = [];

        foreach ($ids as $id) {
            $numeric[] = (int) $id;
        }

        sort($numeric);

        return implode(';', $numeric);
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

    #[Computed]
    public function canAdd(): bool
    {
        if (! $this->varId) {
            return false;
        }

        if (count($this->years) === 0 || count($this->turyears) === 0 || count($this->vervars) === 0) {
            return false;
        }

        if ($this->characteristicOptions->isNotEmpty() && count($this->characteristics) === 0) {
            return false;
        }

        return true;
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

    @if ($varId)
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
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

            <div>
                <flux:label>{{ __('Karakteristik') }}</flux:label>
                <x-checkbox-group
                    wire:model.live="characteristics"
                    :options="$this->characteristicOptions->pluck('turvar', 'turvar_id')"
                    empty="Belum ada data karakteristik untuk tabel ini." />
            </div>

            <div>
                <flux:label>{{ __('Judul Baris') }}</flux:label>
                <x-checkbox-group
                    wire:model.live="vervars"
                    :options="$this->vervarOptions->pluck('vervar', 'vervar_id')"
                    empty="Belum ada data judul baris untuk tabel ini." />
            </div>
        </div>

        <div class="flex gap-2">
            <flux:button wire:click="aturUlang" variant="ghost">{{ __('Atur Ulang') }}</flux:button>
            <flux:button wire:click="tambah" variant="primary" icon="plus" :disabled="! $this->canAdd">
                {{ __('Tambah') }}
            </flux:button>
        </div>
    @endif

    @if ($selected !== [])
        <div class="flex flex-col gap-2">
            <flux:heading size="sm">{{ __('Data Terpilih') }} ({{ count($selected) }})</flux:heading>

            @foreach ($selected as $index => $entry)
                <div class="flex items-start justify-between gap-4 rounded-xl border border-zinc-200 p-3 dark:border-white/10">
                    <div>
                        <p class="text-sm font-medium">{{ $entry['title'] }}</p>
                        <p class="text-xs text-zinc-500">
                            {{ count($entry['years']) }} tahun,
                            {{ count($entry['turyears']) }} turunan tahun,
                            {{ count($entry['characteristics']) }} karakteristik,
                            {{ count($entry['vervars']) }} judul baris
                        </p>
                    </div>
                    <flux:button wire:click="hapus({{ $index }})" variant="ghost" size="sm" icon="trash" />
                </div>
            @endforeach

            <flux:button wire:click="submit" variant="primary">{{ __('Submit') }}</flux:button>
        </div>
    @endif

    @if ($resultJson)
        <div class="relative" x-data>
            <flux:button
                class="absolute right-2 top-2"
                size="sm"
                icon="clipboard"
                x-on:click="navigator.clipboard.writeText($refs.jsonBlock.innerText)">
                {{ __('Salin') }}
            </flux:button>

            <pre x-ref="jsonBlock" class="overflow-x-auto rounded-lg bg-zinc-100 p-4 text-xs dark:bg-zinc-800">{{ $resultJson }}</pre>
        </div>
    @endif
</div>
