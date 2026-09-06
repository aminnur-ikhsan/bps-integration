<?php
// app/Models/BpsUnit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpsUnit extends Model
{
    protected $table = 'data_bps.bps_units';

    protected $fillable = [
        'domain_id',
        'unit_id',
        'unit',
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
