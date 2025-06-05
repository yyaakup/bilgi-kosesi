<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

include "../includes/header.php";
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h2 class="mb-0 fw-bold">Admin Paneli</h2>
                </div>
                <div class="card-body text-center">
                    <p class="lead mb-4">Hoşgeldin, <strong><?= htmlspecialchars($_SESSION['admin_adi']) ?></strong>!</p>
                    <div class="d-grid gap-3">
                        <a href="makaleler.php" class="btn btn-primary btn-lg" role="button" aria-label="Makale Yönetimi">Makale Yönetimi</a>
                        <a href="users.php" class="btn btn-warning btn-lg" role="button" aria-label="Kullanıcı Yönetimi">Kullanıcı Yönetimi</a>
                        <a href="admin_mesajlar.php" class="btn btn-info btn-lg" role="button" aria-label="Gelen Mesajlar">Gelen Mesajlar</a>
                        <a href="logout.php" class="btn btn-danger btn-lg" role="button" aria-label="Çıkış Yap">Çıkış Yap</a>
                    </div>
                </div>
                <div class="card-footer text-center text-muted small">
                    &copy; <?= date("Y") ?> Bilgi Köşesi Admin Paneli
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
