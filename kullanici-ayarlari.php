<?php
session_start();
require_once "../includes/db.php";

// Giriş kontrolü
if (isset($_SESSION['admin_id'])) {
    $user_id = $_SESSION['admin_id'];
    $user_type = 'admin';
} elseif (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_type = 'user';
} else {
    if (file_exists('login.php')) {
        header("Location: login.php");
    } elseif (file_exists('giris.php')) {
        header("Location: giris.php");
    } else {
        echo "Giriş sayfası bulunamadı!";
    }
    exit;
}

// Kullanıcı bilgilerini çek
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Kullanıcı bulunamadı.";
    exit;
}

$mesaj = "";

// Form gönderildiyse
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $isim = isset($_POST['isim']) ? trim($_POST['isim']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $sifre = $_POST['sifre'] ?? '';
    $sifre_tekrar = $_POST['sifre_tekrar'] ?? '';

    // Profil resmi işlemi
    $profil_resmi = $user['profil_resmi'];

    if (isset($_FILES['profil_resmi']) && $_FILES['profil_resmi']['error'] === UPLOAD_ERR_OK) {
        $dosya_tmp = $_FILES['profil_resmi']['tmp_name'];
        $dosya_adi = $_FILES['profil_resmi']['name'];
        $uzanti = strtolower(pathinfo($dosya_adi, PATHINFO_EXTENSION));
        $izinli_uzantilar = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($uzanti, $izinli_uzantilar)) {
            $yeni_dosya_adi = 'profile_' . $user_id . '_' . time() . '.' . $uzanti;
            $hedef_klasor = "../uploads/profil/";
            if (!is_dir($hedef_klasor)) {
                mkdir($hedef_klasor, 0755, true);
            }
            $hedef_yol = $hedef_klasor . $yeni_dosya_adi;

            if (move_uploaded_file($dosya_tmp, $hedef_yol)) {
                $profil_resmi = $yeni_dosya_adi;
            } else {
                $mesaj = "Profil resmi yüklenirken hata oluştu.";
            }
        } else {
            $mesaj = "Sadece JPG, PNG, GIF, WEBP formatlarında dosya yükleyebilirsiniz.";
        }
    }

    if ($sifre !== '' && $sifre !== $sifre_tekrar) {
        $mesaj = "Şifreler eşleşmiyor.";
    }

    if ($mesaj === "") {
        if ($sifre !== '') {
            $hashed_sifre = password_hash($sifre, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET isim = ?, name = ?, email = ?, sifre = ?, profil_resmi = ? WHERE id = ?");
            $stmt->execute([$isim, $name, $email, $hashed_sifre, $profil_resmi, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET isim = ?, name = ?, email = ?, profil_resmi = ? WHERE id = ?");
            $stmt->execute([$isim, $name, $email, $profil_resmi, $user_id]);
        }

        $mesaj = "Bilgileriniz başarıyla güncellendi.";

        // Güncellenen kullanıcı bilgilerini tekrar al
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

include "../includes/header.php";
?>

<div class="container mt-4" style="max-width:600px;">
    <h2>Profil Bilgilerim (<?= htmlspecialchars($user_type) ?>)</h2>

    <?php if ($mesaj): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mesaj) ?></div>
    <?php endif; ?>
<div class="mb-3">
    <label>Profil Resmi</label><br>
    <?php if ($user['profil_resmi'] && file_exists("../uploads/profil/" . $user['profil_resmi'])): ?>
        <img src="../uploads/profil/<?= htmlspecialchars($user['profil_resmi']) ?>" alt="Profil Resmi" style="width:120px; height:120px; object-fit:cover; border-radius:50%; margin-bottom:10px;">
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="profil_resmi_sil" id="profil_resmi_sil" value="1">
            <label class="form-check-label" for="profil_resmi_sil">
                Profil resmini sil
            </label>
        </div>
    <?php else: ?>
        <p>Profil resmi yok.</p>
    <?php endif; ?>
    <input type="file" name="profil_resmi" accept="image/*" class="form-control mt-2">
</div>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label>Ad Soyad (name)</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($user['name']) ?>">
        </div>

        <div class="mb-3">
            <label>Kullanıcı Adı (isim)</label>
            <input type="text" name="isim" class="form-control" required value="<?= htmlspecialchars($user['isim']) ?>">
        </div>

        <div class="mb-3">
            <label>E-posta</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($user['email']) ?>">
        </div>

        <div class="mb-3">
            <label>Yeni Şifre (boş bırakılırsa değişmez)</label>
            <input type="password" name="sifre" class="form-control" autocomplete="new-password">
        </div>

        <div class="mb-3">
            <label>Yeni Şifre Tekrar</label>
            <input type="password" name="sifre_tekrar" class="form-control" autocomplete="new-password">
        </div>

        <div class="mb-3">
            <label>Profil Resmi</label><br>
            <?php if ($user['profil_resmi'] && file_exists("../uploads/profil/" . $user['profil_resmi'])): ?>
                <img src="../uploads/profil/<?= htmlspecialchars($user['profil_resmi']) ?>" alt="Profil Resmi" style="width:120px; height:120px; object-fit:cover; border-radius:50%; margin-bottom:10px;">
            <?php endif; ?>
            <input type="file" name="profil_resmi" accept="image/*" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Güncelle</button>
    </form>
</div>

<?php include "../includes/footer.php"; ?>
