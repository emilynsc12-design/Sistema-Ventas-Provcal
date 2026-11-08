/**
 * publish.mjs  →  npm run publish
 *
 * Sube tus cambios a GitHub:
 *   1. Revisa qué cambió
 *   2. Avisa si detecta archivos con posibles contraseñas
 *   3. Guarda (commit) y sube (push)
 *
 * Uso:
 *   npm run publish
 *   npm run publish -- "Agregué el reporte mensual"
 */
import fs from 'node:fs';
import path from 'node:path';
import { execSync } from 'node:child_process';
import { ROOT, color, ok, aviso, error, info, dim } from './util.mjs';

const mensaje = process.argv[2] || 'Actualización del sistema';

function git(comando) {
  return execSync(`git ${comando}`, {
    cwd: ROOT,
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}

console.log('');
console.log(`${color.green}${color.bold}  Subiendo cambios a GitHub${color.reset}`);
console.log('');

if (!fs.existsSync(path.join(ROOT, '.git'))) {
  error('Esta carpeta no es un repositorio de Git.');
  process.exit(1);
}

// ---------------------------------------------------------
// 1) ¿Hay cambios?
// ---------------------------------------------------------
info('Revisando cambios...');
const estado = git('status --short').trim();

if (!estado) {
  console.log('');
  ok('No hay cambios que subir. Todo ya está en GitHub.');
  console.log('');
  process.exit(0);
}

console.log(`${color.dim}  Archivos que se van a subir:${color.reset}`);
for (const linea of estado.split(/\r?\n/)) {
  console.log(`${color.dim}    ${linea}${color.reset}`);
}

// ---------------------------------------------------------
// 2) Aviso de archivos sensibles
// ---------------------------------------------------------
console.log('');
info('Comprobando que no haya datos sensibles...');

const sensibles = estado
  .split(/\r?\n/)
  .map((l) => l.substring(3).trim().replace(/^"|"$/g, ''))
  .filter((ruta) => /^\.env$|config_local\.php$|mariadb-data|\.pem$|\.key$|my\.cnf$|php-error\.log$|^crear_usuario_admin\.sql$/.test(ruta));

if (sensibles.length > 0) {
  console.log('');
  error('Estos archivos podrían contener contraseñas y NO deben subirse:');
  for (const s of sensibles) console.log(`${color.red}    ${s}${color.reset}`);
  console.log('');
  error('Cancelado. Nada se subió.');
  console.log(`${color.dim}  (están protegidos en .gitignore — si git los muestra, revísalo)${color.reset}`);
  console.log('');
  process.exit(1);
}

ok('No hay archivos sensibles.');

// ---------------------------------------------------------
// 3) Commit
// ---------------------------------------------------------
console.log('');
info('Guardando cambios...');
git('add -A');

const trasAgregar = git('status --short').trim();
if (!trasAgregar) {
  ok('No hay cambios reales que subir.');
  console.log('');
  process.exit(0);
}

git(`commit -m "${mensaje.replace(/"/g, '\\"')}"`);
ok('Commit creado');

// ---------------------------------------------------------
// 4) Push
// ---------------------------------------------------------
console.log('');
info('Subiendo a GitHub...');

try {
  execSync('git push origin main', {
    cwd: ROOT,
    encoding: 'utf8',
    stdio: ['ignore', 'inherit', 'inherit'],
  });
  console.log('');
  console.log(`${color.green}  ────────────────────────────────────────────${color.reset}`);
  console.log(`${color.green}  ✅  Cambios subidos a GitHub${color.reset}`);
  console.log(`${color.dim}     https://github.com/emilynsc12-design/Sistema-Ventas-Provcal${color.reset}`);
  console.log(`${color.green}  ────────────────────────────────────────────${color.reset}`);
} catch {
  console.log('');
  error('No se pudo subir. Puede que falte tu acceso a GitHub.');
  dim('   Prueba desde la terminal:  git push origin main');
}

console.log('');
