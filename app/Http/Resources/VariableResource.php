<?php

namespace App\Http\Resources;

use App\Models\BpsVariable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BpsVariable
 */
class VariableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain_id' => $this->domain_id,
            'var_id' => $this->var_id,
            'title' => $this->title,
            'sub_id' => $this->sub_id,
            'sub_name' => $this->sub_name,
            'def' => $this->def,
            'notes' => $this->notes,
            'unit' => $this->unit,
            'last_synced_at' => $this->last_synced_at,
        ];
    }
}
