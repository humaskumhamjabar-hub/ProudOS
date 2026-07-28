<?php

return [
    'provider' => env('AI_PROVIDER', 'nonaktif'),
    'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
    'api_key' => env('AI_API_KEY'),
    'model' => env('AI_MODEL'),
    'timeout' => (int) env('AI_TIMEOUT', 30),
    'prompt_versi' => env('AI_PROMPT_VERSION', 'konten-v1'),
];
