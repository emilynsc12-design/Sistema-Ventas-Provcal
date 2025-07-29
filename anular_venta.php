<?php
require_once "funciones.php";

if (!isset($_GET['id'])) {
    header("Location: reporte_ventas.php");
    exit;
}

$idVenta = $_GET['id'];

$exito = anularVenta($idVenta);

if ($exito) {
    header("Location: reporte_ventas.php?msg=anulada");
} else {
    header("Location: reporte_ventas.php?msg=error");
}
exit;
