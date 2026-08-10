<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// Créer table logs si elle n'existe pas
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `activity_logs` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NULL,
        `user_email` VARCHAR(191) NULL,
        `action` VARCHAR(100) NOT NULL,
        `table_name` VARCHAR(100) NULL,
        `record_id` INT NULL,
        `description` TEXT NULL,
        `ip_address` VARCHAR(45) NULL,
        `user_agent` VARCHAR(255) NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e) {}

// Récupérer les logs
$logs = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 500")->fetchAll();

// Stats rapides depuis les autres tables
$statsToday = [];
try {
    $statsToday['reservations'] = $pdo->query("SELECT COUNT(*) FROM reservations WHERE DATE(created_at)=CURDATE()")->fetchColumn();
    $statsToday['messages']     = $pdo->query("SELECT COUNT(*) FROM contacts WHERE DATE(created_at)=CURDATE()")->fetchColumn();
    $statsToday['clients']      = $pdo->query("SELECT COUNT(*) FROM clients WHERE DATE(created_at)=CURDATE()")->fetchColumn();
} catch(Exception $e) {}

// Historique d'activité depuis les tables existantes (logs synthétiques)
$activites = [];

try {
    $resas = $pdo->query("SELECT id, CONCAT(COALESCE(prenom,''), ' ', COALESCE(nom,'')) as nom, type_evenement, statut, created_at FROM reservations ORDER BY created_at DESC LIMIT 20")->fetchAll();
    foreach ($resas as $r) {
        $activites[] = [
            'icon'  => 'fa-calendar-check',
            'color' => '#FBB724',
            'bg'    => 'rgba(251,183,36,.12)',
            'titre' => localStorage && localStorage.getItem('admin_lang')=='ar' ? ('حجز ' . ($r['statut'] === 'confirme' ? 'مؤكد' : 'جديد')) : ('Réservation ' . ($r['statut'] === 'confirme' ? 'confirmée' : 'reçue')),
            'desc'  => trim($r['nom']) . ' — ' . ucfirst(str_replace('_',' ',$r['type_evenement'] ?? '')),
            'time'  => $r['created_at'],
            'lien'  => 'reservations.php',
        ];
    }
} catch(Exception $e) {}

try {
    $msgs = $pdo->query("SELECT id, CONCAT(COALESCE(prenom,''), ' ', nom) as nom, sujet, created_at FROM contacts ORDER BY created_at DESC LIMIT 10")->fetchAll();
    foreach ($msgs as $m) {
        $activites[] = [
            'icon'  => 'fa-envelope',
            'color' => '#60A5FA',
            'bg'    => 'rgba(59,130,246,.12)',
            'titre' => 'Nouveau message reçu',
            'desc'  => trim($m['nom']) . ' — ' . ($m['sujet'] ?: 'Sans sujet'),
            'time'  => $m['created_at'],
            'lien'  => 'messages.php?id=' . $m['id'],
        ];
    }
} catch(Exception $e) {}

try {
    $tems = $pdo->query("SELECT id, nom_client, note, statut, created_at FROM temoignages ORDER BY created_at DESC LIMIT 5")->fetchAll();
    foreach ($tems as $t) {
        $activites[] = [
            'icon'  => 'fa-star',
            'color' => '#D4AF37',
            'bg'    => 'rgba(212,175,55,.12)',
            'titre' => 'Nouveau témoignage',
            'desc'  => $t['nom_client'] . ' — ' . $t['note'] . '/5 ⭐',
            'time'  => $t['created_at'],
            'lien'  => 'temoignages-admin.php',
        ];
    }
} catch(Exception $e) {}

// Trier par date décroissante
usort($activites, fn($a,$b) => strtotime($b['time']) - strtotime($a['time']));
$activites = array_slice($activites, 0, 50);

function timeAgo(string $time): string {
    $diff = time() - strtotime($time);
    if ($diff < 60)     return 'À l\'instant';
    if ($diff < 3600)   return floor($diff/60) . ' min';
    if ($diff < 86400)  return floor($diff/3600) . 'h';
    if ($diff < 604800) return floor($diff/86400) . 'j';
    return date('d/m/Y', strtotime($time));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Journaux — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}

    .log-list{display:flex;flex-direction:column;gap:0;background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
    .log-item{display:flex;align-items:flex-start;gap:14px;padding:14px 18px;border-bottom:1px solid rgba(255,255,255,.04);transition:var(--transition);text-decoration:none}
    .log-item:last-child{border-bottom:none}
    .log-item:hover{background:rgba(212,175,55,.04)}
    .log-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0}
    .log-body{flex:1;min-width:0}
    .log-title{font-size:.84rem;font-weight:600;color:var(--white);margin-bottom:3px}
    .log-desc{font-size:.76rem;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .log-time{font-size:.68rem;color:#555;flex-shrink:0;margin-top:2px}
    .log-header{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .log-header h3{font-size:.88rem;color:var(--white);font-weight:600}
    .empty-log{text-align:center;padding:60px 20px;color:#555}
    .empty-log i{font-size:2.5rem;opacity:.15;display:block;margin-bottom:12px}
    .today-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px}
    .info-card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;display:flex;align-items:center;gap:14px}
    .info-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}
    .info-val{font-size:1.4rem;font-weight:700;color:var(--white);font-family:var(--ff-display)}
    .info-lbl{font-size:.72rem;color:var(--text-muted)}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">

  <?php $activePage = 'logs'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2 data-fr="Journaux d'activité" data-ar="سجلات النشاط">Journaux d'activité</h2><p data-fr="Historique des événements du site" data-ar="سجل أحداث الموقع">Historique des événements du site</p></div>
      </div>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
        <div class="admin-avatar">A</div>
      </div>
    </div>

    <div class="admin-content">

      <!-- Stats du jour -->
      <div style="margin-bottom:20px">
        <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;font-weight:700">
          <i class="fas fa-calendar-day" style="color:var(--gold);margin-right:6px"></i><span data-fr="AUJOURD'HUI" data-ar="اليوم" data-fr="AUJOURD'HUI" data-ar="اليوم">AUJOURD'HUI</span>
        </div>
        <div class="today-stats">
          <div class="info-card">
            <div class="info-icon" style="background:rgba(251,183,36,.1);color:#FBB724"><i class="fas fa-calendar-check"></i></div>
            <div>
              <div class="info-val"><?= $statsToday['reservations'] ?? 0 ?></div>
              <div class="info-lbl" data-fr="Réservations" data-ar="الحجوزات">Réservations</div>
            </div>
          </div>
          <div class="info-card">
            <div class="info-icon" style="background:rgba(59,130,246,.1);color:#60A5FA"><i class="fas fa-envelope"></i></div>
            <div>
              <div class="info-val"><?= $statsToday['messages'] ?? 0 ?></div>
              <div class="info-lbl" data-fr="Messages" data-ar="الرسائل">Messages</div>
            </div>
          </div>
          <div class="info-card">
            <div class="info-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-users"></i></div>
            <div>
              <div class="info-val"><?= $statsToday['clients'] ?? 0 ?></div>
              <div class="info-lbl" data-fr="Nouveaux clients" data-ar="عملاء جدد">Nouveaux clients</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Journal d'activité -->
      <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;font-weight:700">
        <i class="fas fa-history" style="color:var(--gold);margin-right:6px"></i><span data-fr="ACTIVITÉ RÉCENTE" data-ar="النشاط الأخير" data-fr="ACTIVITÉ RÉCENTE" data-ar="النشاط الأخير">ACTIVITÉ RÉCENTE</span>
      </div>

      <?php if (empty($activites)): ?>
      <div class="log-list">
        <div class="empty-log">
          <i class="fas fa-history"></i>
          <p><span data-fr="Aucune activité récente" data-ar="لا يوجد نشاط حديث">Aucune activité récente</span> à afficher.</p>
          <p style="font-size:.78rem;margin-top:6px" data-fr="Les réservations, messages et témoignages apparaîtront ici." data-ar="ستظهر الحجوزات والرسائل والشهادات هنا.">Les réservations, messages et témoignages apparaîtront ici.</p>
        </div>
      </div>
      <?php else: ?>
      <div class="log-list">
        <div class="log-header">
          <h3><i class="fas fa-history" style="color:var(--gold);margin-right:8px"></i><span data-fr="Flux d'activité" data-ar="تدفق النشاط">Flux d'activité</span></h3>
          <span style="font-size:.75rem;color:var(--text-muted)"><?= count($activites) ?> <span data-fr="entrées" data-ar="إدخالات">entrées</span></span>
        </div>
        <?php foreach ($activites as $a): ?>
        <a href="<?= htmlspecialchars($a['lien']) ?>" class="log-item">
          <div class="log-icon" style="background:<?= $a['bg'] ?>;color:<?= $a['color'] ?>">
            <i class="fas <?= $a['icon'] ?>"></i>
          </div>
          <div class="log-body">
            <div class="log-title"><?= htmlspecialchars($a['titre']) ?></div>
            <div class="log-desc"><?= htmlspecialchars($a['desc']) ?></div>
          </div>
          <div class="log-time"><?= timeAgo($a['time']) ?></div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Info système -->
      <div style="margin-top:24px;background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px">
        <div style="font-size:.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px">
          <i class="fas fa-server" style="color:var(--gold);margin-right:6px"></i><span data-fr="INFORMATIONS SYSTÈME" data-ar="معلومات النظام" data-fr="INFORMATIONS SYSTÈME" data-ar="معلومات النظام">INFORMATIONS SYSTÈME</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;font-size:.8rem">
          <div><span style="color:var(--text-muted)"><span data-fr="PHP Version" data-ar="إصدار PHP">PHP Version</span> :</span> <span style="color:var(--white)"><?= phpversion() ?></span></div>
          <div><span style="color:var(--text-muted)"><span data-fr="Serveur" data-ar="الخادم">Serveur</span> :</span> <span style="color:var(--white)"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/Nginx' ?></span></div>
          <div><span style="color:var(--text-muted)"><span data-fr="Heure serveur" data-ar="وقت الخادم">Heure serveur</span> :</span> <span style="color:var(--white)"><?= date('d/m/Y H:i:s') ?></span></div>
          <div><span style="color:var(--text-muted)"><span data-fr="Mémoire utilisée" data-ar="الذاكرة المستخدمة">Mémoire utilisée</span> :</span> <span style="color:var(--white)"><?= round(memory_get_usage()/1024/1024, 2) ?> MB</span></div>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
document.getElementById('sidebarToggle').addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('show');
});
document.getElementById('sidebarOverlay').addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
});
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
