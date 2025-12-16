<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Start session only if not already active
}
$id = $_GET['id'] ?? null;
if ($id && isset($_SESSION['cart'][$id])) {
    unset($_SESSION['cart'][$id]);
}
header("Location: cart.php");
?>
