<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BpsConnectionLog extends Model
{
    protected $table = 'data_bps.bps_connection_logs';

    // Tabel ini hanya punya created_at.
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'method',
        'base_url',
        'path',
        'http_status',
        'dns_ms',
        'connect_ms',
        'ttfb_ms',
        'total_ms',
        'response_bytes',
        'request_header',
        'request_parameters',
        'response_header',
        'response_body',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'request_header' => 'array',
            'request_parameters' => 'array',
            'response_header' => 'array',
            'response_body' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
