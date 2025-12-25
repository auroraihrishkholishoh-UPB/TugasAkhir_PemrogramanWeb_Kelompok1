<?php
session_start();
include "config/koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

/* =========================
   TAMBAH PRODUK
========================= */
if (isset($_POST['add_product'])) {

    $nama = mysqli_real_escape_string($mysqli, $_POST['nama']);
    $harga = (int)$_POST['harga'];
    $deskripsi = mysqli_real_escape_string($mysqli, $_POST['deskripsi']);

    $gambar = $_FILES['gambar']['name'];
    $tmp = $_FILES['gambar']['tmp_name'];

    if ($gambar != '') {
        $ext = strtolower(pathinfo($gambar, PATHINFO_EXTENSION));
        $allowed = array('jpg','jpeg','png','webp');

        if (in_array($ext, $allowed)) {
            $namaFile = time().'_'.$gambar;
            move_uploaded_file($tmp, "img/".$namaFile);

            mysqli_query($mysqli,"
                INSERT INTO products (nama_produk,harga,gambar,deskripsi)
                VALUES ('$nama','$harga','$namaFile','$deskripsi')
            ");
        }
    }
}

/* =========================
   HAPUS PRODUK
========================= */
if (isset($_POST['delete_product'])) {
    $id = (int)$_POST['id_product'];

    $q = mysqli_query($mysqli,"SELECT gambar FROM products WHERE id_product=$id");
    if ($q && mysqli_num_rows($q) > 0) {
        $g = mysqli_fetch_assoc($q);
        if ($g['gambar'] != '') unlink("img/".$g['gambar']);
    }

    mysqli_query($mysqli,"DELETE FROM products WHERE id_product=$id");
}

/* =========================
   TAMBAH VARIANT
========================= */
if (isset($_POST['add_variant'])) {

    $id_product = (int)$_POST['id_product'];
    $rasa = mysqli_real_escape_string($mysqli, $_POST['rasa']);
    $ukuran = mysqli_real_escape_string($mysqli, $_POST['ukuran']);
    $tambahan = (int)$_POST['tambahan_harga'];

    mysqli_query($mysqli,"
        INSERT INTO product_variants (id_product,rasa,ukuran,tambahan_harga)
        VALUES ($id_product,'$rasa','$ukuran',$tambahan)
    ");
}

/* =========================
   UPDATE VARIANT
========================= */
if (isset($_POST['update_variant'])) {

    $id_variant = (int)$_POST['id_variant'];
    $rasa = mysqli_real_escape_string($mysqli, $_POST['rasa']);
    $ukuran = mysqli_real_escape_string($mysqli, $_POST['ukuran']);
    $tambahan = (int)$_POST['tambahan_harga'];

    mysqli_query($mysqli,"
        UPDATE product_variants
        SET rasa='$rasa', ukuran='$ukuran', tambahan_harga=$tambahan
        WHERE id_variant=$id_variant
    ");
}

/* =========================
   HAPUS VARIANT
========================= */
if (isset($_POST['delete_variant'])) {
    $id_variant = (int)$_POST['id_variant'];
    mysqli_query($mysqli,"DELETE FROM product_variants WHERE id_variant=$id_variant");
}

/* =========================
   DATA
========================= */
$products = mysqli_query($mysqli,"SELECT * FROM products ORDER BY id_product DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="style.css">
<style>
table { width:100%; border-collapse:collapse; }
th,td { padding:8px; border-bottom:1px solid #ddd; }
.btn { padding:5px 10px; border:none; cursor:pointer; }
.btn-danger { background:#e74c3c;color:#fff; }
.btn-primary { background:#3498db;color:#fff; }
.btn-success { background:#2ecc71;color:#fff; }
input,select,textarea { padding:6px; margin:3px 0; width:100%; }
.variant-box { background:#f9f9f9; padding:10px; margin-top:10px; }
</style>
</head>
<body>

<div class="wrapper">
<div class="main">

<header class="topbar">
    <span>Hi, <?= $_SESSION['nama']; ?></span>
    <form action="logout.php" method="post">
        <button class="logout-btn">Logout</button>
    </form>
</header>

<main class="content">
<hr>

<!-- ================= TAMBAH PRODUK ================= -->
<h3>Tambah Produk</h3>
<form method="post" enctype="multipart/form-data">
    <input name="nama" placeholder="Nama Produk" required>
    <input type="number" name="harga" placeholder="Harga Dasar" required>
    <textarea name="deskripsi" placeholder="Deskripsi"></textarea>
    <input type="file" name="gambar" required>
    <button name="add_product" class="btn btn-success">Simpan Produk</button>
</form>

<hr>

<!-- ================= DATA PRODUK ================= -->
<h3>Data Produk & Variant</h3>

<?php while($p = mysqli_fetch_assoc($products)) { ?>

<div class="variant-box">
    <h4><?= $p['nama_produk']; ?> (Rp <?= number_format($p['harga']); ?>)</h4>

    <form method="post" style="display:inline">
        <input type="hidden" name="id_product" value="<?= $p['id_product']; ?>">
        <button name="delete_product" class="btn btn-danger"
        onclick="return confirm('Hapus produk dan semua variant?')">
        Hapus Produk
        </button>
    </form>

    <p><?= $p['deskripsi']; ?></p>

    <!-- LIST VARIANT -->
    <table>
    <tr>
        <th>Rasa</th>
        <th>Ukuran</th>
        <th>Harga Tambahan</th>
        <th>Aksi</th>
    </tr>

    <?php
    $v = mysqli_query($mysqli,"
        SELECT * FROM product_variants
        WHERE id_product=".$p['id_product']
    );

    while($var = mysqli_fetch_assoc($v)) {
    ?>
    <tr>
        <form method="post">
        <td><input name="rasa" value="<?= $var['rasa']; ?>"></td>
        <td><input name="ukuran" value="<?= $var['ukuran']; ?>"></td>
        <td><input type="number" name="tambahan_harga" value="<?= $var['tambahan_harga']; ?>"></td>
        <td>
            <input type="hidden" name="id_variant" value="<?= $var['id_variant']; ?>">
            <button name="update_variant" class="btn btn-primary">Update</button>
            <button name="delete_variant" class="btn btn-danger"
            onclick="return confirm('Hapus variant?')">Hapus</button>
        </td>
        </form>
    </tr>
    <?php } ?>

    </table>

    <!-- TAMBAH VARIANT -->
    <h5>Tambah Variant</h5>
    <form method="post">
        <input type="hidden" name="id_product" value="<?= $p['id_product']; ?>">
        <input name="rasa" placeholder="Rasa" required>
        <input name="ukuran" placeholder="Ukuran" required>
        <input type="number" name="tambahan_harga" placeholder="Tambahan Harga" value="0">
        <button name="add_variant" class="btn btn-success">Tambah Variant</button>
    </form>
</div>

<hr>

<?php } ?>

</body>
</html>
