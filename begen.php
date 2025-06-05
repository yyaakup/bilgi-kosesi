<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmalısınız']);
    exit;
}

$user_id = $_SESSION['user_id'];
$makale_id = $_POST['makale_id'] ?? null;

if (!$makale_id || !is_numeric($makale_id)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz makale ID']);
    exit;
}

// Kullanıcının zaten beğenip beğenmediğini kontrol et
$stmt = $pdo->prepare("SELECT * FROM makale_begen WHERE makale_id = ? AND kullanici_id = ?");
$stmt->execute([$makale_id, $user_id]);
$varMi = $stmt->fetch(PDO::FETCH_ASSOC);

if ($varMi) {
    // Beğeniyi kaldır
    $stmt = $pdo->prepare("DELETE FROM makale_begen WHERE makale_id = ? AND kullanici_id = ?");
    $stmt->execute([$makale_id, $user_id]);
    $action = 'removed';
} else {
    // Beğeni ekle
    $stmt = $pdo->prepare("INSERT INTO makale_begen (makale_id, kullanici_id) VALUES (?, ?)");
    $stmt->execute([$makale_id, $user_id]);
    $action = 'added';
}

// Güncel beğeni sayısını al
$stmt = $pdo->prepare("SELECT COUNT(*) as toplam FROM makale_begen WHERE makale_id = ?");
$stmt->execute([$makale_id]);
$sayac = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'action' => $action, 'totalLikes' => $sayac['toplam']]);
