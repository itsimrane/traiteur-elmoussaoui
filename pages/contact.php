<?php
require_once __DIR__ . '/../includes/config.php';

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom  = sanitize($_POST['prenom']  ?? '');
    $nom     = sanitize($_POST['nom']     ?? '');
    $email   = sanitize($_POST['email']   ?? '');
    $tel     = sanitize($_POST['telephone'] ?? '');
    $sujet   = sanitize($_POST['sujet']   ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if ($prenom && $nom && $email && $message) {
        try {
            $pdo->prepare("
                INSERT INTO contacts (prenom, nom, email, telephone, sujet, message, statut, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'nouveau', NOW())
            ")->execute([$prenom, $nom, $email, $tel, $sujet, $message]);
            $success = true;
        } catch(Exception $e) {
            $error = 'Une erreur est survenue. Veuillez réessayer.';
        }
    } else {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact — Traiteur EL MOUSSAOUI | Errachidia</title>
  <meta name="description" content="Contactez Traiteur EL MOUSSAOUI pour organiser votre événement à Errachidia. Devis gratuit, réponse rapide.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=Jost:wght@300;400;500;600&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .contact-layout{display:grid;grid-template-columns:1fr 1.4fr;gap:40px;align-items:start}
    .contact-info-card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:32px;position:sticky;top:100px}
    .contact-info-title{font-size:1.2rem;color:var(--white);font-family:var(--ff-display);margin-bottom:6px}
    .contact-info-sub{font-size:.82rem;color:var(--text-muted);margin-bottom:28px;line-height:1.6}
    .contact-item{display:flex;align-items:flex-start;gap:14px;margin-bottom:22px}
    .contact-item-icon{width:44px;height:44px;border-radius:12px;background:rgba(212,175,55,.1);border:1px solid rgba(212,175,55,.2);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:1rem;flex-shrink:0}
    .contact-item-body label{display:block;font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px}
    .contact-item-body span,.contact-item-body a{font-size:.88rem;color:var(--white);text-decoration:none;transition:var(--transition)}
    .contact-item-body a:hover{color:var(--gold)}
    .contact-divider{border:none;border-top:1px solid var(--border);margin:24px 0}
    .social-row{display:flex;gap:10px;margin-top:4px}
    .social-btn{width:38px;height:38px;border-radius:10px;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none;transition:var(--transition);font-size:.9rem}
    .social-btn:hover{border-color:var(--gold);color:var(--gold)}
    .map-frame{width:100%;height:200px;border-radius:12px;border:1px solid var(--border);margin-top:24px;overflow:hidden}
    .contact-form-card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:36px}
    .form-title{font-size:1.2rem;color:var(--white);font-family:var(--ff-display);margin-bottom:6px}
    .form-sub{font-size:.82rem;color:var(--text-muted);margin-bottom:28px}
    .form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .form-group{margin-bottom:18px}
    .form-group label{display:block;font-size:.75rem;font-weight:600;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px}
    .form-group label span{color:#EF5350;margin-left:2px}
    .alert-success{background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.3);color:#25D366;padding:14px 18px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:.88rem}
    .alert-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#EF5350;padding:14px 18px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:.88rem}
    .char-count{font-size:.72rem;color:#555;text-align:right;margin-top:4px}
    @media(max-width:900px){.contact-layout{grid-template-columns:1fr}.contact-info-card{position:static}.form-row-2{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div id="loader"><div class="loader-inner"><div class="loader-ring"></div><div class="loader-logo"><span class="loader-em">EL</span><span class="loader-moussaoui">MOUSSAOUI</span></div></div></div>

<?php $navActive = 'contact'; include_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-hero">
  <div class="container">
    <span class="section-tag" data-aos="fade-down" data-fr="Parlons de votre projet" data-ar="لنتحدث عن مشروعك">Parlons de votre projet</span>
    <h1 data-aos="fade-up" data-fr="Contactez-nous" data-ar="اتصل بنا">Contactez-nous</h1>
    <p data-aos="fade-up" data-aos-delay="150"
       data-fr="Notre équipe vous répond dans les 24h pour organiser l'événement de vos rêves."
       data-ar="فريقنا يرد عليك في غضون 24 ساعة لتنظيم حفل أحلامك.">
      Notre équipe vous répond dans les 24h pour organiser l'événement de vos rêves.
    </p>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="contact-layout">

      <!-- Infos de contact -->
      <div>
        <div class="contact-info-card" data-aos="fade-right">
          <div class="contact-info-title" data-fr="Nos coordonnées" data-ar="معلومات التواصل">Nos coordonnées</div>
          <div class="contact-info-sub" data-fr="Disponibles 7j/7 pour répondre à vos questions." data-ar="متاحون 7 أيام/7 للإجابة على أسئلتكم.">Disponibles 7j/7 pour répondre à vos questions.</div>

          <div class="contact-item">
            <div class="contact-item-icon"><i class="fab fa-whatsapp"></i></div>
            <div class="contact-item-body">
              <label data-fr="WhatsApp & Téléphone" data-ar="واتساب والهاتف">WhatsApp & Téléphone</label>
              <a href="https://wa.me/212626986533" target="_blank" dir="ltr">0626 986 533</a>
            </div>
          </div>

          <div class="contact-item">
            <div class="contact-item-icon"><i class="fas fa-envelope"></i></div>
            <div class="contact-item-body">
              <label data-fr="Email" data-ar="البريد الإلكتروني">Email</label>
              <a href="mailto:contact@traiteur-elmoussaoui.ma">contact@traiteur-elmoussaoui.ma</a>
            </div>
          </div>

          <div class="contact-item">
            <div class="contact-item-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div class="contact-item-body">
              <label data-fr="Localisation" data-ar="الموقع">Localisation</label>
              <span data-fr="Errachidia, Maroc" data-ar="الراشيدية، المغرب">Errachidia, Maroc</span>
            </div>
          </div>

          <div class="contact-item">
            <div class="contact-item-icon"><i class="fas fa-clock"></i></div>
            <div class="contact-item-body">
              <label data-fr="Horaires" data-ar="ساعات العمل">Horaires</label>
              <span data-fr="Lun–Sam : 8h–20h | Dim : 9h–17h" data-ar="الإثنين–السبت: 8ص–8م | الأحد: 9ص–5م">Lun–Sam : 8h–20h | Dim : 9h–17h</span>
            </div>
          </div>

          <hr class="contact-divider">

          <label style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:10px" data-fr="Suivez-nous" data-ar="تابعونا">Suivez-nous</label>
          <div class="social-row">
            <a href="#" class="social-btn" title="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social-btn" title="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="https://wa.me/212626986533" class="social-btn" title="WhatsApp" target="_blank" style="border-color:rgba(37,211,102,.3);color:#25D366"><i class="fab fa-whatsapp"></i></a>
          </div>

          <!-- Google Maps Errachidia -->
          <iframe class="map-frame"
            src="https://maps.google.com/maps?q=Errachidia,Maroc&t=&z=13&ie=UTF8&iwloc=&output=embed"
            frameborder="0" scrolling="no" allowfullscreen loading="lazy">
          </iframe>
        </div>
      </div>

      <!-- Formulaire -->
      <div class="contact-form-card" data-aos="fade-left">
        <div class="form-title" data-fr="Envoyez-nous un message" data-ar="أرسل لنا رسالة">Envoyez-nous un message</div>
        <div class="form-sub" data-fr="Réponse garantie sous 24h. Pour les urgences, appelez le 0626 986 533." data-ar="رد مضمون خلال 24 ساعة. للطوارئ، اتصل على 0626 986 533.">Réponse garantie sous 24h. Pour les urgences, appelez le 0626 986 533.</div>

        <?php if ($success): ?>
        <div class="alert-success">
          <i class="fas fa-check-circle"></i>
          <span data-fr="Votre message a été envoyé ! Nous vous répondrons dans les 24h." data-ar="تم إرسال رسالتك! سنرد عليك خلال 24 ساعة.">Votre message a été envoyé ! Nous vous répondrons dans les 24h.</span>
        </div>
        <?php elseif ($error): ?>
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="contact.php" id="contactForm">
          <div class="form-row-2">
            <div class="form-group">
              <label data-fr="Prénom" data-ar="الاسم الأول">Prénom <span>*</span></label>
              <input type="text" name="prenom" class="form-control" required
                     value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
                     data-fr-placeholder="Votre prénom" data-ar-placeholder="اسمك الأول"
                     placeholder="Votre prénom">
            </div>
            <div class="form-group">
              <label data-fr="Nom" data-ar="الاسم العائلي">Nom <span>*</span></label>
              <input type="text" name="nom" class="form-control" required
                     value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                     data-fr-placeholder="Votre nom" data-ar-placeholder="اسمك العائلي"
                     placeholder="Votre nom">
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label data-fr="Email" data-ar="البريد الإلكتروني">Email <span>*</span></label>
              <input type="email" name="email" class="form-control" required
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                     data-fr-placeholder="votre@email.com" data-ar-placeholder="بريدك@الإلكتروني.com"
                     placeholder="votre@email.com">
            </div>
            <div class="form-group">
              <label data-fr="Téléphone" data-ar="رقم الهاتف">Téléphone</label>
              <input type="tel" name="telephone" class="form-control" dir="ltr"
                     value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>"
                     placeholder="0626 986 533">
            </div>
          </div>

          <div class="form-group">
            <label data-fr="Sujet" data-ar="الموضوع">Sujet</label>
            <select name="sujet" class="form-control">
              <option value="" data-fr="Choisir un sujet" data-ar="اختر موضوعًا">Choisir un sujet</option>
              <option value="Demande de devis" data-fr="Demande de devis" data-ar="طلب عرض أسعار">Demande de devis</option>
              <option value="Mariage" data-fr="Organisation mariage" data-ar="تنظيم حفل زفاف">Organisation mariage</option>
              <option value="Événement professionnel" data-fr="Événement professionnel" data-ar="حدث مهني">Événement professionnel</option>
              <option value="Autre" data-fr="Autre" data-ar="أخرى">Autre</option>
            </select>
          </div>

          <div class="form-group">
            <label data-fr="Message" data-ar="الرسالة">Message <span>*</span></label>
            <textarea name="message" class="form-control" rows="5" required id="msgArea"
                      data-fr-placeholder="Décrivez votre projet, le type d'événement, la date souhaitée, le nombre d'invités..."
                      data-ar-placeholder="صف مشروعك، نوع المناسبة، التاريخ المطلوب، عدد المدعوين..."
                      placeholder="Décrivez votre projet..." oninput="updateCount()"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            <div class="char-count" id="charCount">0 / 1000 caractères</div>
          </div>

          <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:14px">
            <i class="fas fa-paper-plane"></i>
            <span data-fr="Envoyer le message" data-ar="إرسال الرسالة">Envoyer le message</span>
          </button>

          <p style="font-size:.72rem;color:#555;text-align:center;margin-top:12px"
             data-fr="Ou contactez-nous directement sur WhatsApp pour une réponse immédiate."
             data-ar="أو تواصل معنا مباشرة على واتساب للحصول على رد فوري.">
            Ou contactez-nous directement sur <a href="https://wa.me/212626986533" target="_blank" style="color:#25D366">WhatsApp</a>.
          </p>
        </form>
      </div>

    </div>
  </div>
</section>

<section class="section-cta">
  <div class="cta-bg"></div>
  <div class="container">
    <div class="cta-content" data-aos="zoom-in">
      <h2 data-fr="Prêt à organiser votre<br>événement avec nous ?" data-ar="هل أنت مستعد لتنظيم<br>مناسبتك معنا؟" data-html>Prêt à organiser votre<br>événement avec nous ?</h2>
      <div class="cta-actions">
        <a href="reservation.php" class="btn-primary large">
          <i class="fas fa-file-invoice"></i>
          <span data-fr="Demander un devis gratuit" data-ar="طلب عرض مجاني">Demander un devis gratuit</span>
        </a>
        <a href="https://wa.me/212626986533" target="_blank" class="btn-whatsapp large">
          <i class="fab fa-whatsapp"></i> <span dir="ltr">0626 986 533</span>
        </a>
      </div>
    </div>
  </div>
</section>

<footer id="footer">
  <div class="footer-bottom"><div class="container">
    <p>© <?= date('Y') ?> Traiteur EL MOUSSAOUI — Errachidia, Maroc</p>
    <a href="../index.php" style="color:var(--text-muted);text-decoration:none;font-size:.8rem"
       data-fr="Retour à l'accueil" data-ar="العودة للرئيسية">Retour à l'accueil</a>
  </div></div>
</footer>

<a href="https://wa.me/212626986533" class="whatsapp-float" target="_blank"><i class="fab fa-whatsapp"></i></a>
<button class="scroll-top" id="scrollTop"><i class="fas fa-chevron-up"></i></button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="../js/main.js"></script>
<script src="../js/lang.js"></script>
<script>
function updateCount() {
  const ta = document.getElementById('msgArea');
  const cc = document.getElementById('charCount');
  if (ta && cc) cc.textContent = ta.value.length + ' / 1000 caractères';
}
</script>
<?php include_once __DIR__ . '/../includes/admin-bar.php'; ?>
</body>
</html>
