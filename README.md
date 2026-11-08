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

### 2. Entrar al sistema

- **Usuario:** `admin`
- **Contraseña:** `Provcal2026!`

> 💡 Puedes cambiarla desde **Perfil → Cambiar contraseña** dentro del sistema.

### 3. Otros comandos

| Comando | Qué hace |
|---|---|
| `npm run dev` | Arranca base de datos + servidor web |
| `npm run stop` | Apaga todo. Tus datos **no** se borran |

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

## 🛠️ Requisitos

Para correr en local:

- **Node.js** 18 o superior (para `npm run dev`)
- **PHP** 8.2 o superior con las extensiones `pdo_mysql`, `mbstring`, `gd`, `curl` y `zip`
- **MySQL** o **MariaDB**
- **Composer** (solo si necesitas actualizar las librerías de `vendor/`)

Las librerías usadas son:
- **dompdf** → generar tickets en PDF
- **phpspreadsheet** → exportar el kardex a Excel
- **escpos-php** → impresión de tickets
