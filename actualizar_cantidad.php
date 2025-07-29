<?php
session_start();
if (!isset($_POST['id'], $_POST['cantidad'])) {
    header("Location: vender.php");
    exit;
}

$id = $_POST['id'];
$nueva_cantidad = intval($_POST['cantidad']);

foreach ($_SESSION['lista'] as &$producto) {
    if ($producto->id == $id) {
        $producto->cantidad = $nueva_cantidad;
        break;
    }
}

header("Location: vender.php");
