<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// Init table + données par défaut si vide
$pdo->exec("CREATE TABLE IF NOT EXISTS `parametres` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cle` VARCHAR(100) NOT NULL,
    `valeur` TEXT NULL,
    `groupe` VARCHAR(50) NOT NULL DEFAULT 'general',
    `label` VARCHAR(150) NULL,
    `type` ENUM('text','textarea','number','boolean','json','color','email','url','password') DEFAULT 'text',
    `ordre` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_param_cle` (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Insérer valeurs par défaut si table vide
$count = $pdo->query("SELECT COUNT(*) FROM parametres")->fetchColumn();
if ($count == 0) {
    $defaults = [
        // Général
        ['site_nom','Traiteur EL MOUSSAOUI','general','Nom du site','text',1],
        ['site_slogan','Organisation des Évènements et des Fêtes','general','Slogan FR','text',2],
        ['site_slogan_ar','تنظيم وتجهيز جميع المناسبات والحفلات','general','Slogan AR','text',3],
        ['site_description','Traiteur et organisateur d\'événements à Errachidia, Maroc.','general','Description du site','textarea',4],
        ['site_langue_defaut','fr','general','Langue par défaut','text',5],
        // Contact
        ['contact_telephone','0626986533','contact','Téléphone principal','text',10],
        ['contact_telephone2','','contact','Téléphone secondaire','text',11],
        ['contact_email','contact@traiteur-elmoussaoui.ma','contact','Email de contact','email',12],
        ['contact_adresse','Errachidia, Maroc','contact','Adresse','text',13],
        ['contact_whatsapp','212626986533','contact','WhatsApp (format international)','text',14],
        ['contact_facebook','','contact','Lien Facebook','url',15],
        ['contact_instagram','','contact','Lien Instagram','url',16],
        // Business
        ['acompte_pct','30','business','Acompte requis (%)','number',20],
        ['tva_defaut','0','business','TVA par défaut (%)','number',21],
        ['annulation_jours','30','business','Délai annulation sans pénalité (jours)','number',22],
        ['devis_validite','30','business','Validité devis (jours)','number',23],
        ['zone_intervention','Errachidia, Erfoud, Rissani, Goulmima, Rich, Tinghir','business','Zone d\'intervention','textarea',24],
        // Email
        ['email_expediteur','noreply@traiteur-elmoussaoui.ma','email_cfg','Email expéditeur','email',30],
        ['email_nom_expediteur','Traiteur EL MOUSSAOUI','email_cfg','Nom expéditeur','text',31],
        ['email_notif_admin','1','email_cfg','Notifier l\'admin pour chaque réservation','boolean',32],
        ['email_confirmation_client','1','email_cfg','Email de confirmation au client','boolean',33],
        // Apparence
        ['couleur_principale','#D4AF37','apparence','Couleur principale (or)','color',40],
        ['couleur_secondaire','#1A1A2E','apparence','Couleur secondaire (fond)','color',41],
        ['maintenance_mode','0','apparence','Mode maintenance (site inaccessible)','boolean',42],
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO parametres (cle,valeur,groupe,label,type,ordre) VALUES (?,?,?,?,?,?)");
    foreach ($defaults as $d) $stmt->execute($d);
}

// Sauvegarder
$msg = ''; $msgType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    foreach ($_POST as $key => $val) {
        if (str_starts_with($key, 'param_')) {
            $cle  = substr($key, 6);
            $valeur = is_array($val) ? implode(',', $val) : sanitize($val);
            $pdo->prepare("UPDATE parametres SET valeur=? WHERE cle=?")->execute([$valeur, $cle]);
        }
    }
    // Checkboxes booléennes non cochées = 0
    $bools = $pdo->query("SELECT cle FROM parametres WHERE type='boolean'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($bools as $cle) {
        if (!isset($_POST['param_' . $cle])) {
            $pdo->prepare("UPDATE parametres SET valeur='0' WHERE cle=?")->execute([$cle]);
        }
    }
    $msg = 'Paramètres sauvegardés avec succès !'; $msgType = 'success';
}

// Charger tous les paramètres groupés
$allParams = $pdo->query("SELECT * FROM parametres ORDER BY groupe, ordre")->fetchAll();
$grouped = [];
foreach ($allParams as $p) $grouped[$p['groupe']][] = $p;

$groupeLabels = [
    'general'   => ['label'=>'Informations générales', 'label_ar'=>'المعلومات العامة',  'icon'=>'fa-info-circle',      'color'=>'#D4AF37'],
    'contact'   => ['label'=>'Contact & Réseaux', 'label_ar'=>'التواصل والشبكات',       'icon'=>'fa-address-card',     'color'=>'#60A5FA'],
    'business'  => ['label'=>'Configuration Métier', 'label_ar'=>'إعدادات العمل',    'icon'=>'fa-briefcase',        'color'=>'#25D366'],
    'email_cfg' => ['label'=>'Emails & Notifications',  'icon'=>'fa-envelope',         'color'=>'#FBB724'],
    'apparence' => ['label'=>'Apparence & Affichage', 'label_ar'=>'المظهر والعرض',   'icon'=>'fa-paint-brush',      'color'=>'#C084FC'],
];

$ongletActif = $_GET['tab'] ?? 'general';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Paramètres — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}

    /* Layout paramètres */
    .params-layout{display:grid;grid-template-columns:240px 1fr;gap:20px;align-items:start}
    @media(max-width:900px){.params-layout{grid-template-columns:1fr}}

    /* Nav onglets */
    .params-nav{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;position:sticky;top:20px}
    .params-nav-title{padding:14px 16px;border-bottom:1px solid var(--border);font-size:.7rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px}
    .params-tab{display:flex;align-items:center;gap:10px;padding:12px 16px;cursor:pointer;transition:var(--transition);border-bottom:1px solid rgba(255,255,255,.03);text-decoration:none}
    .params-tab:hover{background:rgba(212,175,55,.05)}
    .params-tab.active{background:rgba(212,175,55,.08);border-right:2px solid var(--gold)}
    .params-tab i{width:16px;text-align:center;font-size:.85rem}
    .params-tab span{font-size:.82rem;color:var(--text-muted)}
    .params-tab.active span{color:var(--white)}

    /* Sections paramètres */
    .params-section{display:none}
    .params-section.active{display:block}
    .params-card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:20px}
    .params-card-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
    .params-card-header i{font-size:1rem}
    .params-card-header h3{font-size:.9rem;font-weight:700;color:var(--white)}
    .params-card-body{padding:20px}
    .param-row{display:grid;grid-template-columns:200px 1fr;gap:16px;align-items:start;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.04)}
    .param-row:last-child{border-bottom:none}
    .param-label{font-size:.8rem;font-weight:600;color:var(--text-muted);padding-top:8px}
    .param-label small{display:block;font-size:.68rem;color:#555;font-weight:400;margin-top:2px}

    /* Toggle switch */
    .bool-wrap{display:flex;align-items:center;gap:10px;padding-top:6px}
    .bool-label{font-size:.82rem;color:var(--text-muted)}
    .sw{position:relative;width:44px;height:24px;flex-shrink:0}
    .sw input{opacity:0;width:0;height:0;position:absolute}
    .sw-slider{position:absolute;inset:0;background:#2A2A3E;border-radius:34px;cursor:pointer;transition:.2s}
    .sw-slider::before{content:'';position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#666;border-radius:50%;transition:.2s}
    .sw input:checked + .sw-slider{background:rgba(212,175,55,.3);border:1px solid var(--gold)}
    .sw input:checked + .sw-slider::before{transform:translateX(20px);background:var(--gold)}

    /* Color picker */
    .color-wrap{display:flex;align-items:center;gap:10px}
    .color-preview{width:40px;height:38px;border-radius:8px;border:1px solid var(--border);cursor:pointer;flex-shrink:0}
    .color-text{flex:1}

    /* Save bar */
    .save-bar{position:sticky;bottom:0;background:var(--dark-card);border-top:1px solid var(--border);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;margin-top:20px;border-radius:0 0 var(--radius) var(--radius)}
    .save-hint{font-size:.78rem;color:var(--text-muted)}
    @media(max-width:900px){.param-row{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">

  <?php $activePage = 'parametres'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2 data-fr="Paramètres du site" data-ar="إعدادات الموقع">Paramètres du site</h2><p data-fr="Configuration générale de Traiteur EL MOUSSAOUI" data-ar="الإعدادات العامة لترايتور المساوي">Configuration générale de Traiteur EL MOUSSAOUI</p></div>
      </div>
      <div class="topbar-actions">
        <a href="../index.php" target="_blank" class="topbar-btn" title="Voir le site"><i class="fas fa-external-link-alt"></i></a>
        <div class="admin-avatar">A</div>
      </div>
    </div>

    <div class="admin-content">

      <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType ?>" style="margin-bottom:20px">
        <i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($msg) ?>
      </div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="save" value="1">

        <div class="params-layout">

          <!-- Nav onglets -->
          <div class="params-nav">
            <div class="params-nav-title">Catégories</div>
            <?php foreach ($groupeLabels as $key => $gl): ?>
            <a href="#" class="params-tab <?= $ongletActif === $key ? 'active' : '' ?>"
               onclick="showTab('<?= $key ?>'); return false;">
              <i class="fas <?= $gl['icon'] ?>" style="color:<?= $gl['color'] ?>"></i>
              <span><?= $gl['label'] ?></span>
            </a>
            <?php endforeach; ?>
          </div>

          <!-- Contenu -->
          <div>
            <?php foreach ($groupeLabels as $groupeKey => $gl): ?>
            <div class="params-section <?= $ongletActif === $groupeKey ? 'active' : '' ?>" id="tab-<?= $groupeKey ?>">
              <div class="params-card">
                <div class="params-card-header">
                  <i class="fas <?= $gl['icon'] ?>" style="color:<?= $gl['color'] ?>"></i>
                  <h3 data-fr="<?= $gl['label'] ?>" data-ar="<?= $gl['label_ar'] ?? $gl['label'] ?>"><?= $gl['label'] ?></h3>
                </div>
                <div class="params-card-body">
                  <?php foreach ($grouped[$groupeKey] ?? [] as $p): ?>
                  <div class="param-row">
                    <div class="param-label">
                      <span data-fr="<?= htmlspecialchars($p['label'] ?? $p['cle']) ?>"><?= htmlspecialchars($p['label'] ?? $p['cle']) ?></span>
                      <small><?= htmlspecialchars($p['cle']) ?></small>
                    </div>
                    <div>
                      <?php
                      $name = 'param_' . $p['cle'];
                      $val  = htmlspecialchars($p['valeur'] ?? '');
                      switch ($p['type']):
                        case 'boolean': ?>
                          <div class="bool-wrap">
                            <label class="sw">
                              <input type="checkbox" name="<?= $name ?>" value="1" <?= $p['valeur'] == '1' ? 'checked' : '' ?>>
                              <span class="sw-slider"></span>
                            </label>
                            <span class="bool-label" data-fr="<?= $p['valeur'] == '1' ? 'Activé' : 'Désactivé' ?>" data-ar="<?= $p['valeur'] == '1' ? 'مفعّل' : 'معطّل' ?>"><?= $p['valeur'] == '1' ? 'Activé' : 'Désactivé' ?></span>
                          </div>
                        <?php break;
                        case 'textarea': ?>
                          <textarea name="<?= $name ?>" class="form-control" rows="3"><?= $val ?></textarea>
                        <?php break;
                        case 'color': ?>
                          <div class="color-wrap">
                            <input type="color" class="color-preview" id="color_<?= $p['id'] ?>"
                                   value="<?= $p['valeur'] ?? '#D4AF37' ?>"
                                   oninput="document.getElementById('ctext_<?= $p['id'] ?>').value=this.value">
                            <input type="text" name="<?= $name ?>" id="ctext_<?= $p['id'] ?>"
                                   class="form-control color-text" value="<?= $val ?>"
                                   oninput="document.getElementById('color_<?= $p['id'] ?>').value=this.value"
                                   placeholder="#D4AF37">
                          </div>
                        <?php break;
                        case 'email': ?>
                          <input type="email" name="<?= $name ?>" class="form-control" value="<?= $val ?>">
                        <?php break;
                        case 'url': ?>
                          <input type="url" name="<?= $name ?>" class="form-control" value="<?= $val ?>" placeholder="https://...">
                        <?php break;
                        case 'number': ?>
                          <input type="number" name="<?= $name ?>" class="form-control" value="<?= $val ?>" min="0">
                        <?php break;
                        case 'password': ?>
                          <input type="password" name="<?= $name ?>" class="form-control" value="<?= $val ?>" autocomplete="new-password">
                        <?php break;
                        default: ?>
                          <input type="text" name="<?= $name ?>" class="form-control" value="<?= $val ?>">
                      <?php endswitch; ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>

                <div class="save-bar">
                  <span class="save-hint">
                    <i class="fas fa-info-circle" style="color:var(--gold);margin-right:4px"></i>
                    Les modifications s'appliquent immédiatement sur le site
                  </span>
                  <button type="submit" class="btn-primary" style="padding:10px 28px">
                    <i class="fas fa-save"></i> Sauvegarder
                  </button>
                </div>
              </div>

              <?php if ($groupeKey === 'apparence'): ?>
              <!-- Aperçu couleurs -->
              <div class="params-card">
                <div class="params-card-header">
                  <i class="fas fa-eye" style="color:#C084FC"></i>
                  <h3>Aperçu du thème</h3>
                </div>
                <div class="params-card-body">
                  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
                    <?php
                    $couleurs = [
                      'Principale (Or)' => $grouped['apparence'][0]['valeur'] ?? '#D4AF37',
                      'Fond sombre'     => '#1A1A2E',
                      'Carte'           => '#1E1E2E',
                      'Bordure'         => '#2A2A3E',
                    ];
                    foreach ($couleurs as $lbl => $c):
                    ?>
                    <div style="text-align:center">
                      <div style="width:100%;height:50px;border-radius:8px;background:<?= $c ?>;margin-bottom:6px;border:1px solid rgba(255,255,255,.1)"></div>
                      <div style="font-size:.7rem;color:var(--text-muted)"><?= $lbl ?></div>
                      <div style="font-size:.68rem;color:#555"><?= $c ?></div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>

              <!-- Mode maintenance -->
              <?php
              $maintenance = '0';
              foreach (($grouped['apparence'] ?? []) as $p) {
                if ($p['cle'] === 'maintenance_mode') { $maintenance = $p['valeur']; break; }
              }
              if ($maintenance == '1'): ?>
              <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:var(--radius);padding:16px 20px;display:flex;align-items:center;gap:12px">
                <i class="fas fa-exclamation-triangle" style="color:#EF5350;font-size:1.2rem"></i>
                <div>
                  <div style="color:#EF5350;font-weight:700;font-size:.88rem">⚠️ Mode maintenance activé</div>
                  <div style="color:var(--text-muted);font-size:.78rem;margin-top:2px">Le site public affiche une page de maintenance. Désactivez-le quand vos modifications sont terminées.</div>
                </div>
              </div>
              <?php endif; ?>
              <?php endif; ?>

              <?php if ($groupeKey === 'contact'): ?>
              <!-- Test de contact rapide -->
              <div class="params-card">
                <div class="params-card-header">
                  <i class="fas fa-share-alt" style="color:#60A5FA"></i>
                  <h3>Liens rapides</h3>
                </div>
                <div class="params-card-body" style="display:flex;gap:10px;flex-wrap:wrap">
                  <?php
                  $tel = ''; $wa = ''; $email = '';
                  foreach (($grouped['contact'] ?? []) as $p) {
                    if ($p['cle'] === 'contact_telephone') $tel   = $p['valeur'];
                    if ($p['cle'] === 'contact_whatsapp')  $wa    = $p['valeur'];
                    if ($p['cle'] === 'contact_email')     $email = $p['valeur'];
                  }
                  ?>
                  <?php if ($tel): ?>
                  <a href="tel:<?= $tel ?>" class="btn-secondary" style="text-decoration:none;padding:8px 16px;font-size:.82rem">
                    <i class="fas fa-phone" style="color:var(--gold)"></i> Appeler
                  </a>
                  <?php endif; ?>
                  <?php if ($wa): ?>
                  <a href="https://wa.me/<?= $wa ?>" target="_blank" class="btn-secondary" style="text-decoration:none;padding:8px 16px;font-size:.82rem">
                    <i class="fab fa-whatsapp" style="color:#25D366"></i> WhatsApp
                  </a>
                  <?php endif; ?>
                  <?php if ($email): ?>
                  <a href="mailto:<?= $email ?>" class="btn-secondary" style="text-decoration:none;padding:8px 16px;font-size:.82rem">
                    <i class="fas fa-envelope" style="color:#60A5FA"></i> Email
                  </a>
                  <?php endif; ?>
                </div>
              </div>
              <?php endif; ?>

            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </form>

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

function showTab(key) {
  document.querySelectorAll('.params-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.params-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('tab-' + key).classList.add('active');
  document.querySelector(`.params-tab[onclick*="${key}"]`).classList.add('active');
}

// Mise à jour libellé toggle
document.querySelectorAll('.sw input').forEach(chk => {
  chk.addEventListener('change', function() {
    const lbl = this.closest('.bool-wrap')?.querySelector('.bool-label');
    if (lbl) lbl.textContent = this.checked ? 'Activé' : 'Désactivé';
  });
});
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
