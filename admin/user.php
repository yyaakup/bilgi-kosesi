<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "../includes/db.php";

$mesaj = "";
$biography = isset($_POST['biography']) ? trim($_POST['biography']) : "";

// Giriş kontrolü
if (isset($_SESSION['admin_id'])) {
    $user_id = $_SESSION['admin_id'];
    $user_type = 'admin';
} elseif (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_type = 'user';
} else {
    if (file_exists('login.php')) {
        header("Location: login.php");
    } elseif (file_exists('giris.php')) {
        header("Location: giris.php");
    } else {
        echo "Giriş sayfası bulunamadı!";
    }
    exit;
}

// Kullanıcı bilgilerini çek
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Kullanıcı bulunamadı.";
    exit;
}

// Form gönderildiyse
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $isim = trim($_POST['isim']);
    $email = trim($_POST['email']);
    $sifre = $_POST['sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];

    // Biyografi kontrol
    if (strlen($biography) > 150) {
        $mesaj = "Biyografi en fazla 150 karakter olmalı.";
    } elseif (function_exists('argo_var_mi') && argo_var_mi($biography)) {
        $mesaj = "Biyografi uygunsuz kelimeler içeriyor.";
    }

    // Profil resmi kaldırma isteği var mı?
    if (isset($_POST['profil_resmi_kaldir']) && $_POST['profil_resmi_kaldir'] == 'on') {
        if ($user['profil_resmi'] && file_exists("../uploads/profil/" . $user['profil_resmi'])) {
            unlink("../uploads/profil/" . $user['profil_resmi']);
        }
        $profil_resmi = null;
    } else {
        $profil_resmi = $user['profil_resmi'];
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

    // Şifre kontrolü
    if ($mesaj === "" && $sifre !== '') {
        if ($sifre !== $sifre_tekrar) {
            $mesaj = "Şifreler eşleşmiyor.";
        } elseif (strlen($sifre) < 6) {
            $mesaj = "Şifre en az 6 karakter olmalı.";
        } else {
            $hashed_sifre = password_hash($sifre, PASSWORD_DEFAULT);
        }
    }

    // Güncelleme işlemi
    if ($mesaj === "") {
        if ($sifre !== '') {
            $stmt = $pdo->prepare("UPDATE users SET isim = ?, name = ?, email = ?, sifre = ?, profil_resmi = ?, biography = ? WHERE id = ?");
            $stmt->execute([$isim, $isim, $email, $hashed_sifre, $profil_resmi, $biography, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET isim = ?, name = ?, email = ?, profil_resmi = ?, biography = ? WHERE id = ?");
            $stmt->execute([$isim, $isim, $email, $profil_resmi, $biography, $user_id]);
        }

        $mesaj = "Bilgileriniz başarıyla güncellendi.";

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Kullanıcının beğendiği makaleler
$stmtLikes = $pdo->prepare("
    SELECT m.id, m.baslik, m.resim, k.ad AS kategori_adi
    FROM makale_begen mb
    JOIN makaleler m ON mb.makale_id = m.id
    LEFT JOIN kategoriler k ON m.kategori_id = k.id
    WHERE mb.kullanici_id = ?
    ORDER BY mb.id DESC
    LIMIT 5
");
$stmtLikes->execute([$user_id]);
$likedArticles = $stmtLikes->fetchAll(PDO::FETCH_ASSOC);

// Kullanıcının yaptığı yorumlar
$stmtComments = $pdo->prepare("
    SELECT y.yorum_metni, y.tarih, m.id AS makale_id, m.baslik, m.resim
    FROM yorumlar y
    JOIN makaleler m ON y.makale_id = m.id
    WHERE y.kullanici_id = ?
    ORDER BY y.tarih DESC
    LIMIT 5
");
$stmtComments->execute([$user_id]);
$userComments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";

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

$sqlUser = "SELECT id, name, role, profil_resmi, biography FROM users WHERE id = ?";
$stmtUser = $pdo->prepare($sqlUser);
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Kullanıcı bulunamadı.');
}

$roleDisplay = getRoleDisplay($user['role']);

$sqlLikes = "SELECT m.id, m.baslik, m.yayin_tarihi, m.resim FROM makaleler m
             INNER JOIN makale_begen mb ON m.id = mb.makale_id
             WHERE mb.kullanici_id = ? ORDER BY mb.id DESC LIMIT 10";
$stmtLikes = $pdo->prepare($sqlLikes);
$stmtLikes->execute([$user_id]);
$likedArticles = $stmtLikes->fetchAll(PDO::FETCH_ASSOC);

$sqlComments = "SELECT y.id, y.yorum_metni, y.tarih, m.baslik, m.id AS makale_id
                FROM yorumlar y
                INNER JOIN makaleler m ON y.makale_id = m.id
                WHERE y.kullanici_id = ?
                ORDER BY y.id DESC LIMIT 10";
$stmtComments = $pdo->prepare($sqlComments);
$stmtComments->execute([$user_id]);
$userComments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);

$profileImage = getImageSrc("../uploads/profil/", $user['profil_resmi'], "https://via.placeholder.com/150?text=Profil");
?>
<style>
.profile-card {
            background: #ffffffdd;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            padding: 3rem 3.5rem;
            max-width: 600px;
            width: 100%;
            text-align: center;
            transition: box-shadow 0.3s ease;
        }

        .profile-card:hover {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
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
        }

        .section-title {
            font-weight: 700;
            font-size: 1.7rem;
            color: #0d6efd;
            border-bottom: 4px solid #0d6efd;
            padding-bottom: 0.4rem;
            margin-bottom: 2rem;
            letter-spacing: 0.07em;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
        }

        .liked-article-item {
            background: #e6f0ff;
            border-radius: 12px;
            margin-bottom: 1.3rem;
            padding: 1rem 1.3rem;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.15);
            transition: background-color 0.3s ease;
            text-align: left;
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
        }

        .liked-article-item:hover {
            background-color: #cce0ff;
        }

        .liked-article-img {
            width: 80px;
            height: 55px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #0d6efd;
            margin-right: 20px;
            flex-shrink: 0;
            box-shadow: 0 3px 8px rgba(13, 110, 253, 0.3);
            transition: transform 0.3s ease;
        }

        .liked-article-item:hover .liked-article-img {
            transform: scale(1.15);
        }

        .liked-article-title {
            font-weight: 600;
            color: #0a58ca;
            flex-grow: 1;
            font-size: 1.15rem;
            text-decoration: none;
            user-select: text;
        }

        .liked-article-title:hover {
            text-decoration: underline;
            color: #084298;
        }

        .liked-article-date {
            font-size: 0.9rem;
            color: #6c757d;
            white-space: nowrap;
            margin-left: 15px;
            flex-shrink: 0;
        }

        @media (max-width: 575.98px) {
            .profile-card {
                padding: 2rem 1.5rem;
                margin: 1.5rem;
            }
            .profile-img {
                width: 140px;
                height: 140px;
            }
            .user-name {
                font-size: 2rem;
            }
            .user-role {
                font-size: 1.1rem;
            }
            .section-title {
                font-size: 1.4rem;
            }
            .liked-article-img {
                width: 60px;
                height: 42px;
                margin-right: 15px;
            }
            .liked-article-title {
                font-size: 1rem;
            }
            .liked-article-date {
                margin-left: 8px;
            }
        }
        .liked-article-item p {
    font-size: 1rem;
    color: #444;
}
</style>
<div class="container mt-5">
    <div class="row gy-4">
        <!-- Sol taraf: Kullanıcı bilgileri, beğenilen makaleler ve yorumlar -->
       <!-- Sol kısım: profile.php içeriği -->
        <div class="col-lg-6">
<div class="profile-card">
        <img src="<?= $profileImage ?>" alt="Profil Resmi" class="profile-img" />
        
        <h1 class="user-name"><?= htmlspecialchars($user['name']) ?></h1>
        <div class="user-role">Rol: <?= htmlspecialchars($roleDisplay) ?></div>

        <section class="mb-5">
            <h2 class="section-title">Biyografi</h2>
            <?php if (trim($user['biography']) !== ''): ?>
                <p class="biography"><?= nl2br(htmlspecialchars($user['biography'])) ?></p>
            <?php else: ?>
                <p class="text-muted fst-italic">Biyografi eklenmemiş.</p>
            <?php endif; ?>
        </section>

        <section>
            <h2 class="section-title">Beğendiği Makaleler</h2>
            <?php if ($likedArticles): ?>
                <?php foreach ($likedArticles as $article):
                    $articleImg = getImageSrc("../uploads/", $article['resim'], "https://via.placeholder.com/80x55?text=Makale");
                ?>
                    <a href="makale.php?id=<?= (int)$article['id'] ?>" class="liked-article-item text-decoration-none">
                        <img src="<?= $articleImg ?>" alt="Makale Resmi" class="liked-article-img" />
                        <span class="liked-article-title"><?= htmlspecialchars($article['baslik']) ?></span>
                        <time datetime="<?= htmlspecialchars($article['yayin_tarihi']) ?>" class="liked-article-date"><?= formatDate($article['yayin_tarihi']) ?></time>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted fst-italic">Henüz beğenilen makale yok.</p>
            <?php endif; ?>
            
  <h2 class="section-title">Yorumlar</h2>
    <?php if ($userComments): ?>
        <?php foreach ($userComments as $comment): ?>
            <div class="liked-article-item">
                <div style="flex-grow: 1;">
                    <div><strong>
                        <a href="makale.php?id=<?= $comment['makale_id'] ?>" class="liked-article-title">
                            <?= htmlspecialchars($comment['baslik']) ?>
                        </a></strong></div>
                    <div class="mt-1 text-muted" style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($comment['yorum_metni'])) ?></div>
                    <small class="text-secondary"><?= formatDate($comment['tarih']) ?></small>
                </div>
                <a href="../makale.php?id=<?= $comment['makale_id'] ?>" class="btn btn-sm btn-primary">
    Makale Sayfasına Git
</a>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
   <?php else: ?>
                <p class="text-muted fst-italic">Henüz beğenilen makale bulunmamaktadır.</p>
            <?php endif; ?>
        </section>
    </div>
        
        </div>


  



    </div>
</div>

<?php include "../includes/footer.php"; ?>
