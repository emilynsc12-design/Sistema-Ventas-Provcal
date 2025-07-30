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
    <title>Ticket POS</title>
    <style>
        * {
            font-family: monospace;
            font-size: 12px;
        }

        @media print {
            @page {
                size: 58mm auto;
                margin: 0;
            }

            body {
                width: 58mm;
                margin: 0;
                padding: 5px;
            }
        }

        body {
            width: 58mm;
            margin: auto;
            padding: 5px;
        }

        .center {
            text-align: center;
        }

        .line {
            border-top: 1px dashed black;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            padding: 2px 0;
        }

        .totales {
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>
<body onload="window.print(); setTimeout(() => window.location.href='vender.php', 1000);">

    <div class="center">
        <h2>PROVCAL</h2>
        <p>Catering & Camps</p>
    </div>

    <p>Ticket: #<?= $idVenta ?></p>
    <p>Fecha : <?= $fecha ?></p>
    <p>Hora  : <?= $hora ?></p>
    <p>Cajero: <?= $venta->cajero ?></p>

    <div class="line"></div>

    <table>
        <thead>
            <tr>
                <th style="text-align:left">Producto</th>
                <th style="text-align:center">Cant</th>
                <th style="text-align:right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $prod): 
                $subtotal = number_format($prod->precio * $prod->cantidad, 2);
            ?>
            <tr>
                <td><?= mb_strimwidth($prod->nombre, 0, 20, '') ?></td>
                <td style="text-align:center"><?= $prod->cantidad ?></td>
                <td style="text-align:right">S/.<?= $subtotal ?></td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <div class="line"></div>

    <p class="totales">TOTAL: S/. <?= $total ?></p>

    <div class="center">
        <p>Gracias por su compra</p>
    </div>

</body>
</html>
