<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$mesaj = "";
$hata = false;

// Form değişkenleri için ön tanımlama
$baslik = $ozet = $icerik = $etiketler = '';
$kategori_id = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $baslik = trim($_POST['baslik'] ?? '');
    $ozet = isset($_POST['ozet']) ? trim($_POST['ozet']) : null;
    $icerik = $_POST['icerik'] ?? '';
    $kategori_id = isset($_POST['kategori']) ? (int)$_POST['kategori'] : 0;
    $etiketler = trim($_POST['etiketler'] ?? '');
    $yazar_id = $_SESSION['admin_id'];

    if (empty($baslik)) {
        $mesaj = "Başlık boş olamaz.";
        $hata = true;
    } elseif (empty($icerik)) {
        $mesaj = "İçerik boş olamaz.";
        $hata = true;
    } elseif ($kategori_id <= 0) {
        $mesaj = "Lütfen geçerli bir kategori seçiniz.";
        $hata = true;
    }

    $yeni_dosya_adi = null;

    if (!$hata && isset($_FILES['resim']) && $_FILES['resim']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['resim']['error'] === UPLOAD_ERR_OK) {
            $resim = $_FILES['resim']['name'];
            $hedef_klasor = "../uploads/";
            $uzanti = strtolower(pathinfo($resim, PATHINFO_EXTENSION));
            $izinli_uzantilar = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($uzanti, $izinli_uzantilar)) {
                $mesaj = "Sadece JPG, JPEG, PNG, GIF ve WEBP formatları kabul edilmektedir.";
                $hata = true;
            } else {
                $yeni_dosya_adi = uniqid('img_') . "." . $uzanti;
                $hedef_dosya = $hedef_klasor . $yeni_dosya_adi;

                if (!is_dir($hedef_klasor) || !is_writable($hedef_klasor)) {
                    $mesaj = "Yükleme klasörü mevcut değil veya yazılabilir değil.";
                    $hata = true;
                } else {
                    if (!move_uploaded_file($_FILES['resim']['tmp_name'], $hedef_dosya)) {
                        $mesaj = "Dosya yüklenirken hata oluştu!";
                        $hata = true;
                    }
                }
            }
        } else {
            $mesaj = "Dosya yükleme hatası: " . $_FILES['resim']['error'];
            $hata = true;
        }
    }

    if (!$hata) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO makaleler (baslik, ozet, icerik, kategori_id, resim, yazar_id, olusturma_tarihi) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$baslik, $ozet, $icerik, $kategori_id, $yeni_dosya_adi, $yazar_id]);

            $makale_id = $pdo->lastInsertId();

            $etiket_dizisi = array_filter(array_map('trim', explode(',', $etiketler)));

            if (!empty($etiket_dizisi)) {
                foreach ($etiket_dizisi as $etiket_ad) {
                    $stmt = $pdo->prepare("SELECT id FROM etiketler WHERE ad = ?");
                    $stmt->execute([$etiket_ad]);
                    $etiket_id = $stmt->fetchColumn();

                    if (!$etiket_id) {
                        $stmt = $pdo->prepare("INSERT INTO etiketler (ad) VALUES (?)");
                        $stmt->execute([$etiket_ad]);
                        $etiket_id = $pdo->lastInsertId();
                    }

                    $stmt = $pdo->prepare("INSERT INTO makale_etiketleri (makale_id, etiket_id) VALUES (?, ?)");
                    $stmt->execute([$makale_id, $etiket_id]);
                }
            }

            $pdo->commit();

            $mesaj = "Makale başarıyla eklendi!";
            $baslik = $ozet = $icerik = $etiketler = '';
            $kategori_id = 0;
            $yeni_dosya_adi = null;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $mesaj = "Veritabanı hatası: " . $e->getMessage();
            $hata = true;
        }
    }
}

$kategoriler = $pdo->query("SELECT * FROM kategoriler")->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<div class="container my-5">
  <div class="card shadow-sm mx-auto" style="max-width: 800px; border-radius: 1rem;">
    <div class="card-body p-5">
      <h2 class="card-title mb-4 text-center text-primary fw-bold">Yeni Makale Ekle</h2>

      <?php if ($mesaj): ?>
        <div class="alert <?= $hata ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($mesaj) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" novalidate>
        <div class="mb-4">
          <label for="baslik" class="form-label fw-semibold">Başlık <span class="text-danger">*</span></label>
          <input type="text" id="baslik" name="baslik" class="form-control form-control-lg shadow-sm" required autofocus value="<?= htmlspecialchars($baslik) ?>" placeholder="Makale başlığını giriniz">
        </div>

        <div class="mb-4">
          <label for="icerik" class="form-label fw-semibold">İçerik <span class="text-danger">*</span></label>
          <textarea id="icerik" name="icerik" class="form-control shadow-sm" rows="10" placeholder="Makale içeriğini buraya yazınız..." required><?= htmlspecialchars($icerik) ?></textarea>
        </div>

        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <label for="kategori" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
            <select id="kategori" name="kategori" class="form-select shadow-sm" required>
              <option value="" disabled <?= $kategori_id == 0 ? 'selected' : '' ?>>-- Kategori Seçiniz --</option>
              <?php foreach ($kategoriler as $kat): ?>
                <option value="<?= $kat['id'] ?>" <?= ($kategori_id == $kat['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($kat['ad']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label for="etiketler" class="form-label fw-semibold">Etiketler <small class="text-muted">(Virgülle ayır)</small></label>
            <input type="text" id="etiketler" name="etiketler" class="form-control shadow-sm" placeholder="örn: teknoloji, sağlık, eğitim" value="<?= htmlspecialchars($etiketler) ?>">
          </div>
        </div>

        <div class="mb-4">
          <label for="resim" class="form-label fw-semibold">Kapak Görseli</label>
          <input type="file" id="resim" name="resim" class="form-control shadow-sm" accept="image/*">
          <div class="form-text">Sadece JPG, JPEG, PNG, GIF ve WEBP formatları kabul edilmektedir.</div>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">
            <i class="bi bi-plus-circle me-2"></i> Makale Ekle
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- TinyMCE Editörü -->
<script src="https://cdn.tiny.cloud/1/ko7sf3110pzgmvryxadjc0knhg554fb9hh3fdktyqjwk37e1/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
  selector: 'textarea[name=icerik]',
  plugins: [
    'advlist autolink lists link image charmap preview anchor',
    'searchreplace visualblocks code fullscreen',
    'insertdatetime media table help wordcount codesample',
    'emoticons spellchecker',
    'directionality', 'paste', 'textpattern', 'noneditable', 'visualchars', 'autosave'
  ],
  toolbar: 'undo redo | styleselect | bold italic underline strikethrough | ' +
           'alignleft aligncenter alignright alignjustify | ' +
           'bullist numlist outdent indent | link image media | ' +
           'codesample | forecolor backcolor emoticons | spellchecker | ' +
           'removeformat | fullscreen preview code',
  menubar: 'file edit view insert format tools table help',
  height: 400,
  spellchecker_language: 'tr',
  language: 'tr',
  // language_url: '/js/tr.js', // İstersen Türkçe dil dosyasını buraya ekle
  content_style: "body { font-family:Helvetica,Arial,sans-serif; font-size:16px; }"
});
</script>

<?php include "../includes/footer.php"; ?>
