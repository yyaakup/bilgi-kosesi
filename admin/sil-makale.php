<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../includes/db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: makaleler.php");
    exit;
}

$makale_id = (int)$_GET['id'];

$stmt = $pdo->prepare("DELETE FROM makaleler WHERE id = ?");
$stmt->execute([$makale_id]);

header("Location: makaleler.php");
exit;
