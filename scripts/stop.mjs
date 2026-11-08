/**
 * stop.mjs  →  npm run stop
 *
 * Apaga el servidor web (8000) y la base de datos (3306).
 * Tus datos NO se borran: siguen en la carpeta mariadb-data.
 */
import {
  PUERTO_WEB,
  PUERTO_DB,
  color,
  ok,
  aviso,
  info,
  dim,
  puertoLibre,
  pidsEnPuerto,
  matarPuerto,
} from './util.mjs';
import { execSync } from 'node:child_process';

console.log('');
console.log(`${color.yellow}${color.bold}  Apagando Sistema de Ventas PROVCAL${color.reset}`);
console.log('');

const servicios = [
  ['Servidor web', PUERTO_WEB],
  ['Base de datos', PUERTO_DB],
];

for (const [nombre, puerto] of servicios) {
  if (await puertoLibre(puerto)) {
    dim(`  [--] ${nombre} (puerto ${puerto}) no estaba corriendo`);
    continue;
  }
  const pids = pidsEnPuerto(puerto);
  const matados = matarPuerto(puerto);
  if (matados > 0) {
    ok(`${nombre} detenido  (puerto ${puerto})`);
  } else {
    aviso(`No pude detener ${nombre}. PIDs: ${pids.join(', ')}`);
  }
}

// ---------------------------------------------------------
// Detiene el tunel de Cloudflare (si quedó de una sesión anterior)
// ---------------------------------------------------------
function detenerTunel() {
  if (process.platform !== 'win32') return false;
  try {
    const r = execSync('tasklist /FI "IMAGENAME eq cloudflared.exe" /NH', {
      encoding: 'latin1',
      stdio: ['ignore', 'pipe', 'ignore'],
    });
    if (!/cloudflared\.exe/i.test(r)) return false;
    execSync('taskkill /IM cloudflared.exe /T /F', { stdio: 'ignore' });
    return true;
  } catch {
    return false;
  }
}

if (detenerTunel()) ok('Tunel de internet detenido (cloudflared)');

console.log('');
ok('Sistema apagado. Tus datos siguen guardados.');
console.log(`${color.dim}  Volver a encender:  npm run dev${color.reset}`);
console.log('');
