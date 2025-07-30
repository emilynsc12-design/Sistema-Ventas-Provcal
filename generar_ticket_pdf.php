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
$fecha = date("d/m/Y H:i:s", strtotime($venta->fecha));
$total = number_format($venta->total, 2);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket Venta</title>
    <style>
        body {
            font-family: monospace;
            width: 300px;
            padding: 10px;
        }
        .centrado {
            text-align: center;
        }
        .linea {
            border-top: 1px dashed black;
            margin: 8px 0;
        }
    </style>
</head>
<body onload="window.print(); setTimeout(() => window.location.href='vender.php', 1000);">

    <div class="centrado">
        <h2>PROVCAL</h2>
        <p>Catering & Camps</p>
    </div>

    <p>Ticket: #<?= $idVenta ?></p>
    <p>Fecha : <?= $fecha ?></p>
    <p>Cajero: <?= $venta->cajero ?></p>

    <div class="linea"></div>

    <table style="width: 100%; font-size: 14px;">
        <thead>
            <tr>
                <th style="text-align:left">Producto</th>
                <th>Cant</th>
                <th style="text-align:right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $prod): 
                $subtotal = number_format($prod->precio * $prod->cantidad, 2);
            ?>
            <tr>
                <td><?= $prod->nombre ?></td>
                <td style="text-align:center"><?= $prod->cantidad ?></td>
                <td style="text-align:right">S/.<?= $subtotal ?></td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <div class="linea"></div>

    <h3 class="centrado">TOTAL: S/. <?= $total ?></h3>

    <div class="centrado">
        <p>Gracias por su compra</p>
    </div>
</body>
</html>
