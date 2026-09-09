<?php

// app/Models/BpsDerivedVariable.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpsDerivedVariable extends Model
{
    protected $table = 'data_bps.bps_derived_variables';

    protected $fillable = [
        'domain_id',
        'var_id',
        'turvar_id',
        'turvar',
        'group_turvar_id',
        'name_group_turvar',
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
