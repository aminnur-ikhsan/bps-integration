<?php

namespace App\Models\ClientAccess;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiClient extends Model
{
    protected $table = 'data_access_clients.api_clients';

    protected $fillable = [
        'app_name',
        'token',
        'is_active',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function requestLogs(): HasMany
    {
        return $this->hasMany(ApiRequestLog::class, 'api_client_id');
    }
}
