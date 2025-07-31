<?php
ini_set('max_execution_time', 180); // 120 segundos
include_once "encabezado.php";
include_once "navbar.php";
include_once "funciones.php";
session_start();
if (empty($_SESSION['usuario']))
    header("location: login.php");

if (isset($_POST['buscar'])) {
    if (empty($_POST['inicio']) || empty($_POST['fin']))
        header("location: reporte_ventas.php");
}

if (isset($_POST['buscarPorUsuario'])) {
    if (empty($_POST['idUsuario']))
        header("location: reporte_ventas.php");
}

$fechaInicio = $_POST['inicio'] ?? null;
$fechaFin = $_POST['fin'] ?? null;
$usuario = $_POST['idUsuario'] ?? null;

$ventas = obtenerVentas($fechaInicio, $fechaFin, null, $usuario);

$cartas = [
    ["titulo" => "No. ventas", "icono" => "fa fa-shopping-cart", "total" => count($ventas), "color" => "#A71D45"],
    ["titulo" => "Total ventas", "icono" => "fa fa-money-bill", "total" => "S/." . calcularTotalVentas($ventas), "color" => "#2A8D22"],
    ["titulo" => "Productos vendidos", "icono" => "fa fa-box", "total" => calcularProductosVendidos($ventas), "color" => "#223D8D"],
    ["titulo" => "Ganancia", "icono" => "fa fa-wallet", "total" => "S/." . obtenerGananciaVentas($ventas), "color" => "#D55929"],
];

$usuarios = obtenerUsuarios();
?>
<div class="container">
    <br>
    <h2>Reporte de ventas :
        <?php
        if (empty($fechaInicio))
            echo HOY;
        if ($fechaInicio && $fechaFin)
            echo $fechaInicio . " al " . $fechaFin;
        ?>
    </h2>

    <form class="row mb-3" method="post">
        <div class="col-5">
            <label for="inicio" class="form-label">Fecha búsqueda inicial</label>
            <input type="date" name="inicio" class="form-control" id="inicio">
        </div>
        <div class="col-5">
            <label for="fin" class="form-label">Fecha búsqueda final</label>
            <input type="date" name="fin" class="form-control" id="fin">
        </div>
        <div class="col">
            <input type="submit" name="buscar" value="Buscar" class="btn btn-primary mt-4">
        </div>
    </form>

    <form action="" method="post" class="row mb-3">
        <div class="col-6">
            <select class="form-select" aria-label="Filtrar por usuario" name="idUsuario">
                <option selected value="">Selecciona un usuario</option>
                <?php foreach ($usuarios as $usuario) { ?>
                    <option value="<?= $usuario->id ?>"><?= $usuario->usuario ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-2">
            <input type="submit" name="buscarPorUsuario" value="Buscar por usuario" class="btn btn-secondary">
        </div>
    </form>

    <?php include_once "cartas_totales.php" ?>

    <?php if (count($ventas) > 0) { ?>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Usuario</th>
                    <th>Productos</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ventas as $venta) { ?>
                    <tr>
                        <td><?= $venta->id; ?></td>
                        <td><?= $venta->fecha; ?></td>
                        <td><?= $venta->cliente; ?></td>
                        <td>S/.<?= $venta->total; ?></td>
                        <td><?= $venta->usuario; ?></td>
                        <td>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Cantidad</th>
                                        <th>Precio unitario</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $totalVenta = 0;
                                    foreach ($venta->productos as $producto) {
                                        $subtotal = $producto->cantidad * $producto->precio;
                                        $totalVenta += $subtotal;
                                        ?>
                                        <tr>
                                            <td><?= $producto->nombre; ?></td>
                                            <td><?= $producto->cantidad; ?></td>
                                            <td>S/.<?= number_format($producto->precio, 2); ?></td>
                                            <td>S/.<?= number_format($subtotal, 2); ?></td>
                                        </tr>
                                    <?php } ?>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total de venta:</strong></td>
                                        <td><strong>S/.<?= number_format($totalVenta, 2); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>

                        <td>
                            <?php if (!isset($venta->anulada) || !$venta->anulada): ?>
                                <a href="anular_venta.php?id=<?= $venta->id ?>" class="btn btn-sm btn-danger"
                                    onclick="return confirm('¿Estás seguro de anular esta venta?');">
                                    Anular
                                </a>
                            <?php else: ?>
                                <span class="badge bg-danger">Anulada</span>
                            <?php endif; ?>
                        </td>

                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <div class="alert alert-warning mt-3" role="alert">
            <h1>No se han encontrado ventas</h1>
        </div>
    <?php } ?>
</div>