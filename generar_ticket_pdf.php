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
            font-size: 13px;
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
            padding: 8px;
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

        .spacer {
            height: 10px;
        }

        pre {
            font-family: monospace;
            font-size: 13px;
            margin: 0;
            white-space: pre;
        }
    </style>
</head>
<body onload="window.print(); setTimeout(() => window.location.href='vender.php', 1000);">

<pre class="center" style="font-size:16px;">PROVCAL</pre>
<pre class="center">Catering & Camps</pre>

<div class="spacer"></div>

<pre>Cliente: A.M.C</pre>
<pre><?= str_pad("Fecha: " . $fecha, 24) . str_pad("Hora: " . $hora, 24, " ", STR_PAD_LEFT) ?></pre>

<pre><?= str_repeat("=", 48) ?></pre>
<pre><?= str_pad("Producto", 24) . str_pad("Cant", 6, " ", STR_PAD_LEFT) . str_pad("P.U.", 8, " ", STR_PAD_LEFT) . str_pad("Total", 10, " ", STR_PAD_LEFT) ?></pre>
<pre><?= str_repeat("=", 48) ?></pre>

<?php foreach ($productos as $producto): 
    $nombre = mb_strimwidth($producto->nombre, 0, 24, "");
    $cantidad = $producto->cantidad;
    $precio = number_format($producto->precio, 2);
    $subtotal = number_format($producto->precio * $producto->cantidad, 2);
?>
<pre><?= sprintf("%-24s%6s%8s%10s", $nombre, $cantidad, $precio, $subtotal) ?></pre>
<?php endforeach; ?>

<pre><?= str_repeat("=", 48) ?></pre>
<pre class="right">TOTAL: S/. <?= $total ?></pre>

<div class="spacer"></div>
<pre class="center">Gracias por su compra!</pre>
<div class="spacer"></div>

</body>
</html>
