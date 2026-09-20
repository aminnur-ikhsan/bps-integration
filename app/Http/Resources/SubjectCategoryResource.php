<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain_id' => $this->domain_id,
            'subcat_id' => $this->subcat_id,
            'title' => $this->title,
            'last_synced_at' => $this->last_synced_at,
        ];
    }
}
