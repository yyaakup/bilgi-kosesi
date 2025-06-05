<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include "../includes/header.php";
?>

<h2>Admin Paneli</h2>
<p>Hoş geldin, <strong><?php echo htmlspecialchars($_SESSION['admin_isim']); ?></strong>!</p>

<ul class="list-group w-50">
    <li class="list-group-item"><a href="yeni-makale.php">Yeni Makale Ekle</a></li>
    <li class="list-group-item"><a href="makaleler.php">Tüm Makaleleri Görüntüle</a></li>
    <li class="list-group-item"><a href="kategoriler.php">Kategorileri Yönet</a></li>
    
    <!-- Gelen mesajlar sayfası için link -->
    <li class="list-group-item"><a href="admin_mesajlar.php">Gelen Mesajları Görüntüle</a></li>
    
    <li class="list-group-item"><a href="logout.php">Çıkış Yap</a></li>
</ul>

<?php include "../includes/footer.php"; ?>
