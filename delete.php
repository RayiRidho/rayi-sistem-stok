<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$conn->query("DELETE FROM stok_barang WHERE id = $id");
header("Location: dashboard.php");
exit;
?>