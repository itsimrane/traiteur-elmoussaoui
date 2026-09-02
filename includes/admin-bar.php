<?php
/**
 * includes/admin-bar.php
 * Inclus automatiquement en bas de chaque page publique.
 * Charge la barre admin et le script d'édition inline si connecté.
 */
if (!isAdmin()) return; // Ne rien afficher si pas admin

// Déterminer la zone selon l'URL courante
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$zone = match($currentPage) {
    'index'       => 'accueil',
    'galerie'     => 'galerie',
    'services'    => 'services',
    'packages'    => 'packages',
    'apropos'     => 'apropos',
    'contact'     => 'contact',
    'blog'        => 'blog',
    default       => 'galerie',
};

// Détecter le chemin relatif vers la racine
$depth = substr_count($_SERVER['PHP_SELF'], '/') - 2;
$root  = str_repeat('../', max(0, $depth));
?>
<script>
  window.INLINE_UPLOAD_URL   = '<?= rtrim(SITE_URL, '/') ?>/api/inline_upload.php';
  window.INLINE_DELETE_URL   = '<?= rtrim(SITE_URL, '/') ?>/api/delete_media.php';
  window.INLINE_ZONE         = '<?= $zone ?>';
  window.ADMIN_DASHBOARD_URL = '<?= rtrim(SITE_URL, '/') ?>/admin/dashboard.php';
  window.ADMIN_LOGOUT_URL    = '<?= rtrim(SITE_URL, '/') ?>/admin/logout.php';
</script>
<?php
$adminInlinePath = __DIR__ . '/../js/admin-inline.js';
$adminInlineV = file_exists($adminInlinePath) ? filemtime($adminInlinePath) : time();
?>
<script src="<?= $root ?>js/admin-inline.js?v=<?= $adminInlineV ?>"></script>