<?php
$host = 'localhost';
$user = 'root'; // default username XAMPP
$pass = '';     // default password kosong di XAMPP
$db   = 'toko_baju'; // sesuaikan dengan nama database Anda

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>