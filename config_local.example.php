<?php
/**
 * PLANTILLA de configuración para el HOSTING.
 *
 * Cómo usarla:
 *   1. Copia este archivo y renómbralo a:  config_local.php
 *   2. Cambia los valores por los que te dio tu hosting
 *      (en InfinityFree los ves en el panel → MySQL Databases)
 *   3. Sube config_local.php junto al resto del sitio
 *
 * ⚠️  config_local.php está en .gitignore: NUNCA se sube a Git.
 *     Y como es PHP, el navegador jamás puede descargar su contenido,
 *     así que es más seguro que un archivo .env normal.
 *
 * Nota: InfinityFree NO usa "localhost" como servidor de base de datos.
 *       Ahí te dan un host como  sqlXXX.infinityfree.com
 */
return [
    'DB_HOST' => 'localhost',        // o sqlXXX.infinityfree.com
    'DB_PORT' => '3306',
    'DB_NAME' => 'tu_base_de_datos',
    'DB_USER' => 'tu_usuario',
    'DB_PASS' => 'tu_contrasena',
];
