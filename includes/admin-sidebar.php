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
      <span style="font-size:.65rem;color:var(--text-muted)">Admin Panel v1.0</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-label" data-fr="PRINCIPAL" data-ar="الرئيسية">PRINCIPAL</div>
    <a href="dashboard.php" class="sidebar-link <?= $activePage==='dashboard'?'active':'' ?>">
      <i class="fas fa-tachometer-alt"></i>
      <span data-fr="Tableau de bord" data-ar="لوحة التحكم">Tableau de bord</span>
    </a>
    <a href="reservations.php" class="sidebar-link <?= $activePage==='reservations'?'active':'' ?>">
      <i class="fas fa-calendar-check"></i>
      <span data-fr="Réservations" data-ar="الحجوزات">Réservations</span>
      <?php if ($badgeDevis > 0): ?><span class="sidebar-badge"><?= $badgeDevis ?></span><?php endif; ?>
    </a>
    <a href="devis.php" class="sidebar-link <?= $activePage==='devis'?'active':'' ?>">
      <i class="fas fa-file-invoice"></i>
      <span data-fr="Devis" data-ar="عروض الأسعار">Devis</span>
    </a>
    <a href="clients.php" class="sidebar-link <?= $activePage==='clients'?'active':'' ?>">
      <i class="fas fa-users"></i>
      <span data-fr="Clients" data-ar="العملاء">Clients</span>
    </a>
    <a href="factures.php" class="sidebar-link <?= $activePage==='factures'?'active':'' ?>">
      <i class="fas fa-receipt"></i>
      <span data-fr="Factures" data-ar="الفواتير">Factures</span>
    </a>
    <a href="paiements.php" class="sidebar-link <?= $activePage==='paiements'?'active':'' ?>">
      <i class="fas fa-credit-card"></i>
      <span data-fr="Paiements" data-ar="المدفوعات">Paiements</span>
    </a>

    <div class="sidebar-label" style="margin-top:8px" data-fr="CONTENU" data-ar="المحتوى">CONTENU</div>
    <a href="services-admin.php" class="sidebar-link <?= $activePage==='services'?'active':'' ?>">
      <i class="fas fa-concierge-bell"></i>
      <span data-fr="Services" data-ar="الخدمات">Services</span>
    </a>
    <a href="packages-admin.php" class="sidebar-link <?= $activePage==='packages'?'active':'' ?>">
      <i class="fas fa-box-open"></i>
      <span data-fr="Packages" data-ar="الباقات">Packages</span>
    </a>
    <a href="types-evenements-admin.php" class="sidebar-link <?= $activePage==='types-evenements'?'active':'' ?>">
      <i class="fas fa-users-cog"></i>
      <span data-fr="Capacité événements" data-ar="سعة المناسبات">Capacité événements</span>
    </a>
    <a href="galerie.php" class="sidebar-link <?= $activePage==='galerie'?'active':'' ?>">
      <i class="fas fa-images"></i>
      <span data-fr="Galerie" data-ar="معرض الصور">Galerie</span>
    </a>
    <a href="blog-admin.php" class="sidebar-link <?= $activePage==='blog'?'active':'' ?>">
      <i class="fas fa-pen-nib"></i>
      <span data-fr="Blog" data-ar="المدونة">Blog</span>
    </a>
    <a href="temoignages-admin.php" class="sidebar-link <?= $activePage==='temoignages'?'active':'' ?>">
      <i class="fas fa-star"></i>
      <span data-fr="Témoignages" data-ar="الشهادات">Témoignages</span>
    </a>
    <a href="apropos-admin.php" class="sidebar-link <?= $activePage==='apropos'?'active':'' ?>">
      <i class="fas fa-address-card"></i>
      <span data-fr="Contenu À Propos" data-ar="محتوى من نحن">Contenu À Propos</span>
    </a>

    <div class="sidebar-label" style="margin-top:8px" data-fr="COMMUNICATION" data-ar="التواصل">COMMUNICATION</div>
    <a href="messages.php" class="sidebar-link <?= $activePage==='messages'?'active':'' ?>">
      <i class="fas fa-envelope"></i>
      <span data-fr="Messages" data-ar="الرسائل">Messages</span>
      <?php if ($badgeMessages > 0): ?><span class="sidebar-badge"><?= $badgeMessages ?></span><?php endif; ?>
    </a>
    <a href="notifications.php" class="sidebar-link <?= $activePage==='notifications'?'active':'' ?>">
      <i class="fas fa-bell"></i>
      <span data-fr="Notifications" data-ar="الإشعارات">Notifications</span>
      <?php if ($badgeNotifs > 0): ?><span class="sidebar-badge"><?= $badgeNotifs ?></span><?php endif; ?>
    </a>

    <div class="sidebar-label" style="margin-top:8px" data-fr="SYSTÈME" data-ar="النظام">SYSTÈME</div>
    <a href="utilisateurs.php" class="sidebar-link <?= $activePage==='utilisateurs'?'active':'' ?>">
      <i class="fas fa-user-shield"></i>
      <span data-fr="Utilisateurs" data-ar="المستخدمون">Utilisateurs</span>
    </a>
    <a href="parametres.php" class="sidebar-link <?= $activePage==='parametres'?'active':'' ?>">
      <i class="fas fa-cog"></i>
      <span data-fr="Paramètres" data-ar="الإعدادات">Paramètres</span>
    </a>
    <a href="logs.php" class="sidebar-link <?= $activePage==='logs'?'active':'' ?>">
      <i class="fas fa-history"></i>
      <span data-fr="Journaux" data-ar="السجلات">Journaux</span>
    </a>
  </nav>

  <div class="sidebar-footer">
    <div style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:10px;background:var(--dark-3)">
      <div class="admin-avatar" style="width:34px;height:34px;border-radius:8px;font-size:.85rem"><?= strtoupper(substr($adminNom,0,1)) ?></div>
      <div style="flex:1;min-width:0">
        <div style="font-size:.82rem;color:var(--white);font-weight:500"><?= htmlspecialchars($adminNom) ?></div>
        <div style="font-size:.7rem;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($adminEmail) ?></div>
      </div>
      <a href="logout.php" title="Déconnexion"
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
