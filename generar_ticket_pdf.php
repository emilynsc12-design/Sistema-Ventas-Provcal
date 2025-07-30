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
    <title>Ticket</title>
    <style>
        * {
            font-family: monospace;
        }

        @media print {
            @page {
                size: 58mm auto;
                margin: 0;
            }
            body {
                margin: 0;
            }
        }

        body {
            width: 58mm;
            padding: 5px;
            font-size: 11px;
        }

        .center {
            text-align: center;
        }

        .left {
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .line {
            border-top: 1px dashed black;
            margin: 5px 0;
        }

        .space {
            height: 10px;
        }

        .row {
            display: flex;
            justify-content: space-between;
        }

        .row-full {
            display: flex;
        }

        .row-full .left, .row-full .right {
            flex: 1;
        }

        .row-full .right {
            text-align: right;
        }

        pre {
            font-family: monospace;
            font-size: 11px;
            margin: 0;
        }
    </style>
</head>
<body onload="window.print(); setTimeout(() => window.location.href='vender.php', 1000);">

    <div class="center bold" style="font-size: 16px;">PROVCAL</div>
    <div class="center">Catering & Camps</div>

    <div class="space"></div>

    <div class="left">Cliente: A.M.C</div>

    <div class="row-full">
        <div class="left">Fecha: <?= $fecha ?></div>
        <div class="right">Hora: <?= $hora ?></div>
    </div>

    <div class="line"></div>

    <pre><?= str_pad("Producto", 24) . str_pad("Cant", 6, " ", STR_PAD_LEFT) . str_pad("P.U.", 8, " ", STR_PAD_LEFT) . str_pad("Total", 10, " ", STR_PAD_LEFT) ?></pre>
    <div class="line"></div>

    <?php foreach ($productos as $producto): 
        $nombre = mb_strimwidth($producto->nombre, 0, 24, "");
        $cantidad = $producto->cantidad;
        $precio = number_format($producto->precio, 2);
        $subtotal = number_format($producto->precio * $producto->cantidad, 2);
    ?>
        <pre><?= sprintf("%-24s%6s%8s%10s", $nombre, $cantidad, $precio, $subtotal) ?></pre>
    <?php endforeach; ?>

    <div class="line"></div>

    <div class="right bold">TOTAL: S/. <?= $total ?></div>

    <div class="space"></div>
    <div class="center">Gracias por su compra!</div>
    <div class="space"></div>

</body>
</html>
