<?php

return [
    'provider' => env('AI_PROVIDER', 'nonaktif'),
    'base_url' => env('AI_BASE_URL', 'https://router.mexia.me/v1'),
    'api_key' => env('AI_API_KEY'),
    'model' => env('AI_MODEL'),
    'timeout' => (int) env('AI_TIMEOUT', 90),
    'prompt_versi' => env('AI_PROMPT_VERSION', 'berita-atensi-v1'),
];
