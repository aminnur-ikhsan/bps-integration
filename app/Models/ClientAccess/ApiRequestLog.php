<?php

namespace App\Models\ClientAccess;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiRequestLog extends Model
{
    protected $table = 'data_access_clients.api_request_logs';

    // Tabel ini hanya punya created_at.
    public $timestamps = false;

    protected $fillable = [
        'api_client_id',
        'method',
        'path',
        'ip_address',
        'user_agent',
        'http_status',
        'duration_ms',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }
}
