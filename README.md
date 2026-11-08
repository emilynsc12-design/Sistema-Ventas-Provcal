# Sistema de Ventas PROVCAL

Punto de venta hecho en PHP y MySQL. Sirve para gestionar productos y usuarios, registrar
ventas, llevar el control de stock con kardex, ver reportes, imprimir tickets en PDF y
exportar información a Excel.

## Requisitos

- PHP 8.2 o superior, con las extensiones `pdo_mysql`, `mbstring`, `gd`, `curl` y `zip`
- MySQL o MariaDB
- Node.js 18 o superior, solo para los scripts de abajo

Las librerías ya vienen en la carpeta `vendor/`, así que no hace falta instalar Composer
para correr el sistema. Solo se necesita si quieres actualizarlas:

- `dompdf` para los tickets en PDF
- `phpspreadsheet` para la exportación a Excel
- `escpos-php` para impresión

## Cómo correrlo

```
npm run dev
```

Eso arranca la base de datos y el servidor web, y se queda corriendo en la terminal.
Abre `http://127.0.0.1:8000` en el navegador.

Para detenerlo, presiona `Ctrl + C` o ejecuta `npm run stop`. Los datos no se borran
al apagar.

## Entrar al sistema

El usuario administrador se crea al inicializar la base de datos. La primera vez que
entres, cambia la contraseña desde **Perfil → Cambiar contraseña**.

## Estructura del proyecto

Cada acción del sistema es un archivo PHP en la raíz:

- Acceso: `login.php`, `iniciar_sesion.php`, `cerrar_sesion.php`, `perfil.php`, `cambiar_password.php`
- Panel: `index.php`, `cartas_totales.php`
- Productos: `productos.php`, `agregar_producto.php`, `editar_producto.php`, `eliminar_producto.php`
- Usuarios: `usuarios.php`, `agregar_usuario.php`, `editar_usuario.php`, `eliminar_usuario.php`
- Ventas: `vender.php`, `agregar_producto_venta.php`, `quitar_producto_venta.php`, `actualizar_cantidad.php`, `registrar_venta.php`, `anular_venta.php`, `cancelar_venta.php`
- Reportes: `reporte_ventas.php`, `kardex.php`, `generar_ticket_pdf.php`, `exportar_kardex_excel.php`
- Compartidos: `encabezado.php` (la plantilla HTML), `navbar.php` (el menú),
  `bootstrap.php` (la configuración) y `funciones.php` (toda la lógica y la conexión
  a la base de datos)
- Scripts: los comandos de npm viven en `scripts/`

## Configuración de la base de datos

La conexión no está escrita en el código. Está en un archivo aparte que Git ignora,
así que nunca se sube al repositorio.

Para configurarla en local, copia `.env.example` a `.env` y rellena tus datos:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ventas_php
DB_USER=root
DB_PASS=
```

En un hosting conviene usar `config_local.php` en lugar de `.env`. Al ser un archivo
PHP, el navegador no puede descargar su contenido aunque alguien escriba la URL en la
barra de direcciones. Copia `config_local.example.php` y pon ahí los mismos valores.
Si existen los dos archivos, se usa `config_local.php`.

### Crear las tablas

`esquema.sql` crea las cinco tablas: `usuarios`, `productos`, `ventas`,
`productos_ventas` y `kardex`. Se puede importar desde phpMyAdmin o desde la terminal:

```
mysql -u root -p < esquema.sql
```

`crear_usuario_admin.sql` crea el usuario administrador y unos productos de ejemplo.
Ninguno de los dos archivos se sube a Git.

## Subirlo a un hosting

1. Sube el contenido de la carpeta al directorio `htdocs`, excepto `mariadb-data/`,
   `.git/`, `.env` y `config_local.php`.
2. Crea la base de datos desde el panel del hosting.
3. Importa `esquema.sql` y después `crear_usuario_admin.sql` con phpMyAdmin.
4. Crea `config_local.php` en `htdocs` con los datos que te dio el panel. Ojo: en un
   hosting el `DB_HOST` normalmente no es `localhost`, sino algo como
   `sql300.epizy.com`.
5. Abre tu dominio e inicia sesión.

El `.htaccess` incluido bloquea la descarga pública de `.env`, para que nadie pueda
conseguir la contraseña de la base de datos escribiendo la dirección en el navegador.

`mariadb-data/` es la base de datos local de tu computadora, no se sube.

## Seguridad

- Las contraseñas de los usuarios se guardan con `password_hash()`, nunca en texto plano.
- Las credenciales de la base de datos viven en `.env` o `config_local.php`.
- Todas las páginas que muestran o modifican datos validan que haya sesión iniciada.
- `.env`, `config_local.php` y `mariadb-data/` están en `.gitignore`.
