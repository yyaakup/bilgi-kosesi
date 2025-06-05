<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$profil_resmi = $_SESSION['user_profil_resmi'] ?? '';
$profil_path = '/article_web_site_files/uploads/profil/' . $profil_resmi;
$is_logged_in = isset($_SESSION['user_id']);
$user_is_admin = ($_SESSION['user_role'] ?? '') === 'yazar';
$user_name = $_SESSION['user_name'] ?? '';

if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $profil_path) || !$profil_resmi) {
    $profil_path = '';
}


?>
<head>

    <meta charset="UTF-8">
    <title>Bilgi Köşesi</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/article_web_site_files/assets/css/style.css">
    <style>
        .profil-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-left: 10px;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .profil-initial {
            width: 40px;
            height: 40px;
            background-color: #6c757d;
            color: white;
            font-weight: bold;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-left: 10px;
            cursor: pointer;
            user-select: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .profil-img:hover, .profil-initial:hover {
            transform: scale(1.15);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        .settings-icon {
            font-size: 24px;
            color: white;
            cursor: pointer;
            margin-left: 15px;
        }
        .nav-profile-dropdown {
            position: relative;
            display: inline-block;
        }
        .nav-profile-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #343a40;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            border-radius: 5px;
            z-index: 1000;
        }
        .nav-profile-dropdown-content a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        .nav-profile-dropdown-content a:hover {
            background-color: #495057;
        }
        .nav-profile-dropdown:hover .nav-profile-dropdown-content {
            display: block;
        }
        .settings-icon i {
            font-size: 18px; /* İstediğin boyutu ayarla, mesela 18px */
        }

        /* Menü linklerinin beyaz olması için */
        .navbar-dark .navbar-nav .nav-link {
            color: white;
        }
        .navbar-dark .navbar-nav .nav-link:hover,
        .navbar-dark .navbar-nav .nav-link:focus {
            color: #adb5bd; /* hafif açık gri hover efekti */
        }

        /* Dropdown menü linklerinin beyaz olması */
        .dropdown-menu {
            background-color: #343a40;
        }
        .dropdown-menu .dropdown-item {
            color: white;
        }
        .dropdown-menu .dropdown-item:hover,
        .dropdown-menu .dropdown-item:focus {
            background-color: #495057;
            color: white;
        }

        /* Logo resminin yüksekliği ve margin */
        .navbar-brand img {
            height: 60px;
            margin-right: 10px;
        }

        
    </style>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="/article_web_site_files/index.php">
      <img src="https://i.hizliresim.com/bzyvuht.png" alt="Bilgi Köşesi Logo">
	  
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

<div class="collapse navbar-collapse" id="navbarNav">
  <ul class="navbar-nav mx-auto align-items-center justify-content-center">
    <li class="nav-item">
      <a class="nav-link" href="/article_web_site_files/index.php">
        <i class="bi bi-house-door-fill me-1"></i> Anasayfa
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/article_web_site_files/one_cikanlar.php">
        <i class="bi bi-star-fill me-1"></i> Öne Çıkanlar
      </a>
    </li>
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
        <i class="bi bi-file-earmark-text-fill me-1"></i> Makaleler
      </a>
      <ul class="dropdown-menu">
     <li><a class="dropdown-item" href="/article_web_site_files/kategori.php?kategori=bilim"><i class="bi bi-clipboard-data me-1"></i> Bilim
</a></li>
<li><a class="dropdown-item" href="/article_web_site_files/kategori.php?kategori=teknoloji"><i class="bi bi-cpu me-1"></i> Teknoloji</a></li>
<li><a class="dropdown-item" href="/article_web_site_files/kategori.php?kategori=yapay zeka"><i class="bi bi-robot me-1"></i> Yapay Zeka</a></li>
<li><a class="dropdown-item" href="/article_web_site_files/kategori.php?kategori=yazılım"><i class="bi bi-code-slash me-1"></i> Yazılım</a></li>
<li><a class="dropdown-item" href="/article_web_site_files/kategori.php?kategori=uzay"><i class="bi bi-rocket-fill me-1"></i> Uzay</a></li>
<li><a class="dropdown-item" href="/article_web_site_files/kategori.php?kategori=siyaset"><i class="bi bi-people me-1"></i> Siyaset</a></li>
<li><a class="dropdown-item" href="/article_web_site_files/kategori.php?kategori=edebiyat"><i class="bi bi-journal-text me-1"></i> Edebiyat</a></li>
<li><a class="dropdown-item" href="/article_web_site_files/kategori.php?kategori=felsefe"><i class="bi bi-book me-1"></i> Felsefe</a></li>
<li><a class="dropdown-item" href="/article_web_site_files/kategori.php?kategori=eğitim"><i class="bi bi-mortarboard me-1"></i> Eğitim</a></li>
<li><a class="dropdown-item" href="/article_web_site_files/kategori.php?kategori=din"><i class="bi bi-brightness-high me-1"></i> Din</a></li>
<li><a class="dropdown-item" href="/article_web_site_files/kategori.php?kategori=doğa"><i class="bi bi-tree me-1"></i> Doğa</a></li>
<li><a class="dropdown-item" href="/article_web_site_files/kategori.php?kategori=spor"><i class="bi bi-bicycle me-1"></i> Spor</a></li>

      </ul>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/article_web_site_files/hakkimizda.php">
        <i class="bi bi-info-circle-fill me-1"></i> Hakkımızda
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/article_web_site_files/iletisim.php">
        <i class="bi bi-envelope-fill me-1"></i> İletişim
      </a>
    </li>
    <form class="d-flex ms-3 position-relative" role="search" method="GET" action="/article_web_site_files/arama.php" autocomplete="off" style="width: 300px;">
  <input id="searchInput" class="form-control form-control-sm me-2" type="search" name="q" placeholder="Ara..." aria-label="Ara" />
  <div id="searchResults" class="list-group position-absolute" style="top: 38px; left: 0; right: 0; z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
</form>

  </ul>

  <ul class="navbar-nav align-items-center">
    <?php if (!$is_logged_in): ?>
      <li class="nav-item"><a class="nav-link" href="/article_web_site_files/kayit.php"><i class="bi bi-person-plus-fill me-1"></i> Kayıt Ol</a></li>
      <li class="nav-item"><a class="nav-link" href="/article_web_site_files/giris.php"><i class="bi bi-box-arrow-in-right me-1"></i> Giriş Yap</a></li>
    <?php else: ?>
      <?php if ($user_is_admin): ?>
        <li class="nav-item">
          <a class="nav-link text-warning fw-bold" href="/article_web_site_files/admin/index.php"><i class="bi bi-speedometer2 me-1"></i> Admin Panel</a>
        </li>
      <?php endif; ?>

     <li class="nav-item nav-profile-dropdown d-flex align-items-center">
  <a href="admin/users.php" class="d-flex align-items-center text-decoration-none">
    <?php if ($profil_path): ?>
      <img src="<?= htmlspecialchars($profil_path) ?>" alt="Profil Resmi" class="profil-img" title="<?= htmlspecialchars($user_name) ?>">
    <?php else: ?>
      <div class="profil-initial" title="<?= htmlspecialchars($user_name) ?>">
        <?= mb_substr($user_name, 0, 1, 'UTF-8') ?: '?' ?>
      </div>
    <?php endif; ?>
  </a>
</li>

        <i class="bi bi-sliders"></i>
      </a>
      <li class="nav-item">
        <a class="nav-link" href="/article_web_site_files/cikis.php"><i class="bi bi-box-arrow-right me-1"></i> Çıkış Yap</a>
      </li>
    <?php endif; ?>
  </ul>
</div>

  </div>
</nav>

<div class="container mt-4">
<!-- İçerik buraya -->
<script>
  const input = document.getElementById('searchInput');
  const resultsContainer = document.getElementById('searchResults');

  input.addEventListener('input', () => {
    const query = input.value.trim();

    if (query.length === 0) {
      resultsContainer.innerHTML = '';
      resultsContainer.style.display = 'none';
      return;
    }

    fetch(`/article_web_site_files/arama.php?q=${encodeURIComponent(query)}`)
      .then(response => response.json())
      .then(data => {
        if (!Array.isArray(data) || data.length === 0) {
          resultsContainer.innerHTML = '<div class="list-group-item">Makale bulunamadı</div>';
          resultsContainer.style.display = 'block';
          return;
        }

        resultsContainer.innerHTML = data.map(item => 
          `<a href="/article_web_site_files/makale.php?id=${item.id}" class="list-group-item list-group-item-action">${item.baslik}</a>`
        ).join('');
        resultsContainer.style.display = 'block';
      })
      .catch(() => {
        resultsContainer.innerHTML = '<div class="list-group-item text-danger">Arama yapılamadı</div>';
        resultsContainer.style.display = 'block';
      });
  });

  // Input ve sonuçlar dışına tıklayınca sonuçları gizle
  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !resultsContainer.contains(e.target)) {
      resultsContainer.style.display = 'none';
    }
  });
</script>