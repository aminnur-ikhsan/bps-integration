<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BpsDomain extends Model
{
    protected $fillable = [
        'domain_id',
        'domain_name',
        'domain_url',
        'type',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }
}
