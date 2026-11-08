/**
 * dev.mjs  →  npm run dev
 *
 * Arranca la base de datos (MariaDB) y el servidor web (PHP)
 * y se queda corriendo en pantalla, igual que "npm run dev"
 * en cualquier proyecto de TypeScript.
 *
 *   • Detente con:  Ctrl + C
 *   • Solo detiene lo que ÉL arrancó.
 *
 *   URL:  http://127.0.0.1:8000
 */
import fs from 'node:fs';
import path from 'node:path';
import {
  ROOT,
  PUERTO_WEB,
  PUERTO_DB,
  color,
  ok,
  aviso,
  error,
  info,
  dim,
  puertoLibre,
  encontrarPHP,
  encontrarMariaDB,
  lanzar,
  etiquetar,
  esperarPuerto,
} from './util.mjs';

const hijos = [];      // procesos que arrancamos nosotros
let apagando = false;

function existeConfig() {
  return fs.existsSync(path.join(ROOT, '.env')) || fs.existsSync(path.join(ROOT, 'config_local.php'));
}

async function main() {
  console.log('');
  console.log(`${color.green}${color.bold}  Sistema de Ventas PROVCAL${color.reset}`);
  console.log(`${color.dim}  ------------------------------------------------${color.reset}`);
  console.log('');

  // ---------------------------------------------------------
  // 0) Comprobaciones previas
  // ---------------------------------------------------------
  if (!fs.existsSync(path.join(ROOT, 'vendor', 'autoload.php'))) {
    error('Falta la carpeta "vendor" (librerías de PHP).');
    dim('   Ejecuta:  composer install');
    process.exit(1);
  }

  if (!existeConfig()) {
    aviso('No encontré .env ni config_local.php.');
    dim('   Copia el ejemplo y pon los datos de tu base de datos:');
    dim('   cp .env.example .env');
    dim('');
  }

  const php = encontrarPHP();
  if (!php) {
    error('No encontré PHP instalado.');
    dim('   Instálalo con:  winget install PHP.PHP.8.2');
    process.exit(1);
  }

  const mariadb = encontrarMariaDB();
  if (!mariadb) {
    error('No encontré MariaDB / MySQL instalado.');
    process.exit(1);
  }

  // ---------------------------------------------------------
  // 1) Base de datos
  // ---------------------------------------------------------
  if (await puertoLibre(PUERTO_DB)) {
    info(`Arrancando base de datos (puerto ${PUERTO_DB})...`);

    const datadir = path.join(ROOT, 'mariadb-data');
    if (!fs.existsSync(datadir)) {
      error(`No existe la carpeta de datos: ${datadir}`);
      dim('   Si es una instalación nueva, crea la base con:  npm run db:init');
      process.exit(1);
    }

    const hijo = lanzar(mariadb, [
      `--datadir=${datadir}`,
      `--port=${PUERTO_DB}`,
      '--bind-address=127.0.0.1',
    ]);
    etiquetar(hijo, 'bd', color.cyan);
    hijos.push({ hijo, nombre: 'base de datos' });

    hijo.on('exit', (codigo) => {
      if (!apagando && codigo !== null && codigo !== 0) {
        error('La base de datos se detuvo con error.');
        detener();
      }
    });

    if (!(await esperarPuerto(PUERTO_DB, 40))) {
      error('La base de datos no arrancó en 40 segundos.');
      detener();
      process.exit(1);
    }
    ok(`Base de datos lista  (puerto ${PUERTO_DB})`);
  } else {
    ok(`Base de datos ya estaba corriendo  (puerto ${PUERTO_DB})`);
  }

  // ---------------------------------------------------------
  // 2) Servidor web
  // ---------------------------------------------------------
  if (await puertoLibre(PUERTO_WEB)) {
    info(`Arrancando servidor web PHP (puerto ${PUERTO_WEB})...`);

    const hijo = lanzar(php, ['-S', `127.0.0.1:${PUERTO_WEB}`, '-t', ROOT]);
    etiquetar(hijo, 'web', color.yellow);
    hijos.push({ hijo, nombre: 'servidor web' });

    if (!(await esperarPuerto(PUERTO_WEB, 20))) {
      error('El servidor web no arrancó.');
      detener();
      process.exit(1);
    }
    ok(`Servidor web listo  (puerto ${PUERTO_WEB})`);
  } else {
    ok(`Servidor web ya estaba corriendo  (puerto ${PUERTO_WEB})`);
  }

  // ---------------------------------------------------------
  // 3) Listo
  // ---------------------------------------------------------
  console.log('');
  console.log(`${color.green}  ────────────────────────────────────────────${color.reset}`);
  console.log(`${color.green}  ✅  Abre en tu navegador:${color.reset}`);
  console.log('');
  console.log(`${color.bold}     http://127.0.0.1:${PUERTO_WEB}${color.reset}`);
  console.log('');
  console.log(`${color.green}  ────────────────────────────────────────────${color.reset}`);
  console.log(`${color.dim}  Ctrl + C para detener${color.reset}`);
  console.log('');
}

/** Detiene solo lo que arrancamos nosotros. */
function detener() {
  if (apagando) return;
  apagando = true;
  console.log('');
  info('Deteniendo...');
  for (const { hijo } of hijos) {
    try {
      hijo.kill('SIGTERM');
    } catch {
      /* ya murió */
    }
  }
  setTimeout(() => {
    for (const { hijo } of hijos) {
      try {
        hijo.kill('SIGKILL');
      } catch {
        /* ya murió */
      }
    }
    process.exit(0);
  }, 1500);
}

process.on('SIGINT', detener);
process.on('SIGTERM', detener);

main().catch((e) => {
  error(e.message);
  detener();
  process.exit(1);
});
