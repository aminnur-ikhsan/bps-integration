<?php

namespace App\Services\Bps;

use Carbon\CarbonInterface;

class SyncResult
{
    public function __construct(
        public readonly int $count,
        public readonly CarbonInterface $syncedAt,
    ) {}
}
