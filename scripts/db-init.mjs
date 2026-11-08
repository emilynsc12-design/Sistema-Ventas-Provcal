/**
 * db-init.mjs  →  npm run db:init
 *
 * Crea la base de datos e importa:
 *   1) esquema.sql            → las 5 tablas
 *   2) crear_usuario_admin.sql → usuario admin + productos de ejemplo
 *
 * Lee la configuración de .env (o de config_local.php).
 * Se puede ejecutar varias veces sin romper nada.
 */
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import {
  ROOT,
  color,
  ok,
  aviso,
  error,
  info,
  dim,
  encontrarClienteDB,
} from './util.mjs';

// ---------------------------------------------------------
// 1) Leer configuración
// ---------------------------------------------------------
function leerEnv() {
  const ruta = path.join(ROOT, '.env');
  const cfg = {};
  if (!fs.existsSync(ruta)) return cfg;
  for (const linea of fs.readFileSync(ruta, 'utf8').split(/\r?\n/)) {
    const l = linea.trim();
    if (!l || l.startsWith('#')) continue;
    const i = l.indexOf('=');
    if (i < 0) continue;
    cfg[l.slice(0, i).trim()] = l.slice(i + 1).trim().replace(/^["']|["']$/g, '');
  }
  return cfg;
}

function leerConfigLocal() {
  const ruta = path.join(ROOT, 'config_local.php');
  if (!fs.existsSync(ruta)) return {};
  try {
    // Es PHP: lo ejecutamos con php -r para obtener el array
    const phpRuta = process.env.PHP_BIN || 'php';
    const salida = execFileSync(
      phpRuta,
      ['-r', `echo json_encode(include ${JSON.stringify(ruta)});`],
      { encoding: 'utf8' },
    );
    return JSON.parse(salida);
  } catch {
    return {};
  }
}

const env = leerEnv();
const local = leerConfigLocal();

const DB_HOST = local.DB_HOST || env.DB_HOST || '127.0.0.1';
const DB_PORT = local.DB_PORT || env.DB_PORT || '3306';
const DB_NAME = local.DB_NAME || env.DB_NAME || 'ventas_php';
const DB_USER = local.DB_USER || env.DB_USER || 'root';
const DB_PASS = local.DB_PASS || env.DB_PASS || '';

const cliente = encontrarClienteDB();
if (!cliente) {
  error('No encontré el cliente de base de datos (mariadb / mysql).');
  process.exit(1);
}

for (const archivo of ['esquema.sql', 'crear_usuario_admin.sql']) {
  if (!fs.existsSync(path.join(ROOT, archivo))) {
    error(`Falta el archivo ${archivo}`);
    process.exit(1);
  }
}

console.log('');
console.log(`${color.cyan}${color.bold}  Inicializando base de datos${color.reset}`);
console.log(`${color.dim}  Host: ${DB_HOST}:${DB_PORT}   BD: ${DB_NAME}   Usuario: ${DB_USER}${color.reset}`);
console.log('');

/** Ejecuta SQL leyendo por stdin (evita que la contraseña aparezca en la terminal). */
function ejecutarSQL(sql, base = null) {
  const args = ['-h', DB_HOST, '-P', String(DB_PORT), '-u', DB_USER, '--default-character-set=utf8mb4'];
  if (base) args.push('-D', base);
  return execFileSync(cliente, args, {
    input: sql,
    encoding: 'utf8',
    stdio: ['pipe', 'pipe', 'pipe'],
    env: { ...process.env, MYSQL_PWD: DB_PASS },
  });
}

// ---------------------------------------------------------
// 2) Crear la base de datos
// ---------------------------------------------------------
info('Creando la base de datos...');
try {
  ejecutarSQL(
    `CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`,
  );
  ok(`Base de datos "${DB_NAME}" lista`);
} catch (e) {
  error('No pude crear la base de datos.');
  dim('   Revisa usuario y contraseña en .env');
  dim(String(e.stderr || e.message).trim());
  process.exit(1);
}

// ---------------------------------------------------------
// 3) Importar esquema
// ---------------------------------------------------------
info('Creando las tablas (esquema.sql)...');
try {
  // esquema.sql trae "ventas_php" fijo: lo ajustamos al nombre configurado
  const sql = fs
    .readFileSync(path.join(ROOT, 'esquema.sql'), 'utf8')
    .replace(/ventas_php/g, DB_NAME);
  ejecutarSQL(sql, DB_NAME);
  ok('5 tablas creadas: usuarios, productos, ventas, productos_ventas, kardex');
} catch (e) {
  error('Falló la importación de esquema.sql');
  dim(String(e.stderr || e.message).trim());
  process.exit(1);
}

// ---------------------------------------------------------
// 4) Usuario admin + productos de ejemplo
// ---------------------------------------------------------
if (fs.existsSync(path.join(ROOT, 'crear_usuario_admin.sql'))) {
  info('Creando usuario admin y productos de ejemplo...');
  try {
    const sql = fs
      .readFileSync(path.join(ROOT, 'crear_usuario_admin.sql'), 'utf8')
      .replace(/ventas_php/g, DB_NAME);
    ejecutarSQL(sql, DB_NAME);
    ok('Usuario "admin" y 8 productos creados');
  } catch (e) {
    aviso('No se pudieron cargar los datos de ejemplo (puede que ya existan).');
    dim(String(e.stderr || e.message).trim());
  }
}

console.log('');
console.log(`${color.green}  ────────────────────────────────────────────${color.reset}`);
console.log(`${color.green}  ✅  Base de datos lista${color.reset}`);
console.log('');
console.log(`     Usuario:  ${color.bold}admin${color.reset}`);
console.log(`     Contraseña: ${color.bold}Provcal2026!${color.reset}`);
console.log('');
console.log(`${color.dim}     Arranca el sistema con:  npm run dev${color.reset}`);
console.log(`${color.green}  ────────────────────────────────────────────${color.reset}`);
console.log('');
