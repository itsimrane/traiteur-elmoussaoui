<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// Créer table paiements si elle n'existe pas
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `paiements` (
        `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `facture_id`   INT UNSIGNED NULL,
        `facture_num`  VARCHAR(30)  NULL,
        `nom_client`   VARCHAR(200) NOT NULL,
        `telephone`    VARCHAR(20)  NULL,
        `montant`      DECIMAL(10,2) NOT NULL DEFAULT 0,
        `type`         ENUM('acompte','solde','remboursement','autre') NOT NULL DEFAULT 'acompte',
        `mode`         ENUM('especes','virement','cheque','autre') NOT NULL DEFAULT 'especes',
        `statut`       ENUM('recu','en_attente','annule') NOT NULL DEFAULT 'recu',
        `reference`    VARCHAR(100) NULL COMMENT 'N° chèque ou virement',
        `notes`        TEXT NULL,
        `date_paiement` DATE NOT NULL DEFAULT (CURDATE()),
        `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_paiements_facture` (`facture_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e) {}

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $montant = (float)($_POST['montant'] ?? 0);
        $pdo->prepare("
            INSERT INTO paiements (facture_id, facture_num, nom_client, telephone, montant, type, mode, statut, reference, notes, date_paiement)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            (int)($_POST['facture_id'] ?? 0) ?: null,
            sanitize($_POST['facture_num']  ?? ''),
            sanitize($_POST['nom_client']   ?? ''),
            sanitize($_POST['telephone']    ?? ''),
            $montant,
            sanitize($_POST['type']         ?? 'acompte'),
            sanitize($_POST['mode']         ?? 'especes'),
            sanitize($_POST['statut']       ?? 'recu'),
            sanitize($_POST['reference']    ?? ''),
            sanitize($_POST['notes']        ?? ''),
            $_POST['date_paiement'] ?: date('Y-m-d'),
        ]);
        // Mettre à jour l'acompte sur la facture si liée
        $facId = (int)($_POST['facture_id'] ?? 0);
        if ($facId > 0) {
            $pdo->prepare("
                UPDATE factures
                SET acompte = acompte + ?,
                    reste_a_payer = GREATEST(0, reste_a_payer - ?),
                    statut = CASE WHEN reste_a_payer - ? <= 0 THEN 'payee' WHEN acompte + ? > 0 THEN 'partiellement_payee' ELSE statut END,
                    updated_at = NOW()
                WHERE id = ?
            ")->execute([$montant, $montant, $montant, $montant, $facId]);
        }
        header('Location: paiements.php?msg=Paiement+enregistré+avec+succès&type=success');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM paiements WHERE id = ?")->execute([$id]);
        header('Location: paiements.php?msg=Paiement+supprimé&type=success');
        exit;
    }
}

// Récupérer les paiements
$paiements = $pdo->query("SELECT * FROM paiements ORDER BY date_paiement DESC, created_at DESC")->fetchAll();

// Récupérer les factures pour le select
try {
    $factures = $pdo->query("SELECT id, numero, nom_client, reste_a_payer FROM factures WHERE statut != 'annulee' ORDER BY created_at DESC")->fetchAll();
} catch(Exception $e) { $factures = []; }

// Stats
$total        = count($paiements);
$totalEncaisse= array_sum(array_column($paiements, 'montant'));
$reçus        = array_filter($paiements, fn($p) => $p['statut'] === 'recu');
$enAttente    = array_filter($paiements, fn($p) => $p['statut'] === 'en_attente');
$moisEnCours  = array_filter($paiements, fn($p) => substr($p['date_paiement'],0,7) === date('Y-m'));
$totalMois    = array_sum(array_column(iterator_to_array((function() use ($moisEnCours) { foreach($moisEnCours as $v) yield $v; })()),'montant'));

$typeConfig = [
    'acompte'       => ['label'=>'Acompte',       'color'=>'#FBB724','bg'=>'rgba(251,183,36,.12)'],
    'solde'         => ['label'=>'Solde',          'color'=>'#25D366','bg'=>'rgba(37,211,102,.12)'],
    'remboursement' => ['label'=>'Remboursement',  'color'=>'#EF5350','bg'=>'rgba(239,68,68,.12)'],
    'autre'         => ['label'=>'Autre',          'color'=>'#888',   'bg'=>'rgba(136,136,136,.1)'],
];
$modeIcons = [
    'especes'  => ['icon'=>'fa-money-bill-wave', 'label'=>'Espèces'],
    'virement' => ['icon'=>'fa-university',      'label'=>'Virement'],
    'cheque'   => ['icon'=>'fa-file-alt',        'label'=>'Chèque'],
    'autre'    => ['icon'=>'fa-ellipsis-h',      'label'=>'Autre'],
];
$statutConfig = [
    'recu'       => ['label'=>'Reçu ✓',    'color'=>'#25D366','bg'=>'rgba(37,211,102,.12)'],
    'en_attente' => ['label'=>'En attente', 'color'=>'#FBB724','bg'=>'rgba(251,183,36,.12)'],
    'annule'     => ['label'=>'Annulé',    'color'=>'#EF5350','bg'=>'rgba(239,68,68,.12)'],
];

$msg     = $_GET['msg']  ?? '';
$msgType = $_GET['type'] ?? 'success';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Paiements — Admin EL MOUSSAOUI</title>
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
    .badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700}
    .td-actions{display:flex;gap:6px}
    .act-btn{width:30px;height:30px;border-radius:7px;border:1px solid var(--border);background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.78rem;transition:var(--transition);color:var(--text-muted)}
    .act-btn:hover{border-color:var(--gold);color:var(--gold)}
    .act-btn.danger:hover{border-color:rgba(239,68,68,.4);color:#EF5350}
    .empty-state{text-align:center;padding:60px 20px;color:var(--text-muted)}
    .empty-state i{font-size:2.5rem;opacity:.2;display:block;margin-bottom:12px}
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:2000;align-items:center;justify-content:center;padding:20px}
    .modal-overlay.show{display:flex}
    .modal-box{background:var(--dark-card);border:1px solid var(--border);border-radius:14px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto}
    .modal-header{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:var(--dark-card);z-index:1}
    .modal-header h3{color:var(--white);font-size:.95rem}
    .modal-close{width:30px;height:30px;border-radius:7px;border:1px solid var(--border);background:none;color:var(--text-muted);cursor:pointer;display:flex;align-items:center;justify-content:center}
    .modal-close:hover{border-color:var(--gold);color:var(--gold)}
    .modal-body{padding:22px}
    .modal-footer{padding:14px 22px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .form-full{grid-column:1/-1}
    .tfilter{padding:5px 14px;border-radius:20px;border:1px solid var(--border);background:none;color:#888;cursor:pointer;font-size:.75rem;transition:var(--transition);font-family:var(--ff-body)}
    .tfilter.active,.tfilter:hover{border-color:var(--gold);color:var(--gold)}
    .amount-big{font-family:var(--ff-display);font-size:.95rem;font-weight:700;color:var(--white)}
    /* Graphique mini */
    .mini-bars{display:flex;align-items:flex-end;gap:4px;height:40px;margin-top:8px}
    .mini-bar{flex:1;background:rgba(212,175,55,.3);border-radius:3px 3px 0 0;min-height:4px;transition:.3s;cursor:default}
    .mini-bar:hover{background:var(--gold)}
    .mini-bar-label{font-size:.6rem;color:#555;text-align:center;margin-top:2px}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">

  <?php $activePage = 'paiements'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2 data-fr="Suivi Paiements" data-ar="متابعة المدفوعات">Suivi Paiements</h2><p data-fr="Acomptes, soldes et encaissements" data-ar="الدفعات الأولى، الأرصدة والتحصيلات">Acomptes, soldes et encaissements</p></div>
      </div>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
        <button class="btn-primary" style="padding:8px 18px;font-size:.82rem" onclick="openAdd()">
          <i class="fas fa-plus"></i> <span data-fr="Enregistrer un paiement" data-ar="تسجيل دفعة">Enregistrer un paiement</span>
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
          <div class="stat-card-header"><div class="stat-card-icon gold"><i class="fas fa-credit-card"></i></div></div>
          <div class="stat-card-value"><?= $total ?></div>
          <div class="stat-card-label" data-fr="Total paiements" data-ar="إجمالي الدفعات">Total paiements</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-coins"></i></div></div>
          <div class="stat-card-value" style="font-size:1.1rem" dir="ltr"><?= number_format($totalEncaisse,0,',',' ') ?></div>
          <div class="stat-card-label" data-fr="Total encaissé (MAD)" data-ar="إجمالي المحصّل (MAD)">Total encaissé (MAD)</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(59,130,246,.1);color:#60A5FA"><i class="fas fa-calendar-alt"></i></div></div>
          <div class="stat-card-value" style="font-size:1.1rem" dir="ltr"><?= number_format($totalMois,0,',',' ') ?></div>
          <div class="stat-card-label" data-fr="Ce mois (MAD)" data-ar="هذا الشهر (MAD)">Ce mois (MAD)</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(251,183,36,.1);color:#FBB724"><i class="fas fa-hourglass-half"></i></div></div>
          <div class="stat-card-value"><?= count($enAttente) ?></div>
          <div class="stat-card-label" data-fr="En attente" data-ar="في الانتظار">En attente</div>
        </div>
      </div>

      <!-- Résumé par mode de paiement -->
      <?php if (!empty($paiements)):
        $byMode = [];
        foreach ($paiements as $p) {
            $m = $p['mode'];
            $byMode[$m] = ($byMode[$m] ?? 0) + $p['montant'];
        }
      ?>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px">
        <?php foreach ($modeIcons as $key => $mi):
          $val = $byMode[$key] ?? 0;
          if ($val == 0) continue;
        ?>
        <div class="chart-card" style="padding:16px;text-align:center">
          <i class="fas <?= $mi['icon'] ?>" style="font-size:1.4rem;color:var(--gold);margin-bottom:8px;display:block"></i>
          <div style="font-size:.7rem;color:var(--text-muted);margin-bottom:4px"><?= $mi['label'] ?></div>
          <div style="font-family:var(--ff-display);font-size:1rem;font-weight:700;color:var(--white)" dir="ltr"><?= number_format($val,0,',',' ') ?> MAD</div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Table paiements -->
      <div class="table-wrap">
        <div class="table-topbar">
          <h3 style="color:var(--white);font-size:.9rem">
            <i class="fas fa-list" style="color:var(--gold);margin-right:8px"></i><span data-fr="Historique des paiements" data-ar="سجل المدفوعات">Historique des paiements</span> (<?= $total ?>)
          </h3>
          <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <input type="text" class="search-input" id="searchPai" placeholder="🔍 Client, facture..." data-fr-placeholder="🔍 Client, facture..." data-ar-placeholder="🔍 العميل، الفاتورة..." oninput="filterPai()">
            <div style="display:flex;gap:6px">
              <button class="tfilter active" onclick="setFilter('all',this)" data-fr="Tous" data-ar="الكل">Tous</button>
              <button class="tfilter" onclick="setFilter('recu',this)" data-fr="Reçus" data-ar="مستلمة">Reçus</button>
              <button class="tfilter" onclick="setFilter('en_attente',this)" data-fr="En attente" data-ar="في الانتظار">En attente</button>
              <button class="tfilter" onclick="setFilter('acompte','type',this)" data-fr="Acomptes" data-ar="دفعات أولى">Acomptes</button>
              <button class="tfilter" onclick="setFilter('solde','type',this)" data-fr="Soldes" data-ar="أرصدة">Soldes</button>
            </div>
          </div>
        </div>

        <?php if (empty($paiements)): ?>
        <div class="empty-state">
          <i class="fas fa-credit-card"></i>
          <p data-fr="Aucun paiement enregistré pour l'instant." data-ar="لا توجد مدفوعات مسجلة حالياً.">Aucun paiement enregistré pour l'instant.</p>
          <button class="btn-primary" style="margin-top:16px" onclick="openAdd()">
            <i class="fas fa-plus"></i> <span data-fr="Enregistrer le premier paiement" data-ar="تسجيل أول دفعة">Enregistrer le premier paiement</span>
          </button>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table id="paiTable">
          <thead>
            <tr>
              <th>#</th>
              <th data-fr="Client" data-ar="العميل">Client</th>
              <th data-fr="Facture" data-ar="الفاتورة">Facture</th>
              <th data-fr="Montant" data-ar="المبلغ">Montant</th>
              <th data-fr="Type" data-ar="النوع">Type</th>
              <th data-fr="Mode" data-ar="طريقة الدفع">Mode</th>
              <th data-fr="Statut" data-ar="الحالة">Statut</th>
              <th data-fr="Date" data-ar="التاريخ">Date</th>
              <th data-fr="Actions" data-ar="الإجراءات">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($paiements as $p):
              $tc = $typeConfig[$p['type']] ?? $typeConfig['autre'];
              $sc = $statutConfig[$p['statut']] ?? $statutConfig['recu'];
              $mi = $modeIcons[$p['mode']] ?? $modeIcons['autre'];
              $date = date('d/m/Y', strtotime($p['date_paiement']));
            ?>
            <tr data-statut="<?= $p['statut'] ?>"
                data-type="<?= $p['type'] ?>"
                data-search="<?= strtolower($p['nom_client'].' '.($p['facture_num']??'').' '.($p['telephone']??'')) ?>">
              <td style="color:#555;font-size:.75rem">#<?= $p['id'] ?></td>
              <td>
                <div style="color:var(--white);font-weight:600;font-size:.84rem"><?= htmlspecialchars($p['nom_client']) ?></div>
                <div style="font-size:.72rem;color:#555"><?= htmlspecialchars($p['telephone'] ?? '') ?></div>
              </td>
              <td>
                <?php if ($p['facture_num']): ?>
                <a href="factures.php" style="color:var(--gold);font-size:.8rem;font-weight:600"><?= htmlspecialchars($p['facture_num']) ?></a>
                <?php else: ?>
                <span style="color:#555;font-size:.78rem">—</span>
                <?php endif; ?>
              </td>
              <td class="amount-big" dir="ltr"><?= number_format($p['montant'],0,',',' ') ?> MAD</td>
              <td><span class="badge" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>"><?= $tc['label'] ?></span></td>
              <td>
                <span style="display:flex;align-items:center;gap:5px;font-size:.78rem;color:var(--text-muted)">
                  <i class="fas <?= $mi['icon'] ?>" style="color:var(--gold)"></i> <?= $mi['label'] ?>
                </span>
                <?php if ($p['reference']): ?>
                <div style="font-size:.7rem;color:#555;margin-top:2px">Réf: <?= htmlspecialchars($p['reference']) ?></div>
                <?php endif; ?>
              </td>
              <td><span class="badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>"><?= $sc['label'] ?></span></td>
              <td style="font-size:.78rem;color:#888"><?= $date ?></td>
              <td>
                <div class="td-actions">
                  <button class="act-btn" onclick='openDetail(<?= json_encode($p) ?>)' title="Détail"><i class="fas fa-eye"></i></button>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce paiement ?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
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

<!-- Modal ajouter paiement -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-plus-circle" style="color:var(--gold);margin-right:8px"></i><span data-fr="Enregistrer un paiement" data-ar="تسجيل دفعة">Enregistrer un paiement</span></h3>
      <button class="modal-close" onclick="closeAdd()"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-grid" style="margin-bottom:12px">
          <!-- Lier à une facture -->
          <div class="form-group form-full">
            <label class="form-label">Lier à une facture (optionnel)</label>
            <select name="facture_id" id="facSelect" class="form-control" onchange="fillFromFac(this)">
              <option value="">— Sans facture —</option>
              <?php foreach ($factures as $fac): ?>
              <option value="<?= $fac['id'] ?>"
                      data-num="<?= htmlspecialchars($fac['numero']) ?>"
                      data-client="<?= htmlspecialchars($fac['nom_client']) ?>"
                      data-reste="<?= $fac['reste_a_payer'] ?>">
                <?= htmlspecialchars($fac['numero']) ?> — <?= htmlspecialchars($fac['nom_client']) ?>
                (reste: <?= number_format($fac['reste_a_payer'],0,',',' ') ?> MAD)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="N° Facture" data-ar="رقم الفاتورة">N° Facture</label>
            <input type="text" name="facture_num" id="facNum" class="form-control" placeholder="FAC-2026-0001" data-fr-placeholder="FAC-2026-0001" data-ar-placeholder="FAC-2026-0001">
          </div>
          <div class="form-group">
            <label class="form-label">Nom du client *</label>
            <input type="text" name="nom_client" id="nomClient" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Téléphone" data-ar="الهاتف">Téléphone</label>
            <input type="tel" name="telephone" class="form-control" placeholder="06XXXXXXXX" data-fr-placeholder="06XXXXXXXX" data-ar-placeholder="06XXXXXXXX">
          </div>
          <div class="form-group">
            <label class="form-label">Montant (MAD) *</label>
            <input type="number" name="montant" id="montantInput" class="form-control" min="1" step="100" placeholder="5400" required>
          </div>
          <div class="form-group">
            <label class="form-label">Date de paiement</label>
            <input type="date" name="date_paiement" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" class="form-control">
              <option value="acompte">Acompte (30%)</option>
              <option value="solde" data-fr="Solde" data-ar="رصيد">Solde</option>
              <option value="remboursement">Remboursement</option>
              <option value="autre">Autre</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Mode de paiement" data-ar="طريقة الدفع">Mode de paiement</label>
            <select name="mode" class="form-control">
              <option value="especes">💵 Espèces</option>
              <option value="virement">🏦 Virement</option>
              <option value="cheque">📝 Chèque</option>
              <option value="autre">Autre</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Statut" data-ar="الحالة">Statut</label>
            <select name="statut" class="form-control">
              <option value="recu">Reçu ✓</option>
              <option value="en_attente" data-fr="En attente" data-ar="في الانتظار">En attente</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Référence (n° chèque/virement)</label>
            <input type="text" name="reference" class="form-control" placeholder="Ex: CHQ-00123" data-fr-placeholder="Ex: CHQ-00123" data-ar-placeholder="مثال: CHQ-00123">
          </div>
          <div class="form-group form-full">
            <label class="form-label" data-fr="Notes" data-ar="ملاحظات">Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Remarques..." data-fr-placeholder="Remarques..." data-ar-placeholder="ملاحظات..."></textarea>
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

<!-- Modal détail -->
<div class="modal-overlay" id="detailModal">
  <div class="modal-box" style="max-width:440px">
    <div class="modal-header">
      <h3><i class="fas fa-credit-card" style="color:var(--gold);margin-right:8px"></i>Détail paiement</h3>
      <button class="modal-close" onclick="closeDetail()"><i class="fas fa-times"></i></button>
    </div>
    <div id="detailContent" class="modal-body"></div>
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

// Auto-remplissage depuis facture
function fillFromFac(sel) {
  const opt = sel.options[sel.selectedIndex];
  if (opt.value) {
    document.getElementById('facNum').value    = opt.dataset.num    || '';
    document.getElementById('nomClient').value = opt.dataset.client || '';
    document.getElementById('montantInput').value = opt.dataset.reste || '';
  }
}

function openAdd()  { document.getElementById('addModal').classList.add('show'); }
function closeAdd() { document.getElementById('addModal').classList.remove('show'); }

const tc = <?= json_encode($typeConfig) ?>;
const sc = <?= json_encode($statutConfig) ?>;
const mi = <?= json_encode($modeIcons) ?>;
function openDetail(p) {
  const date = new Date(p.date_paiement).toLocaleDateString('fr-FR');
  const t = tc[p.type] || tc['autre'];
  const s = sc[p.statut] || sc['recu'];
  const m = mi[p.mode] || mi['autre'];
  document.getElementById('detailContent').innerHTML = `
    <div style="text-align:center;margin-bottom:20px">
      <div style="font-family:var(--ff-display);font-size:2rem;font-weight:700;color:var(--gold)" dir="ltr">${parseFloat(p.montant).toLocaleString('fr-FR')} MAD</div>
      <div style="font-size:.8rem;color:var(--text-muted);margin-top:4px">${date}</div>
      <div style="margin-top:10px;display:flex;gap:8px;justify-content:center">
        <span class="badge" style="background:${t.bg};color:${t.color}">${t.label}</span>
        <span class="badge" style="background:${s.bg};color:${s.color}">${s.label}</span>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Client</div><div style="color:var(--white)">${p.nom_client}</div></div>
      <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Téléphone</div><div style="color:var(--white)">${p.telephone||'—'}</div></div>
      <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Facture</div><div style="color:var(--gold)">${p.facture_num||'—'}</div></div>
      <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Mode</div><div style="color:var(--white)"><i class="fas ${m.icon}" style="color:var(--gold);margin-right:4px"></i>${m.label}</div></div>
      ${p.reference ? `<div class="form-full" style="grid-column:1/-1"><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Référence</div><div style="color:var(--white)">${p.reference}</div></div>` : ''}
      ${p.notes ? `<div style="grid-column:1/-1;margin-top:8px;background:var(--dark-3);border-radius:8px;padding:10px;font-size:.82rem;color:var(--text-muted)">${p.notes}</div>` : ''}
    </div>`;
  document.getElementById('detailModal').classList.add('show');
}
function closeDetail() { document.getElementById('detailModal').classList.remove('show'); }

// Filtres
let curFilter = 'all', curFilterCol = 'statut';
function setFilter(val, col, btn) {
  curFilter    = val;
  curFilterCol = col || 'statut';
  document.querySelectorAll('.tfilter').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  filterPai();
}
function filterPai() {
  const q = document.getElementById('searchPai').value.toLowerCase();
  document.querySelectorAll('#paiTable tbody tr').forEach(row => {
    const matchF = curFilter === 'all' ||
                   (curFilterCol === 'statut' && row.dataset.statut === curFilter) ||
                   (curFilterCol === 'type'   && row.dataset.type   === curFilter);
    const matchQ = !q || row.dataset.search.includes(q);
    row.style.display = (matchF && matchQ) ? '' : 'none';
  });
}
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
