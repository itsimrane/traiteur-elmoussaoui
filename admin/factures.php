<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// Créer la table si elle n'existe pas
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `factures` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `numero` VARCHAR(30) NOT NULL,
        `client_id` INT UNSIGNED NULL,
        `reservation_id` INT UNSIGNED NULL,
        `nom_client` VARCHAR(200) NOT NULL,
        `email_client` VARCHAR(191) NULL,
        `telephone_client` VARCHAR(20) NULL,
        `type_evenement` VARCHAR(100) NULL,
        `date_evenement` DATE NULL,
        `nb_personnes` INT NULL,
        `package_nom` VARCHAR(100) NULL,
        `montant_ht` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `tva` DECIMAL(5,2) NOT NULL DEFAULT 0,
        `montant_tva` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `montant_ttc` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `acompte` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `reste_a_payer` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `statut` ENUM('brouillon','envoyee','payee','partiellement_payee','annulee') NOT NULL DEFAULT 'brouillon',
        `notes` TEXT NULL,
        `date_echeance` DATE NULL,
        `date_paiement` DATE NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e) {}

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $montantHT  = (float)($_POST['montant_ht'] ?? 0);
        $tva        = (float)($_POST['tva'] ?? 0);
        $montantTVA = round($montantHT * $tva / 100, 2);
        $montantTTC = round($montantHT + $montantTVA, 2);
        $acompte    = (float)($_POST['acompte'] ?? 0);
        $reste      = round($montantTTC - $acompte, 2);

        // Générer numéro auto
        $lastId = $pdo->query("SELECT MAX(id) FROM factures")->fetchColumn() + 1;
        $numero = 'FAC-' . date('Y') . '-' . str_pad($lastId, 4, '0', STR_PAD_LEFT);

        $pdo->prepare("
            INSERT INTO factures (numero, nom_client, email_client, telephone_client,
                type_evenement, date_evenement, nb_personnes, package_nom,
                montant_ht, tva, montant_tva, montant_ttc, acompte, reste_a_payer,
                statut, notes, date_echeance)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $numero,
            sanitize($_POST['nom_client'] ?? ''),
            sanitize($_POST['email_client'] ?? ''),
            sanitize($_POST['telephone_client'] ?? ''),
            sanitize($_POST['type_evenement'] ?? ''),
            $_POST['date_evenement'] ?: null,
            (int)($_POST['nb_personnes'] ?? 0) ?: null,
            sanitize($_POST['package_nom'] ?? ''),
            $montantHT, $tva, $montantTVA, $montantTTC, $acompte, $reste,
            sanitize($_POST['statut'] ?? 'brouillon'),
            sanitize($_POST['notes'] ?? ''),
            $_POST['date_echeance'] ?: null,
        ]);
        header('Location: factures.php?msg=Facture+créée+avec+succès&type=success');
        exit;
    }

    if ($action === 'update_statut') {
        $id     = (int)($_POST['id'] ?? 0);
        $statut = sanitize($_POST['statut'] ?? '');
        $datePaiement = ($statut === 'payee') ? date('Y-m-d') : null;
        $pdo->prepare("UPDATE factures SET statut=?, date_paiement=?, updated_at=NOW() WHERE id=?")
            ->execute([$statut, $datePaiement, $id]);
        header('Location: factures.php?msg=Statut+mis+à+jour&type=success');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM factures WHERE id=?")->execute([$id]);
        header('Location: factures.php?msg=Facture+supprimée&type=success');
        exit;
    }
}

// Récupérer toutes les factures
$factures = $pdo->query("SELECT * FROM factures ORDER BY created_at DESC")->fetchAll();
$packages = $pdo->query("SELECT nom FROM packages WHERE actif=1 ORDER BY ordre")->fetchAll(PDO::FETCH_COLUMN);

// Stats
$total       = count($factures);
$totalHT     = array_sum(array_column($factures, 'montant_ttc'));
$payees      = array_filter($factures, fn($f) => $f['statut'] === 'payee');
$enAttente   = array_filter($factures, fn($f) => in_array($f['statut'], ['envoyee','partiellement_payee']));
$totalEncaisse = array_sum(array_column($factures, 'acompte'));
$totalReste    = array_sum(array_column($factures, 'reste_a_payer'));

$statutConfig = [
    'brouillon'           => ['label' => 'Brouillon',       'color' => '#888',    'bg' => 'rgba(136,136,136,.12)'],
    'envoyee'             => ['label' => 'Envoyée',          'color' => '#60A5FA', 'bg' => 'rgba(59,130,246,.12)'],
    'payee'               => ['label' => 'Payée ✓',          'color' => '#25D366', 'bg' => 'rgba(37,211,102,.12)'],
    'partiellement_payee' => ['label' => 'Partiel',          'color' => '#FBB724', 'bg' => 'rgba(251,183,36,.12)'],
    'annulee'             => ['label' => 'Annulée',          'color' => '#EF5350', 'bg' => 'rgba(239,68,68,.12)'],
];

$msg     = $_GET['msg']  ?? '';
$msgType = $_GET['type'] ?? 'success';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Factures — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { overflow-x:hidden; }
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
    .statut-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap}
    .td-actions{display:flex;gap:6px}
    .act-btn{width:30px;height:30px;border-radius:7px;border:1px solid var(--border);background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.78rem;transition:var(--transition);color:var(--text-muted);text-decoration:none}
    .act-btn:hover{border-color:var(--gold);color:var(--gold)}
    .act-btn.danger:hover{border-color:rgba(239,68,68,.4);color:#EF5350}
    .empty-state{text-align:center;padding:60px 20px;color:var(--text-muted)}
    .empty-state i{font-size:2.5rem;opacity:.2;display:block;margin-bottom:12px}
    .amount-col{font-family:var(--ff-display);font-size:.95rem;font-weight:700;color:var(--white)}
    .reste-col{color:#FBB724;font-weight:600}
    .reste-zero{color:#25D366}

    /* Modal */
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:2000;align-items:center;justify-content:center;padding:20px}
    .modal-overlay.show{display:flex}
    .modal-box{background:var(--dark-card);border:1px solid var(--border);border-radius:14px;width:100%;max-width:600px;max-height:90vh;overflow-y:auto}
    .modal-header{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:var(--dark-card);z-index:1}
    .modal-header h3{color:var(--white);font-size:.95rem}
    .modal-close{width:30px;height:30px;border-radius:7px;border:1px solid var(--border);background:none;color:var(--text-muted);cursor:pointer;display:flex;align-items:center;justify-content:center}
    .modal-close:hover{border-color:var(--gold);color:var(--gold)}
    .modal-body{padding:22px}
    .modal-footer{padding:14px 22px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .form-full{grid-column:1/-1}
    .calc-preview{background:var(--dark-3);border-radius:10px;padding:14px 18px;margin-top:12px}
    .calc-row{display:flex;justify-content:space-between;padding:4px 0;font-size:.82rem}
    .calc-row.total{border-top:1px solid var(--border);padding-top:8px;margin-top:4px;font-weight:700;color:var(--white);font-size:.95rem}
    .calc-row.acompte{color:#FBB724}
    .calc-row.reste{color:#25D366;font-weight:700}
    .tfilter{padding:5px 14px;border-radius:20px;border:1px solid var(--border);background:none;color:#888;cursor:pointer;font-size:.75rem;transition:var(--transition);font-family:var(--ff-body)}
    .tfilter.active,.tfilter:hover{border-color:var(--gold);color:var(--gold)}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">

  <?php $activePage = 'factures'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2 data-fr="Gestion Factures" data-ar="إدارة الفواتير">Gestion Factures</h2><p data-fr="Suivi financier et facturation" data-ar="المتابعة المالية والفوترة">Suivi financier et facturation</p></div>
      </div>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
        <button class="btn-primary" style="padding:8px 18px;font-size:.82rem" onclick="openAddModal()">
          <i class="fas fa-plus"></i> <span data-fr="Nouvelle facture" data-ar="فاتورة جديدة">Nouvelle facture</span>
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

      <!-- Stats financières -->
      <div class="stats-grid" style="margin-bottom:24px">
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon gold"><i class="fas fa-receipt"></i></div></div>
          <div class="stat-card-value"><?= $total ?></div>
          <div class="stat-card-label" data-fr="Total factures" data-ar="إجمالي الفواتير">Total factures</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-coins"></i></div></div>
          <div class="stat-card-value" style="font-size:1.2rem" dir="ltr"><?= number_format($totalEncaisse, 0, ',', ' ') ?></div>
          <div class="stat-card-label" data-fr="Encaissé (MAD)" data-ar="المحصّل (MAD)">Encaissé (MAD)</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(251,183,36,.1);color:#FBB724"><i class="fas fa-hourglass-half"></i></div></div>
          <div class="stat-card-value" style="font-size:1.2rem" dir="ltr"><?= number_format($totalReste, 0, ',', ' ') ?></div>
          <div class="stat-card-label" data-fr="Reste à payer (MAD)" data-ar="المتبقي للدفع (MAD)">Reste à payer (MAD)</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(59,130,246,.1);color:#60A5FA"><i class="fas fa-chart-line"></i></div></div>
          <div class="stat-card-value" style="font-size:1.2rem" dir="ltr"><?= number_format($totalHT, 0, ',', ' ') ?></div>
          <div class="stat-card-label" data-fr="CA Total (MAD)" data-ar="رقم الأعمال (MAD)">CA Total (MAD)</div>
        </div>
      </div>

      <!-- Table factures -->
      <div class="table-wrap">
        <div class="table-topbar">
          <h3 style="color:var(--white);font-size:.9rem">
            <i class="fas fa-list" style="color:var(--gold);margin-right:8px"></i><span data-fr="Liste des factures" data-ar="قائمة الفواتير">Liste des factures</span> (<?= $total ?>)
          </h3>
          <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <input type="text" class="search-input" id="searchFac" placeholder="🔍 Client, numéro..." data-fr-placeholder="🔍 Client, numéro..." data-ar-placeholder="🔍 العميل، الرقم..." oninput="filterFac()">
            <div style="display:flex;gap:6px">
              <button class="tfilter active" onclick="setFilter('all',this)" data-fr="Toutes" data-ar="الكل">Toutes</button>
              <button class="tfilter" onclick="setFilter('envoyee',this)" data-fr="Envoyées" data-ar="مُرسلة">Envoyées</button>
              <button class="tfilter" onclick="setFilter('payee',this)" data-fr="Payées" data-ar="مدفوعة">Payées</button>
              <button class="tfilter" onclick="setFilter('partiellement_payee',this)" data-fr="Partiel" data-ar="جزئية">Partiel</button>
              <button class="tfilter" onclick="setFilter('brouillon',this)" data-fr="Brouillons" data-ar="مسودات">Brouillons</button>
            </div>
          </div>
        </div>

        <?php if (empty($factures)): ?>
        <div class="empty-state">
          <i class="fas fa-receipt"></i>
          <p data-fr="Aucune facture pour l'instant." data-ar="لا توجد فواتير حالياً.">Aucune facture pour l'instant.</p>
          <button class="btn-primary" style="margin-top:16px" onclick="openAddModal()">
            <i class="fas fa-plus"></i> <span data-fr="Créer la première facture" data-ar="إنشاء أول فاتورة">Créer la première facture</span>
          </button>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table id="facTable">
          <thead>
            <tr>
              <th>N° Facture</th>
              <th data-fr="Client" data-ar="العميل">Client</th>
              <th data-fr="Événement" data-ar="المناسبة">Événement</th>
              <th data-fr="Montant TTC" data-ar="المبلغ الإجمالي">Montant TTC</th>
              <th>Acompte</th>
              <th>Reste</th>
              <th data-fr="Statut" data-ar="الحالة">Statut</th>
              <th data-fr="Échéance" data-ar="تاريخ الاستحقاق">Échéance</th>
              <th data-fr="Actions" data-ar="الإجراءات">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($factures as $f):
              $sc   = $statutConfig[$f['statut']] ?? $statutConfig['brouillon'];
              $date = !empty($f['date_echeance']) ? date('d/m/Y', strtotime($f['date_echeance'])) : '—';
              $isLate = !empty($f['date_echeance']) && $f['date_echeance'] < date('Y-m-d') && $f['statut'] !== 'payee';
            ?>
            <tr data-statut="<?= $f['statut'] ?>"
                data-search="<?= strtolower($f['numero'] . ' ' . $f['nom_client'] . ' ' . ($f['email_client'] ?? '')) ?>">
              <td>
                <span style="font-family:var(--ff-display);color:var(--gold);font-size:.85rem;font-weight:700"><?= htmlspecialchars($f['numero']) ?></span>
              </td>
              <td>
                <div style="color:var(--white);font-size:.84rem;font-weight:600"><?= htmlspecialchars($f['nom_client']) ?></div>
                <div style="font-size:.73rem;color:#555"><?= htmlspecialchars($f['telephone_client'] ?? '') ?></div>
              </td>
              <td>
                <span style="background:var(--dark-3);padding:3px 10px;border-radius:6px;font-size:.75rem">
                  <?= ucfirst(str_replace('_',' ', $f['type_evenement'] ?? '—')) ?>
                </span>
              </td>
              <td class="amount-col" dir="ltr"><?= number_format($f['montant_ttc'], 0, ',', ' ') ?> MAD</td>
              <td style="font-size:.82rem;color:#25D366" dir="ltr"><?= number_format($f['acompte'], 0, ',', ' ') ?> MAD</td>
              <td class="<?= $f['reste_a_payer'] <= 0 ? 'reste-zero' : 'reste-col' ?>" dir="ltr">
                <?= $f['reste_a_payer'] <= 0 ? '✓ Soldé' : number_format($f['reste_a_payer'], 0, ',', ' ') . ' MAD' ?>
              </td>
              <td>
                <span class="statut-badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>">
                  <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                  <?= $sc['label'] ?>
                </span>
              </td>
              <td style="font-size:.78rem;<?= $isLate ? 'color:#EF5350;font-weight:700' : 'color:#555' ?>">
                <?= $date ?><?= $isLate ? ' ⚠️' : '' ?>
              </td>
              <td>
                <div class="td-actions">
                  <button class="act-btn" onclick='openDetail(<?= json_encode($f) ?>)' title="Voir"><i class="fas fa-eye"></i></button>
                  <a href="print_facture.php?id=<?= $f['id'] ?>" target="_blank" class="act-btn" title="Imprimer" style="color:#60A5FA;border-color:rgba(59,130,246,.3)"><i class="fas fa-print"></i></a>
                  <button class="act-btn" onclick='openStatutModal(<?= $f['id'] ?>, "<?= $f['statut'] ?>")' title="Changer statut"><i class="fas fa-exchange-alt"></i></button>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer cette facture ?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $f['id'] ?>">
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

<!-- Modal nouvelle facture -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-plus-circle" style="color:var(--gold);margin-right:8px"></i><span data-fr="Nouvelle facture" data-ar="فاتورة جديدة">Nouvelle facture</span></h3>
      <button class="modal-close" onclick="closeAdd()"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group form-full">
            <label class="form-label" data-fr="Nom du client *" data-ar="اسم العميل *">Nom du client *</label>
            <input type="text" name="nom_client" class="form-control" placeholder="Prénom Nom" data-fr-placeholder="Prénom Nom" data-ar-placeholder="الاسم الكامل" required>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Téléphone" data-ar="الهاتف">Téléphone</label>
            <input type="tel" name="telephone_client" class="form-control" placeholder="06XXXXXXXX" data-fr-placeholder="06XXXXXXXX" data-ar-placeholder="06XXXXXXXX">
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Email" data-ar="البريد الإلكتروني">Email</label>
            <input type="email" name="email_client" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Type d'événement" data-ar="نوع المناسبة">Type d'événement</label>
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
            <label class="form-label" data-fr="Date événement" data-ar="تاريخ المناسبة">Date événement</label>
            <input type="date" name="date_evenement" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Nombre d'invités</label>
            <input type="number" name="nb_personnes" class="form-control" placeholder="100" min="1">
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Package" data-ar="الباقة">Package</label>
            <select name="package_nom" class="form-control">
              <option value="">Sans package</option>
              <?php foreach ($packages as $p): ?>
              <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Date d'échéance" data-ar="تاريخ الاستحقاق">Date d'échéance</label>
            <input type="date" name="date_echeance" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Montant HT (MAD) *</label>
            <input type="number" name="montant_ht" id="montantHT" class="form-control" placeholder="18000" min="0" step="100" oninput="calcTotal()" required>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="TVA (%)" data-ar="الضريبة (%)">TVA (%)</label>
            <input type="number" name="tva" id="tvaInput" class="form-control" value="0" min="0" max="100" step="0.5" oninput="calcTotal()">
          </div>
          <div class="form-group">
            <label class="form-label">Acompte reçu (MAD)</label>
            <input type="number" name="acompte" id="acompteInput" class="form-control" value="0" min="0" step="100" oninput="calcTotal()">
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Statut" data-ar="الحالة">Statut</label>
            <select name="statut" class="form-control">
              <option value="brouillon" data-fr="Brouillon" data-ar="مسودة">Brouillon</option>
              <option value="envoyee" data-fr="Envoyée" data-ar="مُرسلة">Envoyée</option>
              <option value="partiellement_payee" data-fr="Partiellement payée" data-ar="مدفوعة جزئياً">Partiellement payée</option>
              <option value="payee" data-fr="Payée" data-ar="مدفوعة">Payée</option>
            </select>
          </div>
        </div>

        <!-- Aperçu calcul -->
        <div class="calc-preview">
          <div class="calc-row"><span>Montant HT</span><span id="prevHT" dir="ltr">0 MAD</span></div>
          <div class="calc-row"><span>TVA</span><span id="prevTVA" dir="ltr">0 MAD</span></div>
          <div class="calc-row total"><span>Total TTC</span><span id="prevTTC" dir="ltr">0 MAD</span></div>
          <div class="calc-row acompte"><span>Acompte reçu</span><span id="prevAcompte" dir="ltr">0 MAD</span></div>
          <div class="calc-row reste"><span data-fr="Reste à payer" data-ar="المتبقي للدفع">Reste à payer</span><span id="prevReste" dir="ltr">0 MAD</span></div>
        </div>

        <div class="form-group" style="margin-top:14px">
          <label class="form-label" data-fr="Notes" data-ar="ملاحظات">Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Remarques internes..." data-fr-placeholder="Remarques internes..." data-ar-placeholder="ملاحظات داخلية..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="closeAdd()">Annuler</button>
        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Créer la facture</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal détail facture -->
<div class="modal-overlay" id="detailModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-receipt" style="color:var(--gold);margin-right:8px"></i>Détail facture</h3>
      <button class="modal-close" onclick="closeDetail()"><i class="fas fa-times"></i></button>
    </div>
    <div id="detailContent"></div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closeDetail()" data-fr="Fermer" data-ar="إغلاق" data-fr="Fermer" data-ar="إغلاق">Fermer</button>
      <a id="printBtn" href="#" target="_blank" class="btn-primary"><i class="fas fa-print"></i> Imprimer</a>
    </div>
  </div>
</div>

<!-- Modal changement statut -->
<div class="modal-overlay" id="statutModal">
  <div class="modal-box" style="max-width:380px">
    <div class="modal-header">
      <h3><i class="fas fa-exchange-alt" style="color:var(--gold);margin-right:8px"></i>Changer le statut</h3>
      <button class="modal-close" onclick="closeStatut()"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update_statut">
      <input type="hidden" name="id" id="statutId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nouveau statut</label>
          <select name="statut" id="statutSelect" class="form-control">
            <option value="brouillon" data-fr="Brouillon" data-ar="مسودة">Brouillon</option>
            <option value="envoyee" data-fr="Envoyée" data-ar="مُرسلة">Envoyée</option>
            <option value="partiellement_payee" data-fr="Partiellement payée" data-ar="مدفوعة جزئياً">Partiellement payée</option>
            <option value="payee">Payée ✓</option>
            <option value="annulee" data-fr="Annulée" data-ar="ملغاة">Annulée</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="closeStatut()">Annuler</button>
        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Mettre à jour</button>
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

// Calcul auto
function calcTotal() {
  const ht      = parseFloat(document.getElementById('montantHT').value)    || 0;
  const tva     = parseFloat(document.getElementById('tvaInput').value)      || 0;
  const acompte = parseFloat(document.getElementById('acompteInput').value)  || 0;
  const mTVA    = Math.round(ht * tva / 100);
  const ttc     = ht + mTVA;
  const reste   = Math.max(0, ttc - acompte);
  const fmt = n => n.toLocaleString('fr-FR') + ' MAD';
  document.getElementById('prevHT').textContent      = fmt(ht);
  document.getElementById('prevTVA').textContent     = fmt(mTVA);
  document.getElementById('prevTTC').textContent     = fmt(ttc);
  document.getElementById('prevAcompte').textContent = fmt(acompte);
  document.getElementById('prevReste').textContent   = fmt(reste);
}

// Modals
function openAddModal() { document.getElementById('addModal').classList.add('show'); }
function closeAdd()     { document.getElementById('addModal').classList.remove('show'); }

const sc = <?= json_encode($statutConfig) ?>;
function openDetail(f) {
  const s  = sc[f.statut] || sc['brouillon'];
  const fmt = n => parseFloat(n||0).toLocaleString('fr-FR') + ' MAD';
  const dateEch = f.date_echeance ? new Date(f.date_echeance).toLocaleDateString('fr-FR') : '—';
  const dateEv  = f.date_evenement ? new Date(f.date_evenement).toLocaleDateString('fr-FR') : '—';
  document.getElementById('detailContent').innerHTML = `
    <div style="padding:20px 22px;background:linear-gradient(135deg,rgba(212,175,55,.06),transparent);border-bottom:1px solid var(--border)">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <div>
          <div style="font-family:var(--ff-display);font-size:1.2rem;color:var(--gold);font-weight:700">${f.numero}</div>
          <div style="font-size:.82rem;color:var(--white);margin-top:4px">${f.nom_client}</div>
        </div>
        <span class="statut-badge" style="background:${s.bg};color:${s.color}">${s.label}</span>
      </div>
    </div>
    <div style="padding:20px 22px">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px">
        <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Téléphone</div><div style="color:var(--white)">${f.telephone_client||'—'}</div></div>
        <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Email</div><div style="color:var(--white)">${f.email_client||'—'}</div></div>
        <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Événement</div><div style="color:var(--white)">${(f.type_evenement||'—').replace('_',' ')}</div></div>
        <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Date événement</div><div style="color:var(--white)">${dateEv}</div></div>
        <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Package</div><div style="color:var(--gold)">${f.package_nom||'—'}</div></div>
        <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Échéance</div><div style="color:var(--white)">${dateEch}</div></div>
      </div>
      <div style="background:var(--dark-3);border-radius:10px;padding:16px">
        <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:.84rem"><span style="color:var(--text-muted)">Montant HT</span><span style="color:var(--white)" dir="ltr">${fmt(f.montant_ht)}</span></div>
        <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:.84rem"><span style="color:var(--text-muted)">TVA (${f.tva}%)</span><span style="color:var(--white)" dir="ltr">${fmt(f.montant_tva)}</span></div>
        <div style="display:flex;justify-content:space-between;padding:8px 0 5px;font-size:1rem;font-weight:700;border-top:1px solid var(--border);margin-top:4px"><span style="color:var(--white)">Total TTC</span><span style="color:var(--gold)" dir="ltr">${fmt(f.montant_ttc)}</span></div>
        <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:.84rem"><span style="color:#FBB724">Acompte reçu</span><span style="color:#FBB724" dir="ltr">${fmt(f.acompte)}</span></div>
        <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:.9rem;font-weight:700"><span style="color:#25D366">Reste à payer</span><span style="color:#25D366" dir="ltr">${parseFloat(f.reste_a_payer)<=0 ? '✓ Soldé' : fmt(f.reste_a_payer)}</span></div>
      </div>
      ${f.notes ? `<div style="margin-top:14px;background:var(--dark-3);border-left:3px solid var(--gold);padding:10px 14px;border-radius:0 8px 8px 0;font-size:.82rem;color:var(--text-muted)">${f.notes}</div>` : ''}
    </div>`;
  document.getElementById('printBtn').href = 'print_facture.php?id=' + f.id;
  document.getElementById('detailModal').classList.add('show');
}
function closeDetail() { document.getElementById('detailModal').classList.remove('show'); }

function openStatutModal(id, statut) {
  document.getElementById('statutId').value      = id;
  document.getElementById('statutSelect').value  = statut;
  document.getElementById('statutModal').classList.add('show');
}
function closeStatut() { document.getElementById('statutModal').classList.remove('show'); }

// Filtres
let currentFilter = 'all';
function setFilter(f, btn) {
  currentFilter = f;
  document.querySelectorAll('.tfilter').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterFac();
}
function filterFac() {
  const q = document.getElementById('searchFac').value.toLowerCase();
  document.querySelectorAll('#facTable tbody tr').forEach(row => {
    const matchF = currentFilter === 'all' || row.dataset.statut === currentFilter;
    const matchQ = !q || row.dataset.search.includes(q);
    row.style.display = (matchF && matchQ) ? '' : 'none';
  });
}
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
