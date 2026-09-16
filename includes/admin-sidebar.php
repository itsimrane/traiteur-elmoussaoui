<?php
if (!defined('ADMIN_SIDEBAR_LOADED')) define('ADMIN_SIDEBAR_LOADED', true);
$activePage = $activePage ?? '';

$badgeMessages = $badgeNotifs = $badgeDevis = 0;
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notifications_masquees` (
        `notif_id` VARCHAR(50) NOT NULL,
        `masque_le` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`notif_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $badgeMessages = $pdo->query("
        SELECT COUNT(*) FROM contacts c WHERE c.statut='nouveau'
          AND NOT EXISTS (SELECT 1 FROM notifications_masquees m WHERE m.notif_id = CONCAT('msg_', c.id))
    ")->fetchColumn();
    $badgeDevis = $pdo->query("
        SELECT COUNT(*) FROM reservations r WHERE r.statut='en_attente'
          AND NOT EXISTS (SELECT 1 FROM notifications_masquees m WHERE m.notif_id = CONCAT('resa_', r.id))
    ")->fetchColumn();
    $badgeNotifs = (int)$badgeMessages + (int)$badgeDevis;
} catch(Exception $e) {}

$adminNom   = $_SESSION['admin_nom']   ?? 'Admin';
$adminEmail = $_SESSION['admin_email'] ?? 'admin@traiteur-elmoussaoui.ma';
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header" style="position:relative">
    <button class="theme-toggle" id="themeToggleAdmin" title="Changer de thème" style="position:absolute;top:10px;right:10px;width:32px;height:32px;font-size:.8rem">
      <i class="fas fa-moon"></i>
      <i class="fas fa-sun"></i>
    </button>
    <div class="logo-text" style="display:flex;flex-direction:column;align-items:center">
      <span style="font-size:.55rem;letter-spacing:4px;color:var(--text-muted)">TRAITEUR</span>
      <span class="logo-name" style="font-size:1.1rem">EL MOUSSAOUI</span>
      <span style="font-size:.65rem;color:var(--text-muted)"><?= t('admin_panel') ?> v1.0</span>
    </div>
    <?php $currentUrl = urlencode(basename($_SERVER['PHP_SELF'] ?? 'dashboard.php') . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')); ?>
    <div class="lang-switch" style="margin:12px auto 0;width:fit-content">
      <a href="set_lang.php?lang=fr&redirect=<?= $currentUrl ?>"
         class="lang-option <?= adminLang()==='fr'?'active':'' ?>" style="text-decoration:none;display:inline-block">FR</a>
      <a href="set_lang.php?lang=ar&redirect=<?= $currentUrl ?>"
         class="lang-option <?= adminLang()==='ar'?'active':'' ?>" style="text-decoration:none;display:inline-block">العربية</a>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-label"><?= t('sidebar_principal') ?></div>
    <a href="dashboard.php" class="sidebar-link <?= $activePage==='dashboard'?'active':'' ?>">
      <i class="fas fa-tachometer-alt"></i>
      <span><?= t('dashboard') ?></span>
    </a>
    <a href="reservations.php" class="sidebar-link <?= $activePage==='reservations'?'active':'' ?>">
      <i class="fas fa-calendar-check"></i>
      <span><?= t('reservations') ?></span>
      <?php if ($badgeDevis > 0): ?><span class="sidebar-badge"><?= $badgeDevis ?></span><?php endif; ?>
    </a>
    <a href="devis.php" class="sidebar-link <?= $activePage==='devis'?'active':'' ?>">
      <i class="fas fa-file-invoice"></i>
      <span><?= t('devis') ?></span>
    </a>
    <a href="clients.php" class="sidebar-link <?= $activePage==='clients'?'active':'' ?>">
      <i class="fas fa-users"></i>
      <span><?= t('clients') ?></span>
    </a>
    <a href="factures.php" class="sidebar-link <?= $activePage==='factures'?'active':'' ?>">
      <i class="fas fa-receipt"></i>
      <span><?= t('factures') ?></span>
    </a>
    <a href="paiements.php" class="sidebar-link <?= $activePage==='paiements'?'active':'' ?>">
      <i class="fas fa-credit-card"></i>
      <span><?= t('paiements') ?></span>
    </a>

    <div class="sidebar-label" style="margin-top:8px"><?= t('sidebar_contenu') ?></div>
    <a href="services-admin.php" class="sidebar-link <?= $activePage==='services'?'active':'' ?>">
      <i class="fas fa-concierge-bell"></i>
      <span><?= t('services') ?></span>
    </a>
    <a href="packages-admin.php" class="sidebar-link <?= $activePage==='packages'?'active':'' ?>">
      <i class="fas fa-box-open"></i>
      <span><?= t('packages') ?></span>
    </a>
    <a href="tentes.php" class="sidebar-link <?= $activePage==='tentes'?'active':'' ?>">
      <i class="fas fa-campground"></i>
      <span><?= t('tentes') ?></span>
    </a>
    <a href="types-evenements-admin.php" class="sidebar-link <?= $activePage==='types-evenements'?'active':'' ?>">
      <i class="fas fa-users-cog"></i>
      <span><?= t('capacite_evenements') ?></span>
    </a>
    <a href="galerie.php" class="sidebar-link <?= $activePage==='galerie'?'active':'' ?>">
      <i class="fas fa-images"></i>
      <span><?= t('galerie') ?></span>
    </a>
    <a href="blog-admin.php" class="sidebar-link <?= $activePage==='blog'?'active':'' ?>">
      <i class="fas fa-pen-nib"></i>
      <span><?= t('blog') ?></span>
    </a>
    <a href="temoignages-admin.php" class="sidebar-link <?= $activePage==='temoignages'?'active':'' ?>">
      <i class="fas fa-star"></i>
      <span><?= t('temoignages') ?></span>
    </a>
    <a href="apropos-admin.php" class="sidebar-link <?= $activePage==='apropos'?'active':'' ?>">
      <i class="fas fa-address-card"></i>
      <span><?= t('contenu_apropos') ?></span>
    </a>

    <div class="sidebar-label" style="margin-top:8px"><?= t('sidebar_communication') ?></div>
    <a href="messages.php" class="sidebar-link <?= $activePage==='messages'?'active':'' ?>">
      <i class="fas fa-envelope"></i>
      <span><?= t('messages') ?></span>
      <?php if ($badgeMessages > 0): ?><span class="sidebar-badge"><?= $badgeMessages ?></span><?php endif; ?>
    </a>
    <a href="notifications.php" class="sidebar-link <?= $activePage==='notifications'?'active':'' ?>">
      <i class="fas fa-bell"></i>
      <span><?= t('notifications') ?></span>
      <?php if ($badgeNotifs > 0): ?><span class="sidebar-badge"><?= $badgeNotifs ?></span><?php endif; ?>
    </a>

    <div class="sidebar-label" style="margin-top:8px"><?= t('sidebar_systeme') ?></div>
    <a href="utilisateurs.php" class="sidebar-link <?= $activePage==='utilisateurs'?'active':'' ?>">
      <i class="fas fa-user-shield"></i>
      <span><?= t('utilisateurs') ?></span>
    </a>
    <a href="parametres.php" class="sidebar-link <?= $activePage==='parametres'?'active':'' ?>">
      <i class="fas fa-cog"></i>
      <span><?= t('parametres') ?></span>
    </a>
    <a href="logs.php" class="sidebar-link <?= $activePage==='logs'?'active':'' ?>">
      <i class="fas fa-history"></i>
      <span><?= t('journaux') ?></span>
    </a>
  </nav>

  <div class="sidebar-footer">
    <div style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:10px;background:var(--dark-3)">
      <div class="admin-avatar" style="width:34px;height:34px;border-radius:8px;font-size:.85rem"><?= strtoupper(substr($adminNom,0,1)) ?></div>
      <div style="flex:1;min-width:0">
        <div style="font-size:.82rem;color:var(--white);font-weight:500"><?= htmlspecialchars($adminNom) ?></div>
        <div style="font-size:.7rem;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($adminEmail) ?></div>
      </div>
      <a href="logout.php" title="<?= t('deconnexion') ?>"
         style="color:var(--text-muted);font-size:.85rem;padding:4px;border-radius:6px;transition:var(--transition)"
         onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--text-muted)'">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </div>
</aside>
<script>
  (function () {
    var saved = localStorage.getItem('theme') || 'dark';
    if (saved === 'light') document.documentElement.setAttribute('data-theme', 'light');
  })();
  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('themeToggleAdmin');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var isLight = document.documentElement.getAttribute('data-theme') === 'light';
      if (isLight) {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('theme', 'dark');
      } else {
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
      }
    });
  });
</script>
