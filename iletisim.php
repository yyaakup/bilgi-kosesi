<?php
session_start();
require_once "includes/db.php"; // Veritabanı bağlantısı

$is_logged_in = $_SESSION['user_logged_in'] ?? false;
$kullanici_id = $_SESSION['user_id'] ?? null; // Kullanıcı ID oturumda varsa al

include "includes/header.php";

$mesaj = "";

if ($is_logged_in && $kullanici_id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $isim = trim($_POST['isim'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $konu = trim($_POST['konu'] ?? '');
        $mesaj_icerik = trim($_POST['mesaj'] ?? '');

        if ($isim && $email && $konu && $mesaj_icerik && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Aynı kullanıcı 12 saat içinde mesaj gönderdi mi kontrol et
            $stmt = $pdo->prepare("SELECT gonderme_tarihi FROM iletisim_mesajlari WHERE kullanici_id = ? ORDER BY gonderme_tarihi DESC LIMIT 1");
            $stmt->execute([$kullanici_id]);
            $son_mesaj = $stmt->fetch(PDO::FETCH_ASSOC);

            $simdi = new DateTime();
            $izin_zamani = $simdi->sub(new DateInterval('PT12H')); // Şu andan 12 saat öncesi

            if ($son_mesaj) {
                $son_gonderme = new DateTime($son_mesaj['gonderme_tarihi']);
                if ($son_gonderme > $izin_zamani) {
                    // 12 saat dolmamış
                    $mesaj = "En son mesajınızı " . $son_gonderme->format('d.m.Y H:i') . " tarihinde gönderdiniz. Lütfen 12 saat sonra tekrar deneyiniz.";
                } else {
                    // Mesaj kaydet
                    $stmt = $pdo->prepare("INSERT INTO iletisim_mesajlari (kullanici_id, isim, email, konu, mesaj) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$kullanici_id, $isim, $email, $konu, $mesaj_icerik]);
                    $mesaj = "Mesajınız başarıyla gönderildi, teşekkürler!";
                }
            } else {
                // İlk mesaj kaydet
                $stmt = $pdo->prepare("INSERT INTO iletisim_mesajlari (kullanici_id, isim, email, konu, mesaj) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$kullanici_id, $isim, $email, $konu, $mesaj_icerik]);
                $mesaj = "Mesajınız başarıyla gönderildi, teşekkürler!";
            }
        } else {
            $mesaj = "Lütfen tüm alanları doğru doldurun.";
        }
    }
} else {
    $mesaj = "Mesaj gönderebilmek için lütfen giriş yapınız.";
}
?>

<div class="container mt-5">
  <div class="text-center mb-4">
    <h1 class="fw-bold">İletişim</h1>
    <p class="text-muted">Bizimle iletişime geçmekten çekinmeyin.</p>
  </div>

  <?php if ($mesaj): ?>
    <div class="alert alert-info text-center shadow-sm rounded"><?= htmlspecialchars($mesaj) ?></div>
  <?php endif; ?>

  <?php if ($is_logged_in): ?>
    <div class="card shadow-lg rounded mx-auto" style="max-width: 650px;">
      <div class="card-body p-4">
        <form method="post" action="iletisim.php">
          <div class="form-floating mb-3">
            <input type="text" class="form-control rounded-3" id="isim" name="isim" placeholder="İsminiz" required>
            <label for="isim">İsminiz</label>
          </div>

          <div class="form-floating mb-3">
            <input type="email" class="form-control rounded-3" id="email" name="email" placeholder="E-posta adresiniz" required>
            <label for="email">E-posta adresiniz</label>
          </div>

          <div class="form-floating mb-3">
            <input type="text" class="form-control rounded-3" id="konu" name="konu" placeholder="Konu" required>
            <label for="konu">Konu</label>
          </div>

          <div class="form-floating mb-4">
            <textarea class="form-control rounded-3" id="mesaj" name="mesaj" placeholder="Mesajınız..." style="height: 180px;" required></textarea>
            <label for="mesaj">Mesajınız</label>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            <i class="bi bi-send"></i> Gönder
          </button>
        </form>
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-warning text-center">
      Mesaj gönderebilmek için <a href="giris.php" class="alert-link">giriş yapınız</a>.
    </div>
  <?php endif; ?>
</div>


<?php include "includes/footer.php"; ?>
