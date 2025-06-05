<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();
require_once "includes/db.php";

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli e-posta giriniz.";
    }
    if (!$password) {
        $errors[] = "Şifre giriniz.";
    }

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $password_ok = password_verify($password, $user['password'] ?? '');
            $sifre_ok = password_verify($password, $user['sifre'] ?? '');

            if ($password_ok || $sifre_ok) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_profil_resmi'] = $user['profil_resmi'] ?? '';
                header("Location: index.php");
                exit;
            } else {
                $errors[] = "E-posta veya şifre hatalı.";
            }
        } else {
            $errors[] = "E-posta veya şifre hatalı.";
        }
    }
}
require_once "includes/header.php";

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kullanıcı Girişi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 420px;">
    <h2 class="mb-4 text-center">Giriş Yap</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label for="email" class="form-label">E-posta</label>
            <input type="email" class="form-control" id="email" name="email" required autofocus>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Şifre</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
		        <a href="kayit.php" class="btn btn-link">Üye Değil Misin? Kayıt Ol</a>
    </form>
</div>
</body>
</html>
<?php include "includes/footer.php"; ?>

