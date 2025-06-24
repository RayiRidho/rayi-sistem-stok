<?php
session_start();
include 'config.php'; // Pastikan file config.php sudah ada dan berisi koneksi database

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$keyword = isset($_GET['search']) ? $_GET['search'] : '';
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$sort_stok = isset($_GET['sort_stok']) ? $_GET['sort_stok'] : 'desc';

$query = "SELECT * FROM stok_barang WHERE 1";
$params = [];
$types = "";

if (!empty($keyword)) {
    $query .= " AND nama_barang LIKE ?";
    $params[] = "%{$keyword}%";
    $types .= "s";
}

if (!empty($kategori)) {
    $query .= " AND kategori = ?";
    $params[] = $kategori;
    $types .= "s";
}

$query .= " ORDER BY jumlah " . ($sort_stok === "asc" ? "ASC" : "DESC");

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

// Ambil daftar kategori unik dari database (opsional, jika Anda ingin dinamis)
// $kategori_list_query = $conn->query("SELECT DISTINCT kategori FROM stok_barang ORDER BY kategori ASC");
// $kategori_list = [];
// while($row_kat = $kategori_list_query->fetch_assoc()) {
//     $kategori_list[] = $row_kat['kategori'];
// }
// Atau pakai list statis yang sudah ada (sesuai contoh Anda):
$kategori_list = ['Shirt', 'Jacket', 'Hoodie', 'Pants', 'Shoes'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Stok Toko Baju</title>
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
    <h3 class="mb-4 text-dark"><i class="fas fa-box me-2"></i> Daftar Stok Barang</h3>

    <div class="card mb-4 p-3">
        <form class="row g-3 align-items-end" method="get">
            <div class="col-md-4 col-lg-3">
                <label for="search_input" class="form-label visually-hidden">Cari nama barang</label>
                <input type="text" name="search" id="search_input" class="form-control" placeholder="Cari nama barang..." value="<?= htmlspecialchars($keyword) ?>">
            </div>
            <div class="col-md-4 col-lg-3">
                <label for="kategori_select" class="form-label visually-hidden">Pilih Kategori</label>
                <select name="kategori" id="kategori_select" class="form-select">
                    <option value="">-- Semua Kategori --</option>
                    <?php
                    foreach ($kategori_list as $kat) {
                        $selected = ($kategori == $kat) ? 'selected' : '';
                        echo "<option value=\"$kat\" $selected>" . htmlspecialchars($kat) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <label for="sort_stok_select" class="form-label visually-hidden">Urutkan Stok</label>
                <select name="sort_stok" id="sort_stok_select" class="form-select">
                    <option value="desc" <?= ($sort_stok == 'desc') ? 'selected' : '' ?>>Stok Terbanyak</option>
                    <option value="asc" <?= ($sort_stok == 'asc') ? 'selected' : '' ?>>Stok Tersedikit</option>
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i> Cari</button>
                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-sync-alt me-1"></i> Reset</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Gambar</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Ukuran</th>
                    <th>Warna</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="<?= $row['jumlah'] < 5 ? 'low-stock' : '' ?>">
                            <td>
                                <?php if (!empty($row['gambar'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($row['gambar']) ?>" alt="Gambar Produk">
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-image" style="font-size: 30px; color: #ced4da;"></i></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                            <td><?= htmlspecialchars($row['kategori']) ?></td>
                            <td><?= htmlspecialchars($row['ukuran']) ?></td>
                            <td><?= htmlspecialchars($row['warna']) ?></td>
                            <td>Rp <?= number_format($row['harga'], 2, ',', '.') ?></td>
                            <td class="<?= $row['jumlah'] < 5 ? 'text-danger fw-bold' : '' ?>"><?= $row['jumlah'] ?></td>
                            <td class="text-center">
                                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?')" class="btn btn-danger btn-sm" title="Hapus"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-info-circle me-2"></i> Tidak ada data barang ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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