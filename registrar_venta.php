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

// Limpiar lista
$_SESSION['lista'] = [];

// Redirigir
echo "
<script>
    alert('Venta realizada con éxito');
    window.location.href='generar_ticket_pdf.php?id={$resultado}';
</script>";
?>