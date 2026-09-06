<?php
// app/Models/BpsPeriod.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpsPeriod extends Model
{
    protected $table = 'data_bps.bps_periods';

    protected $fillable = [
        'domain_id',
        'var_id',
        'th_id',
        'th',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(BpsDomain::class, 'domain_id', 'domain_id');
    }
}
