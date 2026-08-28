<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// Récupérer les rôles
$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();
$rolesMap = array_column($roles, null, 'id');

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $pw = password_hash($_POST['password'] ?? 'Admin@2025', PASSWORD_BCRYPT, ['cost'=>12]);
        try {
            $pdo->prepare("
                INSERT INTO users (role_id, nom, prenom, email, password, telephone, langue, actif)
                VALUES (?,?,?,?,?,?,?,1)
            ")->execute([
                (int)($_POST['role_id']   ?? 2),
                sanitize($_POST['nom']    ?? ''),
                sanitize($_POST['prenom'] ?? ''),
                sanitize($_POST['email']  ?? ''),
                $pw,
                sanitize($_POST['telephone'] ?? ''),
                sanitize($_POST['langue']    ?? 'fr'),
            ]);
            $msg = 'Utilisateur créé avec succès !'; $msgType = 'success';
        } catch(Exception $e) {
            $msg = 'Erreur : ' . ($e->getCode() == 23000 ? 'Cet email est déjà utilisé.' : $e->getMessage());
            $msgType = 'error';
        }
        header('Location: utilisateurs.php?msg='.urlencode($msg).'&type='.$msgType); exit;
    }

    if ($action === 'edit') {
        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            (int)($_POST['role_id']    ?? 2),
            sanitize($_POST['nom']     ?? ''),
            sanitize($_POST['prenom']  ?? ''),
            sanitize($_POST['email']   ?? ''),
            sanitize($_POST['telephone'] ?? ''),
            sanitize($_POST['langue']    ?? 'fr'),
            $id,
        ];
        try {
            $pdo->prepare("UPDATE users SET role_id=?,nom=?,prenom=?,email=?,telephone=?,langue=?,updated_at=NOW() WHERE id=?")
                ->execute($data);
            // Changer mot de passe si rempli
            if (!empty($_POST['password'])) {
                $pw = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost'=>12]);
                $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$pw, $id]);
            }
            $msg = 'Utilisateur mis à jour !'; $msgType = 'success';
        } catch(Exception $e) {
            $msg = 'Erreur : ' . $e->getMessage(); $msgType = 'error';
        }
        header('Location: utilisateurs.php?msg='.urlencode($msg).'&type='.$msgType); exit;
    }

    if ($action === 'toggle_actif') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE users SET actif = NOT actif WHERE id=?")->execute([$id]);
        header('Location: utilisateurs.php'); exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // Soft delete
        $pdo->prepare("UPDATE users SET deleted_at=NOW(), actif=0 WHERE id=? AND role_id > 1")->execute([$id]);
        header('Location: utilisateurs.php?msg=Utilisateur+supprimé&type=success'); exit;
    }
}

// Récupérer les utilisateurs
$users = $pdo->query("
    SELECT u.*, r.label as role_label, r.nom as role_nom
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE u.deleted_at IS NULL
    ORDER BY u.role_id ASC, u.created_at DESC
")->fetchAll();

$total      = count($users);
$actifs     = count(array_filter($users, fn($u) => $u['actif']));
$admins     = count(array_filter($users, fn($u) => in_array($u['role_nom'], ['super_admin','admin'])));

$msg     = $_GET['msg']  ?? '';
$msgType = $_GET['type'] ?? 'success';

$roleColors = [
    'super_admin' => ['color'=>'#D4AF37','bg'=>'rgba(212,175,55,.15)','icon'=>'fa-crown'],
    'admin'       => ['color'=>'#60A5FA','bg'=>'rgba(59,130,246,.15)', 'icon'=>'fa-user-shield'],
    'gestionnaire'=> ['color'=>'#25D366','bg'=>'rgba(37,211,102,.15)', 'icon'=>'fa-user-tie'],
    'client'      => ['color'=>'#888',   'bg'=>'rgba(136,136,136,.1)', 'icon'=>'fa-user'],
];
$langueLabels = ['fr'=>'\ud83c\uddeb\ud83c\uddf7 Français','ar'=>'\ud83c\uddf2\ud83c\udde6 Arabe','en'=>'\ud83c\uddec\ud83c\udde7 Anglais'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Utilisateurs — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}

    .users-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
    .user-card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;transition:var(--transition)}
    .user-card:hover{border-color:rgba(212,175,55,.3);transform:translateY(-2px)}
    .user-card.inactive{opacity:.55}
    .user-card-header{padding:18px 20px;display:flex;align-items:center;gap:14px;position:relative}
    .user-avatar-lg{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.2rem;flex-shrink:0}
    .user-info .user-name{font-size:.95rem;font-weight:700;color:var(--white)}
    .user-info .user-email{font-size:.73rem;color:var(--text-muted);margin-top:2px}
    .user-info .user-tel{font-size:.72rem;color:#555;margin-top:1px}
    .active-dot{position:absolute;top:14px;right:14px;width:9px;height:9px;border-radius:50%}
    .active-dot.on{background:#25D366;box-shadow:0 0 6px #25D366}
    .active-dot.off{background:#555}
    .user-card-body{padding:14px 20px;border-top:1px solid var(--border)}
    .user-meta-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
    .role-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:.7rem;font-weight:700}
    .lang-badge{background:var(--dark-3);color:var(--text-muted);padding:4px 10px;border-radius:20px;font-size:.7rem}
    .user-last-conn{font-size:.72rem;color:#555;margin-bottom:12px}
    .user-actions{display:flex;gap:6px}
    .u-btn{flex:1;padding:7px;border-radius:8px;border:1px solid var(--border);background:none;cursor:pointer;font-size:.75rem;color:var(--text-muted);transition:var(--transition);font-family:var(--ff-body);display:flex;align-items:center;justify-content:center;gap:5px}
    .u-btn:hover{border-color:var(--gold);color:var(--gold)}
    .u-btn.toggle-on{background:rgba(37,211,102,.08);border-color:rgba(37,211,102,.3);color:#25D366}
    .u-btn.toggle-off{background:rgba(136,136,136,.08);border-color:rgba(136,136,136,.3);color:#888}
    .u-btn.danger:hover{border-color:rgba(239,68,68,.4);color:#EF5350}
    .super-protected{font-size:.68rem;color:#555;text-align:center;padding:6px;font-style:italic}

    /* Modal */
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
    .pw-wrap{position:relative}
    .pw-wrap input{padding-right:40px}
    .pw-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#555;cursor:pointer;font-size:.85rem}
    .pw-toggle:hover{color:var(--gold)}
    .search-input{background:var(--dark-3);border:1px solid var(--border);border-radius:8px;padding:8px 14px;color:var(--white);font-size:.82rem;outline:none;width:220px}
    .search-input:focus{border-color:var(--gold)}
    .tfilter{padding:6px 14px;border-radius:20px;border:1px solid var(--border);background:none;color:#888;cursor:pointer;font-size:.75rem;transition:var(--transition);font-family:var(--ff-body)}
    .tfilter.active,.tfilter:hover{border-color:var(--gold);color:var(--gold)}
    .empty-state{text-align:center;padding:60px 20px;color:var(--text-muted)}
    .empty-state i{font-size:2.5rem;opacity:.2;display:block;margin-bottom:12px}

    /* Permissions preview */
    .perm-list{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
    .perm-tag{background:rgba(212,175,55,.08);border:1px solid rgba(212,175,55,.2);color:var(--gold);padding:2px 8px;border-radius:6px;font-size:.65rem}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">

  <?php $activePage = 'utilisateurs'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2 data-fr="Gestion Utilisateurs" data-ar="إدارة المستخدمين">Gestion Utilisateurs</h2><p data-fr="Comptes et permissions d'accès" data-ar="الحسابات وصلاحيات الوصول">Comptes et permissions d'accès</p></div>
      </div>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
        <button class="btn-primary" style="padding:8px 18px;font-size:.82rem" onclick="openAdd()">
          <i class="fas fa-user-plus"></i> <span data-fr="Nouvel utilisateur" data-ar="مستخدم جديد">Nouvel utilisateur</span>
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
          <div class="stat-card-header"><div class="stat-card-icon gold"><i class="fas fa-users"></i></div></div>
          <div class="stat-card-value"><?= $total ?></div>
          <div class="stat-card-label" data-fr="Total utilisateurs" data-ar="إجمالي المستخدمين">Total utilisateurs</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-user-check"></i></div></div>
          <div class="stat-card-value"><?= $actifs ?></div>
          <div class="stat-card-label" data-fr="Actifs" data-ar="نشطون">Actifs</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(212,175,55,.1);color:var(--gold)"><i class="fas fa-user-shield"></i></div></div>
          <div class="stat-card-value"><?= $admins ?></div>
          <div class="stat-card-label" data-fr="Administrateurs" data-ar="المديرون">Administrateurs</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(136,136,136,.1);color:#888"><i class="fas fa-user"></i></div></div>
          <div class="stat-card-value"><?= $total - $admins ?></div>
          <div class="stat-card-label" data-fr="Clients / Autres" data-ar="العملاء / أخرى">Clients / Autres</div>
        </div>
      </div>

      <!-- Filtres -->
      <div style="display:flex;gap:10px;align-items:center;margin-bottom:20px;flex-wrap:wrap">
        <input type="text" class="search-input" id="searchUser" placeholder="🔍 Nom, email..." data-fr-placeholder="🔍 Nom, email..." data-ar-placeholder="🔍 الاسم، البريد..." oninput="filterUsers()">
        <button class="tfilter active" onclick="setFilter('all',this)" data-fr="Tous" data-ar="الكل">Tous</button>
        <?php foreach ($roles as $r): ?>
        <button class="tfilter" onclick="setFilter('<?= $r['nom'] ?>',this)"><?= htmlspecialchars($r['label']) ?></button>
        <?php endforeach; ?>
      </div>

      <!-- Rôles info -->
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px">
        <?php foreach ($roles as $r):
          $rc = $roleColors[$r['nom']] ?? $roleColors['client'];
          $perms = json_decode($r['permissions'] ?? '[]', true) ?: [];
        ?>
        <div style="background:var(--dark-card);border:1px solid var(--border);border-radius:10px;padding:14px">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
            <div style="width:32px;height:32px;border-radius:8px;background:<?= $rc['bg'] ?>;display:flex;align-items:center;justify-content:center;color:<?= $rc['color'] ?>">
              <i class="fas <?= $rc['icon'] ?>"></i>
            </div>
            <span style="font-size:.8rem;font-weight:700;color:var(--white)"><?= htmlspecialchars($r['label']) ?></span>
          </div>
          <div class="perm-list">
            <?php foreach (array_slice($perms, 0, 4) as $p): ?>
            <span class="perm-tag"><?= htmlspecialchars($p) ?></span>
            <?php endforeach; ?>
            <?php if (count($perms) > 4): ?>
            <span class="perm-tag">+<?= count($perms)-4 ?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Grille utilisateurs -->
      <?php if (empty($users)): ?>
      <div class="empty-state">
        <i class="fas fa-users"></i>
        <p>Aucun utilisateur trouvé.</p>
      </div>
      <?php else: ?>
      <div class="users-grid" id="usersGrid">
        <?php foreach ($users as $u):
          $rc   = $roleColors[$u['role_nom'] ?? 'client'] ?? $roleColors['client'];
          $init = strtoupper(substr($u['prenom'],0,1) . substr($u['nom'],0,1));
          $conn = $u['derniere_connexion'] ? date('d/m/Y H:i', strtotime($u['derniere_connexion'])) : 'Jamais';
          $isSuperAdmin = $u['role_nom'] === 'super_admin';
        ?>
        <div class="user-card <?= $u['actif'] ? '' : 'inactive' ?>"
             data-role="<?= $u['role_nom'] ?>"
             data-search="<?= strtolower($u['nom'].' '.$u['prenom'].' '.$u['email']) ?>">
          <div class="user-card-header">
            <div class="user-avatar-lg" style="background:<?= $rc['bg'] ?>;color:<?= $rc['color'] ?>"><?= $init ?></div>
            <div class="user-info">
              <div class="user-name"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></div>
              <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
              <?php if ($u['telephone']): ?>
              <div class="user-tel"><?= htmlspecialchars($u['telephone']) ?></div>
              <?php endif; ?>
            </div>
            <div class="active-dot <?= $u['actif'] ? 'on' : 'off' ?>" title="<?= $u['actif'] ? 'Actif' : 'Inactif' ?>"></div>
          </div>
          <div class="user-card-body">
            <div class="user-meta-row">
              <span class="role-badge" style="background:<?= $rc['bg'] ?>;color:<?= $rc['color'] ?>">
                <i class="fas <?= $rc['icon'] ?>"></i> <?= htmlspecialchars($u['role_label'] ?? $u['role_nom']) ?>
              </span>
              <span class="lang-badge"><?= $langueLabels[$u['langue'] ?? 'fr'] ?? '\ud83c\uddeb\ud83c\uddf7' ?></span>
            </div>
            <div class="user-last-conn">
              <i class="fas fa-clock" style="margin-right:4px;color:#555"></i>
              Dernière connexion : <?= $conn ?>
            </div>
            <?php if ($isSuperAdmin): ?>
            <div class="super-protected">🔒 Compte super admin protégé</div>
            <?php else: ?>
            <div class="user-actions">
              <button class="u-btn" onclick='openEdit(<?= json_encode($u) ?>)'>
                <i class="fas fa-edit"></i> Modifier
              </button>
              <form method="POST" style="flex:1">
                <input type="hidden" name="action" value="toggle_actif">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button type="submit" class="u-btn <?= $u['actif'] ? 'toggle-on' : 'toggle-off' ?>" style="width:100%">
                  <i class="fas fa-<?= $u['actif'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                  <?= $u['actif'] ? 'Actif' : 'Inactif' ?>
                </button>
              </form>
              <form method="POST" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button type="submit" class="u-btn danger"><i class="fas fa-trash"></i></button>
              </form>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<!-- Modal Ajouter -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-user-plus" style="color:var(--gold);margin-right:8px"></i><span data-fr="Nouvel utilisateur" data-ar="مستخدم جديد">Nouvel utilisateur</span></h3>
      <button class="modal-close" onclick="closeModal('addModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group">
            <label class="form-label" data-fr="Prénom *" data-ar="الاسم الأول *">Prénom *</label>
            <input type="text" name="prenom" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Nom *" data-ar="الاسم العائلي *">Nom *</label>
            <input type="text" name="nom" class="form-control" required>
          </div>
          <div class="form-group form-full">
            <label class="form-label" data-fr="Email *" data-ar="البريد الإلكتروني *">Email *</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Téléphone" data-ar="الهاتف">Téléphone</label>
            <input type="tel" name="telephone" class="form-control" placeholder="06XXXXXXXX" data-fr-placeholder="06XXXXXXXX" data-ar-placeholder="06XXXXXXXX">
          </div>
          <div class="form-group">
            <label class="form-label">Langue</label>
            <select name="langue" class="form-control">
              <option value="fr">\ud83c\uddeb\ud83c\uddf7 Français</option>
              <option value="ar">\ud83c\uddf2\ud83c\udde6 Arabe</option>
              <option value="en">\ud83c\uddec\ud83c\udde7 Anglais</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Rôle *" data-ar="الدور *">Rôle *</label>
            <select name="role_id" class="form-control">
              <?php foreach ($roles as $r): if ($r['nom'] === 'super_admin') continue; ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group form-full">
            <label class="form-label" data-fr="Mot de passe *" data-ar="كلمة المرور *">Mot de passe *</label>
            <div class="pw-wrap">
              <input type="password" name="password" id="addPw" class="form-control" placeholder="Min. 8 caractères" required>
              <button type="button" class="pw-toggle" onclick="togglePw('addPw','addPwEye')">
                <i class="fas fa-eye" id="addPwEye"></i>
              </button>
            </div>
          </div>
        </div>
        <div style="background:rgba(212,175,55,.06);border:1px solid rgba(212,175,55,.15);border-radius:8px;padding:12px;font-size:.78rem;color:var(--text-muted)">
          <i class="fas fa-info-circle" style="color:var(--gold);margin-right:6px"></i>
          Le mot de passe sera crypté (bcrypt). Choisissez un mot de passe fort avec majuscules, chiffres et caractères spéciaux.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="closeModal('addModal')">Annuler</button>
        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Créer l'utilisateur</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Modifier -->
<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-user-edit" style="color:var(--gold);margin-right:8px"></i><span data-fr="Modifier l'utilisateur" data-ar="تعديل المستخدم">Modifier l'utilisateur</span></h3>
      <button class="modal-close" onclick="closeModal('editModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      <div class="modal-body">
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group">
            <label class="form-label" data-fr="Prénom *" data-ar="الاسم الأول *">Prénom *</label>
            <input type="text" name="prenom" id="e_prenom" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Nom *" data-ar="الاسم العائلي *">Nom *</label>
            <input type="text" name="nom" id="e_nom" class="form-control" required>
          </div>
          <div class="form-group form-full">
            <label class="form-label" data-fr="Email *" data-ar="البريد الإلكتروني *">Email *</label>
            <input type="email" name="email" id="e_email" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Téléphone" data-ar="الهاتف">Téléphone</label>
            <input type="tel" name="telephone" id="e_telephone" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Langue</label>
            <select name="langue" id="e_langue" class="form-control">
              <option value="fr">\ud83c\uddeb\ud83c\uddf7 Français</option>
              <option value="ar">\ud83c\uddf2\ud83c\udde6 Arabe</option>
              <option value="en">\ud83c\uddec\ud83c\udde7 Anglais</option>
            </select>
          </div>
          <div class="form-group form-full">
            <label class="form-label">Rôle</label>
            <select name="role_id" id="e_role" class="form-control">
              <?php foreach ($roles as $r): if ($r['nom'] === 'super_admin') continue; ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group form-full">
            <label class="form-label">Nouveau mot de passe <span style="color:#555">(laisser vide pour ne pas changer)</span></label>
            <div class="pw-wrap">
              <input type="password" name="password" id="editPw" class="form-control" placeholder="Laisser vide pour garder l'actuel">
              <button type="button" class="pw-toggle" onclick="togglePw('editPw','editPwEye')">
                <i class="fas fa-eye" id="editPwEye"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="closeModal('editModal')">Annuler</button>
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
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

function openEdit(u) {
  document.getElementById('editId').value      = u.id;
  document.getElementById('e_prenom').value    = u.prenom     || '';
  document.getElementById('e_nom').value       = u.nom        || '';
  document.getElementById('e_email').value     = u.email      || '';
  document.getElementById('e_telephone').value = u.telephone  || '';
  document.getElementById('e_langue').value    = u.langue     || 'fr';
  document.getElementById('e_role').value      = u.role_id    || '2';
  document.getElementById('editPw').value      = '';
  document.getElementById('editModal').classList.add('show');
}

function togglePw(inputId, iconId) {
  const inp  = document.getElementById(inputId);
  const icon = document.getElementById(iconId);
  const isText = inp.type === 'text';
  inp.type   = isText ? 'password' : 'text';
  icon.className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
}

// Filtres
let curFilter = 'all';
function setFilter(f, btn) {
  curFilter = f;
  document.querySelectorAll('.tfilter').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterUsers();
}
function filterUsers() {
  const q = document.getElementById('searchUser').value.toLowerCase();
  document.querySelectorAll('.user-card').forEach(card => {
    const matchR = curFilter === 'all' || card.dataset.role === curFilter;
    const matchQ = !q || card.dataset.search.includes(q);
    card.style.display = (matchR && matchQ) ? '' : 'none';
  });
}
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
