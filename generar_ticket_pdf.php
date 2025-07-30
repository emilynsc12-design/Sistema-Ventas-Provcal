<?php
require_once 'funciones.php';

if (!isset($_GET['id'])) {
    echo "ID de venta no especificado.";
    exit;
}

$idVenta = $_GET['id'];
$venta = select("SELECT ventas.*, usuarios.nombre AS cajero FROM ventas 
                 INNER JOIN usuarios ON usuarios.id = ventas.idUsuario 
                 WHERE ventas.id = ?", [$idVenta])[0];

$productos = obtenerProductosVendidos($idVenta);
$fecha = date("d/m/Y", strtotime($venta->fecha));
$hora = date("H:i", strtotime($venta->fecha));
$total = number_format($venta->total, 2);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket Venta</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            width: 300px;
            margin: 0 auto;
            white-space: pre;
        }
        .centrado {
            text-align: center;
        }
        .linea {
            margin: 8px 0;
        }
    </style>
</head>
<body onload="window.print(); setTimeout(() => window.location.href='vender.php', 1000);">

<div class="centrado">
PROVCAL
Catering & Camps
</div>

Cliente: A.M.C  
Fecha: <?= $fecha ?>            Hora: <?= $hora ?>


<?= str_repeat("=", 48) . "\n" ?>
<?= sprintf("%-24s%6s%8s%10s", "Producto", "Cant", "P.U.", "Total") . "\n" ?>
<?= str_repeat("=", 48) . "\n" ?>

<?php foreach ($productos as $prod):
    $nombre = mb_strimwidth($prod->nombre, 0, 24, "");
    $cantidad = $prod->cantidad;
    $precio = number_format($prod->venta, 2);
    $subtotal = number_format($prod->venta * $prod->cantidad, 2);
    echo sprintf("%-24s%6s%8s%10s\n", $nombre, $cantidad, $precio, $subtotal);
endforeach; ?>

<?= str_repeat("=", 48) . "\n" ?>
<?= str_pad("TOTAL: S/. " . $total, 48, " ", STR_PAD_LEFT) . "\n\n" ?>

<div class="centrado">
Gracias por su compra!
</div>

</body>
</html>
