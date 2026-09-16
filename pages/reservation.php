<?php
require_once __DIR__ . '/../includes/config.php';

// Récupérer les services depuis la BDD
$services = $pdo->query("SELECT * FROM services WHERE actif=1 ORDER BY categorie_tarif, ordre")->fetchAll();

// Tentes actives (si la table existe déjà — sinon liste vide, sans casser la page)
$tentesDispo = [];
try { $tentesDispo = $pdo->query("SELECT * FROM tentes WHERE actif=1 ORDER BY ordre ASC, id ASC")->fetchAll(); }
catch (Exception $e) { /* migration pas encore lancée */ }

// Villes autour d'Errachidia
$villes = [
  'Errachidia',
  'Erfoud',
  'Rissani',
  'Goulmima',
  'Rich',
  'Midelt',
  'Tinghir',
  'Boumalne Dadès',
  'Kelaat Mgouna',
  'Ouarzazate',
  'Alnif',
  'Boudnib',
  'Tinejdad',
  'Arfoud',
  'Merzouga',
  'Autre'
];

$typesEvenements = [
  'mariage' => ['label' => 'Mariage', 'label_ar' => 'حفل زفاف', 'icon' => 'fa-heart'],
  'fiancailles' => ['label' => 'Fiançailles', 'label_ar' => 'حفل خطوبة', 'icon' => 'fa-ring'],
  'circoncision' => ['label' => 'Circoncision', 'label_ar' => 'حفل ختان', 'icon' => 'fa-baby'],
  'anniversaire' => ['label' => 'Anniversaire', 'label_ar' => 'عيد ميلاد', 'icon' => 'fa-birthday-cake'],
  'reception-pro' => ['label' => 'Réception Pro', 'label_ar' => 'استقبال مهني', 'icon' => 'fa-briefcase'],
  'buffet-banquet' => ['label' => 'Buffet', 'label_ar' => 'بوفيه', 'icon' => 'fa-utensils'],
  'ceremonie-reli' => ['label' => 'Cérémonie religieuse', 'label_ar' => 'مناسبة دينية', 'icon' => 'fa-star-and-crescent'],
];

// Vraies limites d'invités par type, définies par l'admin (nulle = illimité)
$maxInvitesParType = [];
try {
    $rows = $pdo->query("SELECT slug, max_invites FROM types_evenements WHERE max_invites IS NOT NULL")->fetchAll();
    foreach ($rows as $r) { $maxInvitesParType[$r['slug']] = (int)$r['max_invites']; }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">

<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Demander un devis — Traiteur EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=Jost:wght@300;400;500;600&family=Amiri:wght@400;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    /* ── Stepper ─────────────────────────────────── */
    .stepper {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0;
      margin-bottom: 48px
    }

    .step {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      position: relative;
      flex: 1;
      max-width: 160px
    }

    .step-circle {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      border: 2px solid var(--border);
      background: var(--dark-card);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .9rem;
      color: #555;
      transition: var(--transition);
      position: relative;
      z-index: 1
    }

    .step.active .step-circle {
      border-color: var(--gold);
      background: rgba(212, 175, 55, .15);
      color: var(--gold)
    }

    .step.done .step-circle {
      border-color: var(--gold);
      background: var(--gold);
      color: var(--dark)
    }

    .step-label {
      font-size: .68rem;
      color: #555;
      text-align: center;
      text-transform: uppercase;
      letter-spacing: .5px;
      transition: var(--transition)
    }

    .step.active .step-label,
    .step.done .step-label {
      color: var(--gold)
    }

    .step-line {
      flex: 1;
      height: 2px;
      background: var(--border);
      margin-top: -36px;
      position: relative;
      z-index: 0;
      max-width: 80px;
      transition: var(--transition)
    }

    .step-line.done {
      background: var(--gold)
    }

    /* ── Steps content ────────────────────────────── */
    .step-content {
      display: none
    }

    .step-content.active {
      display: block
    }

    /* ── Types événement ─────────────────────────── */
    .event-types-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      margin-bottom: 24px
    }

    .event-type-card {
      background: var(--dark-card);
      border: 2px solid var(--border);
      border-radius: 14px;
      padding: 24px 16px;
      text-align: center;
      cursor: pointer;
      transition: var(--transition)
    }

    .event-type-card:hover {
      border-color: rgba(212, 175, 55, .4);
      transform: translateY(-2px)
    }

    .event-type-card.selected {
      border-color: var(--gold);
      background: rgba(212, 175, 55, .08)
    }

    .event-type-card i {
      font-size: 1.8rem;
      color: var(--gold);
      display: block;
      margin-bottom: 10px
    }

    .event-type-card .et-label {
      font-size: .85rem;
      font-weight: 600;
      color: var(--white)
    }

    .event-type-card .et-label-ar {
      font-size: .75rem;
      color: var(--text-muted);
      margin-top: 3px;
      font-family: 'Amiri', serif
    }

    /* ── Services grid ────────────────────────────── */
    .cat-tabs {
      display: flex;
      gap: 8px;
      margin-bottom: 20px;
      flex-wrap: wrap
    }

    .cat-tab {
      padding: 7px 18px;
      border-radius: 20px;
      border: 1.5px solid var(--border);
      background: none;
      color: #888;
      cursor: pointer;
      font-size: .78rem;
      font-family: var(--ff-body);
      transition: var(--transition)
    }

    .cat-tab.active,
    .cat-tab:hover {
      border-color: var(--gold);
      color: var(--gold)
    }

    .cat-tab.bronze.active {
      border-color: #CD7F32;
      color: #CD7F32
    }

    .cat-tab.argent.active {
      border-color: #C0C0C0;
      color: #C0C0C0
    }

    .cat-tab.or.active {
      border-color: #D4AF37;
      color: #D4AF37
    }

    .services-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px
    }

    .tentes-select-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    .tente-select-card {
      background: var(--dark-card);
      border: 2px solid var(--border);
      border-radius: 14px;
      overflow: hidden;
      cursor: pointer;
      transition: var(--transition);
    }
    .tente-select-card:hover { border-color: var(--gold-dim, var(--gold)); }
    .tente-select-card.selected { border-color: var(--gold); box-shadow: 0 0 0 1px var(--gold); }
    .tsc-photo {
      width: 100%; height: 140px;
      background: var(--dark-3) center/cover no-repeat;
      display: flex; align-items: center; justify-content: center;
      color: #555; font-size: 2rem;
    }
    .tsc-body { padding: 14px 16px; }
    .tsc-name { color: var(--white); font-weight: 700; font-size: .95rem; margin-bottom: 8px; }
    .tsc-meta { display: flex; justify-content: space-between; font-size: .74rem; color: var(--text-muted); margin-bottom: 8px; }
    .tsc-desc { font-size: .74rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px; }
    .tsc-choose-btn {
      width: 100%; padding: 9px; border-radius: 8px; border: 1px solid var(--gold);
      background: transparent; color: var(--gold); font-size: .8rem; font-weight: 600; cursor: pointer;
      transition: var(--transition);
    }
    .tente-select-card.selected .tsc-choose-btn { background: var(--gold); color: var(--dark-1, #111); }

    .service-chk-card {
      background: var(--dark-card);
      border: 2px solid var(--border);
      border-radius: 12px;
      padding: 16px;
      cursor: pointer;
      transition: var(--transition);
      position: relative;
      display: flex;
      flex-direction: column;
      gap: 8px
    }

    .service-chk-card:hover {
      border-color: rgba(212, 175, 55, .3)
    }

    .service-chk-card.checked {
      border-color: var(--gold);
      background: rgba(212, 175, 55, .06)
    }

    .service-chk-card.hidden {
      display: none
    }

    .service-chk-card input[type=checkbox] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
      margin: 0
    }

    .svc-header {
      display: flex;
      align-items: center;
      gap: 10px
    }

    .svc-icon {
      width: 36px;
      height: 36px;
      border-radius: 9px;
      background: rgba(212, 175, 55, .1);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--gold);
      font-size: .9rem;
      flex-shrink: 0
    }

    .svc-name {
      font-size: .83rem;
      font-weight: 600;
      color: var(--white)
    }

    .svc-desc {
      font-size: .72rem;
      color: var(--text-muted);
      line-height: 1.5
    }

    .svc-price {
      font-family: var(--ff-display);
      font-size: 1rem;
      font-weight: 700;
      color: var(--gold)
    }

    .svc-tier {
      position: absolute;
      top: 10px;
      right: 10px;
      font-size: .6rem;
      padding: 2px 7px;
      border-radius: 10px;
      font-weight: 700;
      text-transform: uppercase
    }

    .svc-tier.bronze {
      background: rgba(205, 127, 50, .15);
      color: #CD7F32
    }

    .svc-tier.argent {
      background: rgba(192, 192, 192, .15);
      color: #C0C0C0
    }

    .svc-tier.or {
      background: rgba(212, 175, 55, .15);
      color: #D4AF37
    }

    .checkmark {
      position: absolute;
      top: 10px;
      left: 10px;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      border: 2px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition)
    }

    .service-chk-card.checked .checkmark {
      background: var(--gold);
      border-color: var(--gold);
      color: var(--dark)
    }

    .no-services {
      text-align: center;
      padding: 40px;
      color: #555;
      grid-column: 1/-1
    }

    .no-services i {
      font-size: 2rem;
      margin-bottom: 10px;
      display: block;
      opacity: .3
    }

    /* ── Total bar ────────────────────────────────── */
    .total-bar {
      position: sticky;
      bottom: 0;
      background: rgba(13, 13, 20, .97);
      backdrop-filter: blur(12px);
      border-top: 1px solid rgba(212, 175, 55, .3);
      padding: 14px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-radius: 0 0 var(--radius) var(--radius);
      margin-top: 20px;
      z-index: 100
    }

    .total-bar .total-label {
      font-size: .78rem;
      color: var(--text-muted)
    }

    .total-bar .total-amount {
      font-family: var(--ff-display);
      font-size: 1.6rem;
      font-weight: 700;
      color: var(--gold)
    }

    .total-bar .total-detail {
      font-size: .72rem;
      color: #555;
      margin-top: 2px
    }

    .selected-services-list {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      max-width: 50%
    }

    .svc-chip {
      background: rgba(212, 175, 55, .1);
      border: 1px solid rgba(212, 175, 55, .2);
      color: var(--gold);
      padding: 3px 10px;
      border-radius: 20px;
      font-size: .7rem
    }

    /* ── Récap devis ──────────────────────────────── */
    .recap-card {
      background: var(--dark-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      margin-bottom: 20px
    }

    .recap-header {
      padding: 16px 20px;
      background: linear-gradient(135deg, rgba(212, 175, 55, .1), transparent);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 10px
    }

    .recap-header i {
      color: var(--gold)
    }

    .recap-header h4 {
      color: var(--white);
      font-size: .9rem;
      font-weight: 700
    }

    .recap-body {
      padding: 20px
    }

    .recap-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid rgba(255, 255, 255, .04);
      font-size: .83rem
    }

    .recap-row:last-child {
      border-bottom: none
    }

    .recap-row .r-label {
      color: var(--text-muted)
    }

    .recap-row .r-value {
      color: var(--white);
      font-weight: 500
    }

    .recap-services-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 4px
    }

    .recap-services-table th {
      font-size: .68rem;
      color: #555;
      text-transform: uppercase;
      padding: 8px 0;
      text-align: left;
      border-bottom: 1px solid var(--border)
    }

    .recap-services-table td {
      padding: 10px 0;
      border-bottom: 1px solid rgba(255, 255, 255, .04);
      font-size: .82rem;
      color: var(--text-muted)
    }

    .recap-services-table td:last-child {
      text-align: right;
      color: var(--gold);
      font-weight: 600;
      font-family: var(--ff-display)
    }

    .recap-total-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 20px;
      background: rgba(212, 175, 55, .06);
      border-top: 2px solid rgba(212, 175, 55, .2)
    }

    .recap-total-row .rt-label {
      font-size: .88rem;
      color: var(--white);
      font-weight: 700
    }

    .recap-total-row .rt-amount {
      font-family: var(--ff-display);
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--gold)
    }

    /* ── Navigation btns ──────────────────────────── */
    .step-nav {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 28px;
      padding-top: 20px;
      border-top: 1px solid var(--border)
    }

    .step-nav .btn-back {
      background: none;
      border: 1px solid var(--border);
      color: var(--text-muted);
      padding: 11px 24px;
      border-radius: 10px;
      cursor: pointer;
      font-family: var(--ff-body);
      font-size: .85rem;
      transition: var(--transition)
    }

    .step-nav .btn-back:hover {
      border-color: var(--gold);
      color: var(--gold)
    }

    .form-container {
      background: var(--dark-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 36px;
      max-width: 900px;
      margin: 0 auto
    }

    .event-details-grid {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 16px;
      margin-top: 24px
    }

    @media(max-width:768px) {
      .event-types-grid {
        grid-template-columns: repeat(2, 1fr)
      }

      .services-grid {
        grid-template-columns: 1fr 1fr
      }

      .total-bar {
        flex-direction: column;
        gap: 10px;
        text-align: center
      }

      .selected-services-list {
        max-width: 100%;
        justify-content: center
      }

      .event-details-grid {
        grid-template-columns: 1fr
      }
    }

    @media(max-width:480px) {
      .services-grid {
        grid-template-columns: 1fr
      }

      .event-types-grid {
        grid-template-columns: 1fr 1fr
      }
    }
    /* ═══════════════ Calendrier de réservation ═══════════════ */
    .pro-calendar {
      background: var(--dark-card); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 18px; margin-top: 4px;
    }
    .cal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .cal-nav-btn {
      width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--border);
      background: var(--dark-3); color: var(--gold); cursor: pointer;
      display: flex; align-items: center; justify-content: center; transition: var(--transition);
    }
    .cal-nav-btn:hover:not(:disabled) { border-color: var(--gold); transform: scale(1.06); }
    .cal-nav-btn:disabled { opacity: .3; cursor: not-allowed; }
    .cal-title { font-family: var(--ff-display); font-size: 1.15rem; color: var(--white); font-weight: 700; }
    .cal-weekdays { display: grid; grid-template-columns: repeat(7,1fr); text-align: center; margin-bottom: 6px; }
    .cal-weekdays span { font-size: .68rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; }
    .cal-days { display: grid; grid-template-columns: repeat(7,1fr); gap: 4px; }
    .cal-day {
      aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
      border-radius: 9px; font-size: .84rem; color: var(--text-light); cursor: pointer;
      transition: var(--transition); position: relative; border: 1px solid transparent;
    }
    .cal-day.empty { visibility: hidden; cursor: default; }
    .cal-day.today { border-color: var(--gold); font-weight: 700; color: var(--gold); }
    .cal-day.disabled { color: #444; cursor: not-allowed; opacity: .4; }
    .cal-day.attente { background: rgba(251,183,36,.14); color: #FBB724; }
    .cal-day.attente::after { content:''; position:absolute; bottom:4px; width:5px; height:5px; border-radius:50%; background:#FBB724; }
    .cal-day.reserve { background: rgba(239,68,68,.14); color: #EF5350; cursor: not-allowed; opacity: .85; }
    .cal-day.reserve::after { content:''; position:absolute; bottom:4px; width:5px; height:5px; border-radius:50%; background:#EF5350; }
    .cal-day:not(.disabled):not(.reserve):not(.empty):hover { background: rgba(212,175,55,.14); color: var(--gold); transform: scale(1.05); }
    .cal-day.selected { background: var(--gold) !important; color: var(--dark) !important; font-weight: 700; opacity: 1 !important; }
    .cal-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border); }
    .cal-today-btn { background: none; border: 1px solid var(--border); color: var(--gold); border-radius: 20px; padding: 6px 16px; font-size: .75rem; cursor: pointer; transition: var(--transition); }
    .cal-today-btn:hover { border-color: var(--gold); }
    .cal-legend { display: flex; gap: 14px; flex-wrap: wrap; font-size: .72rem; color: var(--text-muted); }
    .cal-legend span { display: flex; align-items: center; gap: 5px; }
    .cal-legend .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .dot-dispo { background: #25D366; }
    .dot-attente { background: #FBB724; }
    .dot-reserve { background: #EF5350; }
    .cal-selected-label { margin-top: 10px; font-size: .85rem; color: var(--gold); font-weight: 600; min-height: 20px; }
    @media(max-width:480px){
      .cal-day{font-size:.76rem}
      .cal-legend{gap:8px;font-size:.68rem}
      .cal-title{font-size:1rem}
    }
    @keyframes waPulse {
      0%, 100% { box-shadow: 0 0 0 0 rgba(37,211,102,.5); }
      50% { box-shadow: 0 0 0 14px rgba(37,211,102,0); }
    }
    .wa-pulse { animation: waPulse 1.4s ease-out infinite; }
  </style>
</head>

<body>
  <div id="loader">
    <div class="loader-inner">
      <div class="loader-ring"></div>
      <div class="loader-logo"><span class="loader-em">EL</span><span class="loader-moussaoui">MOUSSAOUI</span></div>
    </div>
  </div>


  <?php $navActive = "";
  include_once __DIR__ . "/../includes/navbar.php"; ?>


  <div class="page-hero" style="padding:80px 0 50px">
    <div class="container">
      <span class="section-tag" data-fr="Gratuit & Sans engagement" data-ar="مجاني وبدون التزام">Gratuit & Sans
        engagement</span>
      <h1 data-fr="Demander un Devis" data-ar="طلب عرض أسعار" style="font-size:2.8rem">Demander un Devis</h1>
      <p data-fr="En quelques étapes simples, configurez votre événement et recevez un devis détaillé instantanément."
        data-ar="في بضع خطوات بسيطة، قم بتكوين حدثك واحصل على عرض أسعار مفصل على الفور.">
        En quelques étapes simples, configurez votre événement et recevez un devis détaillé instantanément.
      </p>
    </div>
  </div>

  <section class="section" style="padding-top:30px">
    <div class="container">

      <!-- Stepper -->
      <div class="stepper" id="stepper">
        <div class="step active" id="step-ind-1">
          <div class="step-circle"><i class="fas fa-calendar-star"></i></div>
          <div class="step-label" data-fr="Événement" data-ar="المناسبة">Événement</div>
        </div>
        <div class="step-line" id="line-1"></div>
        <div class="step" id="step-ind-2">
          <div class="step-circle"><i class="fas fa-campground"></i></div>
          <div class="step-label" data-fr="Tente" data-ar="الخيمة">Tente</div>
        </div>
        <div class="step-line" id="line-2"></div>
        <div class="step" id="step-ind-3">
          <div class="step-circle"><i class="fas fa-concierge-bell"></i></div>
          <div class="step-label" data-fr="Services" data-ar="الخدمات">Services</div>
        </div>
        <div class="step-line" id="line-3"></div>
        <div class="step" id="step-ind-4">
          <div class="step-circle"><i class="fas fa-user"></i></div>
          <div class="step-label" data-fr="Vos infos" data-ar="معلوماتك">Vos infos</div>
        </div>
        <div class="step-line" id="line-4"></div>
        <div class="step" id="step-ind-5">
          <div class="step-circle"><i class="fas fa-file-invoice"></i></div>
          <div class="step-label" data-fr="Votre devis" data-ar="عرض أسعارك">Votre devis</div>
        </div>
      </div>

      <div class="form-container">

        <!-- ═══ ÉTAPE 1 : Type d'événement + Date + Ville ═══ -->
        <div class="step-content active" id="step1">
          <h2 style="color:var(--white);font-size:1.3rem;margin-bottom:8px">
            <i class="fas fa-calendar-star" style="color:var(--gold);margin-right:8px"></i>
            <span data-fr="Votre événement" data-ar="مناسبتك">Votre événement</span>
          </h2>
          <p style="color:var(--text-muted);font-size:.84rem;margin-bottom:28px"
            data-fr="Quel type d'événement souhaitez-vous organiser ?" data-ar="ما نوع المناسبة التي تريد تنظيمها؟">Quel
            type d'événement souhaitez-vous organiser ?</p>

          <div class="event-types-grid">
            <?php foreach ($typesEvenements as $key => $ev): ?>
              <div class="event-type-card" data-type="<?= $key ?>" onclick="selectType('<?= $key ?>', this)">
                <i class="fas <?= $ev['icon'] ?>"></i>
                <div class="et-label"><?= $ev['label'] ?></div>
                <div class="et-label-ar"><?= $ev['label_ar'] ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="event-details-grid">
            <div class="form-group">
              <label class="form-label" data-fr="Date de l'événement *" data-ar="تاريخ المناسبة *">Date de l'événement
                *</label>

              <div class="pro-calendar" id="proCalendar">
                <div class="cal-header">
                  <button type="button" class="cal-nav-btn" id="calPrev"><i class="fas fa-chevron-left"></i></button>
                  <div class="cal-title" id="calTitle">—</div>
                  <button type="button" class="cal-nav-btn" id="calNext"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="cal-weekdays">
                  <span data-fr="L" data-ar="ن">L</span><span data-fr="M" data-ar="ث">M</span><span data-fr="M" data-ar="ر">M</span><span data-fr="J" data-ar="خ">J</span><span data-fr="V" data-ar="ج">V</span><span data-fr="S" data-ar="س">S</span><span data-fr="D" data-ar="ح">D</span>
                </div>
                <div class="cal-days" id="calDays"></div>
                <div class="cal-footer">
                  <button type="button" class="cal-today-btn" id="calToday" data-fr="Aujourd'hui" data-ar="اليوم">Aujourd'hui</button>
                  <div class="cal-legend">
                    <span><i class="dot dot-dispo"></i> <span data-fr="Disponible" data-ar="متاح">Disponible</span></span>
                    <span><i class="dot dot-attente"></i> <span data-fr="En attente" data-ar="قيد الانتظار">En attente</span></span>
                    <span><i class="dot dot-reserve"></i> <span data-fr="Réservé" data-ar="محجوز">Réservé</span></span>
                  </div>
                </div>
              </div>
              <div class="cal-selected-label" id="dateSelectedLabel"></div>

              <input type="hidden" id="date_evenement" required>
              <small style="color:var(--text-muted);font-size:.72rem;display:block;margin-top:5px"
                     data-fr="Réservation possible entre 7 jours et 2 mois à l'avance."
                     data-ar="الحجز ممكن بين 7 أيام وشهرين مقدماً.">
                Réservation possible entre 7 jours et 2 mois à l'avance.
              </small>
            </div>
            <div class="form-group">
              <label class="form-label" data-fr="Ville / Lieu *" data-ar="المدينة / المكان *">Ville / Lieu *</label>
              <select id="ville" class="form-control" required>
                <option value="">-- Choisir une ville --</option>
                <?php foreach ($villes as $v): ?>
                  <option value="<?= $v ?>"><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" data-fr="Nombre d'invités *" data-ar="عدد الضيوف *">Nombre d'invités *</label>
              <input type="number" id="nb_personnes" class="form-control" min="10" max="2000" placeholder="Ex: 150"
                required>
            </div>
          </div>

          <div class="step-nav">
            <div></div>
            <button class="btn-primary" onclick="goStep2()" style="padding:12px 32px">
              <span data-fr="Choisir la tente" data-ar="اختيار الخيمة">Choisir la tente</span>
              <i class="fas fa-arrow-right" style="margin-left:8px"></i>
            </button>
          </div>
        </div>

        <!-- ═══ ÉTAPE 2 : Choix de la tente ═══ -->
        <div class="step-content" id="step2">
          <h2 style="color:var(--white);font-size:1.3rem;margin-bottom:8px">
            <i class="fas fa-campground" style="color:var(--gold);margin-right:8px"></i>
            <span data-fr="Choisissez votre tente" data-ar="اختر خيمتك">Choisissez votre tente</span>
          </h2>
          <p style="color:var(--text-muted);font-size:.84rem;margin-bottom:20px"
            data-fr="Notre équipe pourra ajuster ce choix selon la disponibilité et vos besoins réels."
            data-ar="سيقوم فريقنا بتعديل هذا الاختيار حسب التوفر واحتياجاتكم الفعلية.">
            Notre équipe pourra ajuster ce choix selon la disponibilité et vos besoins réels.
          </p>

          <?php if (empty($tentesDispo)): ?>
            <div class="message-box" style="padding:20px;border:1px solid var(--border);border-radius:12px;color:var(--text-muted);font-size:.85rem">
              <span data-fr="Aucune tente n'est disponible pour le moment. Notre équipe vous proposera une option adaptée avec votre devis."
                data-ar="لا توجد خيمة متاحة حالياً. سيقترح عليكم فريقنا خياراً مناسباً مع عرض الأسعار.">
                Aucune tente n'est disponible pour le moment. Notre équipe vous proposera une option adaptée avec votre devis.
              </span>
            </div>
          <?php else: ?>
            <div class="tentes-select-grid" id="tentesSelectGrid">
              <?php foreach ($tentesDispo as $t): ?>
                <div class="tente-select-card" data-id="<?= $t['id'] ?>" data-nom="<?= htmlspecialchars($t['nom']) ?>" onclick="selectTente(<?= $t['id'] ?>, this)">
                  <div class="tsc-photo" style="<?= $t['photo'] ? "background-image:url('../assets/uploads/".htmlspecialchars($t['photo'])."')" : '' ?>">
                    <?php if (!$t['photo']): ?><i class="fas fa-campground"></i><?php endif; ?>
                  </div>
                  <div class="tsc-body">
                    <div class="tsc-name"><?= htmlspecialchars($t['nom']) ?></div>
                    <div class="tsc-meta">
                      <span><i class="fas fa-ruler-combined"></i> <?= $t['longueur'] !== null ? $t['longueur'].'m' : '—' ?> × <?= $t['largeur'] !== null ? $t['largeur'].'m' : '—' ?></span>
                      <span><i class="fas fa-users"></i> <?= $t['capacite_max'] !== null ? $t['capacite_max'].' pers.' : '—' ?></span>
                    </div>
                    <?php if ($t['description']): ?><div class="tsc-desc"><?= htmlspecialchars($t['description']) ?></div><?php endif; ?>
                    <button type="button" class="tsc-choose-btn">
                      <span data-fr="Choisir" data-ar="اختيار">Choisir</span>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="step-nav">
            <button class="btn-back" onclick="goStep(1)">
              <i class="fas fa-arrow-left" style="margin-right:6px"></i>
              <span data-fr="Retour" data-ar="رجوع">Retour</span>
            </button>
            <button class="btn-primary" onclick="goStepAfterTente()" style="padding:12px 32px">
              <span data-fr="Choisir les services" data-ar="اختيار الخدمات">Choisir les services</span>
              <i class="fas fa-arrow-right" style="margin-left:8px"></i>
            </button>
          </div>
        </div>

        <!-- ═══ ÉTAPE 2 : Services ═══ -->
        <!-- ═══ ÉTAPE 3 : Services ═══ -->
        <div class="step-content" id="step3">
          <h2 style="color:var(--white);font-size:1.3rem;margin-bottom:8px">
            <i class="fas fa-concierge-bell" style="color:var(--gold);margin-right:8px"></i>
            <span data-fr="Sélectionnez vos services" data-ar="اختر خدماتك">Sélectionnez vos services</span>
          </h2>
          <p style="color:var(--text-muted);font-size:.84rem;margin-bottom:20px"
            data-fr="Cochez les services que vous souhaitez inclure dans votre événement."
            data-ar="حدد الخدمات التي تريد تضمينها في مناسبتك.">
            Cochez les services que vous souhaitez inclure dans votre événement.
          </p>

          <!-- Filtres par catégorie -->
          <div class="cat-tabs">
            <button class="cat-tab active" onclick="filterCat('all',this)" data-fr="Tous les services"
              data-ar="كل الخدمات">Tous les services</button>
            <button class="cat-tab bronze" onclick="filterCat('bronze',this)" data-fr="🥉 Bronze" data-ar="🥉 برونز">🥉
              Bronze</button>
            <button class="cat-tab argent" onclick="filterCat('argent',this)" data-fr="🥈 Argent" data-ar="🥈 فضي">🥈
              Argent</button>
            <button class="cat-tab or" onclick="filterCat('or',this)" data-fr="🥇 Or" data-ar="🥇 ذهبي">🥇 Or</button>
          </div>

          <div class="services-grid" id="servicesGrid">
            <?php
            $tierLabelsAr = ['bronze' => 'برونز', 'argent' => 'فضي', 'or' => 'ذهبي'];
            foreach ($services as $s):
              $typesArr = json_decode($s['types_evenements'] ?? '[]', true) ?: [];
              $typesStr = implode(',', $typesArr);
              $tier = $s['categorie_tarif'] ?? 'bronze';
              $nomAr  = $s['nom_ar'] ?: $s['nom'];
              $descAr = $s['description_ar'] ?: $s['description'];
              ?>
              <div class="service-chk-card" data-id="<?= $s['id'] ?>" data-nom="<?= htmlspecialchars($s['nom']) ?>"
                data-nom-ar="<?= htmlspecialchars($nomAr) ?>"
                data-tier="<?= $tier ?>"
                data-types="<?= htmlspecialchars($typesStr) ?>" onclick="toggleService(this)">
                <input type="checkbox" name="services[]" value="<?= $s['id'] ?>">
                <div class="checkmark"><i class="fas fa-check" style="font-size:.65rem"></i></div>
                <span class="svc-tier <?= $tier ?>" data-fr="<?= ucfirst($tier) ?>" data-ar="<?= htmlspecialchars($tierLabelsAr[$tier] ?? ucfirst($tier)) ?>"><?= ucfirst($tier) ?></span>
                <div class="svc-header">
                  <div class="svc-icon"><i class="fas <?= htmlspecialchars($s['icone'] ?? 'fa-star') ?>"></i></div>
                  <div class="svc-name" data-fr="<?= htmlspecialchars($s['nom']) ?>" data-ar="<?= htmlspecialchars($nomAr) ?>"><?= htmlspecialchars($s['nom']) ?></div>
                </div>
                <?php if ($s['description']): ?>
                  <div class="svc-desc" data-fr="<?= htmlspecialchars($s['description']) ?>" data-ar="<?= htmlspecialchars($descAr) ?>"><?= htmlspecialchars($s['description']) ?></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
            <div class="no-services" id="noServices" style="display:none">
              <i class="fas fa-info-circle"></i>
              <p data-fr="Aucun service spécifique pour ce type d'événement."
                data-ar="لا توجد خدمات محددة لهذا النوع من المناسبات.">Aucun service spécifique pour ce type
                d'événement.</p>
            </div>
          </div>

          <!-- Barre récap sélection (sans montant — le prix sera fixé par notre équipe) -->
          <div class="total-bar" id="totalBar">
            <div>
              <div class="total-label" data-fr="Services sélectionnés" data-ar="الخدمات المختارة">Services sélectionnés</div>
              <div class="total-detail" id="totalDetail" data-fr="0 service sélectionné" data-ar="0 خدمة محددة">0
                service sélectionné</div>
            </div>
            <div class="selected-services-list" id="selectedChips"></div>
          </div>

          <div class="step-nav">
            <button class="btn-back" onclick="goStep(2)">
              <i class="fas fa-arrow-left" style="margin-right:6px"></i>
              <span data-fr="Retour" data-ar="رجوع">Retour</span>
            </button>
            <button class="btn-primary" onclick="goStep4()" style="padding:12px 32px">
              <span data-fr="Vos coordonnées" data-ar="معلوماتك الشخصية">Vos coordonnées</span>
              <i class="fas fa-arrow-right" style="margin-left:8px"></i>
            </button>
          </div>
        </div>

        <!-- ═══ ÉTAPE 4 : Informations client ═══ -->
        <div class="step-content" id="step4">
          <h2 style="color:var(--white);font-size:1.3rem;margin-bottom:8px">
            <i class="fas fa-user" style="color:var(--gold);margin-right:8px"></i>
            <span data-fr="Vos coordonnées" data-ar="معلوماتك الشخصية">Vos coordonnées</span>
          </h2>
          <p style="color:var(--text-muted);font-size:.84rem;margin-bottom:28px"
            data-fr="Pour que nous puissions vous envoyer votre devis personnalisé."
            data-ar="لكي نتمكن من إرسال عرض الأسعار المخصص لك.">
            Pour que nous puissions vous envoyer votre devis personnalisé.
          </p>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="form-group">
              <label class="form-label" data-fr="Prénom *" data-ar="الاسم الأول *">Prénom *</label>
              <input type="text" id="prenom" class="form-control" placeholder="Mohammed" required>
            </div>
            <div class="form-group">
              <label class="form-label" data-fr="Nom *" data-ar="اسم العائلة *">Nom *</label>
              <input type="text" id="nom" class="form-control" placeholder="Alami" required>
            </div>
            <div class="form-group">
              <label class="form-label" data-fr="Téléphone *" data-ar="الهاتف *">Téléphone *</label>
              <input type="tel" id="telephone" class="form-control" placeholder="06XXXXXXXX" required>
            </div>
            <div class="form-group">
              <label class="form-label" data-fr="Email" data-ar="البريد الإلكتروني">Email (optionnel)</label>
              <input type="email" id="email" class="form-control" placeholder="exemple@gmail.com">
            </div>
            <div class="form-group" style="grid-column:1/-1">
              <label class="form-label" data-fr="Message / Demandes spéciales" data-ar="رسالة / طلبات خاصة">Message
                (optionnel)</label>
              <textarea id="message" class="form-control" rows="3"
                placeholder="Précisez vos souhaits, thème, couleurs..."></textarea>
            </div>
          </div>
          <div class="step-nav">
            <button class="btn-back" onclick="goStep(3)">
              <i class="fas fa-arrow-left" style="margin-right:6px"></i>
              <span data-fr="Retour" data-ar="رجوع">Retour</span>
            </button>
            <button class="btn-primary" onclick="generateDevis()" style="padding:12px 32px" id="genBtn">
              <i class="fas fa-file-invoice" style="margin-right:6px"></i>
              <span data-fr="Générer mon devis" data-ar="إنشاء عرض الأسعار">Générer mon devis</span>
            </button>
          </div>
        </div>

        <!-- ═══ ÉTAPE 5 : Récap + PDF ═══ -->
        <div class="step-content" id="step5">
          <div style="text-align:center;margin-bottom:28px">
            <div
              style="width:70px;height:70px;border-radius:50%;background:rgba(37,211,102,.15);border:2px solid #25D366;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:1.8rem;color:#25D366">
              <i class="fas fa-check"></i>
            </div>
            <h2 style="color:var(--white);font-size:1.5rem" data-fr="Votre devis est prêt !"
              data-ar="عرض الأسعار الخاص بك جاهز!">Votre devis est prêt !</h2>
            <p style="color:var(--text-muted);font-size:.85rem;margin-top:6px" id="devisNumero"></p>
          </div>

          <!-- Récap client -->
          <div class="recap-card">
            <div class="recap-header"><i class="fas fa-user"></i>
              <h4 data-fr="Informations client" data-ar="معلومات العميل">Informations client</h4>
            </div>
            <div class="recap-body" id="recapClient"></div>
          </div>

          <!-- Récap événement -->
          <div class="recap-card">
            <div class="recap-header"><i class="fas fa-calendar-check"></i>
              <h4 data-fr="Détails de l'événement" data-ar="تفاصيل المناسبة">Détails de l'événement</h4>
            </div>
            <div class="recap-body" id="recapEvent"></div>
          </div>

          <!-- Services & total -->
          <div class="recap-card">
            <div class="recap-header"><i class="fas fa-concierge-bell"></i>
              <h4 data-fr="Services sélectionnés" data-ar="الخدمات المختارة">Services sélectionnés</h4>
            </div>
            <div class="recap-body">
              <table class="recap-services-table">
                <thead>
                  <tr>
                    <th data-fr="Service" data-ar="الخدمة">Service</th>
                    <th data-fr="Catégorie" data-ar="الفئة">Catégorie</th>
                  </tr>
                </thead>
                <tbody id="recapServicesBody"></tbody>
              </table>
            </div>
            <div class="recap-total-row">
              <span class="rt-label" data-fr="Le prix vous sera communiqué par notre équipe après étude de votre demande" data-ar="سيتم إبلاغكم بالسعر من طرف فريقنا بعد دراسة طلبكم" style="font-size:.8rem">
                Le prix vous sera communiqué par notre équipe après étude de votre demande
              </span>
            </div>
          </div>

          <!-- Bouton principal : Enregistrer la demande -->
          <div id="sendDevisWrap" style="margin-top:28px;text-align:center">
            <div
              style="background:rgba(37,211,102,.06);border:1px solid rgba(37,211,102,.2);border-radius:14px;padding:22px 28px">
              <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:16px"
                data-fr="Cliquez sur le bouton ci-dessous pour envoyer définitivement votre demande à notre équipe."
                data-ar="انقر على الزر أدناه لإرسال طلبك نهائياً إلى فريقنا.">
                Cliquez sur le bouton ci-dessous pour envoyer définitivement votre demande à notre équipe.
              </p>
              <button onclick="envoyerDemande()" id="btnEnvoyer"
                style="background:linear-gradient(135deg,#25D366,#1da851);color:#fff;border:none;padding:15px 36px;border-radius:50px;font-size:1rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:10px;transition:all .3s;box-shadow:0 8px 24px rgba(37,211,102,.3)">
                <i class="fas fa-paper-plane"></i>
                <span data-fr="Enregistrer ma demande" data-ar="حفظ طلبي">Enregistrer ma demande</span>
              </button>
            </div>
          </div>

          <!-- Message confirmation après envoi -->
          <div id="confirmationMsg"
            style="display:none;margin-top:20px;text-align:center;background:rgba(37,211,102,.08);border:1px solid rgba(37,211,102,.25);border-radius:14px;padding:28px">
            <div
              style="width:64px;height:64px;border-radius:50%;background:rgba(37,211,102,.15);border:2px solid #25D366;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.6rem;color:#25D366">
              <i class="fas fa-check-double"></i>
            </div>
            <h3 style="color:#25D366;font-size:1.1rem;margin-bottom:8px" data-fr="Demande envoyée avec succès !"
              data-ar="تم إرسال الطلب بنجاح!">
              Demande envoyée avec succès !
            </h3>
            <p style="color:var(--text-muted);font-size:.88rem;line-height:1.7"
              data-fr="Votre demande a bien été enregistrée. Notre équipe vous contactera prochainement au numéro indiqué."
              data-ar="تم تسجيل طلبك بنجاح، وسيتواصل معك فريقنا قريبًا على الرقم المُدخل.">
              Votre demande a bien été enregistrée. Notre équipe vous contactera prochainement au numéro indiqué.
            </p>
            <p id="confirmRef" style="color:var(--gold);font-size:.82rem;margin-top:10px;font-weight:600"></p>
          </div>

          <!-- Actions secondaires -->
          <p id="waAutoNote" style="text-align:center;color:var(--gold);font-size:.88rem;font-weight:600;margin-bottom:16px;min-height:20px"></p>
          <div style="display:flex;gap:12px;justify-content:center;margin-top:20px;flex-wrap:wrap">
            <a href="#" target="_blank" class="btn-whatsapp" id="btnWhatsappDevis"
              onclick="return verifierAvantWhatsapp(event)"
              style="padding:16px 34px;font-size:1rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:10px;order:-1">
              <i class="fab fa-whatsapp" style="font-size:1.3rem"></i>
              <span data-fr="Envoyer par WhatsApp" data-ar="إرسال عبر واتساب">Envoyer par WhatsApp</span>
            </a>
          </div>
        </div>

      </div><!-- /form-container -->
    </div>
  </section>

  <a href="https://wa.me/212626986533" class="whatsapp-float" target="_blank"><i class="fab fa-whatsapp"></i></a>
  <button class="scroll-top" id="scrollTop"><i class="fas fa-chevron-up"></i></button>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script src="../js/main.js"></script>
  <script src="../js/lang.js"></script>
  <script>
    // ── Données services depuis PHP ──────────────────────────
    const allServices = <?= json_encode(array_map(fn($s) => [
      'id' => $s['id'],
      'nom' => $s['nom'],
      'tier' => $s['categorie_tarif'] ?? 'bronze',
      'icon' => $s['icone'] ?? 'fa-star',
      'types' => json_decode($s['types_evenements'] ?? '[]', true) ?: [],
    ], $services), JSON_UNESCAPED_UNICODE) ?>;

    const typesLabels = <?= json_encode(array_map(fn($t) => $t['label'], $typesEvenements), JSON_UNESCAPED_UNICODE) ?>;
    const tierLabels = { bronze: '🥉 Bronze', argent: '🥈 Argent', or: '🥇 Or' };
    const tierLabelsAr = { bronze: '🥉 برونز', argent: '🥈 فضي', or: '🥇 ذهبي' };

    let currentStep = 1;
    let selectedType = '';
    let selectedTente = null; // { id, nom } ou null
    const tentesDisponibles = <?= json_encode(!empty($tentesDispo)) ?>;
    let selectedServices = [];
    let devisData = {};
    let devisNumero = '';

    // ── Étape 1 ──────────────────────────────────────────────
    function selectType(type, card) {
      document.querySelectorAll('.event-type-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      selectedType = type;
    }

    const maxInvitesParType = <?= json_encode($maxInvitesParType) ?>;
    const eventTypesLabels = <?= json_encode(array_combine(array_keys($typesEvenements), array_column($typesEvenements, 'label'))) ?>;

    function verifierAvantWhatsapp(e) {
      const btn = document.getElementById('btnWhatsappDevis');
      const pasEncoreEnregistre = !btn.href || btn.href.endsWith('#');
      if (pasEncoreEnregistre) {
        e.preventDefault();
        const isAr = document.documentElement.lang === 'ar' || document.body.classList.contains('rtl-mode');
        showAlert(isAr
          ? 'يرجى أولاً الضغط على "حفظ طلبي"، ثم الضغط على "الإرسال عبر واتساب".'
          : 'Veuillez d\'abord cliquer sur « Enregistrer ma demande », puis sur « Envoyer par WhatsApp ».');
        return false;
      }
      return true;
    }

    function goStep2() {
      if (!selectedType) { showAlert('Veuillez choisir un type d\'événement.'); return; }
      const dateVal = document.getElementById('date_evenement').value;
      if (!dateVal) { showAlert('Veuillez saisir la date de l\'événement.'); return; }
      const dateChoisie = new Date(dateVal);
      const dateMin = new Date(); dateMin.setDate(dateMin.getDate() + 7); dateMin.setHours(0,0,0,0);
      const dateMax = new Date(); dateMax.setMonth(dateMax.getMonth() + 2); dateMax.setHours(23,59,59,999);
      if (dateChoisie < dateMin || dateChoisie > dateMax) {
        showAlert('La date doit être comprise entre 7 jours et 2 mois à partir d\'aujourd\'hui.');
        return;
      }
      if (!document.getElementById('ville').value) { showAlert('Veuillez choisir une ville.'); return; }
      const nbInvites = parseInt(document.getElementById('nb_personnes').value, 10);
      if (!nbInvites) { showAlert('Veuillez indiquer le nombre d\'invités.'); return; }
      const maxPourCeType = maxInvitesParType[selectedType];
      if (maxPourCeType && nbInvites > maxPourCeType) {
        showAlert('Le nombre maximal d\'invités pour cet événement est de ' + maxPourCeType + ' personnes.');
        return;
      }
      goStep(2);
    }

    // ── Tente ────────────────────────────────────────────────
    function selectTente(id, card) {
      document.querySelectorAll('.tente-select-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      selectedTente = { id, nom: card.dataset.nom };
    }

    function goStepAfterTente() {
      if (tentesDisponibles && !selectedTente) {
        showAlert('Veuillez choisir une tente (notre équipe pourra ajuster ce choix si besoin).');
        return;
      }
      filterServicesByType();
      goStep(3);
    }

    // ── Services ──────────────────────────────────────────────
    function filterServicesByType() {
      const cards = document.querySelectorAll('.service-chk-card');
      let visible = 0;
      cards.forEach(card => {
        const types = card.dataset.types ? card.dataset.types.split(',') : [];
        const show = !selectedType || types.includes(selectedType);
        card.classList.toggle('hidden', !show);
        if (show) visible++;
      });
      document.getElementById('noServices').style.display = visible === 0 ? 'grid' : 'none';
    }

    function filterCat(cat, btn) {
      document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      document.querySelectorAll('.service-chk-card').forEach(card => {
        if (card.classList.contains('hidden')) return;
        const tier = card.dataset.tier;
        card.style.display = (cat === 'all' || tier === cat) ? '' : 'none';
      });
    }

    function toggleService(card) {
      card.classList.toggle('checked');
      const id = parseInt(card.dataset.id);
      const nom = card.dataset.nom;
      const nomAr = card.dataset.nomAr || nom;
      const tier = card.dataset.tier;

      if (card.classList.contains('checked')) {
        if (!selectedServices.find(s => s.id === id))
          selectedServices.push({ id, nom, nomAr, tier });
      } else {
        selectedServices = selectedServices.filter(s => s.id !== id);
      }
      updateTotal();
    }

    function updateTotal() {
      const count = selectedServices.length;
      document.getElementById('totalDetail').textContent = count + ' service' + (count > 1 ? 's' : '') + ' sélectionné' + (count > 1 ? 's' : '');
      const chips = document.getElementById('selectedChips');
      const curLang = localStorage.getItem('site_lang') || 'fr';
      chips.innerHTML = selectedServices.slice(0, 5).map(s =>
        `<span class="svc-chip">${curLang === 'ar' ? (s.nomAr || s.nom) : s.nom}</span>`
      ).join('') + (count > 5 ? `<span class="svc-chip">+${count - 5}</span>` : '');
    }

    function goStep4() {
      if (selectedServices.length === 0) { showAlert('Veuillez sélectionner au moins un service.'); return; }
      goStep(4);
    }

    // ── Génération devis ──────────────────────────────────────
    function generateDevis() {
      const prenom = document.getElementById('prenom').value.trim();
      const nom = document.getElementById('nom').value.trim();
      const telephone = document.getElementById('telephone').value.trim();

      if (!prenom || !nom) { showAlert('Veuillez saisir votre nom complet.'); return; }
      if (!telephone) { showAlert('Veuillez saisir votre numéro de téléphone.'); return; }

      const btn = document.getElementById('genBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération...';

      devisData = {
        prenom, nom, telephone,
        email: document.getElementById('email').value.trim(),
        message: document.getElementById('message').value.trim(),
        type: selectedType,
        date: document.getElementById('date_evenement').value,
        ville: document.getElementById('ville').value,
        nb: document.getElementById('nb_personnes').value,
        services: selectedServices,
        tente: selectedTente,
      };

      // Étape 4 = simple aperçu côté navigateur. On NE sauvegarde PAS encore
      // en base ici — sinon on crée un doublon avec l'enregistrement final
      // de l'étape 5 (envoyerDemande). Le vrai enregistrement se fait
      // uniquement quand le client clique sur "Enregistrer ma demande".
      devisNumero = 'DEV-' + new Date().getFullYear() + '-' + String(Date.now()).slice(-4);
      devisData.numero = devisNumero;
      buildRecap();
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-file-invoice"></i> Générer mon devis';
      goStep(5);
    }

    function buildRecap() {
      const typeLabel = <?= json_encode(array_column($typesEvenements, 'label', null)) ?>;
      const tl = Object.values(<?= json_encode($typesEvenements) ?>);
      const typeLabelsMap = {};
      <?php foreach ($typesEvenements as $k => $ev): ?>
        typeLabelsMap['<?= $k ?>'] = '<?= $ev['label'] ?>';
      <?php endforeach; ?>

      document.getElementById('devisNumero').textContent = 'Référence : ' + devisData.numero;

      document.getElementById('recapClient').innerHTML = `
    <div class="recap-row"><span class="r-label">Nom complet</span><span class="r-value">${devisData.prenom} ${devisData.nom}</span></div>
    <div class="recap-row"><span class="r-label">Téléphone</span><span class="r-value" dir="ltr">${devisData.telephone}</span></div>
    ${devisData.email ? `<div class="recap-row"><span class="r-label">Email</span><span class="r-value">${devisData.email}</span></div>` : ''}
  `;

      document.getElementById('recapEvent').innerHTML = `
    <div class="recap-row"><span class="r-label">Type d'événement</span><span class="r-value">${typeLabelsMap[devisData.type] || devisData.type}</span></div>
    <div class="recap-row"><span class="r-label">Date</span><span class="r-value" dir="ltr">${new Date(devisData.date).toLocaleDateString('fr-FR')}</span></div>
    <div class="recap-row"><span class="r-label">Ville</span><span class="r-value">${devisData.ville}</span></div>
    <div class="recap-row"><span class="r-label">Nombre d'invités</span><span class="r-value" dir="ltr">${devisData.nb} personnes</span></div>
    ${devisData.tente ? `<div class="recap-row"><span class="r-label">Tente</span><span class="r-value">${devisData.tente.nom}</span></div>` : ''}
  `;

      const curLang = localStorage.getItem('site_lang') || 'fr';
      document.getElementById('recapServicesBody').innerHTML = devisData.services.map(s => `
    <tr>
      <td>${curLang === 'ar' ? (s.nomAr || s.nom) : s.nom}</td>
      <td>${(curLang === 'ar' ? tierLabelsAr[s.tier] : tierLabels[s.tier]) || s.tier}</td>
    </tr>
  `).join('');
    }

    // ── Enregistrer la demande ────────────────────────────────
    function envoyerDemande() {
      const btn = document.getElementById('btnEnvoyer');
      const lang = localStorage.getItem('site_lang') || 'fr';
      const isAr = lang === 'ar';

      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> '
        + (isAr ? 'جارٍ الإرسال...' : 'Envoi en cours...');

      // Construire payload complet avec horodatage
      const payload = {
        ...devisData,
        datetime_demande: new Date().toISOString(),
        statut: 'en_attente',
      };

      fetch('<?= SITE_URL ?>/api/save_devis.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            // Masquer le bouton d'envoi
            document.getElementById('sendDevisWrap').style.display = 'none';
            // Afficher confirmation
            const conf = document.getElementById('confirmationMsg');
            conf.style.display = 'block';
            // Afficher référence
            const ref = res.numero || devisData.numero || '';
            document.getElementById('confirmRef').textContent =
              (isAr ? 'رقم المرجع : ' : 'Référence de votre demande : ') + ref;

            // ── Construction du message WhatsApp complet et pré-rempli ──
            const servicesTexte = (devisData.services || [])
              .map(s => '- ' + s.nom)
              .join('\n');
            const typeLabel = eventTypesLabels[devisData.type] || devisData.type || '';
            const dateAffichee = devisData.date ? new Date(devisData.date).toLocaleDateString('fr-FR') : '';

            const messageWa =
`🍽️ *DEMANDE DE RÉSERVATION — EL MOUSSAOUI*

👤 Nom : ${devisData.prenom} ${devisData.nom}
📱 Téléphone : ${devisData.telephone}
📧 Email : ${devisData.email || '—'}
🎉 Type d'événement : ${typeLabel}
📅 Date : ${dateAffichee}
📍 Ville : ${devisData.ville || '—'}
👥 Nombre d'invités : ${devisData.nb}
⛺ Tente : ${devisData.tente ? devisData.tente.nom : '—'}

📦 Services demandés :
${servicesTexte || '—'}

📝 Informations supplémentaires :
${devisData.message || '—'}

🔖 Référence de demande : ${ref}

Je souhaite recevoir votre devis personnalisé après étude de ma demande. Merci.`;

            const btnWa = document.getElementById('btnWhatsappDevis');
            const waUrl = 'https://wa.me/212626986533?text=' + encodeURIComponent(messageWa);
            if (btnWa) btnWa.href = waUrl;

            // On tente d'ouvrir WhatsApp automatiquement — si le navigateur
            // bloque l'ouverture automatique, le bouton reste bien visible
            // et mis en avant pour que le client clique lui-même.
            const fenetreOuverte = window.open(waUrl, '_blank');
            const noteWa = document.getElementById('waAutoNote');
            if (fenetreOuverte) {
              if (noteWa) noteWa.textContent = isAr
                ? 'تم فتح واتساب في نافذة جديدة. إن لم يظهر، اضغط على الزر أدناه.'
                : 'WhatsApp vient de s\'ouvrir dans un nouvel onglet. S\'il n\'apparaît pas, cliquez sur le bouton ci-dessous.';
            } else {
              if (btnWa) btnWa.classList.add('wa-pulse');
              if (noteWa) noteWa.textContent = isAr
                ? '👇 اضغط على الزر أدناه لإرسال طلبك عبر واتساب'
                : '👇 Cliquez sur le bouton ci-dessous pour envoyer votre demande sur WhatsApp';
            }

            // Re-appliquer traduction
            if (window.applyLang) applyLang(lang);
          } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> '
              + (isAr ? 'حفظ طلبي' : 'Enregistrer ma demande');
            alert(isAr
              ? 'حدث خطأ. يرجى المحاولة مجدداً أو التواصل عبر واتساب.'
              : 'Erreur : ' + (res.message || 'Veuillez réessayer ou contacter via WhatsApp.'));
          }
        })
        .catch(() => {
          // Mode offline — message alternatif
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-paper-plane"></i> '
            + (isAr ? 'حفظ طلبي' : 'Enregistrer ma demande');
          alert(isAr
            ? 'لا يمكن الاتصال بالخادم. يرجى التواصل مباشرة عبر واتساب: 0626 986 533'
            : 'Connexion impossible. Veuillez nous contacter directement via WhatsApp : 0626 986 533');
        });
    }

    // ── Téléchargement PDF ────────────────────────────────────
    function downloadPDF() {
      const params = new URLSearchParams({ data: JSON.stringify(devisData) });
      window.open('<?= SITE_URL ?>/api/generate_devis_pdf.php?' + params.toString(), '_blank');
    }

    // ── Navigation étapes ─────────────────────────────────────
    function goStep(n) {
      document.querySelectorAll('.step-content').forEach(s => s.classList.remove('active'));
      document.getElementById('step' + n).classList.add('active');
      for (let i = 1; i <= 5; i++) {
        const ind = document.getElementById('step-ind-' + i);
        ind.classList.remove('active', 'done');
        if (i < n) ind.classList.add('done');
        else if (i === n) ind.classList.add('active');
        if (i < 5) {
          const line = document.getElementById('line-' + i);
          line.classList.toggle('done', i < n);
        }
      }
      currentStep = n;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
      selectedType = ''; selectedServices = []; selectedTente = null;
      document.querySelectorAll('.event-type-card').forEach(c => c.classList.remove('selected'));
      document.querySelectorAll('.service-chk-card').forEach(c => c.classList.remove('checked'));
      document.getElementById('date_evenement').value = '';
      document.getElementById('ville').value = '';
      document.getElementById('nb_personnes').value = '';
      document.getElementById('prenom').value = '';
      document.getElementById('nom').value = '';
      document.getElementById('telephone').value = '';
      document.getElementById('email').value = '';
      document.getElementById('message').value = '';
      updateTotal();
      goStep(1);
    }

    // ═══════════════ Calendrier de réservation ═══════════════
    (function () {
      const MIN_DATE = new Date(); MIN_DATE.setDate(MIN_DATE.getDate() + 7); MIN_DATE.setHours(0,0,0,0);
      const MAX_DATE = new Date(); MAX_DATE.setMonth(MAX_DATE.getMonth() + 2); MAX_DATE.setHours(0,0,0,0);
      const moisNoms = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

      let vueMois = MIN_DATE.getMonth();
      let vueAnnee = MIN_DATE.getFullYear();
      let datesStatut = {};
      let dateSelectionnee = null;

      function fmtDate(d) {
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
      }

      function chargerDisponibilite(mois, annee) {
        return fetch('../api/calendrier_disponibilite.php?mois=' + (mois+1) + '&annee=' + annee)
          .then(r => r.json())
          .then(res => { datesStatut = (res.success && res.dates) ? res.dates : {}; });
      }

      function peutReculer() {
        const premierMoisVue = new Date(vueAnnee, vueMois, 1);
        const premierMoisMin = new Date(MIN_DATE.getFullYear(), MIN_DATE.getMonth(), 1);
        return premierMoisVue > premierMoisMin;
      }
      function peutAvancer() {
        const premierMoisVue = new Date(vueAnnee, vueMois, 1);
        const premierMoisMax = new Date(MAX_DATE.getFullYear(), MAX_DATE.getMonth(), 1);
        return premierMoisVue < premierMoisMax;
      }

      function dessinerCalendrier() {
        document.getElementById('calTitle').textContent = moisNoms[vueMois] + ' ' + vueAnnee;
        document.getElementById('calPrev').disabled = !peutReculer();
        document.getElementById('calNext').disabled = !peutAvancer();

        const grille = document.getElementById('calDays');
        grille.innerHTML = '';

        const premierJour = new Date(vueAnnee, vueMois, 1);
        let decalage = premierJour.getDay(); // 0=dimanche
        decalage = (decalage === 0) ? 6 : decalage - 1; // On veut Lundi en premier
        const nbJours = new Date(vueAnnee, vueMois + 1, 0).getDate();
        const aujourdHui = new Date(); aujourdHui.setHours(0,0,0,0);

        for (let i = 0; i < decalage; i++) {
          const vide = document.createElement('div');
          vide.className = 'cal-day empty';
          grille.appendChild(vide);
        }

        for (let jour = 1; jour <= nbJours; jour++) {
          const dateObj = new Date(vueAnnee, vueMois, jour);
          const dateStr = fmtDate(dateObj);
          const cell = document.createElement('div');
          cell.className = 'cal-day';
          cell.textContent = jour;

          const horsPlage = dateObj < MIN_DATE || dateObj > MAX_DATE;
          const statut = datesStatut[dateStr];

          if (dateObj.getTime() === aujourdHui.getTime()) cell.classList.add('today');

          if (horsPlage) {
            cell.classList.add('disabled');
          } else if (statut === 'reserve') {
            cell.classList.add('reserve');
            cell.addEventListener('click', () => {
              showAlert("Cette date est déjà réservée. Veuillez choisir une autre date.");
            });
          } else {
            if (statut === 'attente') cell.classList.add('attente');
            cell.addEventListener('click', () => selectionnerDate(dateObj, cell));
          }

          if (dateSelectionnee === dateStr) cell.classList.add('selected');

          grille.appendChild(cell);
        }
      }

      function selectionnerDate(dateObj, cell) {
        dateSelectionnee = fmtDate(dateObj);
        document.getElementById('date_evenement').value = dateSelectionnee;
        document.querySelectorAll('.cal-day.selected').forEach(c => c.classList.remove('selected'));
        cell.classList.add('selected');
        const isAr = document.documentElement.lang === 'ar' || document.body.classList.contains('rtl-mode');
        const dateAffichee = dateObj.toLocaleDateString(isAr ? 'ar-MA' : 'fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
        document.getElementById('dateSelectedLabel').textContent = '✓ ' + dateAffichee;
      }

      function changerMois(delta) {
        vueMois += delta;
        if (vueMois > 11) { vueMois = 0; vueAnnee++; }
        if (vueMois < 0) { vueMois = 11; vueAnnee--; }
        chargerDisponibilite(vueMois, vueAnnee).then(dessinerCalendrier);
      }

      document.getElementById('calPrev').addEventListener('click', () => { if (peutReculer()) changerMois(-1); });
      document.getElementById('calNext').addEventListener('click', () => { if (peutAvancer()) changerMois(1); });
      document.getElementById('calToday').addEventListener('click', () => {
        vueMois = MIN_DATE.getMonth(); vueAnnee = MIN_DATE.getFullYear();
        chargerDisponibilite(vueMois, vueAnnee).then(dessinerCalendrier);
      });

      chargerDisponibilite(vueMois, vueAnnee).then(dessinerCalendrier);
    })();

    function showAlert(msg) {
      const div = document.createElement('div');
      div.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;background:#2E1A1A;border:1px solid rgba(239,68,68,.4);color:#EF5350;padding:14px 20px;border-radius:10px;font-family:Jost,sans-serif;font-size:.85rem;font-weight:600;max-width:340px;box-shadow:0 8px 32px rgba(0,0,0,.5)';
      div.innerHTML = '<i class="fas fa-exclamation-circle" style="margin-right:8px"></i>' + msg;
      document.body.appendChild(div);
      setTimeout(() => div.remove(), 3500);
    }
  </script>

  <?php include_once __DIR__ . '/../includes/admin-bar.php'; ?>
</body>

</html>