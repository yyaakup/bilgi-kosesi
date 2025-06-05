<?php
// arama.php

$host = "sql308.infinityfree.com";
$dbname = "if0_39127142_makale_sitesi";
$username = "if0_39127142";
$password = "qwaszx123TrTr";

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Veritabanı bağlantı hatası']);
    exit;
}

$searchTerm = $_GET['q'] ?? '';
$results = [];

if ($searchTerm !== '') {
    $likeTerm = "%$searchTerm%";

    $stmt = $pdo->prepare("SELECT id, baslik FROM makaleler WHERE baslik LIKE :baslik ORDER BY id DESC LIMIT 5");
    $stmt->bindParam(':baslik', $likeTerm, PDO::PARAM_STR);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($results);
