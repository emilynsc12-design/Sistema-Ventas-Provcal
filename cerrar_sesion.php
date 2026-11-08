<?php
if (session_status() === PHP_SESSION_NONE) session_start();
session_destroy();
session_regenerate_id();
session_unset();
header("location:login.php");
?>