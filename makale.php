<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "includes/db.php";
include "includes/header.php";

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    echo "<div class='container mt-4'><h3>Geçersiz makale ID'si.</h3></div>";
    include "includes/footer.php";
    exit;
}

// Makaleyi getir (kategori, yazar, profil resmi ve diğerleri ile birlikte)
$stmt = $pdo->prepare("
    SELECT m.*, k.ad AS kategori_adi, u.isim AS yazar_adi, u.email AS yazar_email, u.profil_resmi
    FROM makaleler m
    LEFT JOIN kategoriler k ON m.kategori_id = k.id
    LEFT JOIN users u ON m.yazar_id = u.id
    WHERE m.id = ?
");
$stmt->execute([$id]);
$makale = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($makale)) {
    echo "<div class='container mt-4'><h3>Makale bulunamadı.</h3></div>";
    include "includes/footer.php";
    exit;
}

// Etiketleri al
$stmt = $pdo->prepare("SELECT e.ad FROM makale_etiketleri me JOIN etiketler e ON me.etiket_id = e.id WHERE me.makale_id = ?");
$stmt->execute([$id]);
$etiketler = $stmt->fetchAll(PDO::FETCH_COLUMN);

function yorumlari_getir($pdo, $makale_id, $parent_id = null) {
    $sql = "SELECT y.*, u.isim, u.name, u.profil_resmi, u.biography 
            FROM yorumlar y 
            JOIN users u ON y.kullanici_id = u.id 
            WHERE y.makale_id = ? AND y.parent_id " . ($parent_id === null ? "IS NULL" : "= ?") . " 
            ORDER BY y.tarih DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parent_id === null ? [$makale_id] : [$makale_id, $parent_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserName($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() ?: 'Bilinmiyor';
}

function yorum_html($yorumlar, $pdo, $level = 0, $user_id = null) {
    $html = '';
    foreach ($yorumlar as $yorum) {
        $alt_yorumlar = yorumlari_getir($pdo, $yorum['makale_id'], $yorum['id']);
        $isim = getUserName($pdo, $yorum['kullanici_id']);
        $ilkHarf = mb_strtoupper(mb_substr($isim, 0, 1, 'UTF-8'));

        if ($yorum['profil_resmi'] && file_exists("uploads/profil/" . $yorum['profil_resmi'])) {
            $profil = "<img src='uploads/profil/{$yorum['profil_resmi']}' class='rounded-circle me-2' width='40' height='40'>";
        } else {
            $profil = "<div class='rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2' style='width:40px;height:40px;font-weight:bold;font-size:20px;'>$ilkHarf</div>";
        }

        $marginLeft = $level > 0 ? 40 : 0;
        $okSimgesi = $level > 0 ? "<span style='margin-right:6px;color:#666;font-size:25px;'>&#x21B3;</span>" : "";

        $butonlar = "<div class='ms-5 mt-1'>";
        if ($user_id !== null) {
            $butonlar .= "<button class='btn btn-sm btn-link text-primary yanitla-btn' data-id='{$yorum['id']}'>Yanıtla</button>";
        }
        if ($user_id !== null && intval($user_id) === intval($yorum['kullanici_id'])) {
            $butonlar .= "<button class='btn btn-sm btn-link text-warning duzenle-btn' data-id='{$yorum['id']}'>Düzenle</button>
                          <button class='btn btn-sm btn-link text-danger sil-btn' data-id='{$yorum['id']}' data-makale-id='{$yorum['makale_id']}'>Sil</button>";
        }
        $butonlar .= "</div>";

        $tarih = date("d.m.Y", strtotime($yorum['tarih']));
        $yorum_metni = nl2br(htmlspecialchars($yorum['yorum_metni'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $biyografi = htmlspecialchars($yorum['biography'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

       $html .= "<div class='mb-3' style='margin-left: {$marginLeft}px;' role='article' aria-label='Yorum'>
    <div class='d-flex align-items-center mb-1'>
        {$okSimgesi}
        {$profil}
        <div>
            <strong>{$isim}</strong><br>
            <small class='text-muted d-block'>" . htmlspecialchars($biyografi ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</small>
        </div>
        <small class='text-muted ms-auto' style='font-size: 0.85rem;'>{$tarih}</small>
    </div>
    <div class='ms-5 mb-2' style='white-space: pre-wrap;'>{$yorum_metni}</div>
    {$butonlar}
    <div class='yanit-form ms-5 mt-2 d-none' id='yanit-form-{$yorum['id']}'></div>
    <div class='duzenle-form ms-5 mt-2 d-none' id='duzenle-form-{$yorum['id']}'></div>
</div>";

        $html .= yorum_html($alt_yorumlar, $pdo, $level + 1, $user_id);
    }
    return $html;
}

$user_id = $_SESSION['user_id'] ?? null;
$yorumlar = yorumlari_getir($pdo, $id);

$userLiked = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM makale_begen WHERE makale_id = ? AND kullanici_id = ?");
    $stmt->execute([$makale['id'], $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $userLiked = true;
    }
}

// Toplam beğeni sayısı
$stmt = $pdo->prepare("SELECT COUNT(*) as toplam FROM makale_begen WHERE makale_id = ?");
$stmt->execute([$makale['id']]);
$totalLikes = $stmt->fetch(PDO::FETCH_ASSOC)['toplam'];


$user_id = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : null;



$yazar_id = $makale['yazar_id'] ?? null;
$bio = '';

if ($yazar_id) {
    $stmt = $pdo->prepare("SELECT biography FROM users WHERE id = ?");
    $stmt->execute([$yazar_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $bio = $row['biography'];
    }
}
?>





<style>
@media (max-width: 992px) {
  #like-section-mobile {
    display: none !important;
  }
}
  .makale-ust {
    display: flex;
    max-width: 1200px;
    margin: 40px auto 20px;
    gap: 20px;
    align-items: flex-start;
    flex-wrap: wrap;
  }
  .makale-resim {
    flex: 1 1 400px;
    max-height: 400px;
    border-radius: 6px;
    overflow: hidden;
  }
 .makale-resim img {
  width: 100%;           /* Kutuyu tam doldur */
  height: auto;          /* Yüksekliği otomatik, oranı koru */
  object-fit: contain;   /* Resmin tamamı görünür, kırpma olmaz */
  image-rendering: crisp-edges; /* Keskin görüntü için bazı tarayıcılarda yardımcı */
}
  .dik-cizgi {
    width: 2px;
    background-color: #ccc;
    height: 400px;
  }
  .makale-bilgiler {
    flex: 1 1 400px;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .yazar-profil img, .yazar-profil div {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 10px;
  }
  .yazar-profil div {
    background: #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    font-size: 14px;
  }
  .makale-bilgiler p {
    margin: 0;
    font-size: 14px;
    color: #555;
  }
  .etiketler {
    margin-top: 8px;
  }
  .etiket {
    display: inline-block;
    border-radius: 15px;
    padding: 4px 12px;
    font-size: 13px;
    margin-right: 6px;
    color: white;
    background-color: transparent; /* JS renk atayacak */
    transition: background-color 0.3s ease;
    cursor: default;
  }
  .etiket:hover {
    filter: brightness(85%);
  }
  .begen-kisim {
    margin-top: 20px;
  }
  .begen-btn {
    cursor: pointer;
    background: transparent;
    border: 1.5px solid #dc3545;
    color: #dc3545;
    padding: 8px 18px;
    border-radius: 4px;
    font-weight: 600;
    transition: background-color 0.3s ease, color 0.3s ease;
  }
  .begen-btn:hover {
    background-color: #dc3545;
    color: white;
  }
  .makale-alt {
    max-width: 1200px;
    margin: 0 auto 40px;
    padding: 0 15px;
  }
 .makale-baslik {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 20px;
    color: #222;
    text-align: center;
}

  .makale-icerik {
    font-size: 18px;
    line-height: 1.6;
    color: #333;
    white-space: pre-wrap;
  }

  @media (max-width: 992px) {
    .makale-ust {
      flex-direction: column;
      align-items: center;
    }
    .dik-cizgi {
      width: 80%;
      height: 2px;
      margin: 15px 0;
    }
    .makale-resim, .makale-bilgiler {
      flex: none;
      width: 100%;
      max-height: none;
    }
  }
</style>

<style>
  /* Masaüstü genel */
  .makale-ust {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
  }
  .makale-resim img {
    max-width: 100%;
    border-radius: 6px;
  }
  .makale-bilgiler {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 10px;
    text-align: left;
  }
  .yazar-profil {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 10px;
}
  .yazar-profil img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 50%;
  }
  /* Dik çizgi masaüstünde görünür */
  .dik-cizgi {
    width: 2px;
    background-color: #ccc;
    border-radius: 2px;
    display: block;
  }
  /* Beğeni ve etiketler kutusu masaüstü için: tarihin altında */
  #like-tags-desktop {
    margin-top: 10px;
    text-align: left;  /* Masaüstü solda */
  }
  #like-tags-desktop .d-flex {
    justify-content: flex-start; /* solda hizalama */
    gap: 8px;
    flex-wrap: wrap;
  }

  /* Mobilde gizli */
  #like-tags-desktop {
    display: block;
  }
  #like-tags-mobile {
    display: none;
  }

  /* Mobil görünüm ayarları */
  @media (max-width: 992px) {
    .makale-ust {
      flex-direction: column;
      align-items: center;
      text-align: center;
    }
    /* Mobilde dik çizgiyi gizle */
    .dik-cizgi {
      display: none !important;
    }
    .makale-bilgiler {
      align-items: center !important;
      gap: 5px !important;
      text-align: center !important;
    }
    .yazar-profil img {
      width: 100px !important;
      height: 100px !important;
      margin-bottom: 10px !important;
    }

    /* Masaüstü görünümünü gizle, mobil görünümü göster */
    #like-tags-desktop {
      display: none !important;
    }
    #like-tags-mobile {
      display: flex !important;
      flex-direction: column;
      align-items: center;
      text-align: center;
      margin-top: 20px;
      gap: 10px;
    }
    #like-tags-mobile .d-flex {
      justify-content: center !important; /* Ortala mobilde */
      gap: 8px !important;
      flex-wrap: wrap !important;
    }
  }
  #like-tags-desktop {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

#like-tags-desktop > div.d-flex.flex-wrap {
  justify-content: center;
}


@media (max-width: 992px) {
  .makale-icerik {
    background-image: url('uploads/<?= htmlspecialchars($makale['resim']) ?>');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-color: rgba(0, 0, 0, 0.7); /* Siyah %90 opaklık, saydamlık az */
    background-blend-mode: overlay; /* İki katmanı karıştır */
    padding: 20px;
    color: white;
  }
  
  .makale-resim {
    display: none !important;
  }
}

</style>

<div class="makale-ust">
  <div class="makale-resim">
    <?php if ($makale['resim'] && file_exists("uploads/" . $makale['resim'])): ?>
      <img src="uploads/<?= htmlspecialchars($makale['resim']) ?>" alt="<?= htmlspecialchars($makale['baslik']) ?>">
    <?php else: ?>
      <div style="width:100%; height:400px; background:#eee; display:flex; justify-content:center; align-items:center; color:#888; border-radius:6px;">
        Resim yok
      </div>
    <?php endif; ?>
  </div>

  <div class="dik-cizgi"></div>

  <div class="makale-bilgiler">
    <div class="yazar-profil">
      <?php if (!empty($makale['profil_resmi']) && file_exists("uploads/profil/" . $makale['profil_resmi'])): ?>
        <img src="uploads/profil/<?= htmlspecialchars($makale['profil_resmi']) ?>" alt="Yazar Profili">
      <?php else: ?>
        <div>Resim yok</div>
      <?php endif; ?>
    </div>
<small class="text-muted" style="display: block; text-align: center;"><?= htmlspecialchars($bio ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small>

   <p style="text-align: center;">Yazar: <strong><?= htmlspecialchars($makale['yazar_adi']) ?></strong></p>
<p style="text-align: center;">Kategori: <strong><?= htmlspecialchars($makale['kategori_adi']) ?></strong></p>
<p style="text-align: center;">Tarih: <strong><?= date('d.m.Y', strtotime($makale['eklenme_tarihi'])) ?></strong></p>



    <!-- Beğeni ve etiketler masaüstü için, tarihin hemen altında -->
    <div id="like-tags-desktop">
     

      <?php
      $badgeColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];
      shuffle($badgeColors);
      ?>

      <?php if ($etiketler): ?>
        <div class="d-flex flex-wrap gap-2 mt-2">
          <?php foreach ($etiketler as $i => $etiket): ?>
            <?php $color = $badgeColors[$i % count($badgeColors)]; ?>
            <span class="badge bg-<?= $color ?>"><?= htmlspecialchars($etiket) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <br>
     <style>
  #like-section {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    font-weight: 500;
  }
</style>

<div id="like-section" data-liked="<?= $userLiked ? '1' : '0' ?>" data-makale-id="<?= $makale['id'] ?>">
  <button id="like-btn" class="btn btn-sm <?= $userLiked ? 'btn-danger' : 'btn-outline-danger' ?>">
    <?= $userLiked ? 'Beğeniyi Kaldır' : 'Beğen' ?>
  </button>
  <span id="like-count"><?= $totalLikes ?></span> Beğeni
</div>

  </div>
  
</div>

<div class="makale-alt">
<br>
  <h1 class="makale-baslik"><?= htmlspecialchars($makale['baslik']) ?></h1>
  <div class="makale-icerik">
    <?= nl2br(strip_tags($makale['icerik'], '<br><strong><em><ul><li><ol>')) ?>
  </div>

  <!-- Beğeni ve etiketler mobil için, içerik altında ortalı -->
  <div id="like-tags-mobile" class="mt-4">

  <!-- Bu sadece beğeni kısmı, mobilde gizlenecek -->
  <div id="like-section-mobile" data-liked="<?= $userLiked ? '1' : '0' ?>" data-makale-id="<?= $makale['id'] ?>">
    <button id="like-btn-mobile" class="btn btn-sm <?= $userLiked ? 'btn-danger' : 'btn-outline-danger' ?>">
      <?= $userLiked ? 'Beğeniyi Kaldır' : 'Beğen' ?>
    </button>
    <span id="like-count-mobile"><?= $totalLikes ?></span> Beğeni
  </div>

  <!-- Bu etiketler kısmı, mobilde de görünmeye devam edecek -->
  <?php
    $badgeColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];
    shuffle($badgeColors);
  ?>

  <?php if ($etiketler): ?>
    <div class="d-flex flex-wrap gap-2 mt-2 justify-content-center">
      <?php foreach ($etiketler as $i => $etiket): ?>
        <?php $color = $badgeColors[$i % count($badgeColors)]; ?>
        <span class="badge bg-<?= $color ?>"><?= htmlspecialchars($etiket) ?></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

  <?php if (isset($_SESSION['user_id'])): ?>
    <div class="mt-4">
      <hr>
      <h5>Yorum Yap</h5>
      <form id="yorum-form">
        <input type="hidden" name="makale_id" value="<?= $id ?>">
        <input type="hidden" name="parent_id" id="parent_id" value="">
        <textarea name="yorum_metni" class="form-control mb-2" rows="3" required></textarea>
        <button type="submit" class="btn btn-primary">Gönder</button>
      </form>
    </div>
  <?php else: ?>
    <div class="alert alert-warning mt-4">Yorum yapabilmek için <a href="giris.php">giriş yapmalısınız</a>.</div>
  <?php endif; ?>

  <?php
  $yorumlar = yorumlari_getir($pdo, $id);
  ?>
  <h4>Yorumlar</h4>
  <?php
  if (empty($yorumlar)) {
      echo "<div class='alert alert-info'>Henüz yorum yapılmamış.</div>";
  }
  ?>
  <div id="yorumlar-alani">
    <?= yorum_html($yorumlar, $pdo, 0, $_SESSION['user_id']); ?>
  </div>
</div>



<script>
 document.getElementById('like-btn').addEventListener('click', function() {
    const likeSection = document.getElementById('like-section');
    const makaleId = likeSection.getAttribute('data-makale-id');

    fetch('begen.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'makale_id=' + makaleId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btn = document.getElementById('like-btn');
            const count = document.getElementById('like-count');
            if (data.action === 'added') {
                btn.textContent = 'Beğeniyi Kaldır';
                btn.classList.remove('btn-outline-danger');
                btn.classList.add('btn-danger');
            } else {
                btn.textContent = 'Beğen';
                btn.classList.remove('btn-danger');
                btn.classList.add('btn-outline-danger');
            }
            count.textContent = data.totalLikes;
        } else {
            alert(data.message);
        }
    })
    .catch(() => alert('Beğeni işlemi başarısız oldu.'));
});

document.querySelectorAll('.sil-btn').forEach(function(button) {
  button.addEventListener('click', function(e) {
    e.preventDefault();
    if (confirm('Yorumu silmek istediğinize emin misiniz?')) {
      // Silme işlemi için form oluşturup gönderelim
      const yorumId = this.getAttribute('data-id');
      const makaleId = this.getAttribute('data-makale-id') || ''; // makale_id eklemek için
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'yorum_sil.php';

      const inputYorumId = document.createElement('input');
      inputYorumId.type = 'hidden';
      inputYorumId.name = 'yorum_id';
      inputYorumId.value = yorumId;
      form.appendChild(inputYorumId);

      const inputMakaleId = document.createElement('input');
      inputMakaleId.type = 'hidden';
      inputMakaleId.name = 'makale_id';
      inputMakaleId.value = makaleId;
      form.appendChild(inputMakaleId);

      document.body.appendChild(form);
      form.submit();
    }
  });
});

document.addEventListener('click', function(e) {
  if (e.target.classList.contains('duzenle-btn')) {
    const yorumId = e.target.getAttribute('data-id');
    const duzenleFormDiv = document.getElementById('duzenle-form-' + yorumId);

    // Eğer form zaten açıksa gizle, yoksa aç
    if (!duzenleFormDiv.classList.contains('d-none')) {
      duzenleFormDiv.classList.add('d-none');
      duzenleFormDiv.innerHTML = '';
      return;
    }

    // Formu açmadan önce kapat diğer tüm düzenle formlarını
    document.querySelectorAll('.duzenle-form').forEach(formDiv => {
      formDiv.classList.add('d-none');
      formDiv.innerHTML = '';
    });

    // Yorumun mevcut metnini sayfadaki yorum divinden çekebilirsin
    const yorumMetniDiv = e.target.closest('div').previousElementSibling;
    const yorumMetni = yorumMetniDiv.textContent.trim();

    // Formu oluştur
    duzenleFormDiv.innerHTML = `
      <form method="POST" action="yorum_duzenle.php">
        <input type="hidden" name="yorum_id" value="${yorumId}">
        <input type="hidden" name="makale_id" value="<?= $id ?>"> 
        <textarea name="yorum_metni" class="form-control mb-2" required>${yorumMetni}</textarea>
        <button type="submit" name="yorum_duzenle" class="btn btn-sm btn-primary">Kaydet</button>
        <button type="button" class="btn btn-sm btn-secondary iptal-btn">İptal</button>
      </form>
    `;
    duzenleFormDiv.classList.remove('d-none');
  }

  // İptal butonuna basıldığında formu kapat
  if (e.target.classList.contains('iptal-btn')) {
    const duzenleFormDiv = e.target.closest('.duzenle-form');
    duzenleFormDiv.classList.add('d-none');
    duzenleFormDiv.innerHTML = '';
  }
});

const renkler = [
  '#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5',
  '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4caf50',
  '#8bc34a', '#cddc39', '#ffeb3b', '#ffc107', '#ff9800', '#ff5722',
];

document.querySelectorAll('.etiket').forEach((etiket) => {
  const rastgeleRenk = renkler[Math.floor(Math.random() * renkler.length)];
  etiket.style.backgroundColor = rastgeleRenk;
});


</script>
<script>
document.getElementById('yorum-form')?.addEventListener('submit', function(e) {
  e.preventDefault();

  const form = e.target;
  const data = {
    makale_id: form.makale_id.value,
    parent_id: form.parent_id.value,
    yorum_metni: form.yorum_metni.value
  };

  fetch('yorum_ekle.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(data)
  })
  .then(res => res.json())
  .then(response => {
    if (response.status === 'success') {
      location.reload(); // Sayfayı yenile
    } else {
      alert(response.message);
    }
  });
});

// Yanıtla butonları
document.querySelectorAll('.yanitla-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const parentId = btn.dataset.id;
    const hedef = document.getElementById('yanit-form-' + parentId);
    hedef.innerHTML = `
      <form class="yanit-yorum-form">
        <textarea class="form-control mb-2" rows="2" required></textarea>
        <button type="submit" class="btn btn-sm btn-primary">Yanıtla</button>
      </form>
    `;
    hedef.classList.remove('d-none');

    hedef.querySelector('form').addEventListener('submit', function(e) {
      e.preventDefault();
      const yorum = this.querySelector('textarea').value;
      fetch('yorum_ekle.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          makale_id: <?= $id ?>,
          parent_id: parentId,
          yorum_metni: yorum
        })
      })
      .then(res => res.json())
      .then(response => {
        if (response.status === 'success') {
          location.reload();
        } else {
          alert(response.message);
        }
      });
    });
  });
});


function toggleEdit(id) {
    const editForm = document.getElementById(`editForm_${id}`);
    const replyForm = document.getElementById(`replyForm_${id}`);
    
    if (editForm.style.display === "none") {
        editForm.style.display = "block";
        replyForm.style.display = "none";
    } else {
        editForm.style.display = "none";
    }
}

function toggleReply(id) {
    const replyForm = document.getElementById(`replyForm_${id}`);
    const editForm = document.getElementById(`editForm_${id}`);
    
    if (replyForm.style.display === "none") {
        replyForm.style.display = "block";
        editForm.style.display = "none";
    } else {
        replyForm.style.display = "none";
    }
}
</script>

<?php include "includes/footer.php"; ?>
