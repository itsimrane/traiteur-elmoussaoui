<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Récupérer les clients avec leur nombre de réservations
try {
  $clients = $pdo->query("
        SELECT
            c.id, c.civilite, c.prenom, c.nom, c.email, c.telephone, c.ville,
            c.cin, c.actif, c.source, c.created_at,
            COUNT(DISTINCT d.id) AS nb_reservations
        FROM clients c
        LEFT JOIN devis_generes d
            ON d.telephone COLLATE utf8mb4_unicode_ci = c.telephone COLLATE utf8mb4_unicode_ci
        WHERE c.deleted_at IS NULL
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ")->fetchAll();

  // Compléter avec les clients dans devis_generes non encore dans clients
  $devis_clients = $pdo->query("
        SELECT DISTINCT
            0 as id,
            SUBSTRING_INDEX(nom_client,' ',1) as prenom,
            SUBSTRING_INDEX(nom_client,' ',-1) as nom,
            email, telephone, ville,
            'site_web' as source, created_at,
            COUNT(*) as nb_reservations
        FROM devis_generes
        WHERE telephone COLLATE utf8mb4_unicode_ci NOT IN (
            SELECT telephone COLLATE utf8mb4_unicode_ci FROM clients WHERE telephone IS NOT NULL
        )
        GROUP BY telephone
        ORDER BY created_at DESC
    ")->fetchAll();
} catch (Exception $e) {
  $clients = [];
  $debugError = $e->getMessage(); // TEMPORAIRE — pour diagnostiquer
}

$total = count($clients);
$actifs = count(array_filter($clients, fn($c) => $c['actif'] == 1));
$avec_resa = count(array_filter($clients, fn($c) => $c['nb_reservations'] > 0));

$sourceLabels = [
  'site_web' => ['label' => 'Site web', 'icon' => 'fa-globe', 'color' => '#60A5FA'],
  'telephone' => ['label' => 'Téléphone', 'icon' => 'fa-phone', 'color' => '#25D366'],
  'reference' => ['label' => 'Référence', 'icon' => 'fa-users', 'color' => '#D4AF37'],
  'facebook' => ['label' => 'Facebook', 'icon' => 'fa-facebook', 'color' => '#3B82F6'],
  'instagram' => ['label' => 'Instagram', 'icon' => 'fa-instagram', 'color' => '#EC4899'],
  'autre' => ['label' => 'Autre', 'icon' => 'fa-ellipsis-h', 'color' => '#888'],
];

// Traitement ajout/modification client (POST)
$msg = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];

  if ($action === 'add' || $action === 'edit') {
    $data = [
      sanitize($_POST['civilite'] ?? 'M.'),
      sanitize($_POST['nom'] ?? ''),
      sanitize($_POST['prenom'] ?? ''),
      sanitize($_POST['email'] ?? ''),
      sanitize($_POST['telephone'] ?? ''),
      sanitize($_POST['telephone2'] ?? ''),
      sanitize($_POST['adresse'] ?? ''),
      sanitize($_POST['ville'] ?? 'Errachidia'),
      sanitize($_POST['cin'] ?? ''),
      sanitize($_POST['source'] ?? 'site_web'),
      sanitize($_POST['notes_internes'] ?? ''),
    ];

    try {
      if ($action === 'add') {
        $pdo->prepare("
                    INSERT INTO clients (civilite,nom,prenom,email,telephone,telephone2,adresse,ville,cin,source,notes_internes,actif)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,1)
                ")->execute($data);
        $msg = 'Client ajouté avec succès !';
      } else {
        $clientId = (int) ($_POST['client_id'] ?? 0);
        $data[] = $clientId;
        $pdo->prepare("
                    UPDATE clients SET civilite=?,nom=?,prenom=?,email=?,telephone=?,telephone2=?,adresse=?,ville=?,cin=?,source=?,notes_internes=?,updated_at=NOW()
                    WHERE id=?
                ")->execute($data);
        $msg = 'Client mis à jour avec succès !';
      }
      $msgType = 'success';
    } catch (PDOException $e) {
      if ($e->getCode() === '23000') {
        if (stripos($e->getMessage(), 'email') !== false) {
          $msg = 'Un client existe déjà avec cet email. Vérifie la liste des clients ou modifie la fiche existante.';
        } elseif (stripos($e->getMessage(), 'telephone') !== false) {
          $msg = 'Un client existe déjà avec ce numéro de téléphone. Vérifie la liste des clients ou modifie la fiche existante.';
        } else {
          $msg = 'Ce client existe déjà (email ou téléphone en doublon).';
        }
      } else {
        $msg = 'Erreur : ' . $e->getMessage();
      }
      $msgType = 'error';
    }
    header('Location: clients.php?msg=' . urlencode($msg) . '&type=' . $msgType);
    exit;
  }

  if ($action === 'delete') {
    $clientId = (int) ($_POST['client_id'] ?? 0);
    $pdo->prepare("UPDATE clients SET deleted_at = NOW() WHERE id = ?")->execute([$clientId]);
    header('Location: clients.php?msg=Client+supprimé&type=success');
    exit;
  }
}

// Message depuis redirect
if (isset($_GET['msg'])) {
  $msg = $_GET['msg'];
  $msgType = $_GET['type'] ?? 'success';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clients — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body {
      overflow-x: hidden;
    }

    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .6);
      z-index: 999;
    }

    .sidebar-overlay.show {
      display: block;
    }

    @media(max-width:768px) {
      .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        z-index: 1000;
        transform: translateX(-100%);
        transition: var(--transition);
      }

      .sidebar.open {
        transform: translateX(0);
      }
    }

    /* Table */
    .clients-table-wrap {
      background: var(--dark-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }

    .table-topbar {
      padding: 16px 22px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border);
      flex-wrap: wrap;
      gap: 12px;
    }

    .search-input {
      background: var(--dark-3);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 8px 14px;
      color: var(--white);
      font-size: .82rem;
      outline: none;
      width: 240px;
    }

    .search-input:focus {
      border-color: var(--gold);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead th {
      padding: 11px 16px;
      font-size: .7rem;
      color: var(--text-muted);
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .5px;
      border-bottom: 1px solid var(--border);
      text-align: left;
      white-space: nowrap;
    }

    tbody tr {
      border-bottom: 1px solid rgba(255, 255, 255, .04);
      transition: var(--transition);
    }

    tbody tr:last-child {
      border-bottom: none;
    }

    tbody tr:hover {
      background: rgba(212, 175, 55, .03);
    }

    td {
      padding: 13px 16px;
      font-size: .83rem;
      color: var(--text-muted);
      vertical-align: middle;
    }

    .client-avatar {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--gold-dark), var(--gold));
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: .85rem;
      color: var(--dark);
      flex-shrink: 0;
    }

    .client-info strong {
      display: block;
      color: var(--white);
      font-size: .85rem;
    }

    .client-info span {
      font-size: .73rem;
      color: #555;
    }

    .source-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: .7rem;
      background: var(--dark-3);
    }

    .td-actions {
      display: flex;
      gap: 6px;
    }

    .act-btn {
      width: 30px;
      height: 30px;
      border-radius: 7px;
      border: 1px solid var(--border);
      background: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .78rem;
      transition: var(--transition);
      color: var(--text-muted);
      text-decoration: none;
    }

    .act-btn:hover {
      border-color: var(--gold);
      color: var(--gold);
    }

    .act-btn.danger:hover {
      border-color: rgba(239, 68, 68, .4);
      color: #EF5350;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--text-muted);
    }

    .empty-state i {
      font-size: 2.5rem;
      opacity: .2;
      display: block;
      margin-bottom: 12px;
    }

    /* Modal */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .75);
      z-index: 2000;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .modal-overlay.show {
      display: flex;
    }

    .modal-box {
      background: var(--dark-card);
      border: 1px solid var(--border);
      border-radius: 14px;
      width: 100%;
      max-width: 580px;
      max-height: 90vh;
      overflow-y: auto;
    }

    .modal-header {
      padding: 18px 22px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      background: var(--dark-card);
      z-index: 1;
    }

    .modal-header h3 {
      color: var(--white);
      font-size: .95rem;
    }

    .modal-close {
      width: 30px;
      height: 30px;
      border-radius: 7px;
      border: 1px solid var(--border);
      background: none;
      color: var(--text-muted);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-close:hover {
      border-color: var(--gold);
      color: var(--gold);
    }

    .modal-body {
      padding: 22px;
    }

    .modal-footer {
      padding: 14px 22px;
      border-top: 1px solid var(--border);
      display: flex;
      gap: 10px;
      justify-content: flex-end;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .form-full {
      grid-column: 1/-1;
    }

    select.form-control option {
      background: #1E1E2E;
    }

    /* Fiche client */
    .fiche-header {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 20px 22px;
      border-bottom: 1px solid var(--border);
      background: linear-gradient(135deg, rgba(212, 175, 55, .06), transparent);
    }

    .fiche-avatar {
      width: 56px;
      height: 56px;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--gold-dark), var(--gold));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--dark);
      flex-shrink: 0;
    }

    .fiche-name {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--white);
    }

    .fiche-sub {
      font-size: .78rem;
      color: var(--text-muted);
      margin-top: 3px;
    }

    .detail-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .detail-field label {
      display: block;
      font-size: .68rem;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: .5px;
      margin-bottom: 3px;
    }

    .detail-field span {
      color: var(--white);
      font-size: .85rem;
    }

    .notes-box {
      background: var(--dark-3);
      border-radius: 8px;
      padding: 12px 14px;
      font-size: .82rem;
      color: var(--text-muted);
      line-height: 1.6;
    }

    .section-label {
      font-size: .68rem;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: .5px;
      margin: 16px 0 10px;
      font-weight: 700;
    }
  </style>
</head>

<body>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <div class="admin-layout">

    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="logo-text" style="display:flex;flex-direction:column;align-items:center">
          <span class="logo-traiteur"
            style="font-size:.55rem;letter-spacing:4px;color:var(--text-muted)">TRAITEUR</span>
          <span class="logo-name" style="font-size:1.1rem">EL MOUSSAOUI</span>
          <span class="logo-sub" style="font-size:.65rem">Admin Panel v1.0</span>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-label">PRINCIPAL</div>
        <a href="dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="reservations.php" class="sidebar-link"><i class="fas fa-calendar-check"></i> Réservations</a>
        <a href="devis.php" class="sidebar-link"><i class="fas fa-file-invoice"></i> Devis</a>
        <a href="clients.php" class="sidebar-link active"><i class="fas fa-users"></i> Clients</a>
        <a href="factures.php" class="sidebar-link"><i class="fas fa-receipt"></i> Factures</a>
        <a href="paiements.php" class="sidebar-link"><i class="fas fa-credit-card"></i> Paiements</a>
        <div class="sidebar-label" style="margin-top:8px">CONTENU</div>
        <a href="services-admin.php" class="sidebar-link"><i class="fas fa-concierge-bell"></i> Services</a>
        <a href="packages-admin.php" class="sidebar-link"><i class="fas fa-box-open"></i> Packages</a>
        <a href="../pages/galerie.php?edit=1" class="sidebar-link"><i class="fas fa-images"></i> Galerie</a>
        <a href="blog-admin.php" class="sidebar-link"><i class="fas fa-pen-nib"></i> Blog</a>
        <a href="temoignages-admin.php" class="sidebar-link"><i class="fas fa-star"></i> Témoignages</a>
        <div class="sidebar-label" style="margin-top:8px">COMMUNICATION</div>
        <a href="messages.php" class="sidebar-link"><i class="fas fa-envelope"></i> Messages</a>
        <a href="notifications.php" class="sidebar-link"><i class="fas fa-bell"></i> Notifications</a>
        <div class="sidebar-label" style="margin-top:8px">SYSTÈME</div>
        <a href="utilisateurs.php" class="sidebar-link"><i class="fas fa-user-shield"></i> Utilisateurs</a>
        <a href="parametres.php" class="sidebar-link"><i class="fas fa-cog"></i> Paramètres</a>
        <a href="logs.php" class="sidebar-link"><i class="fas fa-history"></i> Journaux</a>
      </nav>
      <div class="sidebar-footer">
        <div style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:10px;background:var(--dark-3)">
          <div class="admin-avatar" style="width:34px;height:34px;border-radius:8px">A</div>
          <div style="flex:1;min-width:0">
            <div style="font-size:.82rem;color:var(--white);font-weight:500">Admin ELM</div>
            <div
              style="font-size:.7rem;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              admin@traiteur-elmoussaoui.ma</div>
          </div>
          <a href="logout.php" title="Déconnexion" style="color:var(--text-muted);font-size:.85rem"
            onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--text-muted)'">
            <i class="fas fa-sign-out-alt"></i>
          </a>
        </div>
      </div>
    </aside>

    <main class="admin-main">
      <div class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px">
          <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
          <div class="topbar-title">
            <h2>Gestion Clients</h2>
            <p>Fiches clients et historique des événements</p>
          </div>
        </div>
        <div class="topbar-actions">
          <button class="topbar-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
          <button class="btn-primary" style="padding:8px 18px;font-size:.82rem" onclick="openAddModal()">
            <i class="fas fa-user-plus"></i> Nouveau client
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
        <?php if (!empty($debugError)): ?>
          <div class="alert alert-error"
            style="margin-bottom:20px;background:rgba(239,68,68,.1);border:1px solid #EF5350;padding:12px 16px;border-radius:8px;color:#EF5350;font-family:monospace;font-size:.8rem">
            🔍 DEBUG (temporaire) : <?= htmlspecialchars($debugError) ?>
          </div>
        <?php else: ?>
          <div
            style="margin-bottom:20px;background:rgba(96,165,250,.1);border:1px solid #60A5FA;padding:12px 16px;border-radius:8px;color:#60A5FA;font-family:monospace;font-size:.8rem">
            🔍 DEBUG (temporaire) : aucune erreur SQL — la requête a retourné <?= count($clients) ?> client(s).
          </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid" style="margin-bottom:24px">
          <div class="stat-card">
            <div class="stat-card-header">
              <div class="stat-card-icon gold"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card-value"><?= $total ?></div>
            <div class="stat-card-label">Total clients</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-header">
              <div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i
                  class="fas fa-user-check"></i></div>
            </div>
            <div class="stat-card-value"><?= $actifs ?></div>
            <div class="stat-card-label">Clients actifs</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-header">
              <div class="stat-card-icon" style="background:rgba(59,130,246,.1);color:#60A5FA"><i
                  class="fas fa-calendar-check"></i></div>
            </div>
            <div class="stat-card-value"><?= $avec_resa ?></div>
            <div class="stat-card-label">Avec réservation</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-header">
              <div class="stat-card-icon" style="background:rgba(168,85,247,.1);color:#C084FC"><i
                  class="fas fa-city"></i></div>
            </div>
            <div class="stat-card-value"><?= count(array_unique(array_column($clients, 'ville'))) ?></div>
            <div class="stat-card-label">Villes différentes</div>
          </div>
        </div>

        <!-- Table clients -->
        <div class="clients-table-wrap">
          <div class="table-topbar">
            <h3 style="color:var(--white);font-size:.9rem"><i class="fas fa-list"
                style="color:var(--gold);margin-right:8px"></i>Liste des clients (<?= $total ?>)</h3>
            <div style="display:flex;gap:10px;align-items:center">
              <input type="text" class="search-input" id="searchClient" placeholder="\ud83d\udd0d Nom, email, tél..."
                oninput="filterClients()">
              <select id="filterSource" class="search-input" style="width:auto" onchange="filterClients()">
                <option value="">Toutes les sources</option>
                <?php foreach ($sourceLabels as $key => $src): ?>
                  <option value="<?= $key ?>"><?= $src['label'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <?php if (empty($clients)): ?>
            <div class="empty-state">
              <i class="fas fa-users"></i>
              <p>Aucun client pour l'instant.</p>
              <button class="btn-primary" style="margin-top:16px" onclick="openAddModal()">
                <i class="fas fa-user-plus"></i> Ajouter le premier client
              </button>
            </div>
          <?php else: ?>
            <div style="overflow-x:auto">
              <table id="clientsTable">
                <thead>
                  <tr>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Ville</th>
                    <th>Source</th>
                    <th>Réservations</th>
                    <th>Inscrit le</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($clients as $c):
                    $initiales = strtoupper(substr($c['prenom'], 0, 1) . substr($c['nom'], 0, 1));
                    $nomComplet = $c['civilite'] . ' ' . $c['prenom'] . ' ' . $c['nom'];
                    $src = $sourceLabels[$c['source'] ?? 'autre'] ?? $sourceLabels['autre'];
                    $dateInscrit = date('d/m/Y', strtotime($c['created_at']));
                    ?>
                    <tr
                      data-search="<?= strtolower($nomComplet . ' ' . $c['email'] . ' ' . $c['telephone'] . ' ' . $c['ville']) ?>"
                      data-source="<?= $c['source'] ?? '' ?>">
                      <td>
                        <div style="display:flex;align-items:center;gap:10px">
                          <div class="client-avatar"><?= $initiales ?></div>
                          <div class="client-info">
                            <strong><?= htmlspecialchars($nomComplet) ?></strong>
                            <span><?= htmlspecialchars($c['cin'] ? 'CIN : ' . $c['cin'] : '') ?></span>
                          </div>
                        </div>
                      </td>
                      <td>
                        <div style="font-size:.82rem;color:var(--white)"><?= htmlspecialchars($c['email']) ?></div>
                        <div style="font-size:.75rem;color:#555"><?= htmlspecialchars($c['telephone']) ?></div>
                      </td>
                      <td style="font-size:.82rem"><?= htmlspecialchars($c['ville'] ?? 'Errachidia') ?></td>
                      <td>
                        <span class="source-badge" style="color:<?= $src['color'] ?>">
                          <i class="fas <?= $src['icon'] ?>"></i> <?= $src['label'] ?>
                        </span>
                      </td>
                      <td style="text-align:center">
                        <?php if ($c['nb_reservations'] > 0): ?>
                          <span
                            style="background:rgba(37,211,102,.1);color:#25D366;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700">
                            <?= $c['nb_reservations'] ?> événement<?= $c['nb_reservations'] > 1 ? 's' : '' ?>
                          </span>
                        <?php else: ?>
                          <span style="color:#555;font-size:.78rem">—</span>
                        <?php endif; ?>
                      </td>
                      <td style="font-size:.75rem;color:#555"><?= $dateInscrit ?></td>
                      <td>
                        <div class="td-actions">
                          <button class="act-btn" onclick='openFiche(<?= json_encode($c) ?>)' title="Voir la fiche">
                            <i class="fas fa-eye"></i>
                          </button>
                          <button class="act-btn" onclick='openEditModal(<?= json_encode($c) ?>)' title="Modifier">
                            <i class="fas fa-edit"></i>
                          </button>
                          <button class="act-btn" onclick="window.location='tel:<?= $c['telephone'] ?>'" title="Appeler">
                            <i class="fas fa-phone"></i>
                          </button>
                          <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce client ?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
                            <button type="submit" class="act-btn danger" title="Supprimer">
                              <i class="fas fa-trash"></i>
                            </button>
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

  <!-- Modal Ajouter / Modifier -->
  <div class="modal-overlay" id="clientModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3 id="modalTitle"><i class="fas fa-user-plus" style="color:var(--gold);margin-right:8px"></i>Nouveau client
        </h3>
        <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
      </div>
      <form method="POST" action="clients.php">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="client_id" id="clientId" value="">
        <div class="modal-body">
          <div class="form-grid" style="margin-bottom:12px">
            <div class="form-group">
              <label class="form-label">Civilité</label>
              <select name="civilite" id="f_civilite" class="form-control">
                <option value="M.">M.</option>
                <option value="Mme">Mme</option>
                <option value="Dr">Dr</option>
                <option value="Prof">Prof</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Prénom *</label>
              <input type="text" name="prenom" id="f_prenom" class="form-control" required>
            </div>
            <div class="form-group form-full">
              <label class="form-label">Nom *</label>
              <input type="text" name="nom" id="f_nom" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email *</label>
              <input type="email" name="email" id="f_email" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Téléphone *</label>
              <input type="tel" name="telephone" id="f_telephone" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Téléphone 2</label>
              <input type="tel" name="telephone2" id="f_telephone2" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label">CIN</label>
              <input type="text" name="cin" id="f_cin" class="form-control" placeholder="AB123456">
            </div>
            <div class="form-group form-full">
              <label class="form-label">Adresse</label>
              <input type="text" name="adresse" id="f_adresse" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label">Ville</label>
              <input type="text" name="ville" id="f_ville" class="form-control" value="Errachidia">
            </div>
            <div class="form-group">
              <label class="form-label">Source</label>
              <select name="source" id="f_source" class="form-control">
                <?php foreach ($sourceLabels as $key => $src): ?>
                  <option value="<?= $key ?>"><?= $src['label'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group form-full">
              <label class="form-label">Notes internes</label>
              <textarea name="notes_internes" id="f_notes" class="form-control" rows="3"
                placeholder="Remarques, préférences..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
          <button type="submit" class="btn-primary"><i class="fas fa-save"></i> <span
              id="saveBtnText">Ajouter</span></button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Fiche client -->
  <div class="modal-overlay" id="ficheModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3><i class="fas fa-id-card" style="color:var(--gold);margin-right:8px"></i>Fiche client</h3>
        <button class="modal-close" onclick="closeFiche()"><i class="fas fa-times"></i></button>
      </div>
      <div id="ficheContent"></div>
      <div class="modal-footer">
        <button class="btn-secondary" onclick="closeFiche()">Fermer</button>
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

    // Filtres
    function filterClients() {
      const q = document.getElementById('searchClient').value.toLowerCase();
      const src = document.getElementById('filterSource').value;
      document.querySelectorAll('#clientsTable tbody tr').forEach(row => {
        const matchQ = !q || row.dataset.search.includes(q);
        const matchSrc = !src || row.dataset.source === src;
        row.style.display = (matchQ && matchSrc) ? '' : 'none';
      });
    }

    // Modal ajouter
    function openAddModal() {
      document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus" style="color:var(--gold);margin-right:8px"></i>Nouveau client';
      document.getElementById('formAction').value = 'add';
      document.getElementById('clientId').value = '';
      document.getElementById('saveBtnText').textContent = 'Ajouter';
      ['civilite', 'prenom', 'nom', 'email', 'telephone', 'telephone2', 'cin', 'adresse', 'ville', 'source', 'notes'].forEach(f => {
        const el = document.getElementById('f_' + f);
        if (el) el.value = f === 'ville' ? 'Errachidia' : f === 'civilite' ? 'M.' : f === 'source' ? 'site_web' : '';
      });
      document.getElementById('clientModal').classList.add('show');
    }

    // Modal modifier
    function openEditModal(c) {
      document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit" style="color:var(--gold);margin-right:8px"></i>Modifier le client';
      document.getElementById('formAction').value = 'edit';
      document.getElementById('clientId').value = c.id;
      document.getElementById('saveBtnText').textContent = 'Enregistrer';
      document.getElementById('f_civilite').value = c.civilite || 'M.';
      document.getElementById('f_prenom').value = c.prenom || '';
      document.getElementById('f_nom').value = c.nom || '';
      document.getElementById('f_email').value = c.email || '';
      document.getElementById('f_telephone').value = c.telephone || '';
      document.getElementById('f_telephone2').value = c.telephone2 || '';
      document.getElementById('f_cin').value = c.cin || '';
      document.getElementById('f_adresse').value = c.adresse || '';
      document.getElementById('f_ville').value = c.ville || 'Errachidia';
      document.getElementById('f_source').value = c.source || 'site_web';
      document.getElementById('f_notes').value = c.notes_internes || '';
      document.getElementById('clientModal').classList.add('show');
    }

    function closeModal() { document.getElementById('clientModal').classList.remove('show'); }

    // Fiche client
    const sourceLabels = <?= json_encode($sourceLabels) ?>;
    function openFiche(c) {
      const src = sourceLabels[c.source || 'autre'] || sourceLabels['autre'];
      const init = ((c.prenom || '')[0] || '').toUpperCase() + ((c.nom || '')[0] || '').toUpperCase();
      const date = c.created_at ? new Date(c.created_at).toLocaleDateString('fr-FR') : '—';
      const dernEv = c.dernier_evenement ? new Date(c.dernier_evenement).toLocaleDateString('fr-FR') : '—';

      document.getElementById('ficheContent').innerHTML = `
    <div class="fiche-header">
      <div class="fiche-avatar">${init}</div>
      <div>
        <div class="fiche-name">${c.civilite} ${c.prenom} ${c.nom}</div>
        <div class="fiche-sub">${c.email}</div>
        <div class="fiche-sub" style="margin-top:4px">
          <span class="source-badge" style="color:${src.color};background:var(--dark-3);padding:3px 10px;border-radius:20px;font-size:.7rem">
            <i class="fas ${src.icon}"></i> ${src.label}
          </span>
        </div>
      </div>
    </div>
    <div class="modal-body">
      <div class="section-label">Coordonnées</div>
      <div class="detail-grid" style="margin-bottom:16px">
        <div class="detail-field"><label>Téléphone</label><span>${c.telephone || '—'}</span></div>
        <div class="detail-field"><label>Téléphone 2</label><span>${c.telephone2 || '—'}</span></div>
        <div class="detail-field"><label>Adresse</label><span>${c.adresse || '—'}</span></div>
        <div class="detail-field"><label>Ville</label><span>${c.ville || 'Errachidia'}</span></div>
        <div class="detail-field"><label>CIN</label><span>${c.cin || '—'}</span></div>
        <div class="detail-field"><label>Client depuis</label><span>${date}</span></div>
      </div>
      <div class="section-label">Historique</div>
      <div class="detail-grid" style="margin-bottom:16px">
        <div class="detail-field"><label>Nb événements</label><span style="color:var(--gold);font-weight:700">${c.nb_reservations || 0}</span></div>
        <div class="detail-field"><label>Dernier événement</label><span>${dernEv}</span></div>
      </div>
      ${c.notes_internes ? `<div class="section-label">Notes internes</div><div class="notes-box">${c.notes_internes}</div>` : ''}
    </div>`;
      document.getElementById('ficheModal').classList.add('show');
    }
    function closeFiche() { document.getElementById('ficheModal').classList.remove('show'); }
  </script>
</body>

</html>