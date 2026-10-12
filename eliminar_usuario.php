<?php
session_start();
if (empty($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];
if (!$id) {
    echo 'No se ha seleccionado el usuario';
    exit;
}
include_once "funciones.php";

$resultado = eliminarUsuario($id);
if(!$resultado){
    echo "Error al eliminar";
    return;
}

header("Location: usuarios.php");
?>