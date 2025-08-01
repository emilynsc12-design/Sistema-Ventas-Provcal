<?php
include_once "funciones.php";
session_start();

if (isset($_POST['agregar']) && isset($_POST['codigo']) && isset($_POST['cantidad'])) {
    $codigo = $_POST['codigo'];
    $cantidadIngresada = intval($_POST['cantidad']);

    // Validar formato del código
    if (!ctype_digit($codigo) || strlen($codigo) != 12) {
        $_SESSION['mensaje_error'] = "El código debe tener exactamente 6 dígitos numéricos.";
        header("Location: vender.php");
        exit;
    }

    if ($cantidadIngresada <= 0) {
        $_SESSION['mensaje_error'] = "La cantidad debe ser mayor que 0.";
        header("Location: vender.php");
        exit;
    }

    // Obtener producto desde BD
    $producto = obtenerProductoPorCodigo($codigo);
    if (!$producto) {
        $_SESSION['mensaje_error'] = "Producto no encontrado.";
        header("Location: vender.php");
        exit;
    }

    // Obtener lista actual
    $_SESSION['lista'] = $_SESSION['lista'] ?? [];

    // Calcular cantidad acumulada en la lista actual
    $cantidadActual = 0;
    foreach ($_SESSION['lista'] as $item) {
        if ($item->id == $producto->id) {
            $cantidadActual += $item->cantidad;
        }
    }

    // Validar stock disponible
    if (($cantidadActual + $cantidadIngresada) > $producto->existencia) {
        $disponible = $producto->existencia - $cantidadActual;
        $_SESSION['mensaje_error'] = "Stock insuficiente. Solo puedes agregar {$disponible} unidad(es) más.";
        header("Location: vender.php");
        exit;
    }

    // Agregar o incrementar en la lista
    $encontrado = false;
    foreach ($_SESSION['lista'] as &$item) {
        if ($item->id == $producto->id) {
            $item->cantidad += $cantidadIngresada;
            $encontrado = true;
            break;
        }
    }

    if (!$encontrado) {
        $producto->cantidad = $cantidadIngresada;
        $_SESSION['lista'][] = $producto;
    }

    header("Location: vender.php");
    exit;
}
?>
