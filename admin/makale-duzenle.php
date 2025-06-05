<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../includes/db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: makaleler.php");
    exit;
}

$makale_id = (int)$_GET['id'];
$hata = "";
$mesaj = "";

// Makale verisini çek
$stmt = $pdo->prepare("SELECT * FROM makaleler WHERE id = ?");
$stmt->execute([$makale_id]);
$makale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$makale) {
    header("Location: makaleler.php");
    exit;
}

// Kategorileri çek
$stmt = $pdo->query("SELECT * FROM kategoriler ORDER BY ad ASC");
$kategoriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = trim($_POST['baslik']);
    $icerik = trim($_POST['icerik']);
    $kategori_id = (int)$_POST['kategori_id'];

    if ($baslik === '' || $icerik === '') {
        $hata = "Başlık ve içerik boş bırakılamaz.";
    } else {
        $stmt = $pdo->prepare("UPDATE makaleler SET baslik = ?, icerik = ?, kategori_id = ? WHERE id = ?");
        if ($stmt->execute([$baslik, $icerik, $kategori_id, $makale_id])) {
            $mesaj = "Makale başarıyla güncellendi.";
            // Verileri yenile
            $stmt = $pdo->prepare("SELECT * FROM makaleler WHERE id = ?");
            $stmt->execute([$makale_id]);
            $makale = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $hata = "Makale güncellenirken hata oluştu.";
        }
    }
}

include "../includes/header.php";
?>

<div class="container mt-4">
    <h1>Makale Düzenle</h1>

    <?php if ($hata): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($hata) ?></div>
    <?php endif; ?>
    <?php if ($mesaj): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mesaj) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="baslik" class="form-label">Başlık</label>
            <input type="text" name="baslik" id="baslik" class="form-control" value="<?= htmlspecialchars($makale['baslik']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="kategori_id" class="form-label">Kategori</label>
            <select name="kategori_id" id="kategori_id" class="form-select" required>
                <?php foreach ($kategoriler as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $k['id'] == $makale['kategori_id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['ad']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="icerik" class="form-label">İçerik</label>
            <textarea name="icerik" id="icerik" class="form-control" rows="10" required><?= htmlspecialchars($makale['icerik']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Güncelle</button>
        <a href="makaleler.php" class="btn btn-secondary">Geri Dön</a>
    </form>
</div>

<?php include "../includes/footer.php"; ?>
