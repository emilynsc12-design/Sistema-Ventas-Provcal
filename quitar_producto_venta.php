<?php
session_start();
if (empty($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];
$productos = $_SESSION['lista'];

foreach ($productos as $key => $producto) {
	if($producto->id == $id){
		unset($productos[$key]);
	}
}

$_SESSION['lista'] = $productos;
header("location: vender.php");
?>