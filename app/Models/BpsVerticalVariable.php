<?php

// app/Models/BpsVerticalVariable.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpsVerticalVariable extends Model
{
    protected $table = 'data_bps.bps_vertical_variables';

    protected $fillable = [
        'domain_id',
        'var_id',
        'vervar_id',
        'vervar',
        'item_ver_id',
        'group_ver_id',
        'name_group_ver_id',
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
