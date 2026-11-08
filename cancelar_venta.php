<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$_SESSION['lista'] = [];
header("location: vender.php");
?>