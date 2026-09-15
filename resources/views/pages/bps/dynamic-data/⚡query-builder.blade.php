<?php
// resources/views/pages/bps/dynamic-data/⚡query-builder.blade.php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Data Dinamis')] class extends Component {
    private const DOMAIN = '3200';
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <flux:heading size="xl">{{ __('Data Dinamis') }}</flux:heading>
</div>
