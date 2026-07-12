<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BrasilApiController extends Controller
{
    /**
     * List all Brazilian states.
     */
    public function states(): JsonResponse
    {
        $states = Cache::remember('brasil_api_states', now()->addDay(), function () {
            $response = Http::get('https://brasilapi.com.br/api/ibge/uf/v1');

            if ($response->failed()) {
                return [];
            }

            $data = $response->json();
            usort($data, fn ($a, $b) => strcasecmp($a['nome'], $b['nome']));

            return $data;
        });

        return response()->json($states);
    }

    /**
     * List cities for a given state.
     */
    public function cities(string $uf): JsonResponse
    {
        $uf = strtoupper($uf);

        $cities = Cache::remember("brasil_api_cities_{$uf}", now()->addDay(), function () use ($uf) {
            $response = Http::get("https://brasilapi.com.br/api/ibge/municipios/v1/{$uf}");

            if ($response->failed()) {
                return [];
            }

            $data = $response->json();
            usort($data, fn ($a, $b) => strcasecmp($a['nome'], $b['nome']));

            return $data;
        });

        return response()->json($cities);
    }
}
