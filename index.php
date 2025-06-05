<?php
require_once "includes/db.php";
include "includes/header.php";

// Son 6 makaleyi, kategori adı ve toplam beğeni sayısı ile birlikte çekiyoruz
$son_makaleler = $pdo->query("
    SELECT 
        m.id, 
        m.baslik, 
        m.resim, 
        m.icerik, 
        m.eklenme_tarihi,  
        k.ad AS kategori_adi,
        (SELECT COUNT(*) FROM makale_begen b WHERE b.makale_id = m.id) AS toplam
    FROM makaleler m
    LEFT JOIN kategoriler k ON m.kategori_id = k.id
    ORDER BY m.eklenme_tarihi DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  .card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgb(0 0 0 / 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 20px rgb(0 0 0 / 0.15);
  }
  .card-img-top {
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    height: 200px;
    object-fit: cover;
  }
  .no-image-placeholder {
    height: 200px;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    background: #ddd;
    color: #777;
    font-weight: 600;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .card-title {
    font-size: 1.25rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 0.8rem;
    color: #2c3e50;
    min-height: 3em; /* Başlık yüksekliği tutarlı olsun */
  }
  .likes {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    color: #e74c3c;
    font-weight: 600;
    gap: 6px;
  }
  .likes svg {
    width: 18px;
    height: 18px;
    fill: currentColor;
  }
  a.text-decoration-none.text-dark:hover {
    color: #3498db;
    text-decoration: none;
  }
</style>

  <h2 class="text-center mb-4 fw-bold">📚 Son Makaleler</h2>
<style>
  .card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .card:hover {
    transform: scale(1.05);
    box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.3);
    z-index: 10;
  }
</style>

<div class="row g-4">
  <?php foreach ($son_makaleler as $m): ?>
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
                <br>📅 <?= date('d.m.Y', strtotime($m['eklenme_tarihi'])) ?>
              </small>
              <span class="text-danger fw-bold">❤️ <?= $m['toplam'] ?></span>
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

</div>

<?php include "includes/footer.php"; ?>
