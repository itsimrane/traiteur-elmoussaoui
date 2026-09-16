<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

/* =========================================================
   CRÉATION DE LA TABLE SI ELLE N'EXISTE PAS
   ========================================================= */
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
        `statut` ENUM('brouillon','envoyee','payee','partiellement_payee','annulee')
            NOT NULL DEFAULT 'brouillon',
        `notes` TEXT NULL,
        `date_echeance` DATE NULL,
        `date_paiement` DATE NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}


/* =========================================================
   ACTIONS POST
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {

        // TVA supprimée : le total facturé est directement le montant saisi
        $montantHT  = (float)($_POST['montant_ht'] ?? 0);
        $tva        = 0;
        $montantTVA = 0;
        $montantTTC = $montantHT;

        $acompte = (float)($_POST['acompte'] ?? 0);
        $reste   = round($montantTTC - $acompte, 2);

        $lastId = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) FROM factures")->fetchColumn() + 1;

        $numero = 'FAC-' . date('Y') . '-' . str_pad($lastId, 4, '0', STR_PAD_LEFT);

        $pdo->prepare("
            INSERT INTO factures (
                numero, client_id, reservation_id, nom_client, email_client, telephone_client,
                type_evenement, date_evenement, nb_personnes, package_nom,
                montant_ht, tva, montant_tva, montant_ttc, acompte, reste_a_payer,
                statut, notes, date_echeance
            )
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $numero,
            (int)($_POST['client_id'] ?? 0) ?: null,
            (int)($_POST['reservation_id'] ?? 0) ?: null,
            sanitize($_POST['nom_client'] ?? ''),
            sanitize($_POST['email_client'] ?? ''),
            sanitize($_POST['telephone_client'] ?? ''),
            sanitize($_POST['type_evenement'] ?? ''),
            !empty($_POST['date_evenement']) ? $_POST['date_evenement'] : null,
            (int)($_POST['nb_personnes'] ?? 0) ?: null,
            sanitize($_POST['package_nom'] ?? ''),
            $montantHT, $tva, $montantTVA, $montantTTC, $acompte, $reste,
            sanitize($_POST['statut'] ?? 'brouillon'),
            sanitize($_POST['notes'] ?? ''),
            !empty($_POST['date_echeance']) ? $_POST['date_echeance'] : null
        ]);

        header('Location: factures.php?msg=Facture+créée+avec+succès&type=success');
        exit;
    }

    if ($action === 'update_statut') {

        $id     = (int)($_POST['id'] ?? 0);
        $statut = sanitize($_POST['statut'] ?? '');
        $datePaiement = ($statut === 'payee') ? date('Y-m-d') : null;

        $pdo->prepare("
            UPDATE factures SET statut = ?, date_paiement = ?, updated_at = NOW() WHERE id = ?
        ")->execute([$statut, $datePaiement, $id]);

        header('Location: factures.php?msg=Statut+mis+à+jour&type=success');
        exit;
    }

    if ($action === 'add_paiement') {
        $factureId = (int)($_POST['facture_id'] ?? 0);
        $montant   = (float)($_POST['montant'] ?? 0);
        $mode      = sanitize($_POST['mode'] ?? 'especes');
        $datePmt   = $_POST['date_paiement'] ?: date('Y-m-d');

        if ($factureId && $montant > 0) {
            $fac = $pdo->prepare("SELECT numero, nom_client, telephone_client, montant_ttc, acompte FROM factures WHERE id=?");
            $fac->execute([$factureId]);
            $f = $fac->fetch();

            if ($f) {
                $nouvelAcompte = (float)$f['acompte'] + $montant;
                $soldeComplet  = $nouvelAcompte >= (float)$f['montant_ttc'];

                $pdo->prepare("
                    INSERT INTO paiements (facture_id, facture_num, nom_client, telephone, montant, type, mode, statut, date_paiement, created_at)
                    VALUES (?,?,?,?,?,?,?,'recu',?,NOW())
                ")->execute([
                    $factureId, $f['numero'], $f['nom_client'], $f['telephone_client'],
                    $montant, $soldeComplet ? 'solde' : 'acompte', $mode, $datePmt
                ]);

                $nouveauReste  = max(0, (float)$f['montant_ttc'] - $nouvelAcompte);
                $nouveauStatut = $nouvelAcompte >= (float)$f['montant_ttc'] ? 'payee' : 'partiellement_payee';
                $datePaiementFacture = $nouveauStatut === 'payee' ? $datePmt : null;

                $pdo->prepare("
                    UPDATE factures SET acompte=?, reste_a_payer=?, statut=?, date_paiement=?, updated_at=NOW() WHERE id=?
                ")->execute([$nouvelAcompte, $nouveauReste, $nouveauStatut, $datePaiementFacture, $factureId]);
            }
        }
        header('Location: factures.php?msg=Paiement+enregistré&type=success');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM factures WHERE id = ?")->execute([$id]);
        header('Location: factures.php?msg=Facture+supprimée&type=success');
        exit;
    }
}


/* =========================================================
   PRÉ-REMPLISSAGE DEPUIS UN DEVIS
   ========================================================= */
$prefillDevis = null;

if (!empty($_GET['from_devis'])) {
    try {
        $pd = $pdo->prepare("
            SELECT d.*, c.nom AS c_nom, c.prenom AS c_prenom, c.telephone AS c_tel, c.email AS c_email,
                   te.nom AS type_nom
            FROM devis d
            LEFT JOIN clients c ON c.id = d.client_id
            LEFT JOIN types_evenements te ON te.id = d.type_evenement_id
            WHERE d.id = ?
        ");
        $pd->execute([(int)$_GET['from_devis']]);
        $prefillDevis = $pd->fetch();
    } catch (Exception $e) {}
}

$pfNom   = $prefillDevis ? trim(($prefillDevis['c_prenom'] ?? '') . ' ' . ($prefillDevis['c_nom'] ?? '')) : '';
$pfTel   = $prefillDevis['c_tel'] ?? '';
$pfEmail = $prefillDevis['c_email'] ?? '';
$pfDate  = $prefillDevis['date_evenement'] ?? '';
$pfNb    = $prefillDevis['nbr_invites'] ?? '';
$pfHT    = $prefillDevis['montant_ht'] ?? '';


/* =========================================================
   RÉCUPÉRER LES FACTURES
   ========================================================= */
$factures = $pdo->query("
    SELECT f.*, r.reference AS resa_ref
    FROM factures f
    LEFT JOIN reservations r ON r.id = f.reservation_id
    ORDER BY f.created_at DESC
")->fetchAll();

$packages = $pdo->query("SELECT nom FROM packages WHERE actif = 1 ORDER BY ordre")->fetchAll(PDO::FETCH_COLUMN);

$total         = count($factures);
$totalHT       = array_sum(array_column($factures, 'montant_ttc'));
$totalEncaisse = array_sum(array_column($factures, 'acompte'));
$totalReste    = array_sum(array_column($factures, 'reste_a_payer'));

$statutConfig = [
    'brouillon'           => ['label' => tt('Brouillon','مسودة'), 'color' => '#888',    'bg' => 'rgba(136,136,136,.12)'],
    'envoyee'             => ['label' => tt('Envoyée','مرسلة'),   'color' => '#60A5FA', 'bg' => 'rgba(59,130,246,.12)'],
    'payee'               => ['label' => tt('Payée','مدفوعة') . ' ✓',   'color' => '#25D366', 'bg' => 'rgba(37,211,102,.12)'],
    'partiellement_payee' => ['label' => tt('Partiel','جزئي'),   'color' => '#FBB724', 'bg' => 'rgba(251,183,36,.12)'],
    'annulee'             => ['label' => tt('Annulée','ملغاة'),   'color' => '#EF5350', 'bg' => 'rgba(239,68,68,.12)'],
];

$msg     = $_GET['msg'] ?? '';
$msgType = $_GET['type'] ?? 'success';

function jsAttr($data): string {
    return htmlspecialchars(
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
        ENT_QUOTES, 'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="<?= adminLang() ?>" dir="<?= adminDir() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= t('factures') ?> — Admin EL MOUSSAOUI</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<style>
body { overflow-x: hidden; }
.sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.6); z-index: 999; }
.sidebar-overlay.show { display: block; }
@media (max-width: 768px) {
    .sidebar { position: fixed; left: 0; top: 0; bottom: 0; z-index: 1000; transform: translateX(-100%); transition: var(--transition); }
    .sidebar.open { transform: translateX(0); }
}
.table-wrap {
    background: var(--dark-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    width: calc(100% + 30px);
    max-width: none;
    margin-right: -30px;
    overflow: hidden;
}
.table-topbar { padding: 16px 22px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 12px; }
.table-wrap > .factures-table-wrap { width: 100%; overflow-x: auto; }
.search-input { background: var(--dark-3); border: 1px solid var(--border); border-radius: 8px; padding: 8px 14px; color: var(--white); font-size: .82rem; outline: none; width: 220px; }
.search-input:focus { border-color: var(--gold); }
.factures-table-wrap { width: 100%; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; }
#facTable { width: 100%; min-width: 1150px; border-collapse: collapse !important; border-spacing: 0 !important; table-layout: fixed !important; margin: 0 !important; padding: 0 !important; }
#facTable th, #facTable td { box-sizing: border-box; min-width: 0; }
#facTable col:nth-child(1) { width: 9%; }
#facTable col:nth-child(2) { width: 13%; }
#facTable col:nth-child(3) { width: 9%; }
#facTable col:nth-child(4) { width: 11%; }
#facTable col:nth-child(5) { width: 10%; }
#facTable col:nth-child(6) { width: 10%; }
#facTable col:nth-child(7) { width: 10%; }
#facTable col:nth-child(8) { width: 9%; }
#facTable col:nth-child(9) { width: 19%; }
#facTable, #facTable thead, #facTable tbody, #facTable tr, #facTable th, #facTable td {
    position: static !important; float: none !important; transform: none !important;
}
#facTable thead { display: table-header-group !important; }
#facTable thead tr { display: table-row !important; }
#facTable thead th {
    display: table-cell !important; box-sizing: border-box !important; padding: 11px 12px !important;
    font-size: .7rem !important; color: var(--text-muted) !important; font-weight: 700 !important;
    text-transform: uppercase !important; letter-spacing: .5px !important;
    border-bottom: 1px solid var(--border) !important; text-align: left !important;
    vertical-align: middle !important; white-space: nowrap !important;
}
#facTable tbody { display: table-row-group !important; }
#facTable tbody tr {
    display: table-row !important; width: 100% !important;
    border-bottom: 1px solid rgba(255,255,255,.04) !important; transition: var(--transition);
}
#facTable tbody tr:last-child { border-bottom: none !important; }
#facTable tbody tr:hover { background: rgba(212,175,55,.03); }
#facTable tbody tr.row-hidden { display: none !important; }
#facTable tbody td {
    display: table-cell !important; box-sizing: border-box !important; padding: 13px 12px !important;
    font-size: .83rem; color: var(--text-muted); vertical-align: middle !important;
    text-align: left !important; overflow: hidden !important;
}
#facTable td:nth-child(1) { overflow-wrap: anywhere; }
#facTable td:nth-child(1) span { display: inline !important; }
#facTable td:nth-child(2) { overflow-wrap: break-word; }
#facTable td:nth-child(2) div { max-width: 100%; }
#facTable td:nth-child(3) { overflow: hidden !important; }
#facTable td:nth-child(3) span { display: inline-block !important; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
#facTable td:nth-child(4), #facTable td:nth-child(5), #facTable td:nth-child(6) { white-space: nowrap !important; }
#facTable .amount-col { font-family: var(--ff-display); font-size: .95rem; font-weight: 700; color: var(--white); white-space: nowrap !important; }
#facTable .reste-col { color: #FBB724; font-weight: 600; }
#facTable .reste-zero { color: #25D366; }
#facTable td:nth-child(7) { white-space: nowrap !important; }
#facTable .statut-badge { display: inline-flex !important; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 20px; font-size: .72rem; font-weight: 700; white-space: nowrap !important; }
#facTable td:nth-child(8) { white-space: nowrap !important; font-size: .78rem; }
#facTable td:nth-child(9) { white-space: nowrap !important; overflow: visible !important; }
#facTable .td-actions { display: flex !important; align-items: center !important; justify-content: flex-start !important; gap: 5px !important; flex-wrap: nowrap !important; width: max-content !important; max-width: none !important; }
#facTable .act-btn {
    width: 27px !important; min-width: 27px !important; max-width: 27px !important;
    height: 27px !important; min-height: 27px !important; max-height: 27px !important; flex: 0 0 27px !important;
    border-radius: 7px; border: 1px solid var(--border); background: none; cursor: pointer;
    display: flex !important; align-items: center !important; justify-content: center !important;
    font-size: .72rem; transition: var(--transition); color: var(--text-muted); text-decoration: none;
}
#facTable .act-btn:hover { border-color: var(--gold); color: var(--gold); }
#facTable .act-btn.danger:hover { border-color: rgba(239,68,68,.4); color: #EF5350; }
#facTable td form { display: inline-flex !important; margin: 0 !important; padding: 0 !important; width: auto !important; }
.empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.empty-state i { font-size: 2.5rem; opacity: .2; display: block; margin-bottom: 12px; }
.tfilter { padding: 5px 14px; border-radius: 20px; border: 1px solid var(--border); background: none; color: #888; cursor: pointer; font-size: .75rem; transition: var(--transition); font-family: var(--ff-body); }
.tfilter.active, .tfilter:hover { border-color: var(--gold); color: var(--gold); }
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.75); z-index: 2000; align-items: center; justify-content: center; padding: 20px; }
.modal-overlay.show { display: flex; }
.modal-box { background: var(--dark-card); border: 1px solid var(--border); border-radius: 14px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
.modal-header { padding: 18px 22px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; background: var(--dark-card); z-index: 1; }
.modal-header h3 { color: var(--white); font-size: .95rem; }
.modal-close { width: 30px; height: 30px; border-radius: 7px; border: 1px solid var(--border); background: none; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; }
.modal-close:hover { border-color: var(--gold); color: var(--gold); }
.modal-body { padding: 22px; }
.modal-footer { padding: 14px 22px; border-top: 1px solid var(--border); display: flex; gap: 10px; justify-content: flex-end; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-full { grid-column: 1 / -1; }
@media (max-width: 480px) { .form-grid { grid-template-columns: 1fr; } .form-full { grid-column: auto; } }
.calc-preview { background: var(--dark-3); border-radius: 10px; padding: 14px 18px; margin-top: 12px; }
.calc-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: .82rem; }
.calc-row.total { border-top: 1px solid var(--border); padding-top: 8px; margin-top: 4px; font-weight: 700; color: var(--white); font-size: .95rem; }
.calc-row.acompte { color: #FBB724; }
.calc-row.reste { color: #25D366; font-weight: 700; }
@media (max-width: 1200px) { .table-wrap { width: 100%; margin-right: 0; } .factures-table-wrap { overflow-x: auto; } #facTable { min-width: 1150px; } }
@media (max-width: 768px) { .table-wrap { width: 100%; margin-right: 0; } .table-topbar { padding: 14px; } #facTable { min-width: 1150px; } }
</style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout <?= adminRtlClass() ?>">
<?php $activePage = 'factures'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>
<main class="admin-main">
<div class="admin-topbar">
    <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title">
            <h2 data-fr="Gestion Factures" data-ar="إدارة الفواتير"><?= tt('Gestion Factures', 'إدارة الفواتير') ?></h2>
            <p data-fr="Suivi financier et facturation" data-ar="المتابعة المالية والفوترة"><?= tt('Suivi financier et facturation', 'المتابعة المالية والفوترة') ?></p>
        </div>
    </div>
    <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
        <button class="btn-primary" style="padding:8px 18px;font-size:.82rem" onclick="openAddModal()">
            <i class="fas fa-plus"></i>
            <span data-fr="Nouvelle facture" data-ar="فاتورة جديدة"><?= tt('Nouvelle facture', 'فاتورة جديدة') ?></span>
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
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card"><div class="stat-card-header"><div class="stat-card-icon gold"><i class="fas fa-receipt"></i></div></div><div class="stat-card-value"><?= $total ?></div><div class="stat-card-label" data-fr="Total factures" data-ar="إجمالي الفواتير"><?= tt('Total factures', 'إجمالي الفواتير') ?></div></div>
    <div class="stat-card"><div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-coins"></i></div></div><div class="stat-card-value" style="font-size:1.2rem" dir="ltr"><?= number_format($totalEncaisse, 0, ',', ' ') ?></div><div class="stat-card-label" data-fr="Encaissé (MAD)" data-ar="المحصّل (MAD)"><?= tt('Encaissé (MAD)', 'المحصّل (MAD)') ?></div></div>
    <div class="stat-card"><div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(251,183,36,.1);color:#FBB724"><i class="fas fa-hourglass-half"></i></div></div><div class="stat-card-value" style="font-size:1.2rem" dir="ltr"><?= number_format($totalReste, 0, ',', ' ') ?></div><div class="stat-card-label" data-fr="Reste à payer (MAD)" data-ar="المتبقي للدفع (MAD)"><?= tt('Reste à payer (MAD)', 'المتبقي للدفع (MAD)') ?></div></div>
    <div class="stat-card"><div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(59,130,246,.1);color:#60A5FA"><i class="fas fa-chart-line"></i></div></div><div class="stat-card-value" style="font-size:1.2rem" dir="ltr"><?= number_format($totalHT, 0, ',', ' ') ?></div><div class="stat-card-label" data-fr="CA Total (MAD)" data-ar="رقم الأعمال (MAD)"><?= tt('CA Total (MAD)', 'رقم الأعمال (MAD)') ?></div></div>
</div>
<div class="table-wrap">
<div class="table-topbar">
    <h3 style="color:var(--white);font-size:.9rem"><i class="fas fa-list" style="color:var(--gold);margin-right:8px"></i><span data-fr="Liste des factures" data-ar="قائمة الفواتير"><?= tt('Liste des factures', 'قائمة الفواتير') ?></span> (<?= $total ?>)</h3>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <input type="text" class="search-input" id="searchFac" placeholder="🔍 Client, numéro..." data-fr-placeholder="🔍 Client, numéro..." data-ar-placeholder="🔍 العميل، الرقم..." oninput="filterFac()">
        <div style="display:flex;gap:6px">
            <button class="tfilter active" onclick="setFilter('all',this)" data-fr="Toutes" data-ar="الكل"><?= tt('Toutes', 'الكل') ?></button>
            <button class="tfilter" onclick="setFilter('envoyee',this)" data-fr="Envoyées" data-ar="مُرسلة"><?= tt('Envoyées', 'مُرسلة') ?></button>
            <button class="tfilter" onclick="setFilter('payee',this)" data-fr="Payées" data-ar="مدفوعة"><?= tt('Payées', 'مدفوعة') ?></button>
            <button class="tfilter" onclick="setFilter('partiellement_payee',this)" data-fr="Partiel" data-ar="جزئية"><?= tt('Partiel', 'جزئية') ?></button>
            <button class="tfilter" onclick="setFilter('brouillon',this)" data-fr="Brouillons" data-ar="مسودات"><?= tt('Brouillons', 'مسودات') ?></button>
        </div>
    </div>
</div>
<?php if (empty($factures)): ?>
<div class="empty-state">
    <i class="fas fa-receipt"></i>
    <p data-fr="Aucune facture pour l'instant." data-ar="لا توجد فواتير حالياً."><?= tt('Aucune facture pour l\'instant.', 'لا توجد فواتير حالياً.') ?></p>
    <button class="btn-primary" style="margin-top:16px" onclick="openAddModal()"><i class="fas fa-plus"></i> <span data-fr="Créer la première facture" data-ar="إنشاء أول فاتورة"><?= tt('Créer la première facture', 'إنشاء أول فاتورة') ?></span></button>
</div>
<?php else: ?>
<div class="factures-table-wrap">
<table id="facTable">
<colgroup>
    <col style="width:9%"><col style="width:13%"><col style="width:9%">
    <col style="width:11%"><col style="width:10%"><col style="width:10%">
    <col style="width:10%"><col style="width:9%"><col style="width:19%">
</colgroup>
<thead>
<tr>
    <th><?= tt('N° Facture','رقم الفاتورة') ?></th>
    <th data-fr="Client" data-ar="العميل"><?= tt('Client', 'العميل') ?></th>
    <th data-fr="Événement" data-ar="المناسبة"><?= tt('Événement', 'المناسبة') ?></th>
    <th data-fr="Montant TTC" data-ar="المبلغ الإجمالي"><?= tt('Montant TTC', 'المبلغ الإجمالي') ?></th>
    <th><?= tt('Acompte','الدفعة الأولى') ?></th>
    <th><?= tt('Reste','المتبقي') ?></th>
    <th data-fr="Statut" data-ar="الحالة"><?= tt('Statut', 'الحالة') ?></th>
    <th data-fr="Échéance" data-ar="تاريخ الاستحقاق"><?= tt('Échéance', 'تاريخ الاستحقاق') ?></th>
    <th data-fr="Actions" data-ar="الإجراءات"><?= tt('Actions', 'الإجراءات') ?></th>
</tr>
</thead>
<tbody>
<?php foreach ($factures as $f): ?>
<?php
$sc = $statutConfig[$f['statut']] ?? $statutConfig['brouillon'];
$dateEch = !empty($f['date_echeance']) ? date('d/m/Y', strtotime($f['date_echeance'])) : '—';
$isLate = !empty($f['date_echeance']) && $f['date_echeance'] < date('Y-m-d') && $f['statut'] !== 'payee';
$resteVal = (float)$f['reste_a_payer'];
$searchBlob = strtolower($f['numero'] . ' ' . $f['nom_client'] . ' ' . ($f['email_client'] ?? ''));
?>
<tr data-statut="<?= htmlspecialchars($f['statut']) ?>" data-search="<?= htmlspecialchars($searchBlob) ?>">
<td><span style="font-family:var(--ff-display);color:var(--gold);font-size:.85rem;font-weight:700"><?= htmlspecialchars($f['numero']) ?></span></td>
<td>
    <div style="color:var(--white);font-size:.84rem;font-weight:600"><?= htmlspecialchars($f['nom_client']) ?></div>
    <?php if (!empty($f['telephone_client'])): ?><div style="font-size:.73rem;color:#555;margin-top:2px"><?= htmlspecialchars($f['telephone_client']) ?></div><?php endif; ?>
    <?php if (!empty($f['resa_ref'])): ?><div style="font-size:.68rem;color:var(--gold);margin-top:2px"><i class="fas fa-link"></i> <?= htmlspecialchars($f['resa_ref']) ?></div><?php endif; ?>
</td>
<td><span style="background:var(--dark-3);padding:3px 10px;border-radius:6px;font-size:.75rem"><?= htmlspecialchars(ucfirst(str_replace('_',' ', $f['type_evenement'] ?? '—'))) ?></span></td>
<td class="amount-col" dir="ltr"><?= number_format((float)$f['montant_ttc'], 0, ',', ' ') ?> MAD</td>
<td dir="ltr" style="font-size:.82rem;color:#25D366"><?= number_format((float)$f['acompte'], 0, ',', ' ') ?> MAD</td>
<td class="<?= $resteVal <= 0 ? 'reste-zero' : 'reste-col' ?>" dir="ltr"><?php if ($resteVal <= 0): ?>✓ Soldé<?php else: ?><?= number_format($resteVal, 0, ',', ' ') ?> MAD<?php endif; ?></td>
<td><span class="statut-badge" style="background:<?= htmlspecialchars($sc['bg']) ?>;color:<?= htmlspecialchars($sc['color']) ?>"><span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;flex:none"></span> <?= htmlspecialchars($sc['label']) ?></span></td>
<td style="font-size:.78rem;<?= $isLate ? 'color:#EF5350;font-weight:700;' : 'color:#555;' ?>"><?= htmlspecialchars($dateEch) ?><?php if ($isLate): ?> ⚠️<?php endif; ?></td>
<td>
    <div class="td-actions">
        <button type="button" class="act-btn" onclick="openDetail(<?= jsAttr($f) ?>)" title="Voir"><i class="fas fa-eye"></i></button>
        <a href="print_facture.php?id=<?= (int)$f['id'] ?>" target="_blank" class="act-btn" title="Imprimer" style="color:#60A5FA;border-color:rgba(59,130,246,.3)"><i class="fas fa-print"></i></a>
        <button type="button" class="act-btn" onclick="openPaiementModal(<?= (int)$f['id'] ?>, <?= (float)$f['reste_a_payer'] ?>, <?= jsAttr($f['numero']) ?>)" title="Enregistrer un paiement" style="color:#25D366;border-color:rgba(37,211,102,.3)"><i class="fas fa-coins"></i></button>
        <button type="button" class="act-btn" onclick="openStatutModal(<?= (int)$f['id'] ?>, <?= jsAttr($f['statut']) ?>)" title="Changer statut"><i class="fas fa-exchange-alt"></i></button>
        <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer cette facture ?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
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

<div class="modal-overlay" id="addModal">
<div class="modal-box">
<div class="modal-header"><h3><i class="fas fa-file-invoice" style="color:var(--gold);margin-right:8px"></i>Nouvelle facture</h3><button type="button" class="modal-close" onclick="closeAdd()"><i class="fas fa-times"></i></button></div>
<form method="POST">
<input type="hidden" name="action" value="add">
<input type="hidden" name="client_id" value="<?= $prefillDevis ? (int)$prefillDevis['client_id'] : '' ?>">
<input type="hidden" name="reservation_id" value="<?= $prefillDevis ? (int)$prefillDevis['reservation_id'] : '' ?>">
<?php if ($prefillDevis): ?>
<div class="modal-body" style="padding-bottom:0"><div style="background:rgba(212,175,55,.08);border:1px solid rgba(212,175,55,.25);border-radius:8px;padding:10px 14px;font-size:.78rem;color:var(--gold);margin-bottom:4px"><i class="fas fa-link"></i> Facture générée depuis le devis <?= htmlspecialchars($prefillDevis['reference']) ?></div></div>
<?php endif; ?>
<div class="modal-body">
<div class="form-grid">
<div class="form-group form-full"><label class="form-label">Nom du client</label><input type="text" name="nom_client" class="form-control" placeholder="Prénom Nom" value="<?= htmlspecialchars($pfNom) ?>" required></div>
<div class="form-group"><label class="form-label">Téléphone</label><input type="tel" name="telephone_client" class="form-control" placeholder="06XXXXXXXX" value="<?= htmlspecialchars($pfTel) ?>"></div>
<div class="form-group"><label class="form-label">Email</label><input type="email" name="email_client" class="form-control" value="<?= htmlspecialchars($pfEmail) ?>"></div>
<div class="form-group"><label class="form-label">Type d'événement</label><input type="text" name="type_evenement" class="form-control" placeholder="Mariage, Fiançailles..."></div>
<div class="form-group"><label class="form-label">Date de l'événement</label><input type="date" name="date_evenement" class="form-control" value="<?= htmlspecialchars($pfDate) ?>"></div>
<div class="form-group"><label class="form-label">Nombre d'invités</label><input type="number" name="nb_personnes" class="form-control" placeholder="100" min="1" value="<?= htmlspecialchars($pfNb) ?>"></div>
<div class="form-group"><label class="form-label">Package</label><select name="package_nom" class="form-control"><option value="">— Aucun —</option><?php foreach ($packages as $p): ?><option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label class="form-label">Montant total (MAD)</label><input type="number" name="montant_ht" id="montantHT" class="form-control" placeholder="18000" min="0" step="100" oninput="calcTotal()" value="<?= htmlspecialchars($pfHT) ?>" required></div>
<div class="form-group"><label class="form-label">Acompte déjà reçu (MAD)</label><input type="number" name="acompte" id="acompteInput" class="form-control" value="0" min="0" step="100" oninput="calcTotal()"></div>
<div class="form-group"><label class="form-label">Échéance</label><input type="date" name="date_echeance" class="form-control"></div>
<div class="form-group form-full"><label class="form-label">Statut</label><select name="statut" class="form-control"><option value="brouillon">Brouillon</option><option value="envoyee">Envoyée</option><option value="partiellement_payee">Partiellement payée</option><option value="payee">Payée</option></select></div>
<div class="form-group form-full"><label class="form-label">Notes (optionnel)</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
</div>
<div class="calc-preview">
<div class="calc-row total"><span>Total</span><span id="prevTTC" dir="ltr">0 MAD</span></div>
<div class="calc-row acompte"><span>Acompte</span><span id="prevAcompte" dir="ltr">0 MAD</span></div>
<div class="calc-row reste"><span>Reste à payer</span><span id="prevReste" dir="ltr">0 MAD</span></div>
</div>
</div>
<div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeAdd()">Annuler</button><button type="submit" class="btn-primary"><i class="fas fa-save"></i> Créer la facture</button></div>
</form>
</div>
</div>

<div class="modal-overlay" id="detailModal">
<div class="modal-box">
<div class="modal-header"><h3><i class="fas fa-receipt" style="color:var(--gold);margin-right:8px"></i>Détail facture</h3><button type="button" class="modal-close" onclick="closeDetail()"><i class="fas fa-times"></i></button></div>
<div id="detailContent"></div>
<div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeDetail()">Fermer</button><a id="printBtn" href="#" target="_blank" class="btn-primary"><i class="fas fa-print"></i> Imprimer</a></div>
</div>
</div>

<div class="modal-overlay" id="statutModal">
<div class="modal-box" style="max-width:380px">
<div class="modal-header"><h3><i class="fas fa-exchange-alt" style="color:var(--gold);margin-right:8px"></i>Changer le statut</h3><button type="button" class="modal-close" onclick="closeStatut()"><i class="fas fa-times"></i></button></div>
<form method="POST">
<input type="hidden" name="action" value="update_statut">
<input type="hidden" name="id" id="statutId">
<div class="modal-body"><div class="form-group"><label class="form-label">Nouveau statut</label><select name="statut" id="statutSelect" class="form-control"><option value="brouillon">Brouillon</option><option value="envoyee">Envoyée</option><option value="partiellement_payee">Partiellement payée</option><option value="payee">Payée ✓</option><option value="annulee">Annulée</option></select></div></div>
<div class="modal-footer"><button type="button" class="btn-secondary" onclick="closeStatut()">Annuler</button><button type="submit" class="btn-primary"><i class="fas fa-save"></i> Mettre à jour</button></div>
</form>
</div>
</div>

<!-- Modal enregistrement de paiement -->
<div class="modal-overlay" id="paiementModal">
<div class="modal-box" style="max-width:400px">
<div class="modal-header">
  <h3><i class="fas fa-coins" style="color:#25D366;margin-right:8px"></i>Enregistrer un paiement</h3>
  <button type="button" class="modal-close" onclick="closePaiement()"><i class="fas fa-times"></i></button>
</div>
<form method="POST">
<input type="hidden" name="action" value="add_paiement">
<input type="hidden" name="facture_id" id="paiementFactureId">
<div class="modal-body">
  <p id="paiementFactureLabel" style="color:var(--text-muted);font-size:.85rem;margin-bottom:16px"></p>
  <div class="form-group">
    <label class="form-label">Montant reçu (MAD)</label>
    <input type="number" name="montant" id="paiementMontant" class="form-control" min="1" step="1" required>
    <div id="paiementResteHint" style="font-size:.75rem;color:#FBB724;margin-top:6px"></div>
  </div>
  <div class="form-group">
    <label class="form-label">Mode de paiement</label>
    <select name="mode" class="form-control">
      <option value="especes">Espèces</option>
      <option value="virement">Virement bancaire</option>
      <option value="cheque">Chèque</option>
      <option value="autre">Autre</option>
    </select>
  </div>
  <div class="form-group">
    <label class="form-label">Date du paiement</label>
    <input type="date" name="date_paiement" class="form-control" value="<?= date('Y-m-d') ?>">
  </div>
</div>
<div class="modal-footer">
  <button type="button" class="btn-secondary" onclick="closePaiement()">Annuler</button>
  <button type="submit" class="btn-primary"><i class="fas fa-check"></i> Enregistrer le paiement</button>
</div>
</form>
</div>
</div>

<script>
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarOverlay = document.getElementById('sidebarOverlay');
if (sidebarToggle) { sidebarToggle.addEventListener('click', () => { const sidebar = document.getElementById('sidebar'); if (sidebar) sidebar.classList.toggle('open'); sidebarOverlay.classList.toggle('show'); }); }
if (sidebarOverlay) { sidebarOverlay.addEventListener('click', () => { const sidebar = document.getElementById('sidebar'); if (sidebar) sidebar.classList.remove('open'); sidebarOverlay.classList.remove('show'); }); }

function calcTotal() {
    const ht = parseFloat(document.getElementById('montantHT')?.value) || 0;
    const acompte = parseFloat(document.getElementById('acompteInput')?.value) || 0;
    const ttc = ht;
    const reste = Math.max(0, Math.round((ttc - acompte) * 100) / 100);
    const fmt = n => n.toLocaleString('fr-FR') + ' MAD';
    const prevTTC = document.getElementById('prevTTC'), prevAcompte = document.getElementById('prevAcompte'), prevReste = document.getElementById('prevReste');
    if (prevTTC) prevTTC.textContent = fmt(ttc);
    if (prevAcompte) prevAcompte.textContent = fmt(acompte);
    if (prevReste) prevReste.textContent = fmt(reste);
}
function openAddModal() { document.getElementById('addModal').classList.add('show'); }
function closeAdd() { document.getElementById('addModal').classList.remove('show'); }
<?php if ($prefillDevis): ?>
document.addEventListener('DOMContentLoaded', () => { openAddModal(); calcTotal(); });
<?php endif; ?>
const sc = <?= json_encode($statutConfig, JSON_UNESCAPED_UNICODE) ?>;
function openDetail(f) {
    const s = sc[f.statut] || sc['brouillon'];
    const fmt = n => parseFloat(n || 0).toLocaleString('fr-FR') + ' MAD';
    const dateEch = f.date_echeance ? new Date(f.date_echeance).toLocaleDateString('fr-FR') : '—';
    const dateEv = f.date_evenement ? new Date(f.date_evenement).toLocaleDateString('fr-FR') : '—';
    document.getElementById('detailContent').innerHTML = `
        <div style="padding:20px 22px;background:linear-gradient(135deg,rgba(212,175,55,.06),transparent);border-bottom:1px solid var(--border)">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div><div style="font-family:var(--ff-display);font-size:1.2rem;color:var(--gold);font-weight:700">${f.numero}</div><div style="font-size:.82rem;color:var(--white);margin-top:4px">${f.nom_client}</div></div>
                <span class="statut-badge" style="background:${s.bg};color:${s.color}">${s.label}</span>
            </div>
        </div>
        <div style="padding:20px 22px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px">
                <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Téléphone</div><div style="color:var(--white)">${f.telephone_client || '—'}</div></div>
                <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Email</div><div style="color:var(--white)">${f.email_client || '—'}</div></div>
                <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Événement</div><div style="color:var(--white)">${(f.type_evenement || '—').replace('_',' ')}</div></div>
                <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Date événement</div><div style="color:var(--white)">${dateEv}</div></div>
                <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Package</div><div style="color:var(--gold)">${f.package_nom || '—'}</div></div>
                <div><div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px">Échéance</div><div style="color:var(--white)">${dateEch}</div></div>
            </div>
            <div style="background:var(--dark-3);border-radius:10px;padding:16px">
                <div style="display:flex;justify-content:space-between;padding:8px 0 5px;font-size:1rem;font-weight:700;border-top:1px solid var(--border);margin-top:4px"><span style="color:var(--white)">Total</span><span style="color:var(--gold)" dir="ltr">${fmt(f.montant_ttc)}</span></div>
                <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:.84rem"><span style="color:#FBB724">Acompte reçu</span><span style="color:#FBB724" dir="ltr">${fmt(f.acompte)}</span></div>
                <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:.9rem;font-weight:700"><span style="color:#25D366">Reste à payer</span><span style="color:#25D366" dir="ltr">${parseFloat(f.reste_a_payer) <= 0 ? '✓ Soldé' : fmt(f.reste_a_payer)}</span></div>
            </div>
            ${f.notes ? `<div style="margin-top:14px;background:var(--dark-3);border-left:3px solid var(--gold);padding:10px 14px;border-radius:0 8px 8px 0;font-size:.82rem;color:var(--text-muted)">${f.notes}</div>` : ''}
        </div>
    `;
    document.getElementById('printBtn').href = 'print_facture.php?id=' + f.id;
    document.getElementById('detailModal').classList.add('show');
}
function closeDetail() { document.getElementById('detailModal').classList.remove('show'); }
function openStatutModal(id, statut) { document.getElementById('statutId').value = id; document.getElementById('statutSelect').value = statut; document.getElementById('statutModal').classList.add('show'); }
function closeStatut() { document.getElementById('statutModal').classList.remove('show'); }

function openPaiementModal(factureId, reste, numero) {
    document.getElementById('paiementFactureId').value = factureId;
    const montantInput = document.getElementById('paiementMontant');
    montantInput.removeAttribute('max');
    const hint = document.getElementById('paiementResteHint');

    if (reste > 0) {
        document.getElementById('paiementFactureLabel').textContent = 'Facture ' + numero + ' — Reste à payer : ' + reste.toLocaleString('fr-FR') + ' MAD';
        montantInput.value = reste;
        hint.textContent = 'Montant suggéré : ' + reste.toLocaleString('fr-FR') + ' MAD (reste dû actuel)';
    } else {
        document.getElementById('paiementFactureLabel').textContent = 'Facture ' + numero + ' — Déjà soldée';
        montantInput.value = '';
        hint.textContent = 'Cette facture est déjà payée. Utilisez ceci uniquement pour corriger une erreur ou ajouter un paiement complémentaire.';
    }
    document.getElementById('paiementModal').classList.add('show');
}
function closePaiement() { document.getElementById('paiementModal').classList.remove('show'); }
let currentFilter = 'all';
function setFilter(f, btn) { currentFilter = f; document.querySelectorAll('.tfilter').forEach(b => b.classList.remove('active')); btn.classList.add('active'); filterFac(); }
function filterFac() {
    const searchInput = document.getElementById('searchFac');
    const q = searchInput ? searchInput.value.toLowerCase().trim() : '';
    document.querySelectorAll('#facTable tbody tr').forEach(row => {
        const matchF = currentFilter === 'all' || row.dataset.statut === currentFilter;
        const matchQ = !q || (row.dataset.search || '').includes(q);
        row.classList.toggle('row-hidden', !(matchF && matchQ));
    });
}
document.addEventListener('click', function(e) { if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('show'); });
document.addEventListener('DOMContentLoaded', function() { if (document.getElementById('montantHT')) calcTotal(); });
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
