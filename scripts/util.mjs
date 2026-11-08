/**
 * util.mjs
 * Utilidades compartidas por los scripts de npm.
 * Encuentra PHP y MariaDB, revisa puertos y mata procesos.
 */
import net from 'node:net';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { execSync, spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

export const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
export const PUERTO_WEB = 8000;
export const PUERTO_DB = 3306;
export const ES_WINDOWS = process.platform === 'win32';

const c = {
  reset: '\x1b[0m',
  green: '\x1b[32m',
  red: '\x1b[31m',
  yellow: '\x1b[33m',
  cyan: '\x1b[36m',
  dim: '\x1b[2m',
  bold: '\x1b[1m',
};

export const color = c;
export const ok = (m) => console.log(`${c.green}[OK]${c.reset} ${m}`);
export const aviso = (m) => console.log(`${c.yellow}[!]${c.reset} ${m}`);
export const error = (m) => console.log(`${c.red}[ERROR]${c.reset} ${m}`);
export const info = (m) => console.log(`${c.cyan}[...]${c.reset} ${m}`);
export const dim = (m) => console.log(`${c.dim}${m}${c.reset}`);

/** ¿Está alguien escuchando en ese puerto? */
export function puertoLibre(puerto, host = '127.0.0.1') {
  return new Promise((resolver) => {
    const sock = net.connect({ port: puerto, host });
    const listo = (libre) => {
      sock.removeAllListeners();
      sock.destroy();
      resolver(libre);
    };
    sock.setTimeout(700);
    sock.once('connect', () => listo(false));
    sock.once('timeout', () => listo(true));
    sock.once('error', () => listo(true));
  });
}

/** Busca un executable en el PATH del sistema. */
export function buscarEnPath(nombre) {
  try {
    const sep = ES_WINDOWS ? ';' : ':';
    const dirs = (process.env.PATH || '').split(sep).filter(Boolean);
    for (const d of dirs) {
      const candidatos = ES_WINDOWS
        ? [path.join(d, nombre), path.join(d, `${nombre}.exe`), path.join(d, `${nombre}.cmd`)]
        : [path.join(d, nombre)];
      for (const c of candidatos) {
        if (fs.existsSync(c)) return c;
      }
    }
  } catch {
    /* PATH ilegible */
  }
  return null;
}

/** Busca php.exe en las rutas típicas de Windows. */
export function encontrarPHP() {
  const enPath = buscarEnPath('php');
  if (enPath) return enPath;

  if (ES_WINDOWS) {
    const bases = [
      path.join(os.homedir(), 'AppData/Local/Microsoft/WinGet/Packages'),
      'C:/php',
      'C:/xampp/php',
      'C:/laragon/bin/php',
      'C:/tools/php',
    ];
    for (const base of bases) {
      const encontrado = buscarRecursivo(base, 'php.exe', 4);
      if (encontrado) return encontrado;
    }
  } else {
    for (const ruta of ['/usr/bin/php', '/usr/local/bin/php', '/opt/homebrew/bin/php']) {
      if (fs.existsSync(ruta)) return ruta;
    }
  }
  return null;
}

/** Busca un archivo dentro de una carpeta con profundidad limitada. */
export function buscarRecursivo(base, nombre, profundidadMax = 4) {
  if (!fs.existsSync(base)) return null;
  let pendientes = [base];
  for (let nivel = 0; nivel <= profundidadMax; nivel++) {
    const siguientes = [];
    for (const dir of pendientes) {
      let entradas = [];
      try {
        entradas = fs.readdirSync(dir, { withFileTypes: true });
      } catch {
        continue;
      }
      for (const e of entradas) {
        const completa = path.join(dir, e.name);
        if (e.isFile() && e.name.toLowerCase() === nombre.toLowerCase()) return completa;
        if (e.isDirectory()) siguientes.push(completa);
      }
    }
    pendientes = siguientes;
    if (pendientes.length === 0) break;
  }
  return null;
}

/** Busca mariadbd (servidor) en las rutas típicas. */
export function encontrarMariaDB() {
  const enPath = buscarEnPath('mariadbd') || buscarEnPath('mysqld');
  if (enPath) return enPath;

  if (ES_WINDOWS) {
    const bases = ['C:/Program Files', 'C:/Program Files (x86)'];
    for (const base of bases) {
      if (!fs.existsSync(base)) continue;
      let dirs = [];
      try {
        dirs = fs.readdirSync(base).filter((d) => /mariadb|mysql/i.test(d));
      } catch {
        continue;
      }
      for (const d of dirs) {
        for (const exe of ['mariadbd.exe', 'mysqld.exe']) {
          const ruta = path.join(base, d, 'bin', exe);
          if (fs.existsSync(ruta)) return ruta;
        }
      }
    }
  }
  return null;
}

/** Busca el cliente de BD (mariadb/mysql) para importar archivos SQL. */
export function encontrarClienteDB() {
  const enPath = buscarEnPath('mariadb') || buscarEnPath('mysql');
  if (enPath) return enPath;

  if (ES_WINDOWS) {
    for (const base of ['C:/Program Files', 'C:/Program Files (x86)']) {
      if (!fs.existsSync(base)) continue;
      let dirs = [];
      try {
        dirs = fs.readdirSync(base).filter((d) => /mariadb|mysql/i.test(d));
      } catch {
        continue;
      }
      for (const d of dirs) {
        for (const exe of ['mariadb.exe', 'mysql.exe']) {
          const ruta = path.join(base, d, 'bin', exe);
          if (fs.existsSync(ruta)) return ruta;
        }
      }
    }
  }
  return null;
}

/** Devuelve los PIDs que escuchan en un puerto. */
export function pidsEnPuerto(puerto) {
  const pids = new Set();
  try {
    if (ES_WINDOWS) {
      const salida = execSync(`netstat -ano -p tcp`, { encoding: 'latin1', stdio: ['ignore', 'pipe', 'ignore'] });
      for (const linea of salida.split(/\r?\n/)) {
        if (!linea.includes('LISTENING')) continue;
        const partes = linea.trim().split(/\s+/);
        // columnas: proto, local, remote, state, pid
        const local = partes[1] || '';
        if (local.endsWith(`:${puerto}`) && partes[4]) pids.add(Number(partes[4]));
      }
    } else {
      const salida = execSync(`lsof -ti tcp:${puerto} -sTCP:LISTEN || true`, {
        encoding: 'utf8',
        stdio: ['ignore', 'pipe', 'ignore'],
      });
      for (const p of salida.split(/\s+/)) {
        const n = Number(p);
        if (Number.isInteger(n) && n > 0) pids.add(n);
      }
    }
  } catch {
    /* no se pudo consultar */
  }
  return [...pids];
}

/** Termina un proceso por PID. */
export function matarProceso(pid) {
  try {
    if (ES_WINDOWS) {
      execSync(`taskkill /PID ${pid} /T /F`, { stdio: 'ignore' });
    } else {
      process.kill(pid, 'SIGTERM');
    }
    return true;
  } catch {
    return false;
  }
}

/** Mata todo lo que escuche en un puerto. */
export function matarPuerto(puerto) {
  const pids = pidsEnPuerto(puerto);
  let matados = 0;
  for (const pid of pids) {
    if (matarProceso(pid)) matados++;
  }
  return matados;
}

/** Lanza un proceso y devuelve el child. */
export function lanzar(comando, args, opciones = {}) {
  return spawn(comando, args, {
    cwd: ROOT,
    stdio: ['ignore', 'pipe', 'pipe'],
    windowsHide: true,
    ...opciones,
  });
}

/** Conecta stdout/stderr de un hijo con un prefijo de color. */
export function etiquetar(hijo, etiqueta, colorEtiqueta) {
  const prefijo = `${colorEtiqueta}[${etiqueta}]${color.reset} `;
  const pintar = (flujo) => {
    let restante = '';
    flujo.on('data', (dato) => {
      const texto = restante + dato.toString();
      const lineas = texto.split(/\r?\n/);
      restante = lineas.pop() ?? '';
      for (const l of lineas) {
        if (l.trim() !== '') console.log(prefijo + l);
      }
    });
    flujo.on('end', () => {
      if (restante.trim() !== '') console.log(prefijo + restante);
    });
  };
  if (hijo.stdout) pintar(hijo.stdout);
  if (hijo.stderr) pintar(hijo.stderr);
}

/** Espera hasta que un puerto esté en uso (o se agote el tiempo). */
export async function esperarPuerto(puerto, segundosMax = 30) {
  for (let i = 0; i < segundosMax * 4; i++) {
    if (!(await puertoLibre(puerto))) return true;
    await new Promise((r) => setTimeout(r, 250));
  }
  return false;
}
