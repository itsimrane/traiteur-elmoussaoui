<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// Récupérer les devis depuis la table reservations (type devis)
// Si table devis séparée n'existe pas, on utilise reservations
try {
    $devis = $pdo->query("
        SELECT r.*, 
               COALESCE(r.nom_client, r.prenom || ' ' || r.nom, 'Client inconnu') as client_nom,
               r.email, r.telephone, r.type_evenement, r.date_evenement,
               r.nb_personnes, r.message, r.statut, r.created_at,
               p.nom as package_nom
        FROM reservations r
        LEFT JOIN packages p ON r.package_id = p.id
        ORDER BY r.created_at DESC
    ")->fetchAll();
} catch(Exception $e) {
    $devis = [];
}

// Stats
$total     = count($devis);
$enAttente = count(array_filter($devis, fn($d) => ($d['statut'] ?? '') === 'en_attente'));
$confirmes = count(array_filter($devis, fn($d) => ($d['statut'] ?? '') === 'confirme'));
$refuses   = count(array_filter($devis, fn($d) => ($d['statut'] ?? '') === 'refuse'));

$statutColors = [
    'en_attente' => ['bg' => 'rgba(251,191,36,.15)',  'color' => '#FBB724', 'label' => 'En attente'],
    'confirme'   => ['bg' => 'rgba(37,211,102,.15)',  'color' => '#25D366', 'label' => 'Confirmé'],
    'refuse'     => ['bg' => 'rgba(239,68,68,.15)',   'color' => '#EF5350', 'label' => 'Refusé'],
    'en_cours'   => ['bg' => 'rgba(59,130,246,.15)',  'color' => '#60A5FA', 'label' => 'En cours'],
    ''           => ['bg' => 'rgba(251,191,36,.15)',  'color' => '#FBB724', 'label' => 'Nouveau'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Devis — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { overflow-x:hidden; }
    .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:999; }
    .sidebar-overlay.show { display:block; }
    @media(max-width:768px){ .sidebar { position:fixed; left:0; top:0; bottom:0; z-index:1000; transform:translateX(-100%); transition:var(--transition); } .sidebar.open { transform:translateX(0); } }

    /* Table devis */
    .devis-table-wrap { background:var(--dark-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
    .devis-table-header { padding:18px 24px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid var(--border); flex-wrap:wrap; gap:12px; }
    .devis-table-header h3 { font-size:.9rem; color:var(--white); font-weight:600; }
    .table-filters { display:flex; gap:8px; flex-wrap:wrap; }
    .tfilter { padding:5px 14px; border-radius:20px; border:1px solid var(--border); background:none; color:#888; cursor:pointer; font-size:.75rem; transition:var(--transition); font-family:var(--ff-body); }
    .tfilter.active, .tfilter:hover { border-color:var(--gold); color:var(--gold); }
    .search-input { background:var(--dark-3); border:1px solid var(--border); border-radius:8px; padding:7px 14px; color:var(--white); font-size:.82rem; outline:none; width:220px; }
    .search-input:focus { border-color:var(--gold); }
    table { width:100%; border-collapse:collapse; }
    thead th { padding:12px 16px; font-size:.72rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border); text-align:left; white-space:nowrap; }
    tbody tr { border-bottom:1px solid rgba(255,255,255,.04); transition:var(--transition); }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:rgba(212,175,55,.03); }
    td { padding:14px 16px; font-size:.84rem; color:var(--text-muted); vertical-align:middle; }
    .td-client strong { display:block; color:var(--white); font-size:.86rem; margin-bottom:2px; }
    .td-client span { font-size:.75rem; }
    .statut-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; font-size:.72rem; font-weight:600; }
    .event-type { background:var(--dark-3); padding:3px 10px; border-radius:6px; font-size:.75rem; color:var(--text-muted); }
    .td-actions { display:flex; gap:6px; }
    .act-btn { width:32px; height:32px; border-radius:8px; border:1px solid var(--border); background:none; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:.8rem; transition:var(--transition); color:var(--text-muted); text-decoration:none; }
    .act-btn:hover { border-color:var(--gold); color:var(--gold); }
    .act-btn.confirm { border-color:rgba(37,211,102,.3); color:#25D366; }
    .act-btn.confirm:hover { background:rgba(37,211,102,.1); }
    .act-btn.refuse { border-color:rgba(239,68,68,.3); color:#EF5350; }
    .act-btn.refuse:hover { background:rgba(239,68,68,.1); }
    .empty-table { text-align:center; padding:60px 20px; color:var(--text-muted); }
    .empty-table i { font-size:2.5rem; opacity:.2; display:block; margin-bottom:12px; }

    /* Modal devis detail */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.75); z-index:2000; align-items:center; justify-content:center; padding:20px; }
    .modal-overlay.show { display:flex; }
    .modal-box { background:var(--dark-card); border:1px solid var(--border); border-radius:14px; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; }
    .modal-header { padding:20px 24px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; background:var(--dark-card); z-index:1; }
    .modal-header h3 { color:var(--white); font-size:1rem; }
    .modal-close { width:32px; height:32px; border-radius:8px; border:1px solid var(--border); background:none; color:var(--text-muted); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:var(--transition); }
    .modal-close:hover { border-color:var(--gold); color:var(--gold); }
    .modal-body { padding:24px; }
    .detail-row { display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap; }
    .detail-field { flex:1; min-width:180px; }
    .detail-field label { display:block; font-size:.7rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
    .detail-field span { color:var(--white); font-size:.88rem; }
    .detail-msg { background:var(--dark-3); border-radius:8px; padding:14px; font-size:.84rem; color:var(--text-muted); line-height:1.6; margin-top:4px; }
    .modal-footer { padding:16px 24px; border-top:1px solid var(--border); display:flex; gap:10px; justify-content:flex-end; }

    /* Nouveau devis form */
    .nouveau-devis-card { background:var(--dark-card); border:1px solid var(--border); border-radius:var(--radius); padding:24px; margin-bottom:24px; display:none; }
    .nouveau-devis-card.show { display:block; }
    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .form-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }
    .form-full { grid-column:1/-1; }
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">

  <!-- SIDEBAR -->
  <?php $activePage = 'devis'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <!-- MAIN -->
  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title">
          <h2 data-fr="Gestion Devis" data-ar="إدارة عروض الأسعار">Gestion Devis</h2>
          <p data-fr="Demandes de devis et estimations tarifaires" data-ar="طلبات وتقديرات عروض الأسعار">Demandes de devis et estimations tarifaires</p>
        </div>
      </div>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
        <button class="btn-primary" style="padding:8px 18px;font-size:.82rem" onclick="toggleNewForm()">
          <i class="fas fa-plus"></i> <span data-fr="Nouveau devis" data-ar="عرض أسعار جديد">Nouveau devis</span>
        </button>
        <div class="admin-avatar">A</div>
      </div>
    </div>

    <div class="admin-content">

      <!-- Stats -->
      <div class="stats-grid" style="margin-bottom:24px">
        <div class="stat-card">
          <div class="stat-card-header">
            <div class="stat-card-icon gold"><i class="fas fa-file-invoice"></i></div>
          </div>
          <div class="stat-card-value"><?= $total ?></div>
          <div class="stat-card-label" data-fr="Total devis" data-ar="إجمالي العروض">Total devis</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header">
            <div class="stat-card-icon" style="background:rgba(251,191,36,.1);color:#FBB724"><i class="fas fa-clock"></i></div>
          </div>
          <div class="stat-card-value"><?= $enAttente ?></div>
          <div class="stat-card-label" data-fr="En attente" data-ar="في الانتظار" data-fr="En attente" data-ar="في الانتظار" data-fr="En attente" data-ar="في الانتظار">En attente</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header">
            <div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-check-circle"></i></div>
          </div>
          <div class="stat-card-value"><?= $confirmes ?></div>
          <div class="stat-card-label" data-fr="Confirmés" data-ar="مؤكدة" data-fr="Confirmés" data-ar="مؤكدة" data-fr="Confirmés" data-ar="مؤكدة">Confirmés</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header">
            <div class="stat-card-icon" style="background:rgba(239,68,68,.1);color:#EF5350"><i class="fas fa-times-circle"></i></div>
          </div>
          <div class="stat-card-value"><?= $refuses ?></div>
          <div class="stat-card-label" data-fr="Refusés" data-ar="مرفوضة" data-fr="Refusés" data-ar="مرفوضة" data-fr="Refusés" data-ar="مرفوضة">Refusés</div>
        </div>
      </div>

      <!-- Formulaire nouveau devis -->
      <div class="nouveau-devis-card" id="newDevisForm">
        <h3 style="color:var(--white);margin-bottom:20px;font-size:.95rem">
          <i class="fas fa-plus-circle" style="color:var(--gold);margin-right:8px"></i>Créer un nouveau devis
        </h3>
        <form method="POST" action="api/save_devis.php">
          <div class="form-grid-2" style="margin-bottom:14px">
            <div class="form-group">
              <label class="form-label">Prénom *</label>
              <input type="text" name="prenom" class="form-control" placeholder="Prénom du client" data-fr-placeholder="Prénom du client" data-ar-placeholder="الاسم الأول للعميل" required>
            </div>
            <div class="form-group">
              <label class="form-label">Nom *</label>
              <input type="text" name="nom" class="form-control" placeholder="Nom du client" data-fr-placeholder="Nom du client" data-ar-placeholder="اسم العائلة للعميل" required>
            </div>
            <div class="form-group">
              <label class="form-label">Téléphone *</label>
              <input type="tel" name="telephone" class="form-control" placeholder="06XXXXXXXX" data-fr-placeholder="06XXXXXXXX" data-ar-placeholder="06XXXXXXXX" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" placeholder="email@exemple.com" data-fr-placeholder="email@exemple.com" data-ar-placeholder="email@exemple.com">
            </div>
            <div class="form-group">
              <label class="form-label">Type d'événement</label>
              <select name="type_evenement" class="form-control">
                <option value="mariage">Mariage</option>
                <option value="fiancailles">Fiançailles</option>
                <option value="circoncision">Circoncision</option>
                <option value="anniversaire">Anniversaire</option>
                <option value="reception_pro">Réception Pro</option>
                <option value="buffet">Buffet</option>
                <option value="autre">Autre</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Package souhaité</label>
              <select name="package_id" class="form-control">
                <option value="">Sans package</option>
                <?php
                $pkgs = $pdo->query("SELECT id, nom, prix FROM packages WHERE actif=1 ORDER BY ordre")->fetchAll();
                foreach ($pkgs as $p):
                ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?> — <?= number_format($p['prix'],0,',',' ') ?> MAD</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Date de l'événement</label>
              <input type="date" name="date_evenement" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label">Nombre d'invités</label>
              <input type="number" name="nb_personnes" class="form-control" placeholder="100" data-fr-placeholder="100" data-ar-placeholder="100" min="1">
            </div>
          </div>
          <div class="form-group" style="margin-bottom:16px">
            <label class="form-label">Message / Demandes spéciales</label>
            <textarea name="message" class="form-control" rows="3" placeholder="Détails supplémentaires..." data-fr-placeholder="Détails supplémentaires..." data-ar-placeholder="تفاصيل إضافية..."></textarea>
          </div>
          <div style="display:flex;gap:10px;justify-content:flex-end">
            <button type="button" class="btn-secondary" onclick="toggleNewForm()">Annuler</button>
            <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Enregistrer le devis</button>
          </div>
        </form>
      </div>

      <!-- Table devis -->
      <div class="devis-table-wrap">
        <div class="devis-table-header">
          <h3><i class="fas fa-list" style="color:var(--gold);margin-right:8px"></i><span data-fr="Liste des devis" data-ar="قائمة عروض الأسعار">Liste des devis</span> (<?= $total ?>)</h3>
          <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <input type="text" class="search-input" id="searchDevis" placeholder="🔍 Rechercher..." data-fr-placeholder="🔍 Rechercher..." data-ar-placeholder="🔍 البحث..." oninput="filterDevis()">
            <div class="table-filters">
              <button class="tfilter active" onclick="filterStatut('all',this)" data-fr="Tous" data-ar="الكل" data-fr="Tous" data-ar="الكل">Tous</button>
              <button class="tfilter" onclick="filterStatut('en_attente',this)" data-fr="En attente" data-ar="في الانتظار" data-fr="En attente" data-ar="في الانتظار">En attente</button>
              <button class="tfilter" onclick="filterStatut('confirme',this)" data-fr="Confirmés" data-ar="مؤكدة" data-fr="Confirmés" data-ar="مؤكدة">Confirmés</button>
              <button class="tfilter" onclick="filterStatut('refuse',this)" data-fr="Refusés" data-ar="مرفوضة" data-fr="Refusés" data-ar="مرفوضة">Refusés</button>
            </div>
          </div>
        </div>

        <?php if (empty($devis)): ?>
        <div class="empty-table">
          <i class="fas fa-file-invoice"></i>
          <p><span data-fr="Aucun devis pour l'instant" data-ar="لا توجد عروض حالياً">Aucun devis pour l'instant</span>.</p>
          <button class="btn-primary" style="margin-top:16px" onclick="toggleNewForm()">
            <i class="fas fa-plus"></i> <span data-fr="Créer le premier devis" data-ar="إنشاء أول عرض أسعار">Créer le premier devis</span>
          </button>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table id="devisTable">
          <thead>
            <tr>
              <th>#</th>
              <th data-fr="Client" data-ar="العميل">Client</th>
              <th data-fr="Événement" data-ar="المناسبة">Événement</th>
              <th data-fr="Date" data-ar="التاريخ">Date</th>
              <th>Invités</th>
              <th>Package</th>
              <th data-fr="Statut" data-ar="الحالة">Statut</th>
              <th data-fr="Reçu le" data-ar="تاريخ الاستلام">Reçu le</th>
              <th data-fr="Actions" data-ar="الإجراءات">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($devis as $d):
              $statut = $d['statut'] ?? 'en_attente';
              $sc     = $statutColors[$statut] ?? $statutColors['en_attente'];
              $nom    = htmlspecialchars(trim(($d['prenom'] ?? '') . ' ' . ($d['nom'] ?? ''))) ?: 'Client #' . $d['id'];
              $date   = !empty($d['date_evenement']) ? date('d/m/Y', strtotime($d['date_evenement'])) : '—';
              $recu   = date('d/m/Y', strtotime($d['created_at']));
              $type   = ucfirst(str_replace('_', ' ', $d['type_evenement'] ?? '—'));
            ?>
            <tr data-statut="<?= $statut ?>"
                data-search="<?= strtolower($nom . ' ' . $type . ' ' . ($d['email'] ?? '') . ' ' . ($d['telephone'] ?? '')) ?>">
              <td style="color:#555;font-size:.78rem">#<?= $d['id'] ?></td>
              <td class="td-client">
                <strong><?= $nom ?></strong>
                <span><?= htmlspecialchars($d['telephone'] ?? '') ?></span>
              </td>
              <td><span class="event-type"><?= $type ?></span></td>
              <td><?= $date ?></td>
              <td><?= $d['nb_personnes'] ? $d['nb_personnes'] . ' pers.' : '—' ?></td>
              <td style="font-size:.78rem"><?= htmlspecialchars($d['package_nom'] ?? '—') ?></td>
              <td>
                <span class="statut-badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>">
                  <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                  <?= $sc['label'] ?>
                </span>
              </td>
              <td style="font-size:.78rem;color:#555"><?= $recu ?></td>
              <td>
                <div class="td-actions">
                  <button class="act-btn" onclick='openDetail(<?= json_encode($d) ?>)' title="Voir le détail">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="act-btn confirm" onclick="changeStatut(<?= $d['id'] ?>,'confirme')" title="Confirmer">
                    <i class="fas fa-check"></i>
                  </button>
                  <button class="act-btn refuse" onclick="changeStatut(<?= $d['id'] ?>,'refuse')" title="Refuser">
                    <i class="fas fa-times"></i>
                  </button>
                  <button class="act-btn" onclick="printDevis(<?= $d['id'] ?>)" title="Imprimer" style="color:#60A5FA;border-color:rgba(59,130,246,.3)">
                    <i class="fas fa-print"></i>
                  </button>
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

<!-- Modal détail devis -->
<div class="modal-overlay" id="detailModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-file-invoice" style="color:var(--gold);margin-right:8px"></i>Détail du devis</h3>
      <button class="modal-close" onclick="closeDetail()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body" id="detailContent"></div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closeDetail()" data-fr="Fermer" data-ar="إغلاق" data-fr="Fermer" data-ar="إغلاق">Fermer</button>
      <button class="btn-primary" id="confirmBtn" onclick="confirmFromModal()">
        <i class="fas fa-check"></i> Confirmer ce devis
      </button>
    </div>
  </div>
</div>

<script>
// Sidebar toggle
document.getElementById('sidebarToggle').addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('show');
});
document.getElementById('sidebarOverlay').addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
});

// Nouveau devis toggle
function toggleNewForm() {
  const f = document.getElementById('newDevisForm');
  f.classList.toggle('show');
  if (f.classList.contains('show')) f.scrollIntoView({behavior:'smooth', block:'start'});
}

// Filtres
let currentStatut = 'all';
function filterStatut(s, btn) {
  currentStatut = s;
  document.querySelectorAll('.tfilter').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}
function filterDevis() { applyFilters(); }
function applyFilters() {
  const q = document.getElementById('searchDevis')?.value.toLowerCase() || '';
  document.querySelectorAll('#devisTable tbody tr').forEach(row => {
    const matchS = currentStatut === 'all' || row.dataset.statut === currentStatut;
    const matchQ = !q || row.dataset.search.includes(q);
    row.style.display = (matchS && matchQ) ? '' : 'none';
  });
}

// Détail modal
let currentDevisId = null;
function openDetail(d) {
  currentDevisId = d.id;
  const statutColors = {
    'en_attente': '#FBB724', 'confirme': '#25D366',
    'refuse': '#EF5350', 'en_cours': '#60A5FA', '': '#FBB724'
  };
  const statutLabels = {
    'en_attente': 'En attente', 'confirme': 'Confirmé',
    'refuse': 'Refusé', 'en_cours': 'En cours', '': 'Nouveau'
  };
  const statut = d.statut || '';
  const color  = statutColors[statut] || '#FBB724';
  const label  = statutLabels[statut] || 'Nouveau';
  const nom    = ((d.prenom || '') + ' ' + (d.nom || '')).trim() || 'Client #' + d.id;
  const date   = d.date_evenement ? new Date(d.date_evenement).toLocaleDateString('fr-FR') : '—';
  const recu   = new Date(d.created_at).toLocaleDateString('fr-FR');

  document.getElementById('detailContent').innerHTML = `
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border)">
      <div>
        <div style="font-size:1.1rem;font-weight:700;color:var(--white)">${nom}</div>
        <div style="font-size:.78rem;color:var(--text-muted)">Reçu le ${recu}</div>
      </div>
      <span style="background:${color}22;color:${color};padding:5px 14px;border-radius:20px;font-size:.78rem;font-weight:700">${label}</span>
    </div>
    <div class="detail-row">
      <div class="detail-field"><label>Téléphone</label><span>${d.telephone || '—'}</span></div>
      <div class="detail-field"><label>Email</label><span>${d.email || '—'}</span></div>
    </div>
    <div class="detail-row">
      <div class="detail-field"><label>Type d'événement</label><span>${(d.type_evenement || '—').replace('_',' ')}</span></div>
      <div class="detail-field"><label>Date souhaitée</label><span>${date}</span></div>
    </div>
    <div class="detail-row">
      <div class="detail-field"><label>Nombre d'invités</label><span>${d.nb_personnes || '—'}</span></div>
      <div class="detail-field"><label>Package</label><span>${d.package_nom || 'Aucun'}</span></div>
    </div>
    ${d.message ? `<div class="form-group" style="margin-top:4px"><label class="form-label">Message</label><div class="detail-msg">${d.message}</div></div>` : ''}
  `;
  document.getElementById('detailModal').classList.add('show');
}
function closeDetail() {
  document.getElementById('detailModal').classList.remove('show');
  currentDevisId = null;
}
function confirmFromModal() {
  if (currentDevisId) changeStatut(currentDevisId, 'confirme');
  closeDetail();
}

// Changer statut
function changeStatut(id, statut) {
  if (!confirm(`Passer ce devis en "${statut}" ?`)) return;
  fetch('<?= SITE_URL ?>/api/update_statut.php', {
    method: 'POST',
    body: new URLSearchParams({ id, statut, table: 'reservations' })
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) location.reload();
    else alert('Erreur : ' + res.message);
  })
  .catch(() => {
    // API pas encore créée — reload pour l'instant
    location.reload();
  });
}

// Imprimer
function printDevis(id) {
  window.open('print_devis.php?id=' + id, '_blank');
}
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
