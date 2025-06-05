<?php
$host = "sql308.infinityfree.com";
$dbname = "if0_39127142_makale_sitesi";
$username = "if0_39127142";
$password = "qwaszx123TrTr";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8;port=3306", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
