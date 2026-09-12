<?php
require_once __DIR__ . '/../includes/config.php';

$typesEvenements = [];
try { $typesEvenements = $pdo->query("SELECT nom FROM types_evenements WHERE actif=1 ORDER BY ordre ASC")->fetchAll(PDO::FETCH_COLUMN); } catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laisser un avis — Traiteur EL MOUSSAOUI | Errachidia</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=Jost:wght@300;400;500;600&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .avis-hero{padding:100px 0 40px;text-align:center}
    .avis-form-card{max-width:640px;margin:0 auto 80px;background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:40px}
    @media(max-width:600px){.avis-form-card{padding:24px;margin-left:16px;margin-right:16px}}
    .star-picker{display:flex;gap:10px;justify-content:center;margin:14px 0 4px}
    .star-picker i{font-size:2rem;color:#3a3a3a;cursor:pointer;transition:var(--transition)}
    .star-picker i.active{color:var(--gold)}
    .star-picker-label{text-align:center;font-size:.78rem;color:var(--text-muted);margin-bottom:20px}
    .avis-success{display:none;text-align:center;padding:40px 20px}
    .avis-success.show{display:block}
    .avis-success i{font-size:3rem;color:#25D366;margin-bottom:16px;display:block}
    #avisFormWrap.hide{display:none}
  </style>
</head>
<body>
<div id="loader"><div class="loader-inner"><div class="loader-ring"></div><div class="loader-logo"><span class="loader-em">EL</span><span class="loader-moussaoui">MOUSSAOUI</span></div></div></div>

<?php $navActive = "avis"; include_once __DIR__ . "/../includes/navbar.php"; ?>

<div class="avis-hero" data-aos="fade-up">
  <div class="container">
    <span class="section-tag" data-fr="Votre expérience compte" data-ar="تجربتكم تهمنا">Votre expérience compte</span>
    <h1 data-fr="Laissez-nous un avis" data-ar="اترك لنا رأياً">Laissez-nous un avis</h1>
    <p style="max-width:520px;margin:12px auto 0;color:var(--text-muted)"
       data-fr="Votre retour nous aide à nous améliorer et aide aussi les futurs clients à nous connaître."
       data-ar="ملاحظاتكم تساعدنا على التحسن وتساعد أيضاً العملاء المستقبليين على التعرف علينا.">
      Votre retour nous aide à nous améliorer et aide aussi les futurs clients à nous connaître.
    </p>
  </div>
</div>

<div class="avis-form-card" data-aos="fade-up">

  <div id="avisFormWrap">
    <form id="avisForm">
      <div class="form-group">
        <label class="form-label" data-fr="Votre nom *" data-ar="اسمك *">Votre nom *</label>
        <input type="text" id="nom_client" class="form-control" placeholder="Prénom Nom" required>
      </div>

      <div class="form-group">
        <label class="form-label" data-fr="Ville (optionnel)" data-ar="المدينة (اختياري)">Ville (optionnel)</label>
        <input type="text" id="ville" class="form-control" placeholder="Errachidia">
      </div>

      <div class="form-group">
        <label class="form-label" data-fr="Type d'événement (optionnel)" data-ar="نوع المناسبة (اختياري)">Type d'événement (optionnel)</label>
        <select id="type_evenement" class="form-control">
          <option value="">—</option>
          <?php foreach ($typesEvenements as $t): ?>
          <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" style="display:block;text-align:center" data-fr="Votre note *" data-ar="تقييمك *">Votre note *</label>
        <div class="star-picker" id="starPicker">
          <i class="fas fa-star" data-val="1"></i>
          <i class="fas fa-star" data-val="2"></i>
          <i class="fas fa-star" data-val="3"></i>
          <i class="fas fa-star" data-val="4"></i>
          <i class="fas fa-star" data-val="5"></i>
        </div>
        <div class="star-picker-label" id="starLabel" data-fr="Touchez une étoile pour noter" data-ar="اضغط على نجمة للتقييم">Touchez une étoile pour noter</div>
      </div>

      <div class="form-group">
        <label class="form-label" data-fr="Votre témoignage *" data-ar="شهادتك *">Votre témoignage *</label>
        <textarea id="contenu" class="form-control" rows="5" placeholder="Racontez-nous votre expérience..." required></textarea>
      </div>

      <div id="avisError" style="display:none;color:#EF5350;font-size:.82rem;margin-bottom:14px"></div>

      <button type="submit" class="btn-primary" style="width:100%;padding:14px" id="avisSubmitBtn">
        <span data-fr="Envoyer mon avis" data-ar="إرسال رأيي">Envoyer mon avis</span>
        <i class="fas fa-paper-plane" style="margin-left:8px"></i>
      </button>

      <p style="text-align:center;font-size:.72rem;color:#555;margin-top:14px"
         data-fr="Votre avis sera publié après vérification par notre équipe."
         data-ar="سيتم نشر رأيك بعد التحقق من طرف فريقنا.">
        Votre avis sera publié après vérification par notre équipe.
      </p>
    </form>
  </div>

  <div class="avis-success" id="avisSuccess">
    <i class="fas fa-check-circle"></i>
    <h3 data-fr="Merci pour votre avis !" data-ar="شكراً على رأيك!">Merci pour votre avis !</h3>
    <p style="color:var(--text-muted);margin-top:8px"
       data-fr="Il sera publié sur notre site après vérification par notre équipe."
       data-ar="سيتم نشره على موقعنا بعد التحقق من طرف فريقنا.">
      Il sera publié sur notre site après vérification par notre équipe.
    </p>
    <a href="../index.php" class="btn-secondary" style="margin-top:20px;display:inline-block" data-fr="Retour à l'accueil" data-ar="العودة إلى الرئيسية">Retour à l'accueil</a>
  </div>

</div>

<footer id="footer">
  <div class="footer-bottom"><div class="container">
    <p>© 2026 Traiteur EL MOUSSAOUI — Errachidia, Maroc</p>
  </div></div>
</footer>

<a href="https://wa.me/212626986533" class="whatsapp-float" target="_blank"><i class="fab fa-whatsapp"></i></a>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="../js/main.js"></script>
<script src="../js/lang.js"></script>

<script>
let noteChoisie = 0;
const stars = document.querySelectorAll('#starPicker i');
const starLabel = document.getElementById('starLabel');
const labels = {1:'Décevant',2:'Moyen',3:'Correct',4:'Très bien',5:'Excellent !'};

function paintStars(val, hover=false) {
  stars.forEach(s => s.classList.toggle('active', s.dataset.val <= val));
}
stars.forEach(s => {
  s.addEventListener('mouseenter', () => paintStars(s.dataset.val, true));
  s.addEventListener('mouseleave', () => paintStars(noteChoisie));
  s.addEventListener('click', () => {
    noteChoisie = parseInt(s.dataset.val);
    paintStars(noteChoisie);
    starLabel.textContent = labels[noteChoisie];
  });
});

document.getElementById('avisForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const errBox = document.getElementById('avisError');
  errBox.style.display = 'none';

  if (noteChoisie < 1) {
    errBox.textContent = 'Merci de choisir une note (étoiles).';
    errBox.style.display = 'block';
    return;
  }

  const btn = document.getElementById('avisSubmitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';

  fetch('../api/save_temoignage.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      nom_client: document.getElementById('nom_client').value,
      ville: document.getElementById('ville').value,
      type_evenement: document.getElementById('type_evenement').value,
      note: noteChoisie,
      contenu: document.getElementById('contenu').value
    })
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      document.getElementById('avisFormWrap').classList.add('hide');
      document.getElementById('avisSuccess').classList.add('show');
    } else {
      errBox.textContent = res.message || 'Une erreur est survenue, réessayez.';
      errBox.style.display = 'block';
      btn.disabled = false;
      btn.innerHTML = '<span>Envoyer mon avis</span> <i class="fas fa-paper-plane" style="margin-left:8px"></i>';
    }
  })
  .catch(() => {
    errBox.textContent = 'Erreur serveur, merci de réessayer.';
    errBox.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<span>Envoyer mon avis</span> <i class="fas fa-paper-plane" style="margin-left:8px"></i>';
  });
});
</script>
</body>
</html>
