<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
require_once "../includes/db.php";

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM makaleler WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: makaleler.php");
exit;
