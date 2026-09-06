<?php
// app/Models/BpsDerivedPeriod.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpsDerivedPeriod extends Model
{
    protected $table = 'data_bps.bps_derived_periods';

    protected $fillable = [
        'domain_id',
        'var_id',
        'turth_id',
        'turth',
        'group_turth_id',
        'name_group_turth',
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
