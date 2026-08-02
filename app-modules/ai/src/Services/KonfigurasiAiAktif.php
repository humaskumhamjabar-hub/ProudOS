<?php

namespace Modules\Ai\Services;

use Illuminate\Database\QueryException;
use Modules\Ai\Models\KonfigurasiAi;

class KonfigurasiAiAktif
{
    /** @return array{provider: string, base_url: string, api_key: ?string, model: ?string, timeout: int, prompt_versi: string} */
    public function get(): array
    {
        $fallback = [
            'provider' => (string) config('ai.provider', 'nonaktif'),
            'base_url' => (string) config('ai.base_url', 'https://router.mexia.me/v1'),
            'api_key' => config('ai.api_key'),
            'model' => config('ai.model'),
            'timeout' => (int) config('ai.timeout', 90),
            'prompt_versi' => (string) config('ai.prompt_versi', 'berita-atensi-v1'),
        ];

        try {
            $tersimpan = KonfigurasiAi::query()->latest('id')->first();
        } catch (QueryException) {
            return $fallback;
        }

        if (! $tersimpan) {
            return $fallback;
        }

        return [
            'provider' => $tersimpan->provider,
            'base_url' => $tersimpan->base_url ?: $fallback['base_url'],
            'api_key' => $tersimpan->api_key,
            'model' => $tersimpan->model,
            'timeout' => $tersimpan->timeout,
            'prompt_versi' => $tersimpan->prompt_versi,
        ];
    }
}
