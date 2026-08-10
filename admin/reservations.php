<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_statut') {
        $id     = (int)($_POST['id'] ?? 0);
        $statut = sanitize($_POST['statut'] ?? '');
        $pdo->prepare("UPDATE reservations SET statut=?, updated_at=NOW() WHERE id=?")->execute([$statut, $id]);
        header('Location: reservations.php?msg=Statut+mis+à+jour&type=success'); exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM reservations WHERE id=?")->execute([$id]);
        header('Location: reservations.php?msg=Réservation+supprimée&type=success'); exit;
    }
}

// Récupérer les réservations
try {
    $reservations = $pdo->query("
        SELECT r.*, p.nom AS package_nom
        FROM reservations r
        LEFT JOIN packages p ON r.package_id = p.id
        ORDER BY r.created_at DESC
    ")->fetchAll();
} catch(Exception $e) {
    $reservations = [];
}

$total      = count($reservations);
$enAttente  = count(array_filter($reservations, fn($r) => ($r['statut'] ?? '') === 'en_attente'));
$confirmes  = count(array_filter($reservations, fn($r) => ($r['statut'] ?? '') === 'confirme'));
$refuses    = count(array_filter($reservations, fn($r) => ($r['statut'] ?? '') === 'refuse'));

$statutConfig = [
    'en_attente' => ['label'=>'En attente','label_ar'=>'في الانتظار', 'color'=>'#FBB724','bg'=>'rgba(251,183,36,.15)'],
    'confirme'   => ['label'=>'Confirmé','label_ar'=>'مؤكد',   'color'=>'#25D366','bg'=>'rgba(37,211,102,.15)'],
    'refuse'     => ['label'=>'Refusé','label_ar'=>'مرفوض',     'color'=>'#EF5350','bg'=>'rgba(239,68,68,.15)'],
    'en_cours'   => ['label'=>'En cours','label_ar'=>'جاري',   'color'=>'#60A5FA','bg'=>'rgba(59,130,246,.15)'],
    'termine'    => ['label'=>'Terminé','label_ar'=>'منتهي',    'color'=>'#888',   'bg'=>'rgba(136,136,136,.1)'],
    ''           => ['label'=>'Nouveau','label_ar'=>'جديد',    'color'=>'#FBB724','bg'=>'rgba(251,183,36,.15)'],
];

$msg     = $_GET['msg']  ?? '';
$msgType = $_GET['type'] ?? 'success';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Réservations — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}
    .table-wrap{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
    .table-topbar{padding:16px 22px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:12px}
    .search-input{background:var(--dark-3);border:1px solid var(--border);border-radius:8px;padding:8px 14px;color:var(--white);font-size:.82rem;outline:none;width:220px}
    .search-input:focus{border-color:var(--gold)}
    table{width:100%;border-collapse:collapse}
    thead th{padding:11px 16px;font-size:.7rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border);text-align:left;white-space:nowrap}
    tbody tr{border-bottom:1px solid rgba(255,255,255,.04);transition:var(--transition)}
    tbody tr:last-child{border-bottom:none}
    tbody tr:hover{background:rgba(212,175,55,.03)}
    td{padding:13px 16px;font-size:.83rem;color:var(--text-muted);vertical-align:middle}
    .statut-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:600}
    .event-tag{background:var(--dark-3);padding:3px 10px;border-radius:6px;font-size:.75rem}
    .td-actions{display:flex;gap:6px}
    .act-btn{width:30px;height:30px;border-radius:7px;border:1px solid var(--border);background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.78rem;transition:var(--transition);color:var(--text-muted)}
    .act-btn:hover{border-color:var(--gold);color:var(--gold)}
    .act-btn.confirm{border-color:rgba(37,211,102,.3);color:#25D366}
    .act-btn.refuse{border-color:rgba(239,68,68,.3);color:#EF5350}
    .act-btn.danger:hover{border-color:rgba(239,68,68,.4);color:#EF5350}
    .empty-state{text-align:center;padding:60px 20px;color:var(--text-muted)}
    .empty-state i{font-size:2.5rem;opacity:.2;display:block;margin-bottom:12px}
    .tfilter{padding:5px 14px;border-radius:20px;border:1px solid var(--border);background:none;color:#888;cursor:pointer;font-size:.75rem;transition:var(--transition);font-family:var(--ff-body)}
    .tfilter.active,.tfilter:hover{border-color:var(--gold);color:var(--gold)}
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:2000;align-items:center;justify-content:center;padding:20px}
    .modal-overlay.show{display:flex}
    .modal-box{background:var(--dark-card);border:1px solid var(--border);border-radius:14px;width:100%;max-width:580px;max-height:90vh;overflow-y:auto}
    .modal-header{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:var(--dark-card);z-index:1}
    .modal-header h3{color:var(--white);font-size:.95rem}
    .modal-close{width:30px;height:30px;border-radius:7px;border:1px solid var(--border);background:none;color:var(--text-muted);cursor:pointer;display:flex;align-items:center;justify-content:center}
    .modal-close:hover{border-color:var(--gold);color:var(--gold)}
    .modal-body{padding:22px}
    .modal-footer{padding:14px 22px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
    .detail-row{display:flex;gap:16px;margin-bottom:14px;flex-wrap:wrap}
    .detail-field{flex:1;min-width:160px}
    .detail-field label{display:block;font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px}
    .detail-field span{color:var(--white);font-size:.85rem}
    .detail-msg{background:var(--dark-3);border-radius:8px;padding:12px;font-size:.82rem;color:var(--text-muted);line-height:1.6}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">

  <?php $activePage = 'reservations'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2 data-fr="Réservations" data-ar="الحجوزات">Réservations</h2><p data-fr="Demandes de réservations et événements" data-ar="طلبات الحجز والمناسبات">Demandes de réservations et événements</p></div>
      </div>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
        <div class="admin-avatar">A</div>
      </div>
    </div>

    <div class="admin-content">

      <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px">
        <i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($msg) ?>
      </div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="stats-grid" style="margin-bottom:24px">
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon gold"><i class="fas fa-calendar-check"></i></div></div>
          <div class="stat-card-value"><?= $total ?></div>
          <div class="stat-card-label" data-fr="Total réservations" data-ar="إجمالي الحجوزات">Total réservations</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(251,183,36,.1);color:#FBB724"><i class="fas fa-clock"></i></div></div>
          <div class="stat-card-value"><?= $enAttente ?></div>
          <div class="stat-card-label" data-fr="En attente" data-ar="في الانتظار" data-fr="En attente" data-ar="في الانتظار">En attente</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-check-circle"></i></div></div>
          <div class="stat-card-value"><?= $confirmes ?></div>
          <div class="stat-card-label" data-fr="Confirmées" data-ar="مؤكدة" data-fr="Confirmées" data-ar="مؤكدة">Confirmées</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(239,68,68,.1);color:#EF5350"><i class="fas fa-times-circle"></i></div></div>
          <div class="stat-card-value"><?= $refuses ?></div>
          <div class="stat-card-label" data-fr="Refusées" data-ar="مرفوضة" data-fr="Refusées" data-ar="مرفوضة">Refusées</div>
        </div>
      </div>

      <!-- Table -->
      <div class="table-wrap">
        <div class="table-topbar">
          <h3 style="color:var(--white);font-size:.9rem"><i class="fas fa-list" style="color:var(--gold);margin-right:8px"></i><span data-fr="Liste des réservations" data-ar="قائمة الحجوزات">Liste des réservations</span> (<?= $total ?>)</h3>
          <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <input type="text" class="search-input" id="searchResa" placeholder="🔍 Client, événement..." data-fr-placeholder="🔍 Client, événement..." data-ar-placeholder="🔍 العميل، المناسبة..." oninput="filterResa()">
            <div style="display:flex;gap:6px">
              <button class="tfilter active" onclick="setFilter('all',this)" data-fr="Tous" data-ar="الكل">Tous</button>
              <button class="tfilter" onclick="setFilter('en_attente',this)" data-fr="En attente" data-ar="في الانتظار">En attente</button>
              <button class="tfilter" onclick="setFilter('confirme',this)" data-fr="Confirmées" data-ar="مؤكدة">Confirmées</button>
              <button class="tfilter" onclick="setFilter('refuse',this)" data-fr="Refusées" data-ar="مرفوضة">Refusées</button>
            </div>
          </div>
        </div>

        <?php if (empty($reservations)): ?>
        <div class="empty-state">
          <i class="fas fa-calendar-check"></i>
          <p data-fr="Aucune réservation pour l'instant." data-ar="لا توجد حجوزات حالياً.">Aucune réservation pour l'instant.</p>
          <p style="font-size:.82rem;margin-top:8px" data-fr="Les réservations soumises via le formulaire public apparaîtront ici." data-ar="ستظهر الحجوزات المقدمة عبر النموذج العام هنا.">Les réservations soumises via le formulaire public apparaîtront ici.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table id="resaTable">
          <thead>
            <tr>
              <th>#</th>
              <th data-fr="Client" data-ar="العميل">Client</th>
              <th data-fr="Événement" data-ar="المناسبة">Événement</th>
              <th data-fr="Date" data-ar="التاريخ">Date</th>
              <th data-fr="Invités" data-ar="الضيوف">Invités</th>
              <th data-fr="Package" data-ar="الباقة">Package</th>
              <th data-fr="Statut" data-ar="الحالة">Statut</th>
              <th data-fr="Reçu le" data-ar="تاريخ الاستلام">Reçu le</th>
              <th data-fr="Actions" data-ar="الإجراءات">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reservations as $r):
              $statut = $r['statut'] ?? 'en_attente';
              $sc     = $statutConfig[$statut] ?? $statutConfig['en_attente'];
              $nom    = htmlspecialchars(trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? ''))) ?: 'Client #' . $r['id'];
              $date   = !empty($r['date_evenement']) ? date('d/m/Y', strtotime($r['date_evenement'])) : '—';
              $recu   = date('d/m/Y', strtotime($r['created_at']));
              $type   = ucfirst(str_replace('_', ' ', $r['type_evenement'] ?? '—'));
            ?>
            <tr data-statut="<?= $statut ?>"
                data-search="<?= strtolower($nom . ' ' . $type . ' ' . ($r['email'] ?? '') . ' ' . ($r['telephone'] ?? '')) ?>">
              <td style="color:#555;font-size:.78rem">#<?= $r['id'] ?></td>
              <td>
                <div style="color:var(--white);font-weight:600;font-size:.85rem"><?= $nom ?></div>
                <div style="font-size:.73rem;color:#555"><?= htmlspecialchars($r['telephone'] ?? '') ?></div>
              </td>
              <td><span class="event-tag"><?= $type ?></span></td>
              <td><?= $date ?></td>
              <td><?= $r['nb_personnes'] ? $r['nb_personnes'] . ' pers.' : '—' ?></td>
              <td style="font-size:.78rem"><?= htmlspecialchars($r['package_nom'] ?? '—') ?></td>
              <td>
                <span class="statut-badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>">
                  <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                  <span data-fr="<?= $sc['label'] ?>" data-ar="<?= $sc['label_ar'] ?? $sc['label'] ?>"><?= $sc['label'] ?></span>
                </span>
              </td>
              <td style="font-size:.75rem;color:#555"><?= $recu ?></td>
              <td>
                <div class="td-actions">
                  <button class="act-btn" onclick='openDetail(<?= json_encode($r) ?>)' title="Voir le détail">
                    <i class="fas fa-eye"></i>
                  </button>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="update_statut">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="statut" value="confirme">
                    <button type="submit" class="act-btn confirm" title="Confirmer">
                      <i class="fas fa-check"></i>
                    </button>
                  </form>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="update_statut">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="statut" value="refuse">
                    <button type="submit" class="act-btn refuse" title="Refuser">
                      <i class="fas fa-times"></i>
                    </button>
                  </form>
                  <?php if (!empty($r['telephone'])): ?>
                  <a href="https://wa.me/212<?= ltrim(preg_replace('/[^0-9]/','',$r['telephone']),'0') ?>?text=<?= urlencode('Bonjour, concernant votre réservation du ' . $date) ?>"
                     target="_blank" class="act-btn" title="WhatsApp" style="color:#25D366;border-color:rgba(37,211,102,.3)">
                    <i class="fab fa-whatsapp"></i>
                  </a>
                  <?php endif; ?>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer cette réservation ?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button type="submit" class="act-btn danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </main>
</div>

<!-- Modal détail -->
<div class="modal-overlay" id="detailModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-calendar-check" style="color:var(--gold);margin-right:8px"></i><span data-fr="Détail réservation" data-ar="تفاصيل الحجز">Détail réservation</span></h3>
      <button class="modal-close" onclick="closeDetail()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body" id="detailContent"></div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closeDetail()" data-fr="Fermer" data-ar="إغلاق">Fermer</button>
    </div>
  </div>
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

let currentFilter = 'all';
function setFilter(f, btn) {
  currentFilter = f;
  document.querySelectorAll('.tfilter').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterResa();
}
function filterResa() {
  const q = document.getElementById('searchResa').value.toLowerCase();
  document.querySelectorAll('#resaTable tbody tr').forEach(row => {
    const matchF = currentFilter === 'all' || row.dataset.statut === currentFilter;
    const matchQ = !q || row.dataset.search.includes(q);
    row.style.display = (matchF && matchQ) ? '' : 'none';
  });
}

const sc = <?= json_encode($statutConfig) ?>;
function openDetail(r) {
  const nom   = ((r.prenom || '') + ' ' + (r.nom || '')).trim() || 'Client #' + r.id;
  const statut = r.statut || 'en_attente';
  const s      = sc[statut] || sc['en_attente'];
  const date   = r.date_evenement ? new Date(r.date_evenement).toLocaleDateString('fr-FR') : '—';
  const recu   = new Date(r.created_at).toLocaleDateString('fr-FR');
  const type   = (r.type_evenement || '—').replace(/_/g,' ');

  document.getElementById('detailContent').innerHTML = `
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border)">
      <div>
        <div style="font-size:1.05rem;font-weight:700;color:var(--white)">${nom}</div>
        <div style="font-size:.78rem;color:var(--text-muted)">Reçu le ${recu}</div>
      </div>
      <span style="background:${s.bg};color:${s.color};padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:700">${s.label}</span>
    </div>
    <div class="detail-row">
      <div class="detail-field"><label>Téléphone</label><span>${r.telephone || '—'}</span></div>
      <div class="detail-field"><label>Email</label><span>${r.email || '—'}</span></div>
    </div>
    <div class="detail-row">
      <div class="detail-field"><label>Type d'événement</label><span>${type}</span></div>
      <div class="detail-field"><label>Date souhaitée</label><span>${date}</span></div>
    </div>
    <div class="detail-row">
      <div class="detail-field"><label>Nombre d'invités</label><span>${r.nb_personnes || '—'}</span></div>
      <div class="detail-field"><label>Package</label><span style="color:var(--gold)">${r.package_nom || 'Aucun'}</span></div>
    </div>
    ${r.message ? `<div style="margin-top:4px"><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:6px">Message</div><div class="detail-msg">${r.message}</div></div>` : ''}
  `;
  document.getElementById('detailModal').classList.add('show');
}
function closeDetail() { document.getElementById('detailModal').classList.remove('show'); }
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
