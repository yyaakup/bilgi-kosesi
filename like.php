<?php
session_start();

// Test için giriş var gibi göster
$_SESSION['user_id'] = 1;

if (!isset($_SESSION['user_id'])) {
    die("Giriş yapmalısınız.");
}

if (!isset($_POST['makale_id']) || !isset($_POST['action'])) {
    die("Eksik veri.");
}

$makale_id = (int) $_POST['makale_id'];
$action = $_POST['action'];

if (!in_array($action, ['like', 'unlike'])) {
    die("Geçersiz işlem.");
}

// Veritabanına bağlan (örnek)
$pdo = new PDO("mysql:host=localhost;dbname=veritabani_adi;charset=utf8", "kullanici_adi", "sifre");

$sql = $action === 'like'
    ? "UPDATE makaleler SET likes = likes + 1 WHERE id = :id"
    : "UPDATE makaleler SET likes = GREATEST(likes - 1, 0) WHERE id = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $makale_id]);

echo "ok";
