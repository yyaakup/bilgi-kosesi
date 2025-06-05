<?php
ob_start();

require_once "includes/db.php";
session_start();
require_once "includes/header.php";

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (!$name) $errors[] = "İsim giriniz.";
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Geçerli e-posta giriniz.";
    if (!$password) $errors[] = "Şifre giriniz.";
    if ($password !== $password_confirm) $errors[] = "Şifreler eşleşmiyor.";

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Bu e-posta zaten kayıtlı.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'kullanici')");
            if ($stmt->execute([$name, $email, $password_hash])) {
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_role'] = 'kullanici';
                $_SESSION['user_profil_resmi'] = '';

                header("Location: index.php");
                exit;
            } else {
                $errors[] = "Kayıt yapılamadı, lütfen tekrar deneyiniz.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol - Bilgi Köşesi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width: 480px;">
    <h2>Kayıt Ol</h2>
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?=htmlspecialchars($err)?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form action="" method="post" novalidate>
        <div class="mb-3">
            <label for="name" class="form-label">İsim</label>
            <input type="text" name="name" id="name" class="form-control" required value="<?=htmlspecialchars($_POST['name'] ?? '')?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">E-posta</label>
            <input type="email" name="email" id="email" class="form-control" required value="<?=htmlspecialchars($_POST['email'] ?? '')?>">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Şifre</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="password_confirm" class="form-label">Şifre Tekrar</label>
            <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
        </div>
		<p class="small mb-3">
    Kayıt olarak <a href="gizlilik.php" target="_blank">Gizlilik Sözleşmesi</a>’ni okuduğunuzu ve kabul ettiğinizi onaylamış olursunuz.
</p>
        <button type="submit" class="btn btn-primary">Kayıt Ol</button>
        <a href="giris.php" class="btn btn-link">Zaten üye misiniz? Giriş Yap</a>
    </form>
</div>
</body>
</html>
<?php include "includes/footer.php"; ?>
