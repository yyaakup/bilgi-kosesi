<?php
session_start();
require_once '../includes/db.php'; // $pdo hazır

function getUserId() {
    $sessionUserId = $_SESSION['user_id'] ?? 0;
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : $sessionUserId;
    return ($userId > 0) ? $userId : 0;
}

function getRoleDisplay($role) {
    $roles = [
        'kullanici' => 'Kullanıcı',
        'yazar' => 'Yazar',
        'admin' => 'Yönetici'
    ];
    return $roles[$role] ?? ucfirst($role);
}

function getImageSrc($path, $filename, $placeholder) {
    if ($filename && file_exists($path . $filename)) {
        return htmlspecialchars($path . $filename);
    }
    return $placeholder;
}

function formatDate($dateStr) {
    $timestamp = strtotime($dateStr);
    if (!$timestamp) return '';
    return date('d.m.Y', $timestamp);
}

$user_id = getUserId();
if ($user_id === 0) {
    die('Geçersiz kullanıcı ID');
}

$sqlUser = "SELECT id, name, role, profil_resmi, biography, email FROM users WHERE id = ?";
$stmtUser = $pdo->prepare($sqlUser);
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Kullanıcı bulunamadı.');
}

$roleDisplay = getRoleDisplay($user['role']);

$sqlLikes = "SELECT m.id, m.baslik, m.yayin_tarihi, m.resim FROM makaleler m
             INNER JOIN makale_begen mb ON m.id = mb.makale_id
             WHERE mb.kullanici_id = ? ORDER BY mb.id DESC LIMIT 3";
$stmtLikes = $pdo->prepare($sqlLikes);
$stmtLikes->execute([$user_id]);
$likedArticles = $stmtLikes->fetchAll(PDO::FETCH_ASSOC);

$sqlComments = "SELECT y.id, y.yorum_metni, y.tarih, m.baslik, m.id AS makale_id
                FROM yorumlar y
                INNER JOIN makaleler m ON y.makale_id = m.id
                WHERE y.kullanici_id = ?
                ORDER BY y.id DESC LIMIT 3";
$stmtComments = $pdo->prepare($sqlComments);
$stmtComments->execute([$user_id]);
$userComments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);

$profileImage = getImageSrc("../uploads/profil/", $user['profil_resmi'], "https://via.placeholder.com/150?text=Profil");

$mesaj = ''; // Mesajı boş başlat

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $biography = trim($_POST['biography'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $profil_resmi = $user['profil_resmi'];

    // Profil resmi kaldırma isteği
    if (isset($_POST['profil_resmi_kaldir']) && $_POST['profil_resmi_kaldir'] === 'on') {
        if ($profil_resmi && file_exists("../uploads/profil/" . $profil_resmi)) {
            unlink("../uploads/profil/" . $profil_resmi);
        }
        $profil_resmi = null;
    }

    // Profil resmi yükleme işlemi
    if (isset($_FILES['profil_resmi']) && $_FILES['profil_resmi']['error'] === UPLOAD_ERR_OK) {
        $dosya_tmp = $_FILES['profil_resmi']['tmp_name'];
        $dosya_adi = $_FILES['profil_resmi']['name'];
        $uzanti = strtolower(pathinfo($dosya_adi, PATHINFO_EXTENSION));
        $izinli_uzantilar = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($uzanti, $izinli_uzantilar)) {
            $yeni_dosya_adi = 'profile_' . $user_id . '_' . time() . '.' . $uzanti;
            $hedef_klasor = "../uploads/profil/";
            if (!is_dir($hedef_klasor)) {
                mkdir($hedef_klasor, 0755, true);
            }
            $hedef_yol = $hedef_klasor . $yeni_dosya_adi;

            if (move_uploaded_file($dosya_tmp, $hedef_yol)) {
                if ($profil_resmi && file_exists("../uploads/profil/" . $profil_resmi)) {
                    unlink("../uploads/profil/" . $profil_resmi);
                }
                $profil_resmi = $yeni_dosya_adi;
            } else {
                $mesaj = "Profil resmi yüklenirken hata oluştu.";
            }
        } else {
            $mesaj = "Sadece JPG, PNG, GIF, WEBP formatlarında dosya yükleyebilirsiniz.";
        }
    }

    if (!$mesaj) {
        // E-posta başka kullanıcıda var mı kontrolü
        $stmtEmailCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmtEmailCheck->execute([$email, $id]);
        $emailExists = $stmtEmailCheck->fetchColumn();

        if ($emailExists) {
            $mesaj = "Bu e-posta zaten başka bir kullanıcı tarafından kullanılıyor.";
        } else {
            try {
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                if ($password !== '') {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    $sql = "UPDATE users SET name = ?, email = ?, biography = ?, profil_resmi = ?, sifre = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);

                    $result = $stmt->execute([$name, $email, $biography, $profil_resmi, $hashedPassword, $id]);
                } else {
                    $sql = "UPDATE users SET name = ?, email = ?, biography = ?, profil_resmi = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);

                    $result = $stmt->execute([$name, $email, $biography, $profil_resmi, $id]);
                }

                if ($result) {
                    $mesaj = "Profil başarıyla güncellendi.";

                    // Güncellenmiş kullanıcı bilgilerini tekrar çek
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    $profileImage = getImageSrc("../uploads/profil/", $user['profil_resmi'], "https://via.placeholder.com/150?text=Profil");
                } else {
                    $mesaj = "Güncelleme sırasında hata oluştu.";
                }
            } catch (PDOException $e) {
                $mesaj = "Hata: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($user['name']) ?> - Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(135deg, #c3cfe2, #cfd9df 90%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #343a40;
            min-height: 100vh;
            padding: 2rem;
        }
        .profile-card {
            background: #ffffffdd;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            padding: 2rem 2.5rem;
            max-width: 100%;
            text-align: center;
            transition: box-shadow 0.3s ease;
        }
        .profile-img {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border-radius: 50%;
            border: 6px solid #0d6efd;
            box-shadow: 0 6px 18px rgba(13, 110, 253, 0.6);
            margin: 0 auto 1.5rem auto;
            transition: transform 0.3s ease;
            display: block;
        }
        .profile-img:hover {
            transform: scale(1.1);
        }
        .user-name {
            font-weight: 800;
            font-size: 2.8rem;
            color: #0d6efd;
            margin-bottom: 0.3rem;
            letter-spacing: 0.04em;
        }
        .user-role {
            font-size: 1.25rem;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 2rem;
            letter-spacing: 0.06em;
            font-style: italic;
        }
        .biography {
    white-space: pre-wrap;
    font-size: 1.15rem;
    line-height: 1.7;
    color: #495057;
    max-width: 480px;
    margin: 0 auto 3rem auto;

    /* Aşağıdaki eklemeler taşmayı engeller */
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
    /* Ayrıca taşmayı engellemek için yatay scrollbar istemiyorsan: */
    overflow-x: hidden;
}
    .section-title {
    font-size: 20px;
    font-weight: 600;
    border-bottom: 2px solid #4a90e2;
    padding-bottom: 8px;
    margin-bottom: 15px;
    color: #4a90e2;
}

.liked-article-item {
    display: flex;
    align-items: center;
    gap: 15px;
    background: #f1f6fb;
    padding: 10px 15px;
    border-radius: 10px;
    margin-bottom: 12px;
    text-decoration: none;
    color: #333;
    transition: background-color 0.3s ease;
}

.liked-article-item:hover {
    background-color: #d6e4ff;
}

.liked-article-img {
    width: 80px;
    height: 55px;
    object-fit: cover;
    border-radius: 6px;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.liked-article-title {
    font-weight: 600;
    flex-grow: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.liked-article-date {
    font-size: 13px;
    color: #999;
    flex-shrink: 0;
}

.liked-article-item p {
    margin: 0;
    flex-grow: 1;
    color: #444;
    font-size: 14px;
}

.liked-article-item[style] {
    background: #fafafa !important;
    border-left: 3px solid #4a90e2;
    padding-left: 12px;
}

.mt-5 {
    margin-top: 3rem !important;
}

.btn-cancel {
  background-color: #f44336; /* Kırmızı ton */
  color: white;
  border: none;
  padding: 10px 20px;
  font-size: 16px;
  border-radius: 5px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.btn-cancel:hover {
  background-color: #d32f2f; /* Daha koyu kırmızı hover efekti */
}
    </style>
</head>

<body>
    <div class="container">
        <div class="row g-4 justify-content-center">

            <!-- Sol taraf: Kullanıcı bilgileri kartı -->
            <div class="col-lg-5 col-md-6">
                <div class="profile-card">
        <img src="<?= $profileImage ?>" alt="Profil Resmi" class="profile-img" />
                    
                    <h1 class="user-name"><?= htmlspecialchars($user['name']) ?></h1>
                    <div class="user-role">Rol: <?= $roleDisplay ?></div>

                    <p class="biography"><?= htmlspecialchars($user['biography']) ?></p>

                    <h3 class="section-title">Beğenilen Makaleler</h3>
                    <?php if (count($likedArticles) === 0): ?>
                        <p>Henüz beğenilen makale yok.</p>
                    <?php else: ?>
                        <?php foreach ($likedArticles as $article): 
                            $articleImg = getImageSrc("../uploads/", $article['resim'], "https://via.placeholder.com/80x55?text=No+Image");
                            $articleDate = formatDate($article['yayin_tarihi']);
                        ?>
                            <a href="../makale.php?id=<?= $article['id'] ?>" class="liked-article-item" title="<?= htmlspecialchars($article['baslik']) ?>">
                                <img src="<?= $articleImg ?>" alt="Makale Resmi" class="liked-article-img" />
                                <span class="liked-article-title"><?= htmlspecialchars($article['baslik']) ?></span>
                                <span class="liked-article-date"><?= $articleDate ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <h3 class="section-title mt-5">Yorumlar</h3>
                    <?php if (count($userComments) === 0): ?>
                        <p>Henüz yorum yapılmamış.</p>
                    <?php else: ?>
                        <?php foreach ($userComments as $comment): 
                            $commentDate = formatDate($comment['tarih']);
                        ?>
                            <div class="liked-article-item" style="background: #f8f9fa;">
                                <p title="<?= htmlspecialchars($comment['yorum_metni']) ?>"><?= mb_strimwidth(htmlspecialchars($comment['yorum_metni']), 0, 70, '...') ?></p>
                                <a href="../makale.php?id=<?= $comment['makale_id'] ?>" class="liked-article-title" style="flex-shrink: 0; margin-left: 10px; font-weight: 600;">
                                    <?= htmlspecialchars($comment['baslik']) ?>
                                </a>
                                <span class="liked-article-date"><?= $commentDate ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sağ taraf: Profil güncelleme formu -->
            <div class="col-lg-6 col-md-6">
                <div class="profile-card">
                    <h2 class="mb-4" style="color:#0d6efd; font-weight: 700;">Profil Güncelle</h2>
                    <?php if ($mesaj): ?>
                        <div class="alert alert-info"><?= htmlspecialchars($mesaj) ?></div>
                    <?php endif; ?>
<form method="post" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="id" value="<?= $user_id ?>" />
                        <div class="mb-3">
                            <label for="name" class="form-label">İsim</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required />
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-posta</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required />
                        </div>
                        <div class="mb-3">
    <label for="password" class="form-label">Yeni Şifre (değiştirmek için doldurun)</label>
    <input type="password" id="password" name="password" class="form-control" placeholder="Yeni şifre">
</div>

                        <div class="mb-3">
                            <label for="biography" class="form-label">Hakkında (Biyografi)</label>
                            <textarea class="form-control" id="biography" name="biography" rows="4" maxlength="50"><?= htmlspecialchars($user['biography']) ?></textarea>
                            <div class="form-text">Maksimum 50 karakter</div>
                        </div>
                        <div class="mb-4">
                            <label for="profil_resmi" class="form-label">Profil Resmi (Opsiyonel)</label>
                            <input type="file" class="form-control" id="profil_resmi" name="profil_resmi" accept="image/*" />
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Güncelle</button>
                        
                    </form>
                    <br>
<button class="btn-cancel" onclick="window.location.href='../index.php';">Tamamlandı Olarak İşaretle</button>

                </div>
            </div>

        </div>
    </div>
</body>
</html>
