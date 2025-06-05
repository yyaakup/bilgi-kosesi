<?php
session_start();

// Veritabanı bağlantısı
$host = "sql308.infinityfree.com";
$dbname = "if0_39127142_makale_sitesi";
$username = "if0_39127142";
$password = "qwaszx123TrTr";

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Zaten giriş yapıldıysa yönlendir
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$hata = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? "";
    $sifre = $_POST['sifre'] ?? "";

    if (!empty($email) && !empty($sifre)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND yetki = 1 LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($sifre, $admin['sifre'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_adi'] = $admin['isim'];
            $_SESSION['admin_rol'] = $admin['rol']; // Rol bilgisi
            $_SESSION['admin_profil_resmi'] = $admin['profil_resmi']; // Profil resmi

            header("Location: index.php");
            exit;
        } else {
            $hata = "Hatalı e-posta veya şifre.";
        }
    } else {
        $hata = "Lütfen tüm alanları doldurun.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Girişi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 420px;">
    <h2 class="mb-4 text-center">Admin Girişi</h2>
    <?php if ($hata): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($hata) ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
        <div class="mb-3">
            <label for="email" class="form-label">E-posta</label>
            <input type="email" class="form-control" id="email" name="email" required autofocus autocomplete="username">
        </div>
        <div class="mb-3 position-relative">
            <label for="sifre" class="form-label">Şifre</label>
            <div class="input-group">
                <input type="password" class="form-control" id="sifre" name="sifre" required autocomplete="current-password">
                <button class="btn btn-outline-secondary" type="button" id="togglePassword">👁️</button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
    </form>
</div>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('sifre');
    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
