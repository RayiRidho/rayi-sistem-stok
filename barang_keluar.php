<?php
date_default_timezone_set('Asia/Jakarta'); // Penting: Pastikan zona waktu PHP sesuai
session_start();
include 'config.php'; // Pastikan file config.php sudah ada dan berisi koneksi database

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barang_id = intval($_POST['barang_id']);
    $jumlah_keluar = intval($_POST['jumlah']); // Menggunakan 'jumlah' sesuai form lama Anda
    $keterangan = isset($_POST['keterangan']) ? trim($_POST['keterangan']) : '';
    // Hapus baris $tanggal_keluar dari sini karena kita akan menggunakan NOW() di SQL

    // Ambil data barang dan stok saat ini dari stok_barang
    $stmt_check = $conn->prepare("SELECT nama_barang, jumlah, harga FROM stok_barang WHERE id = ?");
    $stmt_check->bind_param("i", $barang_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $barang_data = $result_check->fetch_assoc();
    $stmt_check->close();

    if (!$barang_data) {
        $error_message = "Barang tidak ditemukan.";
    } elseif ($jumlah_keluar <= 0) {
        $error_message = "Jumlah keluar harus lebih dari 0.";
    } elseif ($jumlah_keluar > $barang_data['jumlah']) {
        $error_message = "Stok " . htmlspecialchars($barang_data['nama_barang']) . " tidak mencukupi. Stok tersedia: " . $barang_data['jumlah'];
    } else {
        // Mulai transaksi database untuk memastikan konsistensi data
        $conn->begin_transaction();

        try {
            // Kurangi stok barang
            $stmt_update_stok = $conn->prepare("UPDATE stok_barang SET jumlah = jumlah - ? WHERE id = ?");
            $stmt_update_stok->bind_param("ii", $jumlah_keluar, $barang_id);

            if (!$stmt_update_stok->execute()) {
                throw new Exception("Gagal mengurangi stok barang: " . $stmt_update_stok->error);
            }
            $stmt_update_stok->close();

            // Catat transaksi ke tabel barang_keluar
            // Menggunakan NOW() untuk tanggal_keluar
            $stmt_insert_transaksi = $conn->prepare("INSERT INTO barang_keluar (barang_id, jumlah, tanggal_keluar, keterangan) VALUES (?, ?, NOW(), ?)");
            $stmt_insert_transaksi->bind_param("iis", $barang_id, $jumlah_keluar, $keterangan);

            if (!$stmt_insert_transaksi->execute()) {
                throw new Exception("Gagal mencatat transaksi barang keluar: " . $stmt_insert_transaksi->error);
            }
            $stmt_insert_transaksi->close();

            $conn->commit(); // Commit transaksi jika semua berhasil
            $success_message = "Barang keluar berhasil dicatat: " . htmlspecialchars($barang_data['nama_barang']) . " sejumlah " . $jumlah_keluar . ".";
            $_SESSION['success_message'] = $success_message;
            header("Location: barang_keluar.php"); // Redirect untuk mencegah double submit
            exit;

        } catch (Exception $e) {
            $conn->rollback(); // Rollback jika ada kesalahan
            $error_message = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Ambil daftar barang dari database untuk dropdown (hanya barang yang punya stok > 0)
$barang_list = $conn->query("SELECT id, nama_barang, jumlah, ukuran, warna FROM stok_barang WHERE jumlah > 0 ORDER BY nama_barang");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Catat Barang Keluar - Stok Toko Baju</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style2.css" rel="stylesheet">
</head>
<body>

<div class="menu-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">APS Store</div>
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="add.php"><i class="fas fa-plus-circle"></i> Tambah Barang</a>
    <a href="barang_keluar.php"><i class="fas fa-arrow-right"></i> Barang Keluar</a>
    <a href="laporan.php"><i class="fas fa-file-alt"></i> Laporan</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-content">
    <h3 class="mb-4 text-dark"><i class="fas fa-sign-out-alt me-2"></i> Catat Barang Keluar</h3>

    <?php
    // Tampilkan pesan sukses dari session setelah redirect
    if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['success_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php unset($_SESSION['success_message']); endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card p-4">
        <h5 class="form-section-title"><i class="fas fa-boxes"></i> Form Pencatatan Barang Keluar</h5>
        <form method="post" action=""> <div class="mb-3">
                <label for="barang_id" class="form-label">Pilih Barang</label>
                <select name="barang_id" id="barang_id" class="form-select" required>
                    <option value="">-- Pilih Barang --</option>
                    <?php while ($barang = $barang_list->fetch_assoc()): ?>
                        <option value="<?= $barang['id'] ?>">
                            <?= htmlspecialchars($barang['nama_barang']) ?> | Ukuran: <?= htmlspecialchars($barang['ukuran']) ?> | Warna: <?= htmlspecialchars($barang['warna']) ?> (Stok: <?= $barang['jumlah'] ?>)
                        </option>
                    <?php endwhile; ?>
                    <?php if ($barang_list->num_rows == 0): ?>
                        <option value="" disabled>Tidak ada barang tersedia (stok 0).</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="jumlah" class="form-label">Jumlah Keluar</label>
                <input type="number" name="jumlah" id="jumlah" class="form-control" min="1" required>
            </div>

            <div class="mb-4">
                <label for="keterangan" class="form-label">Keterangan (opsional)</label>
                <input type="text" name="keterangan" id="keterangan" class="form-control" placeholder="Misal: Penjualan, Retur, dll.">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i> Catat Keluar</button>
                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }

    // Optional: Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.querySelector('.menu-toggle');
        if (window.innerWidth <= 768 && sidebar.classList.contains('active') && !sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    });
</script>

</body>
</html>