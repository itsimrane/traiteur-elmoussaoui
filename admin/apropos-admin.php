<?php
/**
 * admin/apropos-admin.php
 * Gestion du contenu de la page publique "À propos" :
 * année de fondation, équipe, frise chronologique.
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// ── Auto-création des tables si elles n'existent pas encore ──────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `equipe` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `nom` VARCHAR(150) NOT NULL,
        `role` VARCHAR(150) NOT NULL,
        `role_ar` VARCHAR(150) NULL,
        `icone` VARCHAR(50) NOT NULL DEFAULT 'fa-user',
        `ordre` INT UNSIGNED NOT NULL DEFAULT 0,
        `actif` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `apropos_timeline` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `annee` VARCHAR(20) NOT NULL,
        `titre` VARCHAR(150) NOT NULL,
        `titre_ar` VARCHAR(150) NULL,
        `description` TEXT NULL,
        `description_ar` TEXT NULL,
        `ordre` INT UNSIGNED NOT NULL DEFAULT 0,
        `actif` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch(Exception $e) {}

$msg = ''; $msgType = '';

// ── Traitement des actions ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save_fondation') {
            $annee = (int)($_POST['annee_fondation'] ?? 0);
            $exists = $pdo->query("SELECT id FROM parametres WHERE cle='annee_fondation'")->fetch();
            if ($exists) {
                $pdo->prepare("UPDATE parametres SET valeur=? WHERE cle='annee_fondation'")->execute([$annee]);
            } else {
                $pdo->prepare("INSERT INTO parametres (cle, valeur, groupe, label, type, ordre) VALUES ('annee_fondation', ?, 'apropos', 'Année de fondation', 'number', 100)")->execute([$annee]);
            }
            $msg = 'Année de fondation enregistrée.'; $msgType = 'success';
        }

        if ($action === 'add_membre') {
            $pdo->prepare("INSERT INTO equipe (nom, role, role_ar, icone, ordre) VALUES (?,?,?,?,?)")
                ->execute([
                    sanitize($_POST['nom']), sanitize($_POST['role']), sanitize($_POST['role_ar'] ?? ''),
                    sanitize($_POST['icone'] ?: 'fa-user'), (int)($_POST['ordre'] ?? 0)
                ]);
            $msg = 'Membre ajouté.'; $msgType = 'success';
        }

        if ($action === 'delete_membre') {
            $pdo->prepare("DELETE FROM equipe WHERE id=?")->execute([(int)$_POST['id']]);
            $msg = 'Membre supprimé.'; $msgType = 'success';
        }

        if ($action === 'toggle_membre') {
            $pdo->prepare("UPDATE equipe SET actif = 1 - actif WHERE id=?")->execute([(int)$_POST['id']]);
            $msg = 'Statut mis à jour.'; $msgType = 'success';
        }

        if ($action === 'add_jalon') {
            $pdo->prepare("INSERT INTO apropos_timeline (annee, titre, titre_ar, description, description_ar, ordre) VALUES (?,?,?,?,?,?)")
                ->execute([
                    sanitize($_POST['annee']), sanitize($_POST['titre']), sanitize($_POST['titre_ar'] ?? ''),
                    sanitize($_POST['description'] ?? ''), sanitize($_POST['description_ar'] ?? ''), (int)($_POST['ordre'] ?? 0)
                ]);
            $msg = 'Jalon ajouté.'; $msgType = 'success';
        }

        if ($action === 'delete_jalon') {
            $pdo->prepare("DELETE FROM apropos_timeline WHERE id=?")->execute([(int)$_POST['id']]);
            $msg = 'Jalon supprimé.'; $msgType = 'success';
        }

        if ($action === 'toggle_jalon') {
            $pdo->prepare("UPDATE apropos_timeline SET actif = 1 - actif WHERE id=?")->execute([(int)$_POST['id']]);
            $msg = 'Statut mis à jour.'; $msgType = 'success';
        }

    } catch(Exception $e) {
        $msg = 'Erreur : ' . $e->getMessage(); $msgType = 'error';
    }
}

// ── Chargement des données ────────────────────────────────────
$anneeFondation = '';
try {
    $anneeFondation = $pdo->query("SELECT valeur FROM parametres WHERE cle='annee_fondation'")->fetchColumn() ?: '';
} catch(Exception $e) {}

$membres = [];
try { $membres = $pdo->query("SELECT * FROM equipe ORDER BY ordre ASC, id ASC")->fetchAll(); } catch(Exception $e) {}

$jalons = [];
try { $jalons = $pdo->query("SELECT * FROM apropos_timeline ORDER BY ordre ASC, annee ASC")->fetchAll(); } catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= adminLang() ?>" dir="<?= adminDir() ?>">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= t('contenu_apropos') ?> — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}
    .panel{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px}
    .panel h3{font-size:.95rem;color:var(--white);margin-bottom:6px}
    .panel .sub{font-size:.78rem;color:var(--text-muted);margin-bottom:18px}
    .item-row{display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.04)}
    .item-row:last-child{border-bottom:none}
    .item-icon{width:38px;height:38px;border-radius:10px;background:rgba(212,175,55,.1);display:flex;align-items:center;justify-content:center;color:var(--gold);flex-shrink:0;font-size:.75rem;text-align:center}
    .item-body{flex:1;min-width:0}
    .item-body strong{display:block;color:var(--white);font-size:.85rem}
    .item-body span{color:var(--text-muted);font-size:.75rem}
    .item-actions{display:flex;gap:8px;flex-shrink:0}
    .item-actions button{width:32px;height:32px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text-muted);cursor:pointer;transition:var(--transition)}
    .item-actions button:hover{border-color:var(--gold);color:var(--gold)}
    .item-actions button.danger:hover{border-color:#EF5350;color:#EF5350}
    .item-row.inactive{opacity:.4}
    .inline-form{display:grid;gap:10px;margin-top:16px;padding-top:16px;border-top:1px dashed var(--border)}
    .inline-form .row-2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .inline-form input,.inline-form textarea{background:var(--dark-3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--white);font-size:.82rem;font-family:inherit}
    .inline-form textarea{resize:vertical;min-height:60px}
    .inline-form button{background:var(--gold);color:var(--dark);border:none;border-radius:8px;padding:10px;font-weight:700;font-size:.82rem;cursor:pointer}
    .empty-note{color:#555;font-size:.8rem;text-align:center;padding:20px}

    @media(max-width:600px){
      .inline-form .row-2{grid-template-columns:1fr}
      .item-row{flex-wrap:wrap}
      .item-body{min-width:150px}
    }
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout <?= adminRtlClass() ?>">
  <?php $activePage = 'apropos'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2><?= tt('Contenu « À propos »','محتوى « من نحن »') ?></h2><p><?= tt('Année de fondation, équipe, frise chronologique','سنة التأسيس، الفريق، الجدول الزمني') ?></p></div>
      </div>
      <a href="../pages/apropos.php" target="_blank" class="topbar-btn" title="<?= tt('Voir la page publique','عرض الصفحة العامة') ?>"><i class="fas fa-external-link-alt"></i></a>
    </div>

    <div class="admin-content">
      <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px">
        <i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= htmlspecialchars($msg) ?>
      </div>
      <?php endif; ?>

      <!-- Année de fondation -->
      <div class="panel">
        <h3><i class="fas fa-flag" style="color:var(--gold);margin-right:8px"></i><?= tt('Année de fondation','سنة التأسيس') ?></h3>
        <p class="sub"><?= tt("Affichée sur la page À propos (calcule automatiquement le nombre d'années d'expérience). Laisse vide pour ne rien afficher.",'تُعرض في صفحة من نحن (تحسب تلقائياً عدد سنوات الخبرة). اتركها فارغة لعدم العرض.') ?></p>
        <form method="POST" style="display:flex;gap:12px;align-items:center">
          <input type="hidden" name="action" value="save_fondation">
          <input type="number" name="annee_fondation" value="<?= htmlspecialchars($anneeFondation) ?>"
                 placeholder="Ex: 2015" min="1990" max="<?= date('Y') ?>"
                 style="background:var(--dark-3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--white);font-size:.85rem;width:140px">
          <button type="submit" style="background:var(--gold);color:var(--dark);border:none;border-radius:8px;padding:10px 20px;font-weight:700;font-size:.82rem;cursor:pointer"><?= t('enregistrer') ?></button>
        </form>
      </div>

      <!-- Équipe -->
      <div class="panel">
        <h3><i class="fas fa-users" style="color:var(--gold);margin-right:8px"></i><?= tt('Équipe','الفريق') ?> (<?= count($membres) ?>)</h3>
        <p class="sub"><?= tt("Les membres inactifs n'apparaissent pas sur la page publique.",'الأعضاء غير النشطين لا يظهرون في الصفحة العامة.') ?></p>

        <?php if (empty($membres)): ?>
        <div class="empty-note"><?= tt('Aucun membre pour l\'instant — la section équipe restera masquée sur le site.','لا يوجد عضو حالياً — سيبقى قسم الفريق مخفياً في الموقع.') ?></div>
        <?php else: foreach ($membres as $m): ?>
        <div class="item-row <?= $m['actif'] ? '' : 'inactive' ?>">
          <div class="item-icon"><i class="fas <?= htmlspecialchars($m['icone']) ?>"></i></div>
          <div class="item-body">
            <strong><?= htmlspecialchars($m['nom']) ?></strong>
            <span><?= htmlspecialchars($m['role']) ?></span>
          </div>
          <div class="item-actions">
            <form method="POST" style="display:contents">
              <input type="hidden" name="action" value="toggle_membre">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button type="submit" title="<?= $m['actif'] ? tt('Masquer','إخفاء') : tt('Afficher','إظهار') ?>"><i class="fas fa-<?= $m['actif'] ? 'eye' : 'eye-slash' ?>"></i></button>
            </form>
            <form method="POST" style="display:contents" onsubmit="return confirm('<?= tt('Supprimer ce membre ?','هل تريد حذف هذا العضو؟') ?>')">
              <input type="hidden" name="action" value="delete_membre">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button type="submit" class="danger" title="<?= t('supprimer') ?>"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </div>
        <?php endforeach; endif; ?>

        <form method="POST" class="inline-form">
          <input type="hidden" name="action" value="add_membre">
          <div class="row-2">
            <input type="text" name="nom" placeholder="<?= tt('Nom complet','الاسم الكامل') ?>" required>
            <input type="text" name="icone" placeholder="<?= tt('Icône (ex: fa-user-tie)','أيقونة (مثال: fa-user-tie)') ?>" value="fa-user">
          </div>
          <div class="row-2">
            <input type="text" name="role" placeholder="<?= tt('Rôle (ex: Chef de cuisine)','الدور (مثال: رئيس الطهاة)') ?>" required>
            <input type="text" name="role_ar" placeholder="<?= tt('Rôle en arabe (optionnel)','الدور بالعربية (اختياري)') ?>">
          </div>
          <button type="submit"><i class="fas fa-plus"></i> <?= tt('Ajouter ce membre','إضافة هذا العضو') ?></button>
        </form>
      </div>

      <!-- Frise chronologique -->
      <div class="panel">
        <h3><i class="fas fa-history" style="color:var(--gold);margin-right:8px"></i><?= tt('Frise chronologique','الجدول الزمني') ?> (<?= count($jalons) ?>)</h3>
        <p class="sub"><?= tt("Les grandes dates de l'histoire du traiteur, affichées dans l'ordre choisi.",'أهم التواريخ في مسيرة المطعم، تُعرض بالترتيب المختار.') ?></p>

        <?php if (empty($jalons)): ?>
        <div class="empty-note"><?= tt('Aucun jalon pour l\'instant — la frise restera masquée sur le site.','لا يوجد حدث حالياً — سيبقى الجدول الزمني مخفياً في الموقع.') ?></div>
        <?php else: foreach ($jalons as $j): ?>
        <div class="item-row <?= $j['actif'] ? '' : 'inactive' ?>">
          <div class="item-icon"><?= htmlspecialchars($j['annee']) ?></div>
          <div class="item-body">
            <strong><?= htmlspecialchars($j['titre']) ?></strong>
            <span><?= htmlspecialchars(mb_substr($j['description'] ?? '', 0, 70)) ?></span>
          </div>
          <div class="item-actions">
            <form method="POST" style="display:contents">
              <input type="hidden" name="action" value="toggle_jalon">
              <input type="hidden" name="id" value="<?= $j['id'] ?>">
              <button type="submit" title="<?= $j['actif'] ? tt('Masquer','إخفاء') : tt('Afficher','إظهار') ?>"><i class="fas fa-<?= $j['actif'] ? 'eye' : 'eye-slash' ?>"></i></button>
            </form>
            <form method="POST" style="display:contents" onsubmit="return confirm('<?= tt('Supprimer ce jalon ?','هل تريد حذف هذا الحدث؟') ?>')">
              <input type="hidden" name="action" value="delete_jalon">
              <input type="hidden" name="id" value="<?= $j['id'] ?>">
              <button type="submit" class="danger" title="<?= t('supprimer') ?>"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </div>
        <?php endforeach; endif; ?>

        <form method="POST" class="inline-form">
          <input type="hidden" name="action" value="add_jalon">
          <div class="row-2">
            <input type="text" name="annee" placeholder="<?= tt('Année (ex: 2015)','السنة (مثال: 2015)') ?>" required>
            <input type="text" name="titre" placeholder="<?= tt('Titre (ex: Fondation)','العنوان (مثال: التأسيس)') ?>" required>
          </div>
          <textarea name="description" placeholder="<?= tt('Description courte','وصف مختصر') ?>"></textarea>
          <button type="submit"><i class="fas fa-plus"></i> <?= tt('Ajouter ce jalon','إضافة هذا الحدث') ?></button>
        </form>
      </div>

    </div>
  </main>
</div>
<script>
document.getElementById('sidebarToggle').addEventListener('click',()=>{
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('show');
});
document.getElementById('sidebarOverlay').addEventListener('click',()=>{
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
});
</script>
</body>
</html>