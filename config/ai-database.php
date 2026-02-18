<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    */
    'default' => env('AI_DATABASE_PROVIDER', 'anthropic'),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    */
    'connection' => env('AI_DATABASE_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    */
    'strict_mode' => env('AI_DATABASE_STRICT_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Table Limit
    |--------------------------------------------------------------------------
    */
    'max_tables' => env('AI_DATABASE_MAX_TABLES', 15),

    /*
    |--------------------------------------------------------------------------
    | AI Providers Configuration
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-20250514'),
            'max_tokens' => 2048,
            'temperature' => 0.1,
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'max_tokens' => 2048,
            'temperature' => 0.1,
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
            'temperature' => 0.1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('AI_DATABASE_CACHE_ENABLED', true),
        'ttl' => env('AI_DATABASE_CACHE_TTL', 3600),
        'prefix' => 'ai_database',
    ],
];