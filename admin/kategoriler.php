<?php
require_once "../includes/db.php";
include "../includes/header.php";

// Kodların devamı...


$kategori = $_GET['kategori'] ?? '';

$stmt = $pdo->prepare("
    SELECT m.*, k.ad AS kategori_adi 
    FROM makaleler m 
    LEFT JOIN kategoriler k ON m.kategori_id = k.id 
    WHERE k.ad = ?
    ORDER BY m.olusturma_tarihi DESC
");
$stmt->execute([$kategori]);
$makaleler = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>"<?= htmlspecialchars(ucfirst($kategori)) ?>" Kategorisi</h2>

    <?php if (count($makaleler) > 0): ?>
        <div class="row">
            <?php foreach ($makaleler as $m): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <img src="uploads/<?= htmlspecialchars($m['resim']) ?>" class="card-img-top" alt="<?= htmlspecialchars($m['baslik']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($m['baslik']) ?></h5>
                        <p class="card-text"><?= mb_substr(strip_tags($m['icerik']), 0, 100) ?>...</p>
                        <a href="makale.php?id=<?= $m['id'] ?>" class="btn btn-primary">Devamını Oku</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Bu kategoriye ait henüz bir makale bulunmamaktadır.</p>
    <?php endif; ?>
</div>

<?php require_once "includes/footer.php"; ?>
