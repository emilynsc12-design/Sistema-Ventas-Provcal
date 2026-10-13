<?php
/**
 * bootstrap.php
 *  – Configuración global (errores, autoload, zona horaria…)
 */

/* 0️⃣  Búfer de salida
 *      encabezado.php imprime HTML ANTES de session_start() y de las
 *      llamadas a header(). Sin este búfer, en algunos hosting PHP ya
 *      envió la página y la redirección tras el login falla en silencio.
 *      Con ob_start() todo se guarda en memoria hasta el final.
 */
if (function_exists('ob_start') && !ob_get_level()) {
    ob_start();
}

/* 1️⃣  Ajusta los avisos de error */
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', '0');           // nada en pantalla
ini_set('log_errors', '1');           // pero sí al log
ini_set('error_log', __DIR__ . '/php-error.log');

/* 2️⃣  Zona horaria de la app */
date_default_timezone_set('America/Lima');

/* 3️⃣  Autoloader de Composer (librerías) */
require_once __DIR__ . '/vendor/autoload.php';

/* 4️⃣  Carga la configuración de la base de datos
 *      Prioridad:
 *        1) config_local.php  -> archivo PHP (más seguro: el navegador
 *                                nunca puede descargar su contenido)
 *        2) .env              -> archivo de texto plano
 *      Ambos archivos están en .gitignore y NUNCA se suben a Git.
 */

/* 4-a) Soporte para config_local.php */
$rutaLocal = __DIR__ . '/config_local.php';
if (is_readable($rutaLocal)) {
    $cfg = include $rutaLocal;
    if (is_array($cfg)) {
        foreach ($cfg as $clave => $valor) {
            if (getenv($clave) === false) {
                $v = (string) $valor;
                putenv("$clave=$v");
                $_ENV[$clave] = $v;
            }
        }
    }
}

/* 4-b) Soporte para .env (sobrescribe solo lo que falte) */
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
