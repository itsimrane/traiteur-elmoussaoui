<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $pdo->prepare("
            INSERT INTO temoignages (nom_client, ville, contenu, note, type_evenement, statut, en_vedette, ordre)
            VALUES (?,?,?,?,?,?,?,?)
        ")->execute([
            sanitize($_POST['nom_client']    ?? ''),
            sanitize($_POST['ville']         ?? 'Errachidia'),
            sanitize($_POST['contenu']       ?? ''),
            max(1, min(5, (int)($_POST['note'] ?? 5))),
            sanitize($_POST['type_evenement']?? ''),
            sanitize($_POST['statut']        ?? 'en_attente'),
            isset($_POST['en_vedette']) ? 1 : 0,
            (int)($_POST['ordre']            ?? 0),
        ]);
        header('Location: temoignages-admin.php?msg=Témoignage+ajouté&type=success');
        exit;
    }

    if ($action === 'update_statut') {
        $id     = (int)($_POST['id'] ?? 0);
        $statut = sanitize($_POST['statut'] ?? '');
        $pdo->prepare("UPDATE temoignages SET statut=? WHERE id=?")->execute([$statut, $id]);
        header('Location: temoignages-admin.php?msg=Statut+mis+à+jour&type=success');
        exit;
    }

    if ($action === 'toggle_vedette') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE temoignages SET en_vedette = NOT en_vedette WHERE id=?")->execute([$id]);
        header('Location: temoignages-admin.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM temoignages WHERE id=?")->execute([$id]);
        header('Location: temoignages-admin.php?msg=Témoignage+supprimé&type=success');
        exit;
    }
}

// Récupérer les témoignages
$temoignages = $pdo->query("SELECT * FROM temoignages ORDER BY ordre ASC, created_at DESC")->fetchAll();

$total     = count($temoignages);
$publies   = count(array_filter($temoignages, fn($t) => $t['statut'] === 'publie'));
$attente   = count(array_filter($temoignages, fn($t) => $t['statut'] === 'en_attente'));
$vedettes  = count(array_filter($temoignages, fn($t) => $t['en_vedette'] == 1));
$moyNote   = $total > 0 ? round(array_sum(array_column($temoignages,'note')) / $total, 1) : 0;

$statutConfig = [
    'en_attente' => ['label'=>'En attente', 'color'=>'#FBB724', 'bg'=>'rgba(251,183,36,.12)'],
    'publie'     => ['label'=>'Publié',     'color'=>'#25D366', 'bg'=>'rgba(37,211,102,.12)'],
    'refuse'     => ['label'=>'Refusé',     'color'=>'#EF5350', 'bg'=>'rgba(239,68,68,.12)'],
];

$msg     = $_GET['msg']  ?? '';
$msgType = $_GET['type'] ?? 'success';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Témoignages — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}

    /* Grid témoignages */
    .tem-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px}
    .tem-card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;transition:var(--transition);position:relative}
    .tem-card:hover{border-color:rgba(212,175,55,.3);transform:translateY(-2px)}
    .tem-card.attente{border-left:3px solid #FBB724}
    .tem-card.publie{border-left:3px solid #25D366}
    .tem-card.refuse{border-left:3px solid #EF5350}
    .tem-header{display:flex;align-items:center;gap:12px;margin-bottom:14px}
    .tem-avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--gold-dark),var(--gold));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;color:var(--dark);flex-shrink:0}
    .tem-name{font-size:.9rem;font-weight:700;color:var(--white)}
    .tem-meta{font-size:.73rem;color:var(--text-muted);margin-top:2px}
    .tem-vedette{position:absolute;top:14px;right:14px;color:var(--gold);font-size:.85rem}
    .stars{display:flex;gap:2px;margin-bottom:10px}
    .star{color:#333;font-size:.85rem}
    .star.on{color:#FBB724}
    .tem-content{font-size:.82rem;color:var(--text-muted);line-height:1.7;margin-bottom:16px;font-style:italic;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden}
    .tem-footer{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid var(--border)}
    .statut-pill{padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700}
    .tem-actions{display:flex;gap:6px}
    .act-btn{width:28px;height:28px;border-radius:7px;border:1px solid var(--border);background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.75rem;transition:var(--transition);color:var(--text-muted)}
    .act-btn:hover{border-color:var(--gold);color:var(--gold)}
    .act-btn.danger:hover{border-color:rgba(239,68,68,.4);color:#EF5350}
    .act-btn.star-on{border-color:rgba(251,183,36,.3);color:#FBB724}

    /* Modal */
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:2000;align-items:center;justify-content:center;padding:20px}
    .modal-overlay.show{display:flex}
    .modal-box{background:var(--dark-card);border:1px solid var(--border);border-radius:14px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto}
    .modal-header{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:var(--dark-card);z-index:1}
    .modal-header h3{color:var(--white);font-size:.95rem}
    .modal-close{width:30px;height:30px;border-radius:7px;border:1px solid var(--border);background:none;color:var(--text-muted);cursor:pointer;display:flex;align-items:center;justify-content:center}
    .modal-close:hover{border-color:var(--gold);color:var(--gold)}
    .modal-body{padding:22px}
    .modal-footer{padding:14px 22px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .form-full{grid-column:1/-1}

    /* Star rating input */
    .star-input{display:flex;gap:6px;flex-direction:row-reverse;justify-content:flex-end}
    .star-input input{display:none}
    .star-input label{cursor:pointer;font-size:1.4rem;color:#333;transition:.15s}
    .star-input label:hover,.star-input label:hover~label,
    .star-input input:checked~label{color:#FBB724}

    .empty-state{text-align:center;padding:60px 20px;color:var(--text-muted)}
    .empty-state i{font-size:2.5rem;opacity:.2;display:block;margin-bottom:12px}
    .tfilter{padding:6px 14px;border-radius:20px;border:1px solid var(--border);background:none;color:#888;cursor:pointer;font-size:.75rem;transition:var(--transition);font-family:var(--ff-body)}
    .tfilter.active,.tfilter:hover{border-color:var(--gold);color:var(--gold)}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">

  <?php $activePage = 'temoignages'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2 data-fr="Témoignages Clients" data-ar="شهادات العملاء">Témoignages Clients</h2><p data-fr="Avis et notes des clients" data-ar="آراء وتقييمات العملاء">Avis et notes des clients</p></div>
      </div>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
        <button class="btn-primary" style="padding:8px 18px;font-size:.82rem" onclick="openAdd()">
          <i class="fas fa-plus"></i> <span data-fr="Ajouter un avis" data-ar="إضافة تقييم">Ajouter un avis</span>
        </button>
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
          <div class="stat-card-header"><div class="stat-card-icon gold"><i class="fas fa-star"></i></div></div>
          <div class="stat-card-value"><?= $total ?></div>
          <div class="stat-card-label" data-fr="Total avis" data-ar="إجمالي التقييمات">Total avis</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(251,183,36,.1);color:#FBB724"><i class="fas fa-hourglass-half"></i></div></div>
          <div class="stat-card-value"><?= $attente ?></div>
          <div class="stat-card-label" data-fr="En attente" data-ar="في الانتظار" data-fr="En attente" data-ar="في الانتظار" data-fr="En attente" data-ar="في الانتظار">En attente</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-check-circle"></i></div></div>
          <div class="stat-card-value"><?= $publies ?></div>
          <div class="stat-card-label" data-fr="Publiés" data-ar="منشورة" data-fr="Publiés" data-ar="منشورة">Publiés</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(212,175,55,.1);color:var(--gold)"><i class="fas fa-star-half-alt"></i></div></div>
          <div class="stat-card-value"><?= $moyNote ?><span style="font-size:1rem;color:var(--text-muted)">/5</span></div>
          <div class="stat-card-label" data-fr="Note moyenne" data-ar="متوسط التقييم">Note moyenne</div>
        </div>
      </div>

      <!-- Filtres -->
      <div style="display:flex;gap:10px;align-items:center;margin-bottom:20px;flex-wrap:wrap">
        <button class="tfilter active" onclick="setFilter('all',this)" data-fr="Tous" data-ar="الكل">Tous (<?= $total ?>)</button>
        <button class="tfilter" onclick="setFilter('en_attente',this)">⏳ En attente (<?= $attente ?>)</button>
        <button class="tfilter" onclick="setFilter('publie',this)">✅ Publiés (<?= $publies ?>)</button>
        <button class="tfilter" onclick="setFilter('vedette',this)">⭐ En vedette (<?= $vedettes ?>)</button>
        <button class="tfilter" onclick="setFilter('5',this)">★★★★★ 5 étoiles</button>
      </div>

      <!-- Grille témoignages -->
      <?php if (empty($temoignages)): ?>
      <div class="empty-state">
        <i class="fas fa-star"></i>
        <p><span data-fr="Aucun témoignage" data-ar="لا توجد شهادات">Aucun témoignage</span> pour l'instant.</p>
        <button class="btn-primary" style="margin-top:16px" onclick="openAdd()">
          <i class="fas fa-plus"></i> Ajouter le premier avis
        </button>
      </div>
      <?php else: ?>
      <div class="tem-grid" id="temGrid">
        <?php foreach ($temoignages as $t):
          $sc       = $statutConfig[$t['statut']] ?? $statutConfig['en_attente'];
          $initials = strtoupper(substr($t['nom_client'], 0, 1) . (strpos($t['nom_client'],' ') ? substr($t['nom_client'], strpos($t['nom_client'],' ')+1, 1) : ''));
          $date     = date('d/m/Y', strtotime($t['created_at']));
        ?>
        <div class="tem-card <?= $t['statut'] ?>"
             data-statut="<?= $t['statut'] ?>"
             data-note="<?= $t['note'] ?>"
             data-vedette="<?= $t['en_vedette'] ?>">
          <?php if ($t['en_vedette']): ?>
          <div class="tem-vedette" title="En vedette"><i class="fas fa-star"></i></div>
          <?php endif; ?>

          <div class="tem-header">
            <div class="tem-avatar"><?= htmlspecialchars($initials) ?></div>
            <div>
              <div class="tem-name"><?= htmlspecialchars($t['nom_client']) ?></div>
              <div class="tem-meta">
                <?= htmlspecialchars($t['ville'] ?? 'Errachidia') ?>
                <?php if ($t['type_evenement']): ?>
                 · <?= htmlspecialchars($t['type_evenement']) ?>
                <?php endif; ?>
                · <?= $date ?>
              </div>
            </div>
          </div>

          <div class="stars">
            <?php for ($i=1; $i<=5; $i++): ?>
            <i class="fas fa-star star <?= $i <= $t['note'] ? 'on' : '' ?>"></i>
            <?php endfor; ?>
            <span style="font-size:.75rem;color:var(--text-muted);margin-left:4px"><?= $t['note'] ?>/5</span>
          </div>

          <div class="tem-content">"<?= htmlspecialchars($t['contenu']) ?>"</div>

          <div class="tem-footer">
            <span class="statut-pill" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>">
              <?= $sc['label'] ?>
            </span>
            <div class="tem-actions">
              <!-- Vedette -->
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_vedette">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button type="submit" class="act-btn <?= $t['en_vedette'] ? 'star-on' : '' ?>" title="<?= $t['en_vedette'] ? 'Retirer de la vedette' : 'Mettre en vedette' ?>">
                  <i class="fas fa-star"></i>
                </button>
              </form>
              <!-- Publier -->
              <?php if ($t['statut'] !== 'publie'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="update_statut">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <input type="hidden" name="statut" value="publie">
                <button type="submit" class="act-btn" style="color:#25D366;border-color:rgba(37,211,102,.3)" title="Publier">
                  <i class="fas fa-check"></i>
                </button>
              </form>
              <?php else: ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="update_statut">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <input type="hidden" name="statut" value="en_attente">
                <button type="submit" class="act-btn" style="color:#888;border-color:rgba(136,136,136,.3)" title="Dépublier">
                  <i class="fas fa-eye-slash"></i>
                </button>
              </form>
              <?php endif; ?>
              <!-- Refuser -->
              <?php if ($t['statut'] !== 'refuse'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="update_statut">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <input type="hidden" name="statut" value="refuse">
                <button type="submit" class="act-btn danger" title="Refuser">
                  <i class="fas fa-times"></i>
                </button>
              </form>
              <?php endif; ?>
              <!-- Supprimer -->
              <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce témoignage ?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button type="submit" class="act-btn danger" title="Supprimer">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<!-- Modal ajouter témoignage -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-plus-circle" style="color:var(--gold);margin-right:8px"></i><span data-fr="Ajouter un témoignage" data-ar="إضافة شهادة">Ajouter un témoignage</span></h3>
      <button class="modal-close" onclick="closeAdd()"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-grid" style="margin-bottom:14px">
          <div class="form-group">
            <label class="form-label" data-fr="Nom du client *" data-ar="اسم العميل *">Nom du client *</label>
            <input type="text" name="nom_client" class="form-control" placeholder="Fatima B." required>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Ville" data-ar="المدينة">Ville</label>
            <input type="text" name="ville" class="form-control" value="Errachidia">
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Type d'événement" data-ar="نوع المناسبة">Type d'événement</label>
            <select name="type_evenement" class="form-control">
              <option value="Mariage">Mariage</option>
              <option value="Fiançailles">Fiançailles</option>
              <option value="Circoncision">Circoncision</option>
              <option value="Anniversaire">Anniversaire</option>
              <option value="Réception Pro">Réception Pro</option>
              <option value="Buffet">Buffet</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Statut" data-ar="الحالة">Statut</label>
            <select name="statut" class="form-control">
              <option value="publie">Publier directement</option>
              <option value="en_attente" data-fr="En attente" data-ar="في الانتظار">En attente de modération</option>
            </select>
          </div>
          <div class="form-group form-full">
            <label class="form-label">Note sur 5 *</label>
            <div class="star-input">
              <?php for ($i=5; $i>=1; $i--): ?>
              <input type="radio" name="note" id="star<?= $i ?>" value="<?= $i ?>" <?= $i===5?'checked':'' ?>>
              <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
              <?php endfor; ?>
            </div>
          </div>
          <div class="form-group form-full">
            <label class="form-label" data-fr="Témoignage *" data-ar="نص الشهادة *">Témoignage *</label>
            <textarea name="contenu" class="form-control" rows="4"
                      placeholder="L'avis du client..." required maxlength="600"></textarea>
          </div>
          <div class="form-group form-full">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.85rem;color:var(--text-muted)">
              <input type="checkbox" name="en_vedette" value="1" style="accent-color:var(--gold);width:16px;height:16px">
              <i class="fas fa-star" style="color:var(--gold)"></i> Mettre en vedette (affiché sur l'accueil)
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="closeAdd()">Annuler</button>
        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
      </div>
    </form>
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

function openAdd()  { document.getElementById('addModal').classList.add('show'); }
function closeAdd() { document.getElementById('addModal').classList.remove('show'); }

// Filtres
let curFilter = 'all';
function setFilter(f, btn) {
  curFilter = f;
  document.querySelectorAll('.tfilter').forEach(b => { b.classList.remove('active'); });
  btn.classList.add('active');
  document.querySelectorAll('.tem-card').forEach(card => {
    let show = true;
    if (f === 'all')        show = true;
    else if (f === 'vedette') show = card.dataset.vedette === '1';
    else if (f === '5')       show = card.dataset.note    === '5';
    else                      show = card.dataset.statut  === f;
    card.style.display = show ? '' : 'none';
  });
}
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
