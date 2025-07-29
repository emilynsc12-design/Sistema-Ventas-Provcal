<?php
/***** 1. Validar parámetro *****/
$id = $_GET['id'] ?? null;
if (!$id || !ctype_digit($id)) {
    echo 'Producto no especificado';
    exit;
}

include_once "funciones.php";

/***** 2. Intentar eliminar dentro de un try-catch *****/
try {
    $ok = eliminarProducto($id);   // ← nueva versión con transacción

    if ($ok) {
        // Éxito → volvemos a la lista con un mensaje opcional
        header("Location: productos.php?msg=borrado");
    } else {
        // La función devolvió false (p.ej. FK rota)
        header("Location: productos.php?err=no-borrado");
    }
    exit;

} catch (PDOException $e) {
    // Error inesperado: mostramos mensaje simple o lo registramos
    error_log("Error al eliminar producto $id: " . $e->getMessage());
    echo "No se pudo eliminar el producto (ver log).";
    // Si prefieres redirigir:
    // header("Location: productos.php?err=excepcion");
    exit;
}
?>