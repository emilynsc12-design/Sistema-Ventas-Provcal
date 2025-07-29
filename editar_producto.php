<?php
session_start();
if (empty($_SESSION['usuario'])) {
    header("location: login.php");
    exit;
}

include_once "funciones.php";

// Obtener ID del producto
$id = $_GET['id'] ?? null;
if (!$id) {
    echo 'No se ha seleccionado el producto';
    exit;
}

// Obtener los datos actuales del producto
$producto = obtenerProductoPorId($id);
$existencia_anterior = intval($producto->existencia);

// Procesar formulario si se envió
if (isset($_POST['registrar'])) {
    $codigo = $_POST['codigo'];
    $nombre = $_POST['nombre'];
    $compra = $_POST['compra'];
    $venta = $_POST['venta'];
    $existencia = $_POST['existencia'];

    if (empty($codigo) || empty($nombre) || empty($compra) || empty($venta) || empty($existencia)) {
        $error = "Debes completar todos los datos.";
    } else {
        $resultado = editarProducto($codigo, $nombre, $compra, $venta, $existencia, $id);
        if ($resultado) {
            // Comparar existencia anterior vs nueva
            $existencia_nueva = intval($existencia);
            $diferencia = $existencia_nueva - $existencia_anterior;

            if ($diferencia !== 0) {
                $tipo_movimiento = $diferencia > 0 ? 'INGRESO' : 'AJUSTE';  // AJUSTE si disminuye, ya que no es una venta
                $cantidad = abs($diferencia);
                $observacion = "Ajuste manual desde edición del producto";

                // Registrar en KARDEX con la función
                registrarMovimientoKardex($id, $tipo_movimiento, $cantidad, $existencia_nueva, $observacion);
            }

            header("Location: productos.php");
            exit;
        } else {
            $error = "No se pudo actualizar el producto.";
        }
    }
}
?>

<?php include_once "encabezado.php"; ?>
<?php include_once "navbar.php"; ?>

<div class="container">
    <h3>Editar producto</h3>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger mt-3" role="alert">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="codigo" class="form-label">Código de barras</label>
            <input type="text" name="codigo" class="form-control" value="<?= $producto->codigo; ?>" id="codigo"
                placeholder="Escribe el código de barras del producto">
        </div>
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre o descripción</label>
            <input type="text" name="nombre" class="form-control" value="<?= $producto->nombre; ?>" id="nombre"
                placeholder="Ej. Papas">
        </div>
        <div class="row">
            <div class="col">
                <label for="compra" class="form-label">Precio compra</label>
                <input type="number" name="compra" step="any" value="<?= $producto->compra; ?>" id="compra"
                    class="form-control" placeholder="Precio de compra">
            </div>
            <div class="col">
                <label for="venta" class="form-label">Precio venta</label>
                <input type="number" name="venta" step="any" value="<?= $producto->venta; ?>" id="venta"
                    class="form-control" placeholder="Precio de venta">
            </div>
            <div class="col">
                <label for="existencia" class="form-label">Existencia</label>
                <input type="number" name="existencia" step="any" value="<?= $producto->existencia; ?>" id="existencia"
                    class="form-control" placeholder="Existencia">
            </div>
        </div>
        <div class="text-center mt-3">
            <input type="submit" name="registrar" value="Registrar" class="btn btn-primary btn-lg">
            <a href="productos.php" class="btn btn-danger btn-lg">
                <i class="fa fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>