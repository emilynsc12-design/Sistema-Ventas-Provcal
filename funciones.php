<?php
define("HOY", date("Y-m-d"));

function iniciarSesion($usuario, $password)
{
    $sentencia = "SELECT id, usuario FROM usuarios WHERE usuario  = ?";
    $resultado = select($sentencia, [$usuario]);
    if ($resultado) {
        $usuario = $resultado[0];
        $verificaPass = verificarPassword($usuario->id, $password);
        if ($verificaPass) {
            $_SESSION['usuario'] = $usuario->usuario;
            $_SESSION['idUsuario'] = $usuario->id;
            return $usuario;
        }
    }
}

function verificarPassword($idUsuario, $password)
{
    $sentencia = "SELECT password FROM usuarios WHERE id = ?";
    $contrasenia = select($sentencia, [$idUsuario])[0]->password;
    $verifica = password_verify($password, $contrasenia);
    if ($verifica)
        return true;
}

function cambiarPassword($idUsuario, $password)
{
    $nueva = password_hash($password, PASSWORD_DEFAULT);
    $sentencia = "UPDATE usuarios SET password = ? WHERE id = ?";
    return editar($sentencia, [$nueva, $idUsuario]);
}

function eliminarUsuario($id)
{
    $sentencia = "DELETE FROM usuarios WHERE id = ?";
    return eliminar($sentencia, $id);
}

function editarUsuario($usuario, $nombre, $telefono, $direccion, $id)
{
    $sentencia = "UPDATE usuarios SET usuario = ?, nombre = ?, telefono = ?, direccion = ? WHERE id = ?";
    $parametros = [$usuario, $nombre, $telefono, $direccion, $id];
    return editar($sentencia, $parametros);
}

function obtenerUsuarioPorId($id)
{
    $sentencia = "SELECT id, usuario, nombre, telefono, direccion FROM usuarios WHERE id = ?";
    return select($sentencia, [$id])[0];
}

function usuarioExiste($usuario, $nombre)
{
    $sentencia = "SELECT COUNT(*) as total FROM usuarios WHERE usuario = ? OR nombre = ?";
    $resultado = select($sentencia, [$usuario, $nombre]);

    // Si hay más de 0 coincidencias, el usuario o el nombre completo ya existe
    return $resultado[0]->total > 0;
}

function obtenerUsuarios()
{
    $sentencia = "SELECT id, usuario, nombre, telefono, direccion FROM usuarios";
    return select($sentencia);
}

function registrarUsuario($usuario, $nombre, $telefono, $direccion, $password)
{
    $password = password_hash($password, PASSWORD_DEFAULT);
    $sentencia = "INSERT INTO usuarios (usuario, nombre, telefono, direccion, password) VALUES (?,?,?,?,?)";
    $parametros = [$usuario, $nombre, $telefono, $direccion, $password];
    return insertar($sentencia, $parametros);
}

/* === ❺ NÚMERO DE VENTAS REGISTRADAS ================================== */
function obtenerNumeroVentas()
{
    $sentencia = "SELECT COUNT(*) AS total
                    FROM ventas
                   WHERE anulada = 0";           // ← filtro
    return select($sentencia)[0]->total ?? 0;
}


function obtenerNumeroUsuarios()
{
    $sentencia = "SELECT IFNULL(COUNT(*),0) AS total FROM usuarios";
    return select($sentencia)[0]->total;
}

/* === ❻ VENTAS POR USUARIO (dashboard) ================================ */
function obtenerVentasPorUsuario()
{
    $sentencia = "SELECT SUM(ventas.total)  AS total,
                         usuarios.usuario,
                         COUNT(*)           AS numeroVentas
                    FROM ventas
              INNER JOIN usuarios ON usuarios.id = ventas.idUsuario
                   WHERE ventas.anulada = 0          -- ← filtro
                GROUP BY ventas.idUsuario
                ORDER BY total DESC";
    return select($sentencia);
}

function obtenerProductosMasVendidos()
{
    $sentencia = "SELECT SUM(productos_ventas.cantidad * productos_ventas.precio) AS total, SUM(productos_ventas.cantidad) AS unidades,
    productos.nombre FROM productos_ventas INNER JOIN productos ON productos.id = productos_ventas.idProducto
    GROUP BY productos_ventas.idProducto
    ORDER BY total DESC
    LIMIT 10";
    return select($sentencia);
}

/* === ❶ TOTAL GENERAL ================================================== */
function obtenerTotalVentas($idUsuario = null)
{
    $parametros = [];
    $sentencia = "SELECT IFNULL(SUM(total),0) AS total
                     FROM ventas
                    WHERE anulada = 0";          // ← filtro

    if ($idUsuario !== null) {
        $sentencia .= " AND idUsuario = ?";
        $parametros[] = $idUsuario;
    }
    return select($sentencia, $parametros)[0]->total ?? 0;
}
/* === ❷ TOTAL HOY ====================================================== */
function obtenerTotalVentasHoy($idUsuario = null)
{
    $parametros = [];
    $sentencia = "SELECT IFNULL(SUM(total),0) AS total
                     FROM ventas
                    WHERE DATE(fecha) = CURDATE()
                      AND anulada = 0";          // ← filtro

    if ($idUsuario !== null) {
        $sentencia .= " AND idUsuario = ?";
        $parametros[] = $idUsuario;
    }
    return select($sentencia, $parametros)[0]->total ?? 0;
}

/* === ❸ TOTAL SEMANA =================================================== */
function obtenerTotalVentasSemana($idUsuario = null)
{
    $parametros = [];
    $sentencia = "SELECT IFNULL(SUM(total),0) AS total
                     FROM ventas
                    WHERE WEEK(fecha) = WEEK(NOW())
                      AND anulada = 0";          // ← filtro

    if ($idUsuario !== null) {
        $sentencia .= " AND idUsuario = ?";
        $parametros[] = $idUsuario;
    }
    return select($sentencia, $parametros)[0]->total ?? 0;
}

/* === ❹ TOTAL MES ====================================================== */
function obtenerTotalVentasMes($idUsuario = null)
{
    $parametros = [];
    $sentencia = "SELECT IFNULL(SUM(total),0) AS total
                     FROM ventas
                    WHERE MONTH(fecha) = MONTH(CURRENT_DATE())
                      AND YEAR(fecha)  = YEAR(CURRENT_DATE())
                      AND anulada = 0";          // ← filtro

    if ($idUsuario !== null) {
        $sentencia .= " AND idUsuario = ?";
        $parametros[] = $idUsuario;
    }
    return select($sentencia, $parametros)[0]->total ?? 0;
}

function calcularTotalVentas($ventas)
{
    $total = 0;
    foreach ($ventas as $venta) {
        $total += $venta->total;
    }
    return $total;
}

function calcularProductosVendidos($ventas)
{
    $total = 0;
    foreach ($ventas as $venta) {
        foreach ($venta->productos as $producto) {
            $total += $producto->cantidad;
        }
    }
    return $total;
}

function obtenerGananciaVentas($ventas)
{
    $total = 0;
    foreach ($ventas as $venta) {
        foreach ($venta->productos as $producto) {
            $total += $producto->cantidad * ($producto->precio - $producto->compra);
        }
    }
    return $total;
}

function obtenerVentas($fechaInicio, $fechaFin, $usuario)
{
    $parametros = [];

    $sentencia = "SELECT ventas.*, usuarios.usuario
    FROM ventas 
    INNER JOIN usuarios ON usuarios.id = ventas.idUsuario
    WHERE ventas.anulada = 0";

    if (isset($usuario)) {
        $sentencia .= " AND ventas.idUsuario = ?";
        array_push($parametros, $usuario);
        $ventas = select($sentencia, $parametros);
        return agregarProductosVendidos($ventas);
    }

    if (empty($fechaInicio) && empty($fechaFin)) {
        $sentencia .= " AND DATE(ventas.fecha) = ?";
        array_push($parametros, HOY);
        $ventas = select($sentencia, $parametros);
        return agregarProductosVendidos($ventas);
    }

    if (isset($fechaInicio) && isset($fechaFin)) {
        $sentencia .= " AND DATE(ventas.fecha) >= ? AND DATE(ventas.fecha) <= ?";
        array_push($parametros, $fechaInicio, $fechaFin);
    }

    $ventas = select($sentencia, $parametros);
    return agregarProductosVendidos($ventas);
}

function agregarProductosVendidos($ventas)
{
    foreach ($ventas as $venta) {
        $venta->productos = obtenerProductosVendidos($venta->id);
    }
    return $ventas;
}

function obtenerProductosVendidos($idVenta)
{
    $sentencia = "SELECT productos_ventas.cantidad, productos_ventas.precio, productos.nombre,
    productos.compra
    FROM productos_ventas
    INNER JOIN productos ON productos.id = productos_ventas.idProducto
    WHERE idVenta  = ? ";
    return select($sentencia, [$idVenta]);
}

function registrarVenta($productos, $idUsuario, $total)
{
    $conexion = conectarBaseDatos();

    // Insertar venta
    $sentencia = $conexion->prepare("INSERT INTO ventas (idUsuario, total, fecha) VALUES (?, ?, NOW())");
    $sentencia->execute([$idUsuario, $total]);
    $idVenta = $conexion->lastInsertId();

    foreach ($productos as $producto) {
        // Insertar detalle de venta
        $sentencia = $conexion->prepare("INSERT INTO productos_ventas (idVenta, idProducto, cantidad, precio) VALUES (?, ?, ?, ?)");
        $sentencia->execute([$idVenta, $producto->id, $producto->cantidad, $producto->venta]);

        // Actualizar stock
        $sentencia = $conexion->prepare("UPDATE productos SET existencia = existencia - ? WHERE id = ?");
        $sentencia->execute([$producto->cantidad, $producto->id]);

        // Obtener stock actual para registrar en KARDEX
        $stmtStock = $conexion->prepare("SELECT existencia FROM productos WHERE id = ?");
        $stmtStock->execute([$producto->id]);
        $stock_resultante = $stmtStock->fetchColumn();

        // Registrar en KARDEX como SALIDA
        registrarMovimientoKardex(
            $producto->id,
            'SALIDA',
            $producto->cantidad,
            $stock_resultante,
            'Venta realizada'
        );
    }

    return $idVenta;
}


function registrarProductosVenta($productos, $idVenta)
{
    $sentencia = "INSERT INTO productos_ventas (cantidad, precio, idProducto, idVenta) VALUES (?,?,?,?)";
    foreach ($productos as $producto) {
        $parametros = [$producto->cantidad, $producto->venta, $producto->id, $idVenta];
        insertar($sentencia, $parametros);
        descontarProductos($producto->id, $producto->cantidad);
    }
    return true;
}

function descontarProductos($idProducto, $cantidad)
{
    $sentencia = "UPDATE productos SET existencia  = existencia - ? WHERE id = ?";
    $parametros = [$cantidad, $idProducto];
    return editar($sentencia, $parametros);
}

function obtenerUltimoIdVenta()
{
    $sentencia = "SELECT id FROM ventas ORDER BY id DESC LIMIT 1";
    return select($sentencia)[0]->id;
}

function calcularTotalLista($lista)
{
    $total = 0;
    foreach ($lista as $producto) {
        $total += floatval($producto->venta * $producto->cantidad);
    }
    return $total;
}

function agregarProductoALista($producto, $listaProductos)
{
    if ($producto->existencia < 1)
        return $listaProductos;
    $producto->cantidad = 1;

    $existe = verificarSiEstaEnLista($producto->id, $listaProductos);

    if (!$existe) {
        array_push($listaProductos, $producto);
    } else {
        $existenciaAlcanzada = verificarExistencia($producto->id, $listaProductos, $producto->existencia);

        if ($existenciaAlcanzada)
            return $listaProductos;

        $listaProductos = agregarCantidad($producto->id, $listaProductos);
    }

    return $listaProductos;

}

function verificarExistencia($idProducto, $listaProductos, $existencia)
{
    foreach ($listaProductos as $producto) {
        if ($producto->id == $idProducto) {
            if ($existencia <= $producto->cantidad)
                return true;
        }
    }
    return false;
}

function verificarSiEstaEnLista($idProducto, $listaProductos)
{
    foreach ($listaProductos as $producto) {
        if ($producto->id == $idProducto) {
            return true;
        }
    }
    return false;
}

function anularVenta($idVenta)
{
    $conexion = conectarBaseDatos();

    // Verifica si ya está anulada
    $stmt = $conexion->prepare("SELECT anulada FROM ventas WHERE id = ?");
    $stmt->execute([$idVenta]);
    $venta = $stmt->fetch();
    if (!$venta || $venta->anulada) {
        return false; // Ya anulada o no existe
    }

    // Marcar como anulada
    $stmt = $conexion->prepare("UPDATE ventas SET anulada = 1 WHERE id = ?");
    $stmt->execute([$idVenta]);

    // Obtener productos de la venta
    $stmt = $conexion->prepare("SELECT idProducto, cantidad FROM productos_ventas WHERE idVenta = ?");
    $stmt->execute([$idVenta]);
    $productos = $stmt->fetchAll();

    foreach ($productos as $p) {
        $idProducto = $p->idProducto;
        $cantidad = $p->cantidad;

        // 1. Restaurar stock
        $stmt = $conexion->prepare("UPDATE productos SET existencia = existencia + ? WHERE id = ?");
        $stmt->execute([$cantidad, $idProducto]);

        // 2. Obtener stock actualizado
        $stmtStock = $conexion->prepare("SELECT existencia FROM productos WHERE id = ?");
        $stmtStock->execute([$idProducto]);
        $stock_resultante = $stmtStock->fetchColumn();

        // 3. Registrar ajuste en Kardex
        registrarMovimientoKardex(
            $idProducto,
            'AJUSTE',
            $cantidad,
            $stock_resultante,
            "Anulación de venta ID $idVenta"
        );
    }

    return true;
}

function agregarCantidad($idProducto, $listaProductos)
{
    foreach ($listaProductos as $producto) {
        if ($producto->id == $idProducto) {
            $producto->cantidad++;
        }
    }
    return $listaProductos;
}

function obtenerProductoPorCodigo($codigo)
{
    $sentencia = "SELECT * FROM productos WHERE codigo = ?";
    $producto = select($sentencia, [$codigo]);
    if ($producto)
        return $producto[0];
    return [];
}

function obtenerNumeroProductos()
{
    $sentencia = "SELECT IFNULL(SUM(existencia),0) AS total FROM productos";
    $fila = select($sentencia);
    if ($fila)
        return $fila[0]->total;
}

function obtenerTotalInventario()
{
    $sentencia = "SELECT IFNULL(SUM(existencia * venta),0) AS total FROM productos";
    $fila = select($sentencia);
    if ($fila)
        return $fila[0]->total;
}

//function calcularGananciaProductos(){
//    $sentencia = "SELECT IFNULL(SUM(existencia*venta) - SUM(existencia*compra),0) AS total FROM productos";
//    $fila = select($sentencia);
//    if($fila) return $fila[0]->total;
//}

function eliminarProducto($idProducto)
{
    $bd = conectarBaseDatos();
    try {
        $bd->beginTransaction();

        // 1. Borrar movimientos de kardex del producto
        $bd->prepare("DELETE FROM kardex WHERE idProducto = ?")
            ->execute([$idProducto]);

        // 2. Borrar registros de ventas detalle (si los hay)
        $bd->prepare("DELETE FROM productos_ventas WHERE idProducto = ?")
            ->execute([$idProducto]);

        // 3. (Opcional) Si tuvieras otra tabla de compras, bórrala aquí

        // 4. Borrar finalmente el producto
        $bd->prepare("DELETE FROM productos WHERE id = ?")
            ->execute([$idProducto]);

        $bd->commit();
        return true;

    } catch (PDOException $e) {
        $bd->rollBack();
        throw $e; // o devuelve false y maneja el error arriba
    }
}


function editarProducto($codigo, $nombre, $compra, $venta, $existencia, $id)
{
    $sentencia = "UPDATE productos SET codigo = ?, nombre = ?, compra = ?, venta = ?, existencia = ? WHERE id = ?";
    $parametros = [$codigo, $nombre, $compra, $venta, $existencia, $id];
    return editar($sentencia, $parametros);
}

function obtenerProductoPorId($id)
{
    $sentencia = "SELECT * FROM productos WHERE id = ?";
    return select($sentencia, [$id])[0];
}

function obtenerProductos($busqueda = null)
{
    $parametros = [];
    $sentencia = "SELECT * FROM productos ";
    if (isset($busqueda)) {
        $sentencia .= " WHERE nombre LIKE ? OR codigo LIKE ?";
        array_push($parametros, "%" . $busqueda . "%", "%" . $busqueda . "%");
    }
    return select($sentencia, $parametros);
}

function registrarProducto($codigo, $nombre, $compra, $venta, $existencia)
{
    $bd = conectarBaseDatos();
    $bd->beginTransaction();

    // 1) Insertar producto
    $stmt = $bd->prepare(
        "INSERT INTO productos(codigo, nombre, compra, venta, existencia)
         VALUES (?,?,?,?,?)"
    );
    $stmt->execute([$codigo, $nombre, $compra, $venta, $existencia]);

    $idProducto = $bd->lastInsertId();

    // 2) Registrar stock inicial en KARDEX **solo si la existencia > 0**
    if ($existencia > 0) {
        $stmtK = $bd->prepare(
            "INSERT INTO kardex (idProducto, tipo_movimiento, cantidad,
                                 stock_resultante, fecha, observacion)
             VALUES (?, 'AJUSTE', ?, ?, NOW(),
                     'Stock inicial (alta de producto)')"
        );
        $stmtK->execute([$idProducto, $existencia, $existencia]);
    }

    $bd->commit();
    return true;
}


function select($sentencia, $parametros = [])
{
    $bd = conectarBaseDatos();
    $respuesta = $bd->prepare($sentencia);
    $respuesta->execute($parametros);
    return $respuesta->fetchAll();
}

function insertar($sentencia, $parametros)
{
    $bd = conectarBaseDatos();
    $respuesta = $bd->prepare($sentencia);
    return $respuesta->execute($parametros);
}

function eliminar($sentencia, $id)
{
    $bd = conectarBaseDatos();
    $respuesta = $bd->prepare($sentencia);
    return $respuesta->execute([$id]);
}

function editar($sentencia, $parametros)
{
    $bd = conectarBaseDatos();
    $respuesta = $bd->prepare($sentencia);
    return $respuesta->execute($parametros);
}

//KARDEX
function registrarMovimientoKardex($idProducto, $tipo_movimiento, $cantidad, $stock_resultante, $observacion = "")
{
    $sentencia = "INSERT INTO kardex (idProducto, tipo_movimiento, cantidad, stock_resultante, observacion)
                  VALUES (?, ?, ?, ?, ?)";
    $parametros = [$idProducto, $tipo_movimiento, $cantidad, $stock_resultante, $observacion];
    insertar($sentencia, $parametros);
}

//CONEXION A LA BASE DE DATOS
//  ⚠️  Los datos se leen de las variables de entorno (archivo .env, que NO se sube a Git).
//       Nunca escribas contraseñas dentro de este archivo.
function conectarBaseDatos()
{
    $host = getenv('DB_HOST') ?: '';
    $port = (int) (getenv('DB_PORT') ?: 3306);
    $db   = getenv('DB_NAME') ?: '';
    $user = getenv('DB_USER') ?: '';
    $pass = getenv('DB_PASS') ?: '';
    $charset = 'utf8mb4';

    if ($host === '' || $db === '' || $user === '') {
        throw new RuntimeException(
            'Falta la configuración de la base de datos. ' .
            'Copia el archivo .env.example a .env y completa DB_HOST, DB_NAME, DB_USER y DB_PASS.'
        );
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-05:00'"
    ];

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    return new PDO($dsn, $user, $pass, $options);
}



function verificarInactividad()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['usuario'])) { // Cambié 'usuario_id' por 'usuario'
        $inactivity_limit = 480; // 8 minutos de inactividad

        if (isset($_SESSION['last_activity'])) {
            $inactive_time = time() - $_SESSION['last_activity'];

            // Mensaje de depuración
            error_log("Tiempo de inactividad: $inactive_time segundos");

            if ($inactive_time > $inactivity_limit) {
                // Mensaje de depuración antes de destruir la sesión
                echo "Sesión expirada. Cerrando sesión...<br>";
                error_log("Sesión expirada. Cerrando sesión...");

                session_unset();
                session_destroy();
                header("Location: login.php");
                exit();
            }
        }

        // Actualiza la última actividad
        $_SESSION['last_activity'] = time();
    }
}

