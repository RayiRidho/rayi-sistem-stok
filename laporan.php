<?php
session_start();
include 'config.php'; // Pastikan file config.php sudah ada dan berisi koneksi database

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']); // Hapus pesan setelah ditampilkan
unset($_SESSION['error_message']);   // Hapus pesan setelah ditampilkan

// Inisialisasi variabel filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Query untuk Laporan Barang Masuk
$queryMasuk = "SELECT bm.id, bm.jumlah, bm.tanggal_masuk,
                      sb.nama_barang, sb.kategori, sb.ukuran, sb.warna, sb.harga
               FROM barang_masuk bm
               JOIN stok_barang sb ON bm.barang_id = sb.id
               WHERE 1";

$paramsMasuk = [];
$typesMasuk = '';

if (!empty($start_date)) {
    $queryMasuk .= " AND bm.tanggal_masuk >= ?";
    $paramsMasuk[] = $start_date;
    $typesMasuk .= 's';
}
if (!empty($end_date)) {
    $queryMasuk .= " AND bm.tanggal_masuk <= ?";
    $paramsMasuk[] = $end_date;
    $typesMasuk .= 's';
}

$queryMasuk .= " ORDER BY bm.tanggal_masuk DESC, bm.id DESC";

if (!empty($paramsMasuk)) {
    $stmtMasuk = $conn->prepare($queryMasuk);
    if ($stmtMasuk) {
        $stmtMasuk->bind_param($typesMasuk, ...$paramsMasuk);
        $stmtMasuk->execute();
        $resultMasuk = $stmtMasuk->get_result();
    } else {
        $error_message .= "Error preparing statement for Barang Masuk: " . $conn->error;
        $resultMasuk = false;
    }
} else {
    $resultMasuk = $conn->query($queryMasuk);
}

// Query untuk Laporan Barang Keluar
$queryKeluar = "SELECT bk.id, bk.jumlah, bk.tanggal_keluar,
                       sb.nama_barang, sb.kategori, sb.ukuran, sb.warna, sb.harga
                FROM barang_keluar bk
                JOIN stok_barang sb ON bk.barang_id = sb.id
                WHERE 1";

$paramsKeluar = [];
$typesKeluar = '';

if (!empty($start_date)) {
    $queryKeluar .= " AND bk.tanggal_keluar >= ?";
    $paramsKeluar[] = $start_date;
    $typesKeluar .= 's';
}
if (!empty($end_date)) {
    $queryKeluar .= " AND bk.tanggal_keluar <= ?";
    $paramsKeluar[] = $end_date;
    $typesKeluar .= 's';
}

$queryKeluar .= " ORDER BY bk.tanggal_keluar DESC, bk.id DESC";

if (!empty($paramsKeluar)) {
    $stmtKeluar = $conn->prepare($queryKeluar);
    if ($stmtKeluar) {
        $stmtKeluar->bind_param($typesKeluar, ...$paramsKeluar);
        $stmtKeluar->execute();
        $resultKeluar = $stmtKeluar->get_result();
    } else {
        $error_message .= "Error preparing statement for Barang Keluar: " . $conn->error;
        $resultKeluar = false;
    }
} else {
    $resultKeluar = $conn->query($queryKeluar);
}

$totalMasuk = 0;
$totalKeluar = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Stok Toko Baju</title>
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
    <h3 class="mb-4 text-dark"><i class="fas fa-chart-line me-2"></i> Laporan Barang Masuk & Keluar</h3>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4 p-3">
        <h5 class="form-section-title"><i class="fas fa-filter"></i> Filter Laporan Berdasarkan Tanggal</h5>
        <form class="row g-3 align-items-end" method="get">
            <div class="col-md-5 col-lg-4">
                <label for="start_date" class="form-label">Dari Tanggal</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div class="col-md-5 col-lg-4">
                <label for="end_date" class="form-label">Sampai Tanggal</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i> Tampilkan</button>
                <a href="laporan.php" class="btn btn-secondary"><i class="fas fa-sync-alt me-1"></i> Reset Filter</a>
            </div>
        </form>
    </div>

    <div class="card mb-5 p-4">
        <h5 class="form-section-title"><i class="fas fa-arrow-alt-circle-down"></i> Laporan Barang Masuk</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Tanggal Masuk</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Ukuran</th>
                        <th>Warna</th>
                        <th>Harga Satuan</th>
                        <th>Jumlah Masuk</th>
                        <th>Total Harga Masuk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultMasuk && $resultMasuk->num_rows > 0): ?>
                        <?php while($row = $resultMasuk->fetch_assoc()):
                            $subtotalMasuk = $row['harga'] * $row['jumlah'];
                            $totalMasuk += $subtotalMasuk;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tanggal_masuk']) ?></td>
                            <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                            <td><?= htmlspecialchars($row['kategori']) ?></td>
                            <td><?= htmlspecialchars($row['ukuran']) ?></td>
                            <td><?= htmlspecialchars($row['warna']) ?></td>
                            <td>Rp <?= number_format($row['harga'], 2, ',', '.') ?></td>
                            <td><?= $row['jumlah'] ?></td>
                            <td>Rp <?= number_format($subtotalMasuk, 2, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-4">
                            <i class="fas fa-info-circle me-2"></i> Tidak ada data barang masuk ditemukan untuk periode ini.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="7" class="text-end">Total Nilai Barang Masuk (Periode Filter)</th>
                        <th colspan="1">Rp <?= number_format($totalMasuk, 2, ',', '.') ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card p-4">
        <h5 class="form-section-title"><i class="fas fa-arrow-alt-circle-up"></i> Laporan Barang Keluar</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Tanggal Keluar</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Ukuran</th>
                        <th>Warna</th>
                        <th>Harga Satuan</th>
                        <th>Jumlah Keluar</th>
                        <th>Total Harga Keluar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultKeluar && $resultKeluar->num_rows > 0): ?>
                        <?php while($row = $resultKeluar->fetch_assoc()):
                            $subtotalKeluar = $row['harga'] * $row['jumlah'];
                            $totalKeluar += $subtotalKeluar;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tanggal_keluar']) ?></td>
                            <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                            <td><?= htmlspecialchars($row['kategori']) ?></td>
                            <td><?= htmlspecialchars($row['ukuran']) ?></td>
                            <td><?= htmlspecialchars($row['warna']) ?></td>
                            <td>Rp <?= number_format($row['harga'], 2, ',', '.') ?></td>
                            <td><?= $row['jumlah'] ?></td>
                            <td>Rp <?= number_format($subtotalKeluar, 2, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-4">
                            <i class="fas fa-info-circle me-2"></i> Tidak ada data barang keluar ditemukan untuk periode ini.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="7" class="text-end">Total Nilai Barang Keluar (Periode Filter)</th>
                        <th colspan="1">Rp <?= number_format($totalKeluar, 2, ',', '.') ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
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