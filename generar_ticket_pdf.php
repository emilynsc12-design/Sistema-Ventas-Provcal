<?php
require_once 'funciones.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['id'])) {
    echo "ID de venta no especificado.";
    exit;
}

$idVenta = $_GET['id'];
$venta = select("SELECT ventas.*, usuarios.nombre AS cajero FROM ventas 
                 INNER JOIN usuarios ON usuarios.id = ventas.idUsuario 
                 WHERE ventas.id = ?", [$idVenta]);

if (!$venta) {
    echo "Venta no encontrada.";
    exit;
}

$venta = $venta[0];
$productos = obtenerProductosVendidos($idVenta);
$fecha = date("d/m/Y", strtotime($venta->fecha));
$hora = date("H:i", strtotime($venta->fecha));
$total = number_format($venta->total, 2);

// HTML
ob_start();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Ticket</title>
    <style>
        @page {
            size: 255pt auto;
            margin: 0;
        }

        body {
            font-family: 'share_tech_mono_normal_1f0d736d02a43e11c467969cc34f784f', monospace;
            font-size: 17px;
            margin: 0;
            padding: 0;
            width: 255pt;
            line-height: 1.2;
        }

        .texto-resaltado {
            text-shadow: 0.5px 0.5px black;
        }

        pre {
            margin: 0;
            padding: 0;
            white-space: pre;
        }

        .encabezado {
            font-size: 30px;
            font-weight: bold;
            text-align: center;
        }

        .encabezado2 {
            font-size: 18px;
            text-align: center;
        }

        .detalle {
            font-size: 17px;
        }

        .gracias {
            font-size: 17px;
            text-align: center;
        }
    </style>

</head>

<body class="texto-resaltado">
    <pre class="encabezado">PROVCAL</pre>
    <pre class="encabezado2">Catering & Camps</pre>
    <br>

    <pre class="detalle">Cliente: A.M.C</pre>
    <table
        style="width: 100%; font-family: 'share_tech_mono_normal_1f0d736d02a43e11c467969cc34f784f', monospace; font-size: 17px; border-collapse: collapse; margin: 0; padding: 0;">
        <tr>
            <td style="text-align: left;">Fecha: <?= $fecha ?></td>
            <td style="text-align: right;">Hora: <?= $hora ?></td>
        </tr>
    </table>
    <pre class="detalle"><?= str_repeat("=", 42) ?></pre>
    <pre class="detalle"><?= sprintf("%-14s %4s %6s %8s", "Producto", "Cant", "P.U.", "Subtotal") ?></pre>

    <pre class="detalle"><?= str_repeat("=", 42) ?></pre>

    <?php foreach ($productos as $producto):
        $nombre = mb_strimwidth($producto->nombre, 0, 15, "");
        $cantidad = $producto->cantidad;
        $precio = number_format($producto->precio, 2);
        $subtotal = number_format($producto->precio * $producto->cantidad, 2);
        ?>
        <pre class="detalle"><?= sprintf("%-14s %4s %6s %8s", $nombre, $cantidad, $precio, $subtotal) ?></pre>
    <?php endforeach; ?>

    <pre class="detalle"><?= str_repeat("=", 42) ?></pre>
    <pre class="detalle"><?= sprintf("%34s", "TOTAL: S/. " . $total) ?></pre>

    <br><br>
    <pre class="gracias">Gracias por su compra!</pre>
</body>

</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('defaultFont', 'share_tech_mono_normal_1f0d736d02a43e11c467969cc34f784f');
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('isFontSubsettingEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper([0, 0, 270, 1100], 'portrait');
$dompdf->render();

// Si no se está redirigiendo, entregar el PDF directamente (por si se llama directo)
$dompdf->stream("ticket_venta_$idVenta.pdf", ["Attachment" => false]);
exit;
?>