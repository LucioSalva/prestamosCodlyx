<?php

/* ============================================================
 *  CONFIGURACIÓN — EDITA ESTOS VALORES
 *  Los obtienes en cPanel → "Bases de datos MySQL".
 *  En HostGator el nombre de la base y del usuario llevan un
 *  prefijo con tu cuenta, por ejemplo:  cpaneluser_pagos
 * ============================================================ */

return [
    // Casi siempre 'localhost' en HostGator.
    'host'    => 'localhost',

    // Nombre de la base de datos (con prefijo).
    'name'    => 'cpaneluser_pagos',

    // Usuario de la base de datos (con prefijo).
    'user'    => 'cpaneluser_pagos',

    // Contraseña que pusiste al crear el usuario MySQL.
    'pass'    => 'TU_CONTRASENA',

    'charset' => 'utf8mb4',

    // Muestra errores en pantalla (déjalo en false en producción).
    'debug'   => false,
];
