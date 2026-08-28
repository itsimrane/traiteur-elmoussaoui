<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$packages = $pdo->query("SELECT * FROM packages WHERE actif = 1 ORDER BY ordre ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Packages — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { overflow-x: hidden; }
    .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:999; }
    .sidebar-overlay.show { display:block; }
    @media(max-width:768px) {
      .sidebar { position:fixed; left:0; top:0; bottom:0; z-index:1000; transform:translateX(-100%); transition:var(--transition); }
      .sidebar.open { transform:translateX(0); }
    }

    .pkg-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:24px; }
    @media(max-width:960px) { .pkg-grid { grid-template-columns:1fr; } }

    .pkg-card { background:var(--dark-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; transition:var(--transition); }
    .pkg-card:hover { border-color:rgba(212,175,55,.3); }
    .pkg-card.featured { border-color:var(--gold); }

    .pkg-card-top { padding:20px 22px; position:relative; }
    .pkg-dot-row { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
    .pkg-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
    .pkg-card-name { font-size:1.25rem; color:var(--white); font-weight:700; font-family:var(--ff-display); }
    .pkg-card-name-ar { font-size:.85rem; color:var(--gold); opacity:.7; margin-top:2px; }
    .pkg-recommended { position:absolute; top:14px; right:14px; background:var(--gold); color:var(--dark); font-size:.65rem; font-weight:700; padding:4px 10px; border-radius:12px; }

    /* Badge devis — affiché à la place du prix côté aperçu */
    .pkg-devis-badge {
      display:inline-flex; align-items:center; gap:6px;
      background:rgba(212,175,55,.08); border:1px solid rgba(212,175,55,.25);
      color:var(--gold); padding:6px 14px; border-radius:20px;
      font-size:.78rem; font-weight:600; margin-top:10px;
    }
    .pkg-live-guests { font-size:.78rem; color:var(--text-muted); margin-top:6px; }

    /* Note interne prix */
    .prix-interne-note {
      background:rgba(212,175,55,.06); border:1px dashed rgba(212,175,55,.25);
      border-radius:8px; padding:10px 14px; margin-bottom:14px;
      font-size:.75rem; color:var(--text-muted); display:flex; align-items:center; gap:8px;
    }
    .prix-interne-note i { color:var(--gold); flex-shrink:0; }
    .prix-interne-note strong { color:var(--white); }

    .pkg-card-body { padding:20px 22px; border-top:1px solid var(--border); }
    .section-hd { font-size:.7rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:.6px; margin-bottom:10px; display:flex; align-items:center; gap:6px; }
    .section-hd i { color:var(--gold); }
    .form-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px; }
    .price-wrap { position:relative; }
    .price-wrap input { padding-right:56px !important; }
    .price-wrap .cur { position:absolute; right:12px; top:50%; transform:translateY(-50%); font-size:.75rem; color:#555; font-weight:700; }

    .items-list { list-style:none; display:flex; flex-direction:column; gap:6px; margin-bottom:10px; }
    .item-row { display:flex; align-items:center; gap:8px; background:var(--dark-3); border:1px solid var(--border); border-radius:8px; padding:8px 12px; cursor:grab; transition:var(--transition); }
    .item-row:active { cursor:grabbing; opacity:.6; }
    .item-row.over { border-color:var(--gold); background:rgba(212,175,55,.06); }
    .item-row .grip { color:#333; font-size:.85rem; flex-shrink:0; }
    .item-row input { flex:1; background:none; border:none; color:var(--white); font-size:.84rem; outline:none; }
    .item-row .del { background:none; border:none; color:#555; cursor:pointer; font-size:.8rem; padding:2px 6px; border-radius:6px; transition:var(--transition); }
    .item-row .del:hover { color:#EF5350; }
    .btn-add-row { display:flex; align-items:center; justify-content:center; gap:6px; background:rgba(212,175,55,.07); border:1px dashed rgba(212,175,55,.3); border-radius:8px; padding:8px; color:var(--gold); cursor:pointer; font-size:.8rem; width:100%; transition:var(--transition); }
    .btn-add-row:hover { background:rgba(212,175,55,.14); }

    .toggle-row { display:flex; align-items:center; gap:10px; margin-bottom:16px; }
    .sw { position:relative; width:42px; height:22px; flex-shrink:0; }
    .sw input { opacity:0; width:0; height:0; position:absolute; }
    .sw-slider { position:absolute; inset:0; background:var(--dark-3); border-radius:22px; cursor:pointer; transition:.2s; }
    .sw-slider::before { content:''; position:absolute; height:16px; width:16px; left:3px; bottom:3px; background:#666; border-radius:50%; transition:.2s; }
    .sw input:checked + .sw-slider { background:rgba(212,175,55,.25); border:1px solid var(--gold); }
    .sw input:checked + .sw-slider::before { transform:translateX(20px); background:var(--gold); }
    .sw-label { font-size:.82rem; color:var(--text-muted); }

    .save-row { display:flex; align-items:center; justify-content:space-between; padding-top:14px; border-top:1px solid var(--border); }
    .save-msg { font-size:.75rem; }
    .save-msg.ok  { color:#66BB6A; }
    .save-msg.err { color:#EF5350; }

    .info-banner {
      background:rgba(59,130,246,.08); border:1px solid rgba(59,130,246,.2);
      border-radius:10px; padding:14px 18px; margin-bottom:24px;
      display:flex; align-items:flex-start; gap:12px; font-size:.83rem; color:var(--text-muted);
    }
    .info-banner i { color:#60A5FA; font-size:1.1rem; flex-shrink:0; margin-top:1px; }
    .info-banner strong { color:var(--white); }
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">

<?php $activePage = 'packages'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title">
          <h2 data-fr="Gestion Packages" data-ar="إدارة الباقات">Gestion Packages</h2>
          <p data-fr="Modifiez le contenu, les inclusions et les paramètres de chaque formule" data-ar="عدّل المحتوى والمميزات وإعدادات كل باقة">Modifiez le contenu, les inclusions et les paramètres de chaque formule</p>
        </div>
      </div>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()" title="Actualiser"><i class="fas fa-sync-alt"></i></button>
        <a href="../pages/packages.php" target="_blank" class="topbar-btn" title="Voir les packages publics"><i class="fas fa-external-link-alt"></i></a>
        <div class="admin-avatar"><?= strtoupper(substr($_SESSION['admin_nom']??'A',0,1)) ?></div>
      </div>
    </div>

    <div class="admin-content">

      <div id="alertSuccess" style="display:none;margin-bottom:16px" class="alert alert-success">
        <i class="fas fa-check-circle"></i> <span id="alertSuccessMsg"></span>
      </div>
      <div id="alertError" style="display:none;margin-bottom:16px" class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <span id="alertErrorMsg"></span>
      </div>

      <!-- Bannière info -->
      <div class="info-banner">
        <i class="fas fa-eye-slash"></i>
        <div>
          <strong data-fr="Prix masqués côté public" data-ar="الأسعار مخفية عن العملاء">Prix masqués côté public</strong><br>
          <span data-fr="Les prix des packages ne sont pas affichés aux clients — ils voient uniquement le badge" data-ar="لا تُعرض أسعار الباقات للعملاء — يرون فقط شارة">Les prix des packages ne sont pas affichés aux clients — ils voient uniquement le badge</span>
          <em>"Sur devis personnalisé"</em><span data-fr=". Vous pouvez quand même gérer les prix ici pour usage interne (devis, factures)." data-ar=". يمكنك إدارة الأسعار هنا للاستخدام الداخلي (عروض الأسعار، الفواتير).">
              . Vous pouvez quand même gérer les prix ici pour usage interne (devis, factures).</span>
        </div>
      </div>

      <?php if (empty($packages)): ?>
      <div style="text-align:center;padding:80px 20px;color:var(--text-muted)">
        <i class="fas fa-box-open" style="font-size:3rem;opacity:.15;display:block;margin-bottom:16px"></i>
        <h3 style="color:var(--white);margin-bottom:8px">Aucun package trouvé</h3>
        <p style="font-size:.85rem">Exécutez le fichier <code>update_packages.sql</code> dans phpMyAdmin pour créer les packages.</p>
      </div>
      <?php else: ?>

      <div class="pkg-grid">
        <?php foreach ($packages as $pkg):
          $contenu = json_decode($pkg['contenu'] ?? '[]', true) ?: [];
          $color   = $pkg['couleur_badge'] ?? '#D4AF37';
          $prixF   = number_format((float)$pkg['prix'], 0, ',', ' ');
        ?>
        <div class="pkg-card <?= $pkg['mis_en_avant'] ? 'featured' : '' ?>" id="card-<?= $pkg['id'] ?>">

          <!-- Aperçu en-tête -->
          <div class="pkg-card-top" style="background:linear-gradient(135deg,<?= $color ?>14,transparent)">
            <?php if ($pkg['mis_en_avant']): ?>
            <div class="pkg-recommended"><i class="fas fa-star"></i> RECOMMANDÉ</div>
            <?php endif; ?>
            <div class="pkg-dot-row">
              <div class="pkg-dot" style="background:<?= $color ?>"></div>
              <div>
                <div class="pkg-card-name"><?= htmlspecialchars($pkg['nom']) ?></div>
                <div class="pkg-card-name-ar"><?= htmlspecialchars($pkg['nom_ar'] ?? '') ?></div>
              </div>
            </div>
            <!-- Aperçu public : badge devis à la place du prix -->
            <div class="pkg-devis-badge">
              <i class="fas fa-file-invoice"></i> <span data-fr="Sur devis personnalisé" data-ar="بعرض أسعار مخصص">Sur devis personnalisé</span>
            </div>
            <div class="pkg-live-guests" id="lg-<?= $pkg['id'] ?>">
              <?= $pkg['min_personnes'] ?>–<?= $pkg['max_personnes'] ?> invités · <?= $pkg['duree_heures'] ?>h
            </div>
          </div>

          <!-- Formulaire édition -->
          <div class="pkg-card-body">
            <form class="pkg-form" data-id="<?= $pkg['id'] ?>">

              <!-- Note prix interne -->
              <div class="prix-interne-note">
                <i class="fas fa-lock"></i>
                <span data-fr="Prix interne (non affiché au public) :" data-ar="السعر الداخلي (غير معروض للعملاء) :">Prix interne (non affiché au public) :</span><span>
                  <strong id="lp-<?= $pkg['id'] ?>"><?= $prixF ?> MAD</strong>
                  </span><span data-fr="— utilisé pour vos devis et factures uniquement." data-ar="— للاستخدام الداخلي فقط.">— utilisé pour vos devis et factures uniquement.</span>
                </span>
              </div>

              <!-- Prix & Capacité -->
              <div style="margin-bottom:18px">
                <div class="section-hd"><i class="fas fa-tag"></i> <span data-fr="Prix interne & Capacité" data-ar="السعر الداخلي والطاقة">Prix interne & Capacité</span></div>
                <div class="form-row-2">
                  <div class="form-group">
                    <label class="form-label" data-fr="Prix interne (MAD)" data-ar="السعر الداخلي (MAD)">Prix interne (MAD)</label>
                    <div class="price-wrap">
                      <input type="number" name="prix" class="form-control"
                             value="<?= (int)$pkg['prix'] ?>" min="100" step="100" required
                             oninput="livePreview(<?= $pkg['id'] ?>)">
                      <span class="cur">MAD</span>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label" data-fr="Durée (heures)" data-ar="المدة (ساعات)">Durée (heures)</label>
                    <input type="number" name="duree_heures" class="form-control"
                           value="<?= $pkg['duree_heures'] ?>" min="1" max="24" step="0.5"
                           oninput="livePreview(<?= $pkg['id'] ?>)">
                  </div>
                  <div class="form-group">
                    <label class="form-label" data-fr="Invités min" data-ar="أدنى عدد ضيوف">Invités min</label>
                    <input type="number" name="min_personnes" class="form-control"
                           value="<?= $pkg['min_personnes'] ?>" min="1"
                           oninput="livePreview(<?= $pkg['id'] ?>)">
                  </div>
                  <div class="form-group">
                    <label class="form-label" data-fr="Invités max" data-ar="أقصى عدد ضيوف">Invités max</label>
                    <input type="number" name="max_personnes" class="form-control"
                           value="<?= $pkg['max_personnes'] ?>" min="1"
                           oninput="livePreview(<?= $pkg['id'] ?>)">
                  </div>
                </div>
              </div>

              <!-- Description -->
              <div style="margin-bottom:18px">
                <div class="section-hd"><i class="fas fa-align-left"></i> <span data-fr="Description" data-ar="الوصف">Description</span></div>
                <div class="form-group">
                  <textarea name="description" class="form-control" rows="2"
                            placeholder="Description courte affichée sous le nom du package..." data-fr-placeholder="Description courte affichée sous le nom du package..." data-ar-placeholder="وصف قصير يظهر تحت اسم الباقة..."><?= htmlspecialchars($pkg['description'] ?? '') ?></textarea>
                </div>
              </div>

              <!-- Inclusions -->
              <div style="margin-bottom:18px">
                <div class="section-hd">
                  <i class="fas fa-list-check"></i> <span data-fr="Inclusions" data-ar="المحتويات">Inclusions</span>
                  <span style="color:#555;font-size:.65rem;margin-left:4px" data-fr="(glisser pour réordonner)" data-ar="(اسحب لإعادة الترتيب)">(glisser pour réordonner)</span>
                </div>
                <ul class="items-list" id="il-<?= $pkg['id'] ?>">
                  <?php foreach ($contenu as $item): ?>
                  <li class="item-row" draggable="true">
                    <span class="grip"><i class="fas fa-grip-vertical"></i></span>
                    <input type="text" value="<?= htmlspecialchars($item) ?>" placeholder="Inclusion..." data-fr-placeholder="Inclusion..." data-ar-placeholder="مميزة...">
                    <button type="button" class="del" onclick="delRow(this)"><i class="fas fa-times"></i></button>
                  </li>
                  <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-add-row" onclick="addRow(<?= $pkg['id'] ?>)">
                  <i class="fas fa-plus"></i> <span data-fr="Ajouter une inclusion" data-ar="إضافة ميزة">Ajouter une inclusion</span>
                </button>
              </div>

              <!-- Badge Recommandé -->
              <div class="toggle-row">
                <label class="sw">
                  <input type="checkbox" name="mis_en_avant" value="1" <?= $pkg['mis_en_avant'] ? 'checked' : '' ?>>
                  <span class="sw-slider"></span>
                </label>
                <span class="sw-label"><i class="fas fa-star" style="color:var(--gold);margin-right:4px"></i><span data-fr='Badge "Recommandé" visible côté public' data-ar='شارة "موصى به" مرئية للعموم'>Badge "Recommandé" visible côté public</span></span>
              </div>

              <div class="save-row">
                <span class="save-msg" id="sm-<?= $pkg['id'] ?>"></span>
                <button type="submit" class="btn-primary btn-sm">
                  <i class="fas fa-save"></i> <span data-fr="Enregistrer" data-ar="حفظ">Enregistrer</span>
                </button>
              </div>

            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php endif; ?>
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

// Aperçu temps réel (prix interne + invités/durée)
function livePreview(id) {
  const form  = document.querySelector('.pkg-form[data-id="' + id + '"]');
  const prix  = parseInt(form.querySelector('[name=prix]').value) || 0;
  const duree = parseFloat(form.querySelector('[name=duree_heures]').value) || 0;
  const minP  = parseInt(form.querySelector('[name=min_personnes]').value) || 0;
  const maxP  = parseInt(form.querySelector('[name=max_personnes]').value) || 0;
  document.getElementById('lp-' + id).textContent = prix.toLocaleString('fr-FR') + ' MAD';
  document.getElementById('lg-' + id).textContent = minP + '\u2013' + maxP + ' invit\u00e9s \u00b7 ' + duree + 'h';
}

// Drag & drop inclusions
function initDrag(list) {
  let dragging = null;
  list.querySelectorAll('.item-row').forEach(row => {
    row.addEventListener('dragstart', () => {
      dragging = row;
      setTimeout(() => { row.style.opacity = '.4'; }, 0);
    });
    row.addEventListener('dragend', () => {
      row.style.opacity = '1';
      dragging = null;
      list.querySelectorAll('.item-row').forEach(r => r.classList.remove('over'));
    });
    row.addEventListener('dragover', e => { e.preventDefault(); row.classList.add('over'); });
    row.addEventListener('dragleave', () => row.classList.remove('over'));
    row.addEventListener('drop', e => {
      e.preventDefault();
      row.classList.remove('over');
      if (dragging && dragging !== row) {
        const rect = row.getBoundingClientRect();
        if (e.clientY < rect.top + rect.height / 2) {
          list.insertBefore(dragging, row);
        } else {
          list.insertBefore(dragging, row.nextSibling);
        }
      }
    });
  });
}
document.querySelectorAll('.items-list').forEach(initDrag);

function addRow(id) {
  const list = document.getElementById('il-' + id);
  const li   = document.createElement('li');
  li.className = 'item-row';
  li.draggable = true;
  li.innerHTML = '<span class="grip"><i class="fas fa-grip-vertical"></i></span>'
    + '<input type="text" placeholder="Nouvelle inclusion..." data-fr-placeholder="Nouvelle inclusion..." data-ar-placeholder="ميزة جديدة...">'
    + '<button type="button" class="del" onclick="delRow(this)"><i class="fas fa-times"></i></button>';
  list.appendChild(li);
  li.querySelector('input').focus();
  initDrag(list);
}

function delRow(btn) { btn.closest('.item-row').remove(); }

document.querySelectorAll('.pkg-form').forEach(form => {
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const id  = this.dataset.id;
    const sm  = document.getElementById('sm-' + id);
    const btn = this.querySelector('[type=submit]');

    const items = Array.from(document.querySelectorAll('#il-' + id + ' input'))
      .map(i => i.value.trim()).filter(Boolean);

    const fd = new FormData(this);
    fd.set('id', id);
    fd.set('contenu', JSON.stringify(items));

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    sm.className  = 'save-msg';
    sm.textContent = '';

    try {
      const res  = await fetch('../api/update_package.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        sm.className = 'save-msg ok';
        sm.innerHTML = '<i class="fas fa-check"></i> Enregistr\u00e9';
        showAlert('success', localStorage.getItem('admin_lang')==='ar' ? 'تم تحديث الباقة — السعر الداخلي: ' : 'Package mis à jour — Prix interne : ' + data.data.prix);
      } else {
        sm.className = 'save-msg err';
        sm.textContent = data.message;
        showAlert('error', data.message);
      }
    } catch(err) {
      sm.className = 'save-msg err';
      sm.textContent = 'Erreur r\u00e9seau';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> <span data-fr="Enregistrer" data-ar="حفظ">Enregistrer</span>';
  });
});

function showAlert(type, msg) {
  const el  = document.getElementById(type === 'success' ? 'alertSuccess' : 'alertError');
  const txt = document.getElementById(type === 'success' ? 'alertSuccessMsg' : 'alertErrorMsg');
  txt.textContent = msg;
  el.style.display = 'flex';
  window.scrollTo({ top: 0, behavior: 'smooth' });
  setTimeout(() => { el.style.display = 'none'; }, 5000);
}
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
