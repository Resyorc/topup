<?php

return [
    'provider'      => env('AI_PROVIDER', 'openai'),
    'api_key'       => env('AI_API_KEY', env('OPENAI_API_KEY')),
    'default_model' => env('AI_DEFAULT_MODEL', 'gpt-4o-mini'),
    'timeout'       => (int) env('AI_TIMEOUT', 30),
    'max_tokens'    => (int) env('AI_MAX_TOKENS', 2048),
    'log_enabled'   => (bool) env('AI_LOG_ENABLED', true),
];
