<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../includes/db.php";

// Kategori filtresi varsa al
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : null;

// Veritabanı sorgusu
if ($kategori) {
    $stmt = $pdo->prepare("
        SELECT m.id, m.baslik, m.olusturma_tarihi, k.ad AS kategori_adi
        FROM makaleler m
        LEFT JOIN kategoriler k ON m.kategori_id = k.id
        WHERE k.ad = ?
        ORDER BY m.olusturma_tarihi DESC
    ");
    $stmt->execute([$kategori]);
} else {
    $stmt = $pdo->query("
        SELECT m.id, m.baslik, m.olusturma_tarihi, k.ad AS kategori_adi
        FROM makaleler m
        LEFT JOIN kategoriler k ON m.kategori_id = k.id
        ORDER BY m.olusturma_tarihi DESC
    ");
}
$makaleler = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<div class="container mt-4">
    <h1>
        <?= $kategori ? ucfirst($kategori) . " Makaleleri" : "Makale Yönetimi" ?>
    </h1>

    <a href="yeni-makale.php" class="btn btn-success mb-3">Yeni Makale Ekle</a>

    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Başlık</th>
                <th>Kategori</th>
                <th>Oluşturulma Tarihi</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($makaleler as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['id']) ?></td>
                <td><?= htmlspecialchars($m['baslik']) ?></td>
                <td><?= htmlspecialchars($m['kategori_adi'] ?? 'Kategori yok') ?></td>
                <td><?= htmlspecialchars($m['olusturma_tarihi']) ?></td>
                <td>
                    <a href="sil-makale.php?id=<?= $m['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu makaleyi silmek istediğine emin misin?');">Sil</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($makaleler) == 0): ?>
            <tr><td colspan="5" class="text-center">Hiç makale bulunamadı.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include "../includes/footer.php"; ?>
