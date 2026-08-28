<?php
/**
 * includes/navbar.php
 * Navbar partagée — toutes les pages publiques
 * Avant d'inclure : définir $navActive = 'accueil'|'services'|'packages'|'galerie'|'blog'|'apropos'|'contact'
 */
$navActive = $navActive ?? '';
$isAdminConnected = function_exists('isAdmin') && isAdmin();
$inPages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$root    = $inPages ? '../' : '';
$base    = $inPages ? '' : 'pages/';
?>
<header id="header">
  <nav class="navbar">
    <div class="nav-logo">
      <a href="<?= $root ?>index.php">
        <div class="logo-text">
          <span class="logo-traiteur">TRAITEUR</span>
          <span class="logo-name">EL MOUSSAOUI</span>
          <span class="logo-sub">أفراح المساوي</span>
        </div>
      </a>
    </div>
    <ul class="nav-links" id="navLinks">
      <li><a href="<?= $root ?>index.php" class="nav-link <?= $navActive==='accueil'?'active':'' ?>" data-fr="Accueil" data-ar="الرئيسية">Accueil</a></li>
      <li><a href="<?= $base ?>services.php" class="nav-link <?= $navActive==='services'?'active':'' ?>" data-fr="Services" data-ar="خدماتنا">Services</a></li>
      <li><a href="<?= $base ?>packages.php" class="nav-link <?= $navActive==='packages'?'active':'' ?>" data-fr="Packages" data-ar="الباقات">Packages</a></li>
      <li><a href="<?= $base ?>galerie.php" class="nav-link <?= $navActive==='galerie'?'active':'' ?>" data-fr="Galerie" data-ar="معرض الصور">Galerie</a></li>
      <li><a href="<?= $base ?>blog.php" class="nav-link <?= $navActive==='blog'?'active':'' ?>" data-fr="Blog" data-ar="المقالات">Blog</a></li>
      <li><a href="<?= $base ?>apropos.php" class="nav-link <?= $navActive==='apropos'?'active':'' ?>" data-fr="À Propos" data-ar="من نحن">À Propos</a></li>
      <li><a href="<?= $base ?>contact.php" class="nav-link <?= $navActive==='contact'?'active':'' ?>" data-fr="Contact" data-ar="اتصل بنا">Contact</a></li>
    </ul>
    <div class="nav-actions">
      <a href="tel:0626986533" class="nav-tel">
        <i class="fab fa-whatsapp"></i><span dir="ltr">0626 986 533</span>
      </a>
      <a href="<?= $base ?>reservation.php" class="btn-reservation" data-fr="Devis gratuit" data-ar="عرض مجاني" data-html>
        Devis gratuit <i class="fas fa-file-invoice"></i>
      </a>
      <!-- ✅ Icône admin — toutes les pages -->
      <?php if ($isAdminConnected): ?>
      <a href="<?= $root ?>admin/dashboard.php"
         class="nav-admin-icon connected"
         title="Panneau Admin — connecté">
        <i class="fas fa-user-shield"></i>
      </a>
      <?php else: ?>
      <a href="<?= $root ?>admin/login.php"
         class="nav-admin-icon disconnected"
         title="Connexion Admin">
        <i class="fas fa-user-lock"></i>
      </a>
      <?php endif; ?>
      <div class="lang-switch">
        <span class="lang-option active" data-lang="fr">FR</span>
        <span class="lang-option" data-lang="ar">AR</span>
      </div>
      <button class="nav-toggle" id="navToggle">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>
</header>