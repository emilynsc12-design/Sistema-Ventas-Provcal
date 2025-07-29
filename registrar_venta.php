<?php
include_once "funciones.php";
require __DIR__ . '/vendor/autoload.php';  // Para la librería escpos-php
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

session_start();

$productos = $_SESSION['lista'];
$idUsuario = $_SESSION['idUsuario'];
$total = calcularTotalLista($productos);
$idCliente = null;

if (count($productos) === 0) {
    header("location: vender.php");
    exit;
}

$resultado = registrarVenta($productos, $idUsuario, $idCliente, $total);

if (!$resultado) {
    echo "Error al registrar la venta";
    return;
}

// IMPRESIÓN DEL TICKET
try {
    $nombreImpresora = "POS-90";
    $connector = new WindowsPrintConnector($nombreImpresora);
    $printer = new Printer($connector);

    // Cabecera
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->setTextSize(2, 2);
    $printer->text("PROVCAL\n");
    $printer->setTextSize(1, 1);
    $printer->text("Catering & Camps\n");

    $printer->feed(1);
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->text("Cliente: A.M.C\n");

    // Fecha y hora alineadas en una misma línea
    $printer->text(
        str_pad("Fecha: " . date("d/m/Y"), 24) .
        str_pad("Hora: " . date("H:i"), 24, " ", STR_PAD_LEFT) . "\n"
    );

    $printer->text(str_repeat("=", 48) . "\n");

    // ENCABEZADO
    $printer->text(
        str_pad("Producto", 24) .
        str_pad("Cant", 6, " ", STR_PAD_LEFT) .
        str_pad("P.U.", 8, " ", STR_PAD_LEFT) .
        str_pad("Total", 10, " ", STR_PAD_LEFT) . "\n"
    );
    $printer->text(str_repeat("=", 48) . "\n");


    $printer->setFont(Printer::FONT_A); // fuente más confiable
    $printer->setJustification(Printer::JUSTIFY_LEFT); // garantiza alineación izquierda
    // DETALLES con sprintf exacto (alineación a 48 caracteres)
    foreach ($productos as $producto) {
        $nombre = mb_strimwidth($producto->nombre, 0, 24, ""); // más seguro aún sin relleno
        $cantidad = $producto->cantidad;
        $precio = number_format($producto->venta, 2);
        $subtotal = number_format($cantidad * $producto->venta, 2);

        $printer->text(sprintf(
            "%-24s%6s%8s%10s\n",
            $nombre,
            $cantidad,
            $precio,
            $subtotal
        ));
    }


    // TOTAL
    $printer->text(str_repeat("=", 48) . "\n");
    $printer->setJustification(Printer::JUSTIFY_RIGHT);
    $printer->text("TOTAL: S/. " . number_format($total, 2) . "\n");

    // CIERRE
    $printer->feed(2);
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("Gracias por su compra!\n\n");
    $printer->cut();
    $printer->close();

} catch (Exception $e) {
    error_log("Error al imprimir: " . $e->getMessage());
}


// Limpiar lista
$_SESSION['lista'] = [];

// Redirigir
echo "
<script>
    alert('Venta realizada con éxito');
    window.location.href='vender.php';
</script>";
?>