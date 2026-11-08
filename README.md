# Sistema de Ventas PROVCAL

Sistema de punto de venta (POS) hecho en **PHP + MySQL**. Permite gestionar productos,
usuarios, ventas, control de stock con kardex, reportes, tickets en PDF y exportación a Excel.

---

## 🚀 Cómo usarlo (lo importante)

### 1. Arrancar el sistema

```bash
npm run dev
```

Esto enciende la **base de datos** y el **servidor web**, y se queda corriendo
en pantalla (igual que en cualquier proyecto de TypeScript).

- Abre en tu navegador: **http://127.0.0.1:8000**
- Para detenerlo: **`Ctrl + C`**

### 2. Entrar al sistema

- **Usuario:** `admin`
- **Contraseña:** `Provcal2026!`

> 💡 Puedes cambiarla desde **Perfil → Cambiar contraseña** dentro del sistema.

### 3. Otros comandos

| Comando | Qué hace |
|---|---|
| `npm run dev` | Arranca base de datos + servidor web |
| `npm run stop` | Apaga todo. Tus datos **no** se borran |
| `npm run db:init` | Crea la base de datos, las tablas y el usuario admin |
| `npm run publish -- "mensaje"` | Sube tus cambios a GitHub |

---

## 📁 Estructura del proyecto

Cada acción del sistema es un archivo PHP en la raíz:

| Grupo | Archivos |
|---|---|
| **Acceso** | `login.php`, `iniciar_sesion.php`, `cerrar_sesion.php`, `perfil.php`, `cambiar_password.php` |
| **Panel** | `index.php`, `cartas_totales.php` |
| **Productos** | `productos.php`, `agregar_producto.php`, `editar_producto.php`, `eliminar_producto.php` |
| **Usuarios** | `usuarios.php`, `agregar_usuario.php`, `editar_usuario.php`, `eliminar_usuario.php` |
| **Ventas** | `vender.php`, `agregar_producto_venta.php`, `quitar_producto_venta.php`, `actualizar_cantidad.php`, `registrar_venta.php`, `anular_venta.php`, `cancelar_venta.php` |
| **Reportes** | `reporte_ventas.php`, `kardex.php`, `generar_ticket_pdf.php`, `exportar_kardex_excel.php` |
| **Compartidos** | `encabezado.php` (plantilla HTML), `navbar.php` (menú), `bootstrap.php` (configuración), `funciones.php` (toda la lógica y la conexión a BD) |
| **Scripts** | `scripts/*.mjs` → los comandos de npm de arriba |

---

## ⚙️ Configuración

La conexión a la base de datos **no está en el código**, sino en un archivo de
configuración que **nunca se sube a Git** (está en `.gitignore`).

Hay dos formatos, y el sistema acepta cualquiera de los dos:

**Opción A — `.env`** (texto plano, cómodo en local):

1. Copia `.env.example` a `.env`
2. Completa los datos:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ventas_php
DB_USER=root
DB_PASS=tu_contrasena
```

**Opción B — `config_local.php`** (más seguro, recomendado en hosting):

Copia `config_local.example.php` a `config_local.php` y pon los mismos valores
en formato PHP. Al ser un archivo PHP, **el navegador jamás puede descargar su contenido**.

> 💡 En local basta con `.env`. Si existen ambos, se usa `config_local.php`.

### Base de datos

```bash
npm run db:init
```

Ese comando crea la base de datos, las 5 tablas (`usuarios`, `productos`, `ventas`,
`productos_ventas`, `kardex`) y el usuario admin con 8 productos de ejemplo.

Si prefieres hacerlo a mano, los archivos están en la raíz:
- `esquema.sql` → solo las tablas
- `crear_usuario_admin.sql` → usuario admin + productos de ejemplo

---

## 🌐 Despliegue en un hosting gratuito

El proyecto está pensado para subirse tal cual a un hosting con **PHP + MySQL**
(InfinityFree, 000webhost, AwardSpace, etc.):

1. **Sube el contenido** de la carpeta (excepto `mariadb-data/`, `.git/`, `.env`
   y `config_local.php`) al directorio `htdocs` del hosting.
2. **Crea la base de datos** desde el panel del hosting.
3. **Importa** `esquema.sql` y luego `crear_usuario_admin.sql` desde phpMyAdmin.
4. **Crea `config_local.php`** en `htdocs` con los datos que te dio el panel.
   (Recuerda: en hosting el `DB_HOST` normalmente **no** es `localhost`).
5. Abre tu dominio y entra con `admin` / `Provcal2026!`.

El archivo `.htaccess` que viene incluido **bloquea el acceso público a `.env`**,
para que nadie pueda descargar la contraseña de la base de datos escribiendo la URL.

> ⚠️ Lo único que **no** se sube es `mariadb-data/`: esa carpeta es la base de datos
> **local** de tu computadora. En el hosting usarás la que crees en el panel.

---

## 🔒 Seguridad

Algunas cosas que se tienen en cuenta en este proyecto:

- Las contraseñas de los usuarios se guardan con `password_hash()` (nunca en texto plano).
- Las credenciales de la base de datos viven en `.env` o `config_local.php`, no en el código.
- Todas las páginas que muestran o modifican datos validan que exista sesión iniciada.
  Si alguien intenta abrir una de esas páginas sin entrar, lo manda al login.
- El `.htaccess` impide que se descarguen archivos sensibles desde el navegador.

> ⚠️ **Importante:** `.env`, `config_local.php` y la carpeta `mariadb-data/` **nunca**
> deben subirse a Git. Los tres ya están ignorados en `.gitignore`.

---

## 🛠️ Requisitos

Para correr en local:

- **Node.js** 18 o superior (para `npm run dev`)
- **PHP** 8.2 o superior con las extensiones `pdo_mysql`, `mbstring`, `gd`, `curl` y `zip`
- **MySQL** o **MariaDB**
- **Composer** (solo si necesitas actualizar las librerías de `vendor/`)

Para el hosting solo necesitas que soporte **PHP 8.2+** y **MySQL**.

Las librerías usadas son:
- **dompdf** → generar tickets en PDF
- **phpspreadsheet** → exportar el kardex a Excel
- **escpos-php** → impresión de tickets
