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
