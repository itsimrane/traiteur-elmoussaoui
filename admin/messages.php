<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_lu') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE contacts SET statut='lu', lu_le=NOW() WHERE id=? AND statut='nouveau'")
            ->execute([$id]);
        header('Location: messages.php'); exit;
    }
    if ($action === 'mark_traite') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE contacts SET statut='traite', repondu_le=NOW() WHERE id=?")
            ->execute([$id]);
        header('Location: messages.php?msg=Message+marqué+traité&type=success'); exit;
    }
    if ($action === 'archive') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE contacts SET statut='archive' WHERE id=?")->execute([$id]);
        header('Location: messages.php'); exit;
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM contacts WHERE id=?")->execute([$id]);
        header('Location: messages.php?msg=Message+supprimé&type=success'); exit;
    }
    if ($action === 'mark_all_lu') {
        $pdo->exec("UPDATE contacts SET statut='lu', lu_le=NOW() WHERE statut='nouveau'");
        header('Location: messages.php'); exit;
    }
}

// Marquer comme lu si ouverture détail
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE contacts SET statut='lu', lu_le=NOW() WHERE id=? AND statut='nouveau'")
        ->execute([$id]);
}

// Récupérer les messages
$messages = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();

$total    = count($messages);
$nouveaux = count(array_filter($messages, fn($m) => $m['statut'] === 'nouveau'));
$lus      = count(array_filter($messages, fn($m) => $m['statut'] === 'lu'));
$traites  = count(array_filter($messages, fn($m) => $m['statut'] === 'traite'));

$statutConfig = [
    'nouveau'  => ['label'=>'Nouveau',  'color'=>'#EF5350', 'bg'=>'rgba(239,68,68,.12)',   'icon'=>'fa-envelope'],
    'lu'       => ['label'=>'Lu',       'color'=>'#FBB724', 'bg'=>'rgba(251,183,36,.12)',  'icon'=>'fa-envelope-open'],
    'traite'   => ['label'=>'Traité',   'color'=>'#25D366', 'bg'=>'rgba(37,211,102,.12)',  'icon'=>'fa-check-circle'],
    'archive'  => ['label'=>'Archivé',  'color'=>'#888',    'bg'=>'rgba(136,136,136,.1)',  'icon'=>'fa-archive'],
];

$msg     = $_GET['msg']  ?? '';
$msgType = $_GET['type'] ?? 'success';

// Message sélectionné
$selectedId  = isset($_GET['id']) ? (int)$_GET['id'] : null;
$selectedMsg = null;
if ($selectedId) {
    foreach ($messages as $m) {
        if ($m['id'] == $selectedId) { $selectedMsg = $m; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}

    /* Layout messagerie */
    .inbox-layout{display:grid;grid-template-columns:360px 1fr;gap:0;background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;min-height:600px}
    @media(max-width:900px){.inbox-layout{grid-template-columns:1fr}}

    /* Liste messages */
    .inbox-list{border-right:1px solid var(--border);overflow-y:auto;max-height:calc(100vh - 200px)}
    .inbox-toolbar{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:8px;position:sticky;top:0;background:var(--dark-card);z-index:1}
    .inbox-search{background:var(--dark-3);border:1px solid var(--border);border-radius:8px;padding:7px 12px;color:var(--white);font-size:.78rem;outline:none;flex:1}
    .inbox-search:focus{border-color:var(--gold)}
    .msg-item{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.04);cursor:pointer;transition:var(--transition);position:relative}
    .msg-item:hover{background:rgba(212,175,55,.04)}
    .msg-item.active{background:rgba(212,175,55,.08);border-right:2px solid var(--gold)}
    .msg-item.nouveau::before{content:'';position:absolute;left:6px;top:50%;transform:translateY(-50%);width:6px;height:6px;border-radius:50%;background:#EF5350}
    .msg-item-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:4px}
    .msg-sender{font-size:.84rem;font-weight:600;color:var(--white)}
    .msg-sender.nouveau{color:#EF5350}
    .msg-date{font-size:.68rem;color:#555}
    .msg-subject{font-size:.75rem;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:3px}
    .msg-preview{font-size:.72rem;color:#555;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .msg-statut-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .empty-inbox{text-align:center;padding:60px 20px;color:#555}
    .empty-inbox i{font-size:2.5rem;opacity:.2;display:block;margin-bottom:12px}

    /* Détail message */
    .inbox-detail{display:flex;flex-direction:column}
    .detail-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;color:#555;padding:40px}
    .detail-empty i{font-size:3rem;opacity:.15;margin-bottom:16px}
    .detail-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
    .detail-sender-info h3{font-size:1rem;color:var(--white);font-weight:700;margin-bottom:4px}
    .detail-sender-meta{display:flex;gap:12px;flex-wrap:wrap;font-size:.75rem;color:var(--text-muted)}
    .detail-sender-meta a{color:var(--gold);text-decoration:none}
    .detail-sender-meta a:hover{text-decoration:underline}
    .detail-actions{display:flex;gap:8px;flex-shrink:0}
    .dact-btn{padding:7px 14px;border-radius:8px;border:1px solid var(--border);background:none;cursor:pointer;font-size:.75rem;color:var(--text-muted);font-family:var(--ff-body);transition:var(--transition);text-decoration:none;display:inline-flex;align-items:center;gap:5px}
    .dact-btn:hover{border-color:var(--gold);color:var(--gold)}
    .dact-btn.success{border-color:rgba(37,211,102,.3);color:#25D366}
    .dact-btn.success:hover{background:rgba(37,211,102,.1)}
    .dact-btn.danger{border-color:rgba(239,68,68,.3);color:#EF5350}
    .dact-btn.danger:hover{background:rgba(239,68,68,.1)}
    .detail-subject{padding:14px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .detail-subject h4{font-size:.9rem;color:var(--white);font-weight:600}
    .statut-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:.72rem;font-weight:700}
    .detail-body{padding:24px;flex:1;overflow-y:auto}
    .msg-bubble{background:var(--dark-3);border-radius:12px;padding:18px 20px;font-size:.88rem;color:var(--text-muted);line-height:1.8;border-left:3px solid var(--border)}
    .detail-reply{padding:16px 24px;border-top:1px solid var(--border);background:var(--dark-card)}
    .reply-area{width:100%;background:var(--dark-3);border:1px solid var(--border);border-radius:8px;padding:12px;color:var(--white);font-size:.85rem;resize:vertical;outline:none;font-family:var(--ff-body);min-height:80px}
    .reply-area:focus{border-color:var(--gold)}
    .reply-footer{display:flex;justify-content:space-between;align-items:center;margin-top:10px}
    .reply-hint{font-size:.72rem;color:#555}
    .filter-tabs{display:flex;gap:4px;padding:0 16px 12px}
    .ftab{padding:5px 12px;border-radius:20px;border:1px solid var(--border);background:none;color:#888;cursor:pointer;font-size:.72rem;transition:var(--transition);font-family:var(--ff-body)}
    .ftab.active,.ftab:hover{border-color:var(--gold);color:var(--gold)}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">

  <?php $activePage = 'messages'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title">
          <h2 data-fr="Boîte de réception" data-ar="صندوق الوارد">Boîte de réception</h2>
          <p><?= $nouveaux > 0 ? "<span style='color:#EF5350;font-weight:700'>$nouveaux nouveau(x) message(s)</span>" : 'Tous les messages lus' ?></p>
        </div>
      </div>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
        <?php if ($nouveaux > 0): ?>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="mark_all_lu">
          <button type="submit" class="btn-secondary" style="padding:8px 14px;font-size:.78rem">
            <i class="fas fa-check-double"></i> Tout marquer lu
          </button>
        </form>
        <?php endif; ?>
        <div class="admin-avatar">A</div>
      </div>
    </div>

    <div class="admin-content">

      <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>" style="margin-bottom:16px">
        <i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($msg) ?>
      </div>
      <?php endif; ?>

      <!-- Stats rapides -->
      <div class="stats-grid" style="margin-bottom:20px">
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon gold"><i class="fas fa-envelope"></i></div></div>
          <div class="stat-card-value"><?= $total ?></div>
          <div class="stat-card-label" data-fr="Total messages" data-ar="إجمالي الرسائل">Total messages</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(239,68,68,.1);color:#EF5350"><i class="fas fa-circle"></i></div></div>
          <div class="stat-card-value"><?= $nouveaux ?></div>
          <div class="stat-card-label" data-fr="Non lus" data-ar="غير مقروءة">Non lus</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(251,183,36,.1);color:#FBB724"><i class="fas fa-envelope-open"></i></div></div>
          <div class="stat-card-value"><?= $lus ?></div>
          <div class="stat-card-label" data-fr="Lus" data-ar="مقروءة" data-fr="Lus" data-ar="مقروءة">Lus</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-check-circle"></i></div></div>
          <div class="stat-card-value"><?= $traites ?></div>
          <div class="stat-card-label" data-fr="Traités" data-ar="معالجة" data-fr="Traités" data-ar="معالجة">Traités</div>
        </div>
      </div>

      <!-- Interface messagerie -->
      <div class="inbox-layout">

        <!-- Liste gauche -->
        <div class="inbox-list">
          <div class="inbox-toolbar">
            <input type="text" class="inbox-search" id="inboxSearch" placeholder="🔍 Rechercher..." data-fr-placeholder="🔍 Rechercher..." data-ar-placeholder="🔍 البحث..." oninput="filterInbox()">
          </div>
          <div class="filter-tabs">
            <button class="ftab active" onclick="setTab('all',this)" data-fr="Tous" data-ar="الكل" data-fr="Tous" data-ar="الكل">Tous</button>
            <button class="ftab" onclick="setTab('nouveau',this)" data-fr="Nouveaux" data-ar="جديدة">Nouveaux</button>
            <button class="ftab" onclick="setTab('lu',this)" data-fr="Lus" data-ar="مقروءة">Lus</button>
            <button class="ftab" onclick="setTab('traite',this)" data-fr="Traités" data-ar="معالجة">Traités</button>
          </div>

          <?php if (empty($messages)): ?>
          <div class="empty-inbox">
            <i class="fas fa-inbox"></i>
            <p><span data-fr="Aucun message" data-ar="لا توجد رسائل">Aucun message</span></p>
          </div>
          <?php else: ?>
          <?php foreach ($messages as $m):
            $sc   = $statutConfig[$m['statut']] ?? $statutConfig['lu'];
            $nom  = trim(($m['prenom'] ?? '') . ' ' . $m['nom']);
            $date = date('d/m H:i', strtotime($m['created_at']));
            $isActive = $selectedId == $m['id'];
          ?>
          <div class="msg-item <?= $m['statut'] === 'nouveau' ? 'nouveau' : '' ?> <?= $isActive ? 'active' : '' ?>"
               data-statut="<?= $m['statut'] ?>"
               data-search="<?= strtolower($nom . ' ' . $m['sujet'] . ' ' . $m['message']) ?>"
               onclick="window.location='messages.php?id=<?= $m['id'] ?>'">
            <div class="msg-item-header">
              <span class="msg-sender <?= $m['statut'] === 'nouveau' ? 'nouveau' : '' ?>">
                <?= htmlspecialchars($nom) ?>
              </span>
              <span class="msg-date"><?= $date ?></span>
            </div>
            <div class="msg-subject"><?= htmlspecialchars($m['sujet'] ?? 'Sans sujet') ?></div>
            <div class="msg-preview"><?= htmlspecialchars(mb_substr($m['message'], 0, 80)) ?>...</div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Détail droit -->
        <div class="inbox-detail">
          <?php if (!$selectedMsg): ?>
          <div class="detail-empty">
            <i class="fas fa-envelope-open-text"></i>
            <p style="font-size:.9rem;margin-bottom:6px">Sélectionnez un message</p>
            <p style="font-size:.78rem">Cliquez sur un message à gauche pour le lire</p>
          </div>
          <?php else:
            $sc  = $statutConfig[$selectedMsg['statut']] ?? $statutConfig['lu'];
            $nom = trim(($selectedMsg['prenom'] ?? '') . ' ' . $selectedMsg['nom']);
            $date = date('d/m/Y à H:i', strtotime($selectedMsg['created_at']));
          ?>
          <div class="detail-header">
            <div class="detail-sender-info">
              <h3><?= htmlspecialchars($nom) ?></h3>
              <div class="detail-sender-meta">
                <a href="mailto:<?= htmlspecialchars($selectedMsg['email']) ?>">
                  <i class="fas fa-envelope"></i> <?= htmlspecialchars($selectedMsg['email']) ?>
                </a>
                <?php if ($selectedMsg['telephone']): ?>
                <a href="tel:<?= htmlspecialchars($selectedMsg['telephone']) ?>">
                  <i class="fas fa-phone"></i> <?= htmlspecialchars($selectedMsg['telephone']) ?>
                </a>
                <?php endif; ?>
                <span><i class="fas fa-clock"></i> <?= $date ?></span>
              </div>
            </div>
            <div class="detail-actions">
              <?php if ($selectedMsg['statut'] !== 'traite'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="mark_traite">
                <input type="hidden" name="id" value="<?= $selectedMsg['id'] ?>">
                <button type="submit" class="dact-btn success">
                  <i class="fas fa-check"></i> Traité
                </button>
              </form>
              <?php endif; ?>
              <a href="mailto:<?= htmlspecialchars($selectedMsg['email']) ?>?subject=Re: <?= urlencode($selectedMsg['sujet'] ?? '') ?>"
                 class="dact-btn">
                <i class="fas fa-reply"></i> Répondre
              </a>
              <?php if ($selectedMsg['statut'] !== 'archive'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="archive">
                <input type="hidden" name="id" value="<?= $selectedMsg['id'] ?>">
                <button type="submit" class="dact-btn"><i class="fas fa-archive"></i></button>
              </form>
              <?php endif; ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce message ?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $selectedMsg['id'] ?>">
                <button type="submit" class="dact-btn danger"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </div>

          <div class="detail-subject">
            <h4><?= htmlspecialchars($selectedMsg['sujet'] ?? 'Sans sujet') ?></h4>
            <span class="statut-badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>">
              <i class="fas <?= $sc['icon'] ?>"></i> <?= $sc['label'] ?>
            </span>
          </div>

          <div class="detail-body">
            <div class="msg-bubble">
              <?= nl2br(htmlspecialchars($selectedMsg['message'])) ?>
            </div>
            <?php if ($selectedMsg['repondu_le']): ?>
            <div style="margin-top:14px;font-size:.75rem;color:#555;text-align:right">
              <i class="fas fa-reply" style="margin-right:4px"></i>
              Répondu le <?= date('d/m/Y', strtotime($selectedMsg['repondu_le'])) ?>
            </div>
            <?php endif; ?>
          </div>

          <div class="detail-reply">
            <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:8px;font-weight:600;text-transform:uppercase;letter-spacing:.5px">
              <i class="fas fa-reply" style="color:var(--gold);margin-right:4px"></i> Réponse rapide
            </div>
            <textarea class="reply-area" id="replyArea"
                      placeholder="Tapez votre réponse... (s'ouvre dans votre client email)"></textarea>
            <div class="reply-footer">
              <span class="reply-hint">
                <i class="fas fa-info-circle"></i>
                La réponse sera envoyée via votre client email
              </span>
              <a id="replyBtn"
                 href="mailto:<?= htmlspecialchars($selectedMsg['email']) ?>?subject=Re: <?= urlencode($selectedMsg['sujet'] ?? '') ?>"
                 class="btn-primary" style="padding:8px 18px;font-size:.8rem;text-decoration:none"
                 onclick="attachReply(this)">
                <i class="fas fa-paper-plane"></i> Envoyer la réponse
              </a>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>

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

// Filtres
let curTab = 'all';
function setTab(f, btn) {
  curTab = f;
  document.querySelectorAll('.ftab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterInbox();
}
function filterInbox() {
  const q = document.getElementById('inboxSearch').value.toLowerCase();
  document.querySelectorAll('.msg-item').forEach(item => {
    const matchT = curTab === 'all' || item.dataset.statut === curTab;
    const matchQ = !q || item.dataset.search.includes(q);
    item.style.display = (matchT && matchQ) ? '' : 'none';
  });
}

// Attacher la réponse au lien mailto
function attachReply(el) {
  const reply = document.getElementById('replyArea')?.value || '';
  if (reply.trim()) {
    const base = el.href.split('&body=')[0];
    el.href = base + '&body=' + encodeURIComponent(reply);
  }
}
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
