<?php

// app/Models/BpsVariable.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpsVariable extends Model
{
    protected $table = 'data_bps.bps_variables';

    protected $fillable = [
        'domain_id',
        'var_id',
        'title',
        'sub_id',
        'sub_name',
        'def',
        'notes',
        'vertical',
        'unit',
        'graph_id',
        'graph_name',
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

    public function getLabelAttribute(): string
    {
        return "{$this->var_id} — {$this->title}";
    }
}
