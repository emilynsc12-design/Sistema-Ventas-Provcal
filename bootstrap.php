<?php
/**
 * bootstrap.php
 *  – Configuración global (errores, autoload, zona horaria…)
 */

/* 1️⃣  Ajusta los avisos de error */
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', '0');           // nada en pantalla
ini_set('log_errors', '1');           // pero sí al log
ini_set('error_log', __DIR__ . '/php-error.log');

/* 2️⃣  Zona horaria de la app */
date_default_timezone_set('America/Lima');

/* 3️⃣  Autoloader de Composer (librerías) */
require_once __DIR__ . '/vendor/autoload.php';

/* 4️⃣  Carga las variables de entorno del archivo .env
 *      (archivo local, NO se sube a Git — ver .gitignore)
 */
function cargarVariablesEntorno(string $rutaArchivo): void
{
    if (!is_readable($rutaArchivo)) {
        return;                       // no existe .env: se usan valores por defecto
    }

    $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lineas as $linea) {
        $linea = trim($linea);

        // Ignorar líneas vacías y comentarios
        if ($linea === '' || $linea[0] === '#') {
            continue;
        }

        // Formato  CLAVE=valor
        $igual = strpos($linea, '=');
        if ($igual === false) {
            continue;
        }

        $clave = trim(substr($linea, 0, $igual));
        $valor = trim(substr($linea, $igual + 1));

        // Quitar comillas envolventes (simples o dobles)
        if ($valor !== '' && ($valor[0] === '"' || $valor[0] === "'")) {
            $valor = trim($valor, "\"'");
        }

        if ($clave === '' || getenv($clave) !== false) {
            continue;                  // no pisar variables ya definidas en el sistema
        }

        putenv("$clave=$valor");
        $_ENV[$clave] = $valor;
    }
}

cargarVariablesEntorno(__DIR__ . '/.env');
