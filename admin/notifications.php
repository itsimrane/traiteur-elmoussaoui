<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// Créer des notifications auto depuis les vraies tables
function genNotifications(PDO $pdo): array {
    $notifs = [];
    $now    = time();

    // Nouveaux messages contact
    try {
        $msgs = $pdo->query("SELECT id, nom, prenom, sujet, created_at FROM contacts WHERE statut='nouveau' ORDER BY created_at DESC LIMIT 10")->fetchAll();
        foreach ($msgs as $m) {
            $nom = trim(($m['prenom']??'') . ' ' . $m['nom']);
            $notifs[] = [
                'id'     => 'msg_' . $m['id'],
                'type'   => 'message',
                'icon'   => 'fa-envelope',
                'color'  => '#EF5350',
                'bg'     => 'rgba(239,68,68,.12)',
                'titre'  => 'Nouveau message de ' . $nom,
                'desc'   => $m['sujet'] ?: 'Sans sujet',
                'time'   => $m['created_at'],
                'lien'   => 'messages.php?id=' . $m['id'],
                'lu'     => false,
            ];
        }
    } catch(Exception $e) {}

    // Nouvelles réservations
    try {
        $resas = $pdo->query("SELECT id, prenom, nom, type_evenement, date_evenement, created_at FROM reservations WHERE statut='en_attente' ORDER BY created_at DESC LIMIT 10")->fetchAll();
        foreach ($resas as $r) {
            $nom = trim(($r['prenom']??'') . ' ' . ($r['nom']??''));
            $notifs[] = [
                'id'     => 'resa_' . $r['id'],
                'type'   => 'reservation',
                'icon'   => 'fa-calendar-check',
                'color'  => '#FBB724',
                'bg'     => 'rgba(251,183,36,.12)',
                'titre'  => 'Nouvelle réservation — ' . ($r['type_evenement'] ?? 'Événement'),
                'desc'   => $nom . ($r['date_evenement'] ? ' · ' . date('d/m/Y', strtotime($r['date_evenement'])) : ''),
                'time'   => $r['created_at'],
                'lien'   => 'reservations.php',
                'lu'     => false,
            ];
        }
    } catch(Exception $e) {}

    // Factures en retard
    try {
        $facs = $pdo->query("SELECT id, numero, nom_client, reste_a_payer, date_echeance FROM factures WHERE statut NOT IN ('payee','annulee') AND date_echeance < CURDATE() ORDER BY date_echeance ASC LIMIT 5")->fetchAll();
        foreach ($facs as $f) {
            $notifs[] = [
                'id'     => 'fac_' . $f['id'],
                'type'   => 'facture',
                'icon'   => 'fa-exclamation-triangle',
                'color'  => '#EF5350',
                'bg'     => 'rgba(239,68,68,.12)',
                'titre'  => 'Facture en retard — ' . $f['numero'],
                'desc'   => $f['nom_client'] . ' · ' . number_format($f['reste_a_payer'],0,',',' ') . ' MAD restant',
                'time'   => $f['date_echeance'] . ' 00:00:00',
                'lien'   => 'factures.php',
                'lu'     => false,
            ];
        }
    } catch(Exception $e) {}

    // Témoignages en attente
    try {
        $tems = $pdo->query("SELECT id, nom_client, created_at FROM temoignages WHERE statut='en_attente' ORDER BY created_at DESC LIMIT 5")->fetchAll();
        foreach ($tems as $t) {
            $notifs[] = [
                'id'     => 'tem_' . $t['id'],
                'type'   => 'temoignage',
                'icon'   => 'fa-star',
                'color'  => '#D4AF37',
                'bg'     => 'rgba(212,175,55,.12)',
                'titre'  => 'Nouveau témoignage en attente',
                'desc'   => 'De : ' . $t['nom_client'],
                'time'   => $t['created_at'],
                'lien'   => 'temoignages-admin.php',
                'lu'     => false,
            ];
        }
    } catch(Exception $e) {}

    // Paiements en attente
    try {
        $pais = $pdo->query("SELECT id, nom_client, montant, created_at FROM paiements WHERE statut='en_attente' ORDER BY created_at DESC LIMIT 5")->fetchAll();
        foreach ($pais as $p) {
            $notifs[] = [
                'id'     => 'pai_' . $p['id'],
                'type'   => 'paiement',
                'icon'   => 'fa-credit-card',
                'color'  => '#60A5FA',
                'bg'     => 'rgba(59,130,246,.12)',
                'titre'  => 'Paiement en attente de confirmation',
                'desc'   => $p['nom_client'] . ' · ' . number_format($p['montant'],0,',',' ') . ' MAD',
                'time'   => $p['created_at'],
                'lien'   => 'paiements.php',
                'lu'     => false,
            ];
        }
    } catch(Exception $e) {}

    // Trier par date décroissante
    usort($notifs, fn($a,$b) => strtotime($b['time']) - strtotime($a['time']));
    return $notifs;
}

$notifs   = genNotifications($pdo);
$total    = count($notifs);
$nonLues  = count(array_filter($notifs, fn($n) => !$n['lu']));

// Grouper par type
$byType = [];
foreach ($notifs as $n) { $byType[$n['type']][] = $n; }

// Stats par type
$typeStats = [
    'message'    => ['label'=>'Messages',     'icon'=>'fa-envelope',            'color'=>'#EF5350'],
    'reservation'=> ['label'=>'Réservations', 'icon'=>'fa-calendar-check',      'color'=>'#FBB724'],
    'facture'    => ['label'=>'Factures',      'icon'=>'fa-exclamation-triangle','color'=>'#EF5350'],
    'temoignage' => ['label'=>'Témoignages',   'icon'=>'fa-star',                'color'=>'#D4AF37'],
    'paiement'   => ['label'=>'Paiements',     'icon'=>'fa-credit-card',         'color'=>'#60A5FA'],
];

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
  <title>Notifications — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}

    /* Layout 2 colonnes */
    .notif-layout{display:grid;grid-template-columns:260px 1fr;gap:20px;align-items:start}
    @media(max-width:900px){.notif-layout{grid-template-columns:1fr}}

    /* Sidebar filtres */
    .notif-sidebar{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;position:sticky;top:20px}
    .notif-sidebar-header{padding:14px 16px;border-bottom:1px solid var(--border);font-size:.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px}
    .notif-filter{display:flex;align-items:center;gap:10px;padding:11px 16px;cursor:pointer;transition:var(--transition);border-bottom:1px solid rgba(255,255,255,.03)}
    .notif-filter:hover{background:rgba(212,175,55,.05)}
    .notif-filter.active{background:rgba(212,175,55,.08);border-right:2px solid var(--gold)}
    .notif-filter i{width:18px;text-align:center;font-size:.85rem}
    .notif-filter span{flex:1;font-size:.82rem;color:var(--text-muted)}
    .notif-filter.active span{color:var(--white)}
    .notif-count{background:var(--dark-3);color:var(--text-muted);border-radius:10px;padding:1px 8px;font-size:.65rem;font-weight:700}
    .notif-count.red{background:rgba(239,68,68,.15);color:#EF5350}

    /* Liste notifications */
    .notif-list{display:flex;flex-direction:column;gap:0;background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
    .notif-list-header{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .notif-list-header h3{font-size:.88rem;color:var(--white);font-weight:600}
    .notif-item{display:flex;align-items:flex-start;gap:14px;padding:14px 18px;border-bottom:1px solid rgba(255,255,255,.04);transition:var(--transition);cursor:pointer;text-decoration:none;position:relative}
    .notif-item:last-child{border-bottom:none}
    .notif-item:hover{background:rgba(212,175,55,.04)}
    .notif-item.unread::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--gold)}
    .notif-icon-wrap{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}
    .notif-body{flex:1;min-width:0}
    .notif-title{font-size:.84rem;font-weight:600;color:var(--white);margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .notif-desc{font-size:.75rem;color:var(--text-muted);margin-bottom:4px}
    .notif-time{font-size:.68rem;color:#555;display:flex;align-items:center;gap:4px}
    .notif-action{flex-shrink:0;font-size:.75rem;color:var(--gold);opacity:0;transition:.2s}
    .notif-item:hover .notif-action{opacity:1}
    .notif-date-sep{padding:8px 18px;background:var(--dark-3);font-size:.68rem;color:#555;text-transform:uppercase;letter-spacing:.5px;font-weight:700}
    .empty-notif{text-align:center;padding:60px 20px;color:#555}
    .empty-notif i{font-size:2.5rem;opacity:.15;display:block;margin-bottom:12px}

    /* Type labels */
    .type-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:6px;font-size:.65rem;font-weight:700}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">

  <?php $activePage = 'notifications'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title">
          <h2 data-fr="Notifications" data-ar="الإشعارات">Notifications</h2>
          <p><?= $total > 0 ? "$total alertes actives" : '<span data-fr="Aucune notification" data-ar="لا توجد إشعارات">Aucune notification</span>' ?></p>
        </div>
      </div>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()" title="Actualiser"><i class="fas fa-sync-alt"></i></button>
        <div class="admin-avatar">A</div>
      </div>
    </div>

    <div class="admin-content">

      <!-- Stats -->
      <div class="stats-grid" style="margin-bottom:24px">
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon gold"><i class="fas fa-bell"></i></div></div>
          <div class="stat-card-value"><?= $total ?></div>
          <div class="stat-card-label" data-fr="Total alertes" data-ar="إجمالي التنبيهات">Total alertes</div>
        </div>
        <?php foreach ($typeStats as $type => $ts): $cnt = count($byType[$type] ?? []); if (!$cnt) continue; ?>
        <div class="stat-card">
          <div class="stat-card-header">
            <div class="stat-card-icon" style="background:<?= $ts['color'] ?>18;color:<?= $ts['color'] ?>">
              <i class="fas <?= $ts['icon'] ?>"></i>
            </div>
          </div>
          <div class="stat-card-value"><?= $cnt ?></div>
          <div class="stat-card-label"><?= $ts['label'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if (empty($notifs)): ?>
      <!-- Tout OK -->
      <div style="text-align:center;padding:80px 20px;background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius)">
        <div style="width:80px;height:80px;border-radius:50%;background:rgba(37,211,102,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2rem;color:#25D366">
          <i class="fas fa-check-circle"></i>
        </div>
        <h3 style="color:var(--white);margin-bottom:8px">Tout est à jour !</h3>
        <p style="color:var(--text-muted);font-size:.88rem"><span data-fr="Aucune notification" data-ar="لا توجد إشعارات">Aucune notification</span> en attente. Votre site fonctionne parfaitement.</p>
      </div>

      <?php else: ?>
      <div class="notif-layout">

        <!-- Filtres par type -->
        <div class="notif-sidebar">
          <div class="notif-sidebar-header">Filtrer par type</div>
          <div class="notif-filter active" onclick="filterNotif('all',this)">
            <i class="fas fa-bell" style="color:var(--gold)"></i>
            <span data-fr="Toutes" data-ar="الكل">Toutes</span>
            <span class="notif-count red"><?= $total ?></span>
          </div>
          <?php foreach ($typeStats as $type => $ts): $cnt = count($byType[$type] ?? []); if (!$cnt) continue; ?>
          <div class="notif-filter" onclick="filterNotif('<?= $type ?>',this)" data-type="<?= $type ?>">
            <i class="fas <?= $ts['icon'] ?>" style="color:<?= $ts['color'] ?>"></i>
            <span><?= $ts['label'] ?></span>
            <span class="notif-count"><?= $cnt ?></span>
          </div>
          <?php endforeach; ?>
          <!-- Liens rapides -->
          <div style="padding:12px 16px;border-top:1px solid var(--border);margin-top:4px">
            <div style="font-size:.7rem;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Accès rapide</div>
            <a href="messages.php" style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:.78rem;color:var(--text-muted);text-decoration:none;transition:.2s" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--text-muted)'">
              <i class="fas fa-envelope" style="width:14px;color:var(--gold)"></i> Boîte messages
            </a>
            <a href="reservations.php" style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:.78rem;color:var(--text-muted);text-decoration:none;transition:.2s" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--text-muted)'">
              <i class="fas fa-calendar-check" style="width:14px;color:var(--gold)"></i> Réservations
            </a>
            <a href="factures.php" style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:.78rem;color:var(--text-muted);text-decoration:none;transition:.2s" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--text-muted)'">
              <i class="fas fa-receipt" style="width:14px;color:var(--gold)"></i> Factures
            </a>
          </div>
        </div>

        <!-- Liste notifications -->
        <div class="notif-list" id="notifList">
          <div class="notif-list-header">
            <h3><i class="fas fa-bell" style="color:var(--gold);margin-right:8px"></i>Alertes actives</h3>
            <span style="font-size:.75rem;color:var(--text-muted)"><?= $total ?> notification<?= $total > 1 ? 's' : '' ?></span>
          </div>

          <?php
          $lastDate = null;
          foreach ($notifs as $n):
            $dateStr = date('Y-m-d', strtotime($n['time']));
            $dateLbl = match($dateStr) {
                date('Y-m-d')                        => "Aujourd'hui",
                date('Y-m-d', strtotime('-1 day'))   => 'Hier',
                default                              => date('d/m/Y', strtotime($n['time'])),
            };
          ?>
          <?php if ($lastDate !== $dateStr): $lastDate = $dateStr; ?>
          <div class="notif-date-sep" data-type="<?= $n['type'] ?>"><?= $dateLbl ?></div>
          <?php endif; ?>

          <a href="<?= htmlspecialchars($n['lien']) ?>" class="notif-item unread" data-type="<?= $n['type'] ?>">
            <div class="notif-icon-wrap" style="background:<?= $n['bg'] ?>;color:<?= $n['color'] ?>">
              <i class="fas <?= $n['icon'] ?>"></i>
            </div>
            <div class="notif-body">
              <div class="notif-title"><?= htmlspecialchars($n['titre']) ?></div>
              <div class="notif-desc"><?= htmlspecialchars($n['desc']) ?></div>
              <div class="notif-time">
                <i class="fas fa-clock"></i>
                <?= timeAgo($n['time']) ?>
                <span class="type-badge" style="background:<?= $n['bg'] ?>;color:<?= $n['color'] ?>;margin-left:6px">
                  <?= $typeStats[$n['type']]['label'] ?? $n['type'] ?>
                </span>
              </div>
            </div>
            <span class="notif-action"><i class="fas fa-chevron-right"></i></span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

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

function filterNotif(type, btn) {
  document.querySelectorAll('.notif-filter').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.notif-item, .notif-date-sep').forEach(el => {
    el.style.display = (type === 'all' || el.dataset.type === type) ? '' : 'none';
  });
}

// Auto-refresh toutes les 60s
setTimeout(() => location.reload(), 60000);
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
