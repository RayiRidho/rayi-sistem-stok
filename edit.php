<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM stok_barang WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama_barang'];
    $ukuran = $_POST['ukuran'];
    $warna = $_POST['warna'];
    $harga = str_replace(',', '.', $_POST['harga']);
    $jumlah = $_POST['jumlah'];
    $kategori = $_POST['kategori'];

    // Cek apakah ada file gambar baru yang diupload
    if (!empty($_FILES['gambar']['name'])) {
        $gambarBaru = basename($_FILES['gambar']['name']);
        $targetDir = "uploads/";
        $targetFile = $targetDir . $gambarBaru;
        move_uploaded_file($_FILES['gambar']['tmp_name'], $targetFile);
    } else {
        $gambarBaru = $row['gambar']; // gunakan gambar lama jika tidak ada upload baru
    }

    // Update data termasuk kategori
    $stmt = $conn->prepare("UPDATE stok_barang SET nama_barang=?, ukuran=?, warna=?, harga=?, jumlah=?, gambar=?, kategori=? WHERE id=?");
    $stmt->bind_param("sssdsssi", $nama, $ukuran, $warna, $harga, $jumlah, $gambarBaru, $kategori, $id);
    $stmt->execute();
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Barang</h2>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label>Nama Barang</label>
            <input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($row['nama_barang']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Ukuran</label>
            <input type="text" name="ukuran" class="form-control" value="<?= htmlspecialchars($row['ukuran']) ?>">
        </div>
        <div class="mb-3">
            <label>Warna</label>
            <input type="text" name="warna" class="form-control" value="<?= htmlspecialchars($row['warna']) ?>">
        </div>
        <div class="mb-3">
            <label>Harga</label>
            <input type="text" name="harga" class="form-control" value="<?= htmlspecialchars($row['harga']) ?>">
        </div>
        <div class="mb-3">
            <label>Jumlah</label>
            <input type="number" name="jumlah" class="form-control" value="<?= htmlspecialchars($row['jumlah']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Kategori</label>
            <select name="kategori" class="form-control" required>
                <option value="">-- Pilih Kategori --</option>
                <option value="Shirt" <?= $row['kategori'] == 'Shirt' ? 'selected' : '' ?>>Shirt</option>
                <option value="Jacket" <?= $row['kategori'] == 'Jacket' ? 'selected' : '' ?>>Jacket</option>
                <option value="Hoodie" <?= $row['kategori'] == 'Hoodie' ? 'selected' : '' ?>>Hoodie</option>
                <option value="Pants" <?= $row['kategori'] == 'Pants' ? 'selected' : '' ?>>Pants</option>
                <option value="Shoes" <?= $row['kategori'] == 'Shoes' ? 'selected' : '' ?>>Shoes</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Gambar Produk (opsional)</label>
            <input type="file" name="gambar" class="form-control">
            <?php if (!empty($row['gambar'])): ?>
                <div class="mt-2">
                    <p>Gambar saat ini:</p>
                    <img src="uploads/<?= htmlspecialchars($row['gambar']) ?>" alt="Gambar Produk" style="width: 100px;">
                </div>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="dashboard.php" class="btn btn-secondary">Batal</a>
    </form>
</div>
</body>
</html>
