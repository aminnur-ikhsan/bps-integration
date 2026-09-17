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

    private const array TOGGLE_FIELDS = ['years', 'turyears', 'characteristics', 'vervars'];

    public string $subcatId = '';
    public string $subId = '';
    public ?string $varId = null;

    public array $years = [];
    public array $turyears = [];
    public array $characteristics = [];
    public array $vervars = [];

    public string $yearSearch = '';
    public string $turyearSearch = '';
    public string $characteristicSearch = '';
    public string $vervarSearch = '';

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
        $this->yearSearch = '';
        $this->turyearSearch = '';
        $this->characteristicSearch = '';
        $this->vervarSearch = '';
    }

    private function resetTableForm(): void
    {
        $this->varId = null;
        $this->years = [];
        $this->turyears = [];
        $this->characteristics = [];
        $this->vervars = [];
        $this->yearSearch = '';
        $this->turyearSearch = '';
        $this->characteristicSearch = '';
        $this->vervarSearch = '';
    }

    public function aturUlang(): void
    {
        $this->resetTableForm();
    }

    public function toggle(string $field, string $value): void
    {
        if (! in_array($field, self::TOGGLE_FIELDS, true)) {
            return;
        }

        $current = $this->{$field};

        if (in_array($value, $current, true)) {
            $this->{$field} = array_values(array_diff($current, [$value]));

            return;
        }

        $current[] = $value;
        $this->{$field} = $current;
    }

    public function toggleAll(string $field): void
    {
        if (! in_array($field, self::TOGGLE_FIELDS, true)) {
            return;
        }

        // "Visible" = hasil query saat ini, sudah ikut terfilter pencarian
        // kalau kotak cari sedang dipakai. Pilih Semua cuma bekerja pada apa
        // yang sedang tampil, dan tidak boleh menghapus centang lain yang
        // kebetulan sedang tidak tampil karena filter pencarian.
        $visible = [];

        foreach ($this->fieldOptions($field) as $option) {
            $visible[] = (string) $option->{$this->fieldIdColumn($field)};
        }

        $current = $this->{$field};
        $allVisibleAlreadySelected = array_diff($visible, $current) === [];

        if ($allVisibleAlreadySelected) {
            $this->{$field} = array_values(array_diff($current, $visible));

            return;
        }

        $this->{$field} = array_values(array_unique(array_merge($current, $visible)));
    }

    private function fieldOptions(string $field): \Illuminate\Support\Collection
    {
        return match ($field) {
            'years' => $this->yearOptions,
            'turyears' => $this->turyearOptions,
            'characteristics' => $this->characteristicOptions,
            'vervars' => $this->vervarOptions,
            default => collect(),
        };
    }

    private function fieldIdColumn(string $field): string
    {
        return match ($field) {
            'years' => 'th_id',
            'turyears' => 'turth_id',
            'characteristics' => 'turvar_id',
            'vervars' => 'vervar_id',
            default => '',
        };
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

        $query = BpsPeriod::where('domain_id', self::DOMAIN)->where('var_id', $this->varId);

        if ($this->yearSearch !== '') {
            $query->where('th', 'ilike', '%'.$this->yearSearch.'%');
        }

        return $query->orderBy('th_id')->get();
    }

    #[Computed]
    public function turyearOptions()
    {
        if (! $this->varId) {
            return collect();
        }

        $query = BpsDerivedPeriod::where('domain_id', self::DOMAIN)->where('var_id', $this->varId);

        if ($this->turyearSearch !== '') {
            $query->where('turth', 'ilike', '%'.$this->turyearSearch.'%');
        }

        return $query->orderBy('turth_id')->get();
    }

    #[Computed]
    public function characteristicOptions()
    {
        if (! $this->varId) {
            return collect();
        }

        $query = BpsDerivedVariable::where('domain_id', self::DOMAIN)->where('var_id', $this->varId);

        if ($this->characteristicSearch !== '') {
            $query->where('turvar', 'ilike', '%'.$this->characteristicSearch.'%');
        }

        return $query->orderBy('turvar_id')->get();
    }

    #[Computed]
    public function vervarOptions()
    {
        if (! $this->varId) {
            return collect();
        }

        $query = BpsVerticalVariable::where('domain_id', self::DOMAIN)->where('var_id', $this->varId);

        if ($this->vervarSearch !== '') {
            $query->where('vervar', 'ilike', '%'.$this->vervarSearch.'%');
        }

        return $query->orderBy('vervar_id')->get();
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

    <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
        <div class="flex flex-col gap-6 lg:w-80 lg:flex-shrink-0 lg:sticky lg:top-6 lg:z-10">
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
            <div class="flex flex-1 flex-col gap-4">
                <x-selection-card
                    label="{{ __('Tahun') }}"
                    field="years"
                    search-field="yearSearch"
                    :options="$this->yearOptions->pluck('th', 'th_id')"
                    :selected="$years"
                    empty="Belum ada data tahun untuk tabel ini." />

                <x-selection-card
                    label="{{ __('Turunan Tahun') }}"
                    field="turyears"
                    search-field="turyearSearch"
                    :options="$this->turyearOptions->pluck('turth', 'turth_id')"
                    :selected="$turyears"
                    :group-label="$this->turyearOptions->first()?->name_group_turth"
                    empty="Belum ada data turunan tahun untuk tabel ini." />

                <x-selection-card
                    label="{{ __('Karakteristik') }}"
                    field="characteristics"
                    search-field="characteristicSearch"
                    :options="$this->characteristicOptions->pluck('turvar', 'turvar_id')"
                    :selected="$characteristics"
                    :group-label="$this->characteristicOptions->first()?->name_group_turvar"
                    empty="Belum ada data karakteristik untuk tabel ini." />

                <x-selection-card
                    label="{{ __('Judul Baris') }}"
                    field="vervars"
                    search-field="vervarSearch"
                    :options="$this->vervarOptions->pluck('vervar', 'vervar_id')"
                    :selected="$vervars"
                    :group-label="$this->vervarOptions->first()?->name_group_ver_id"
                    empty="Belum ada data judul baris untuk tabel ini." />
            </div>
        @endif
    </div>

    @if ($varId)
        <div class="flex items-center justify-between gap-4 border-t border-zinc-200 pt-4 dark:border-white/10">
            <p class="text-sm text-zinc-500">
                @if ($this->canAdd)
                    {{ count($years) }} {{ __('tahun') }} · {{ count($turyears) }} {{ __('turunan tahun') }} · {{ count($characteristics) }} {{ __('karakteristik') }} · {{ count($vervars) }} {{ __('judul baris') }}
                @else
                    {{ __('Pilih minimal satu di tiap bagian wajib untuk mengaktifkan Tambah.') }}
                @endif
            </p>

            <div class="flex gap-2">
                <flux:button wire:click="aturUlang" variant="ghost">{{ __('Atur Ulang') }}</flux:button>
                <flux:button wire:click="tambah" variant="primary" icon="plus" :disabled="! $this->canAdd">
                    {{ __('Tambah') }}
                </flux:button>
            </div>
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
