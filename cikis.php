<?php
session_start();

// Tüm oturum verilerini temizle
$_SESSION = [];

// Oturumu tamamen sonlandır
session_destroy();

// Çıkış yaptıktan sonra ana sayfaya yönlendir
header("Location: /article_web_site_files/index.php");
exit;
