<?php
require_once "includes/db.php";
require_once "includes/header.php";

// Kategori parametresi kontrolü
$kategori_adi = $_GET['kategori'] ?? null;

if (!$kategori_adi) {
    echo "<div class='container mt-5'><div class='alert alert-danger text-center'>Kategori bulunamadı.</div></div>";
    require_once "includes/footer.php";
    exit;
}

// Kategori ID'sini bul
$stmt = $pdo->prepare("SELECT id FROM kategoriler WHERE ad = ?");
$stmt->execute([$kategori_adi]);
$kategori = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kategori) {
    echo "<div class='container mt-5'><div class='alert alert-warning text-center'>Kategori bulunamadı: " . htmlspecialchars($kategori_adi) . "</div></div>";
    require_once "includes/footer.php";
    exit;
}

// Kategoriye ait makaleleri getir (kategori adı ile birlikte)
$stmt = $pdo->prepare("
    SELECT m.*, k.ad AS kategori_adi 
    FROM makaleler m 
    INNER JOIN kategoriler k ON m.kategori_id = k.id
    WHERE m.kategori_id = ? 
    ORDER BY m.olusturma_tarihi DESC
");
$stmt->execute([$kategori['id']]);
$makaleler = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h2 class="text-center mb-4"><?= htmlspecialchars(ucwords(str_replace("-", " ", $kategori_adi))) ?> Makaleleri</h2>

    <?php if (empty($makaleler)): ?>
        <p class="text-center text-muted">Bu kategoriye ait henüz makale yok.</p>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($makaleler as $m): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100 border-0 rounded-4">
                        <?php if ($m['resim'] && file_exists("uploads/" . $m['resim'])): ?>
                            <img src="uploads/<?= htmlspecialchars($m['resim']) ?>" 
                                 class="card-img-top rounded-top-4" 
                                 alt="<?= htmlspecialchars($m['baslik']) ?>" 
                                 style="height: 200px; width: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light d-flex justify-content-center align-items-center" 
                                 style="height: 200px; width: 100%; border-top-left-radius: 1rem; border-top-right-radius: 1rem;">
                                <span class="text-muted">📷 Resim Yok</span>
                            </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary text-center"><?= htmlspecialchars($m['baslik']) ?></h5>

                           <?php
  $temizIcerik = strip_tags(html_entity_decode($m['icerik']));
  $ozet = mb_substr($temizIcerik, 0, 100);
  $uzunluk = mb_strlen($temizIcerik);
?>
<p class="card-text text-muted small">
  <?= $ozet ?><?= $uzunluk > 100 ? '...' : '' ?>
</p>


                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-secondary">
                                        🏷 <?= htmlspecialchars($m['kategori_adi']) ?>  
                                        <br>📅 <?= date('d.m.Y', strtotime($m['olusturma_tarihi'])) ?>
                                    </small>
                                    <span class="text-danger fw-bold">❤️ <?= (int)$m['begeni_sayisi'] ?></span>
                                </div>
                                <a href="makale.php?id=<?= $m['id'] ?>" class="btn btn-outline-primary w-100 rounded-pill">
                                    Oku
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once "includes/footer.php"; ?>
