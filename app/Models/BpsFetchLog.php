<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BpsFetchLog extends Model
{
    protected $table = 'data_bps.bps_fetch_logs';

    // Tabel ini hanya punya created_at.
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'endpoint',
        'params',
        'status',
        'http_status',
        'records_count',
        'duration_ms',
        'error',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
