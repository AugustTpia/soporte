<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Clave de cifrado de la bóveda de credenciales
    |--------------------------------------------------------------------------
    |
    | Clave dedicada, independiente de APP_KEY. Debe ser una clave de 32 bytes
    | codificada en base64, con el prefijo "base64:". Nunca debe versionarse.
    |
    */
    'current_key' => env('CREDENTIALS_ENCRYPTION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Claves anteriores
    |--------------------------------------------------------------------------
    |
    | Lista separada por comas de claves previas con prefijo "base64:". Permite
    | descifrar secretos existentes durante una rotación gradual.
    |
    */
    'previous_keys' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CREDENTIALS_ENCRYPTION_PREVIOUS_KEYS', '')),
    ))),
];
