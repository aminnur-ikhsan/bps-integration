<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VariableResource;
use App\Models\BpsVariable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VariableController extends Controller
{
    // Kolom var_id dan sub_id bertipe integer 32-bit di Postgres.
    private const MAX_INT = 2147483647;

    public function index(Request $request): AnonymousResourceCollection
    {
        $domainId = $request->attributes->get('domain_id');

        $query = BpsVariable::where('domain_id', $domainId);

        $varIdParam = $request->query('var_id');

        if (is_string($varIdParam) && $varIdParam !== '') {
            $query->whereIn('var_id', $this->parseVarIds($varIdParam));
        }

        $subIdParam = $request->query('sub_id');

        if (is_string($subIdParam) && $subIdParam !== '') {
            $query->where('sub_id', $this->safeInt($subIdParam));
        }

        $variables = $query->orderBy('var_id')->get();

        return VariableResource::collection($variables);
    }

    public function show(Request $request): VariableResource|JsonResponse
    {
        $domainId = $request->attributes->get('domain_id');

        // Dibaca dari route, bukan argumen method: Laravel mengisi argumen
        // berurutan, dan parameter pertama grup ini adalah {region}.
        $varId = (string) $request->route('var_id');

        $variable = BpsVariable::where('domain_id', $domainId)
            ->where('var_id', $this->safeInt($varId))
            ->first();

        if ($variable === null) {
            return response()->json(['message' => 'Variabel tidak ditemukan.'], 404);
        }

        return new VariableResource($variable);
    }

    /**
     * Pecah "1,2,3" jadi [1, 2, 3]. Token yang bukan angka positif dibuang.
     *
     * @return int[]
     */
    private function parseVarIds(string $csv): array
    {
        $varIds = [];

        foreach (explode(',', $csv) as $token) {
            $varId = $this->safeInt($token);

            if ($varId > 0) {
                $varIds[] = $varId;
            }
        }

        return $varIds;
    }

    // Angka di luar rentang integer 32-bit pasti tidak cocok dengan baris manapun,
    // tapi kalau dikirim apa adanya Postgres melempar error 500. Jadikan 0 saja.
    private function safeInt(string $value): int
    {
        $number = (int) $value;

        if ($number < 0 || $number > self::MAX_INT) {
            return 0;
        }

        return $number;
    }
}
