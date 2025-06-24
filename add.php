<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

$barang_result = $conn->query("SELECT id, nama_barang, ukuran, warna FROM stok_barang ORDER BY nama_barang ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mode = $_POST['mode'];

    if ($mode == 'baru') {
        $nama = trim($_POST['nama_barang']);
        $ukuran = trim($_POST['ukuran']);
        $warna = trim($_POST['warna']);
        $harga = floatval(str_replace(',', '.', $_POST['harga']));
        $jumlah = intval($_POST['jumlah']);
        $kategori = trim($_POST['kategori']);

        if (empty($nama) || empty($ukuran) || empty($warna) || $harga <= 0 || $jumlah <= 0 || empty($kategori)) {
            $_SESSION['error_message'] = "Semua field wajib diisi dan nilai harus valid untuk barang baru.";
            header("Location: add.php");
            exit;
        }

        $nama_file = null;
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $nama_file = basename($_FILES['gambar']['name']);
            $target_path = $upload_dir . $nama_file;
            $file_info = pathinfo($nama_file);
            $file_extension = $file_info['extension'];
            $file_name_only = $file_info['filename'];
            $counter = 1;
            while (file_exists($target_path)) {
                $nama_file = $file_name_only . '_' . $counter . '.' . $file_extension;
                $target_path = $upload_dir . $nama_file;
                $counter++;
            }

            if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $target_path)) {
                $_SESSION['error_message'] = "Gagal mengunggah gambar.";
                header("Location: add.php");
                exit;
            }
        }

        // Mulai transaksi database untuk kedua INSERT
        $conn->begin_transaction();

        try {
            // Simpan ke stok_barang
            $stmt_stok = $conn->prepare("INSERT INTO stok_barang (nama_barang, ukuran, warna, harga, jumlah, gambar, kategori) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_stok->bind_param("sssdiss", $nama, $ukuran, $warna, $harga, $jumlah, $nama_file, $kategori);

            if (!$stmt_stok->execute()) {
                throw new Exception("Gagal menambahkan barang baru ke stok: " . $stmt_stok->error);
            }
            $barang_id_baru = $conn->insert_id; // Ambil ID barang yang baru saja di-INSERT
            $stmt_stok->close();

            // Catat sebagai transaksi barang masuk untuk stok awal
            $stmt_masuk = $conn->prepare("INSERT INTO barang_masuk (barang_id, jumlah, tanggal_masuk) VALUES (?, ?, NOW())");
            $stmt_masuk->bind_param("ii", $barang_id_baru, $jumlah); // Gunakan $barang_id_baru dan $jumlah awal
            if (!$stmt_masuk->execute()) {
                throw new Exception("Gagal mencatat stok awal sebagai barang masuk: " . $stmt_masuk->error);
            }
            $stmt_masuk->close();

            $conn->commit(); // Commit transaksi jika kedua-duanya berhasil
            $_SESSION['success_message'] = "Barang baru berhasil ditambahkan dan stok awal dicatat sebagai barang masuk.";
            header("Location: dashboard.php"); // Redirect ke dashboard setelah tambah barang baru
            exit;

        } catch (Exception $e) {
            $conn->rollback(); // Rollback jika ada kesalahan
            $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
            header("Location: add.php");
            exit; // Penting: Exit setelah redirect
        }

    } elseif ($mode == 'lama') {
        // ... (Kode untuk Tambah Stok Barang Lama - tidak ada perubahan di sini)
        $jumlah_lama = intval($_POST['jumlah']);
        $barang_id_lama = intval($_POST['barang_id']);

        if ($barang_id_lama <= 0 || $jumlah_lama <= 0) {
            $_SESSION['error_message'] = "Pilih barang dan masukkan jumlah yang valid untuk penambahan stok.";
            header("Location: add.php");
            exit;
        }

        $stmt_get_name = $conn->prepare("SELECT nama_barang FROM stok_barang WHERE id = ?");
        $stmt_get_name->bind_param("i", $barang_id_lama);
        $stmt_get_name->execute();
        $barang_name_res = $stmt_get_name->get_result();
        $barang_name_row = $barang_name_res->fetch_assoc();
        $barang_name = $barang_name_row ? $barang_name_row['nama_barang'] : "Barang Tidak Dikenal";
        $stmt_get_name->close();


        $conn->begin_transaction();

        try {
            $stmt_update_stok = $conn->prepare("UPDATE stok_barang SET jumlah = jumlah + ? WHERE id = ?");
            $stmt_update_stok->bind_param("ii", $jumlah_lama, $barang_id_lama);

            if (!$stmt_update_stok->execute()) {
                throw new Exception("Gagal memperbarui stok barang: " . $stmt_update_stok->error);
            }
            $stmt_update_stok->close();

            $stmt_insert_masuk = $conn->prepare("INSERT INTO barang_masuk (barang_id, jumlah, tanggal_masuk) VALUES (?, ?, NOW())");
            $stmt_insert_masuk->bind_param("ii", $barang_id_lama, $jumlah_lama);

            if (!$stmt_insert_masuk->execute()) {
                throw new Exception("Gagal mencatat transaksi barang masuk: " . $stmt_insert_masuk->error);
            }
            $stmt_insert_masuk->close();

            $conn->commit();
            $_SESSION['success_message'] = "Stok barang " . htmlspecialchars($barang_name) . " berhasil ditambahkan sebanyak " . $jumlah_lama . ".";
            header("Location: dashboard.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
            header("Location: add.php");
            exit;
        }
    }
    // Jika tidak ada mode yang dikenali, redirect kembali ke add.php
    header("Location: add.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Barang / Stok - Stok Toko Baju</title>
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
    <h3 class="mb-4 text-dark"><i class="fas fa-plus-circle me-2"></i> Tambah Barang & Stok</h3>

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

    <div class="card mb-4 p-4">
        <h5 class="form-section-title"><i class="fas fa-box-open"></i> Tambah Barang Baru ke Inventaris</h5>
        <form method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="mode" value="baru">
            <div class="mb-3">
                <label for="nama_barang" class="form-label">Nama Barang</label>
                <input type="text" name="nama_barang" id="nama_barang" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="ukuran" class="form-label">Ukuran</label>
                <input type="text" name="ukuran" id="ukuran" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="warna" class="form-label">Warna</label>
                <input type="text" name="warna" id="warna" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="harga" class="form-label">Harga (Rp)</label>
                <input type="number" name="harga" id="harga" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="mb-3">
                <label for="jumlah_baru" class="form-label">Jumlah Awal Stok</label>
                <input type="number" name="jumlah" id="jumlah_baru" class="form-control" min="1" required>
            </div>
            <div class="mb-3">
                <label for="kategori" class="form-label">Kategori</label>
                <select name="kategori" id="kategori" class="form-select" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="T-shirt">T-shirt</option>
                    <option value="Jacket">Jacket</option>
                    <option value="Pants">Pants</option>
                    <option value="Shoes">Shoes</option>
                    <option value="Aksesoris">Aksesoris</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="gambar" class="form-label">Gambar Produk (opsional)</label>
                <input type="file" name="gambar" id="gambar" class="form-control" accept="image/*">
                <div class="form-text">Maksimal ukuran file 2MB, format JPG, PNG.</div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Simpan Barang Baru</button>
        </form>
    </div>

    <div class="card p-4">
        <h5 class="form-section-title"><i class="fas fa-truck-loading"></i> Tambah Stok Barang Lama</h5>
        <form method="post" action="">
            <input type="hidden" name="mode" value="lama">
            <div class="mb-3">
                <label for="barang_id_lama" class="form-label">Pilih Barang</label>
                <select name="barang_id" id="barang_id_lama" class="form-select" required>
                    <option value="">-- Pilih Barang --</option>
                    <?php
                    $barang_result->data_seek(0);
                    while ($barang = $barang_result->fetch_assoc()): ?>
                        <option value="<?= $barang['id'] ?>">
                            <?= htmlspecialchars($barang['nama_barang']) ?> | <?= htmlspecialchars($barang['ukuran']) ?> | <?= htmlspecialchars($barang['warna']) ?>
                        </option>
                    <?php endwhile; ?>
                    <?php if ($barang_result->num_rows == 0): ?>
                        <option value="" disabled>Belum ada barang di inventaris.</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="jumlah_lama" class="form-label">Jumlah Masuk</label>
                <input type="number" name="jumlah" id="jumlah_lama" class="form-control" min="1" required>
            </div>
            <button type="submit" class="btn btn-success"><i class="fas fa-cart-plus me-2"></i> Tambah Stok</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }

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