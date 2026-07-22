<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Offline token lifetime
    |--------------------------------------------------------------------------
    |
    | Cuantas horas es valido el token de login offline emitido a los RFV.
    | Se piensa como "duracion de una jornada de trabajo en campo", no como
    | la sesion de servidor normal (ver SESSION_LIFETIME en config/session.php).
    |
    */

    'token_ttl_hours' => (int) env('OFFLINE_TOKEN_TTL_HOURS', 12),

];
