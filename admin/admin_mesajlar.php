<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');

require_once "../includes/db.php";

// Admin kontrolü
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'yazar') {
    header("Location: ../giris.php");
    exit;
}

include "../includes/header.php";

// Mesajları çek
$stmt = $pdo->prepare("SELECT m.*, u.name FROM iletisim_mesajlari m LEFT JOIN users u ON m.kullanici_id = u.id ORDER BY m.gonderme_tarihi DESC");
$stmt->execute();
$mesajlar = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<html lang="tr">
<meta charset="UTF-8">

<div class="container mt-4">
  <h1>Gelen Mesajlar</h1>

  <?php if (!$mesajlar): ?>
    <p>Hiç mesaj yok.</p>
  <?php else: ?>
    <table class="table table-striped table-bordered align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>İsim</th>
          <th>Email</th>
          <th>Konu</th>
          <th>Mesaj</th>
          <th>Gönderme Tarihi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($mesajlar as $m): ?>
          <tr>
            <td><?= htmlspecialchars($m['id']) ?></td>
            <td><?= htmlspecialchars($m['isim']) ?></td>
            <td><?= htmlspecialchars($m['email']) ?></td>
            <td><?= htmlspecialchars($m['konu']) ?></td>
            <td style="max-width: 400px; white-space: pre-wrap;"><?= nl2br(htmlspecialchars($m['mesaj'])) ?></td>
            <td><?= date('d.m.Y H:i', strtotime($m['gonderme_tarihi'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?>
