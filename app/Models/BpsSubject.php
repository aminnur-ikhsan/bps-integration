<?php

namespace App\Models;

use App\Relations\CompositeBelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpsSubject extends Model
{
    protected $table = 'data_bps.bps_subjects';

    protected $fillable = [
        'domain_id',
        'sub_id',
        'subcat_id',
        'title',
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

    /**
     * Relasi ke BpsSubjectCategory
     * BPS tidak menjamin relasi sempurna antar endpoint, jadi relasi bersifat opsional
     */
    public function category()
    {
        $instance = $this->newRelatedInstance(BpsSubjectCategory::class);

        return new CompositeBelongsTo(
            $instance->newQuery(),
            $this,
            'subcat_id',
            'subcat_id',
            'domain_id',
            'domain_id',
            'category'
        );
    }
}
