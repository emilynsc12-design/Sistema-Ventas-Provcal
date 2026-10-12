# Sistema de Ventas PROVCAL

Sistema de punto de venta (POS) hecho en **PHP + MySQL**. Permite gestionar productos,
usuarios, ventas, control de stock con kardex, reportes, tickets en PDF y exportación a Excel.

---

## 🚀 Cómo usarlo (lo importante)

Tienes cuatro botones de doble clic en la carpeta del proyecto:

| Archivo | Qué hace |
|---|---|
| **`Iniciar sistema.bat`** | Enciende la base de datos y el servidor web. Abre `http://127.0.0.1:8000` |
| **`Iniciar con internet.bat`** | Enciende todo **y** crea un enlace público de internet (https) |
| **`Detener sistema.bat`** | Apaga la base de datos, el servidor y el enlace de internet |
| **`Subir a GitHub.bat`** | Sube tus cambios a GitHub con un solo clic |

### Entrar al sistema
- **Usuario:** `admin`
- **Contraseña:** `Provcal2026!`

> 💡 Puedes cambiarla desde **Perfil → Cambiar contraseña** dentro del sistema.

### Sobre el enlace público
El enlace de **`Iniciar con internet.bat`** es **temporario**: cambia cada vez que lo
inicias, y solo funciona mientras esa ventana siga abierta y la computadora esté encendida.

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

---

## ⚙️ Configuración

La conexión a la base de datos **no está en el código**, sino en el archivo **`.env`**,
que **nunca se sube a Git** (está en `.gitignore`).

Si vas a usar el proyecto en otra computadora:

1. Copia `.env.example` a `.env`
2. Completa los datos de tu base de datos:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ventas_php
DB_USER=root
DB_PASS=tu_contrasena
```

### Base de datos
El archivo **`esquema.sql`** crea las 5 tablas del sistema:

- `usuarios`
- `productos`
- `ventas`
- `productos_ventas` (detalle de cada venta)
- `kardex` (movimientos de entrada y salida de stock)

Para crearla desde cero:
```bash
mariadb -u root -p < esquema.sql
```

---

## 🔒 Seguridad

Algunas cosas que se tienen en cuenta en este proyecto:

- Las contraseñas de los usuarios se guardan con `password_hash()` (nunca en texto plano).
- Las credenciales de la base de datos viven en `.env`, no en el código.
- Todas las páginas que muestran o modifican datos validan que exista sesión iniciada.
  Si alguien intenta abrir una de esas páginas sin entrar, lo manda al login.

> ⚠️ **Importante:** el archivo `.env` y la carpeta `mariadb-data/` **nunca** deben
> subirse a Git. Ambos ya están ignorados en `.gitignore`.

---

## 🛠️ Requisitos

- PHP 8.2 o superior con las extensiones `pdo_mysql`, `mbstring`, `gd`, `curl` y `zip`
- MySQL o MariaDB
- Composer (solo si necesitas actualizar las librerías de `vendor/`)

Las librerías usadas son:
- **dompdf** → generar tickets en PDF
- **phpspreadsheet** → exportar el kardex a Excel
- **escpos-php** → impresión de tickets
