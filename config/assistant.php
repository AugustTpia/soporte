<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Asistente de soporte (Gemini)
    |--------------------------------------------------------------------------
    |
    | La llave se lee de GEMINI_API_KEY en .env. No hay llave por defecto y
    | nunca se guarda una llave real en el repositorio. El usuario la crea
    | en Google AI Studio y la pone en .env.
    |
    | Modelo principal probado: gemini-3.5-flash. gemini-2.5-flash ya no
    | está disponible para cuentas nuevas (404) y gemini-3.8-flash /
    | gemini-flash-latest devuelven 503 por demanda, por eso no están
    | en la lista de fallbacks.
    |
    */

    'api_key' => env('GEMINI_API_KEY'),

    'model' => env('GEMINI_MODEL', 'gemini-3.5-flash'),

    'fallbacks' => [
        'gemini-3.5-flash',
        'gemini-3.6-flash',
        'gemini-3.1-flash-lite',
    ],

    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),

    'timeout' => 30,
];
