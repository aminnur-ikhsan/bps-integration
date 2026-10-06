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
            'notes' => $this->cleanNotes($this->notes),
            'unit' => $this->unit,
            'last_synced_at' => $this->last_synced_at,
        ];
    }

    /**
     * Notes dari BPS berupa HTML ter-escape dan kadang berisi ribuan paragraf kosong.
     * Dibersihkan jadi teks polos, satu paragraf per baris. Kosong jadi null.
     */
    private function cleanNotes(?string $notes): ?string
    {
        if ($notes === null) {
            return null;
        }

        // "&lt;p&gt;" jadi "<p>", lalu penutup paragraf dan <br> jadi baris baru.
        $text = html_entity_decode($notes, ENT_QUOTES | ENT_HTML5);
        $text = preg_replace('#</p>|<br\s*/?>#i', "\n", $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5);

        $lines = [];

        foreach (explode("\n", $text) as $line) {
            $line = trim(preg_replace('/[\s\x{00a0}]+/u', ' ', $line));

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        if ($lines === []) {
            return null;
        }

        return implode("\n", $lines);
    }
}
