<?php

namespace App\Http\Resources;

use App\Models\BpsSubject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BpsSubject
 */
class SubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain_id' => $this->domain_id,
            'sub_id' => $this->sub_id,
            'subcat_id' => $this->subcat_id,
            'title' => $this->title,
            'last_synced_at' => $this->last_synced_at,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'subcat_id' => $this->category->subcat_id,
                'title' => $this->category->title,
            ] : null,
        ];
    }
}
