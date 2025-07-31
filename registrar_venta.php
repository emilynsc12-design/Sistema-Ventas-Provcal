<?php
include_once "funciones.php";
require __DIR__ . '/vendor/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

session_start();

$productos = $_SESSION['lista'] ?? [];
$idUsuario = $_SESSION['idUsuario'] ?? null;
$idCliente = null;

header('Content-Type: application/json');

if (count($productos) === 0 || !$idUsuario) {
    echo json_encode(['success' => false]);
    exit;
}

$total = calcularTotalLista($productos);
$idVenta = registrarVenta($productos, $idUsuario, $idCliente, $total);

if (!$idVenta) {
    echo json_encode(['success' => false]);
    exit;
}

// Limpiar lista
$_SESSION['lista'] = [];

echo json_encode(['success' => true, 'id_venta' => $idVenta]);
