<?php
/**
 * Page : admin/login.php
 * Traiteur EL MOUSSAOUI — Authentification réelle via session PHP
 */
require_once __DIR__ . '/../includes/config.php';

// Si déjà connecté, rediriger vers dashboard
if (isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pw    = $_POST['password'] ?? '';

    // Identifiants valides (en production : vérifier en BDD avec password_verify)
    $valid_email = 'admin@traiteur-elmoussaoui.ma';
    $valid_pw    = 'Admin@2025';

    if ($email === $valid_email && $pw === $valid_pw) {
        // Créer la session admin
        $_SESSION['admin_id']    = 1;
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_nom']   = 'Administrateur';
        $_SESSION['user_role']   = 'admin';
        $_SESSION['login_time']  = time();

        // Régénérer l'ID de session pour sécurité
        session_regenerate_id(true);

        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Email ou mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion Admin — Traiteur EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { background: linear-gradient(135deg, #050505 0%, #0D0900 50%, #050505 100%); display:flex; align-items:center; justify-content:center; min-height:100vh; }
    .login-card { position:relative; overflow:hidden; }
    .login-card::before {
      content:''; position:absolute; top:-2px; left:-2px; right:-2px; bottom:-2px;
      background: conic-gradient(from 0deg, var(--gold), transparent 60%, var(--gold) 100%);
      border-radius:22px; z-index:-1; animation: rotateBorder 4s linear infinite;
    }
    .login-card::after {
      content:''; position:absolute; inset:1px; background:var(--dark-card); border-radius:20px; z-index:-1;
    }
    @keyframes rotateBorder { to { transform: rotate(360deg); } }
    .bg-pattern {
      position:fixed; inset:0; z-index:0;
      background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23D4AF37' fill-opacity='0.03'%3E%3Cpath d='M30 0l8 8-8 8-8-8 8-8zm0 44l8 8-8 8-8-8 8-8zM0 30l8-8 8 8-8 8-8-8zm44 0l8-8 8 8-8 8-8-8z'/%3E%3C/g%3E%3C/svg%3E");
      pointer-events:none;
    }
    .login-wrap { position:relative; z-index:1; width:100%; max-width:440px; padding:20px; }
    .input-icon { position:relative; }
    .input-icon i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.9rem; }
    .input-icon .form-control { padding-left:40px; }
    .input-icon .toggle-pw { position:absolute; right:14px; top:50%; transform:translateY(-50%); cursor:pointer; color:var(--text-muted); background:none; border:none; font-size:0.9rem; }
    .toggle-pw:hover { color:var(--gold); }
    .remember-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .remember-row label { display:flex; align-items:center; gap:8px; font-size:0.82rem; color:var(--text-muted); cursor:pointer; }
    .remember-row input[type="checkbox"] { accent-color:var(--gold); }
    .btn-login { width:100%; padding:14px; font-size:0.95rem; font-weight:600; letter-spacing:0.5px; }
    .demo-hint { margin-top:16px; padding:12px 16px; background:rgba(212,175,55,0.06); border:1px dashed var(--border); border-radius:10px; font-size:0.78rem; color:var(--text-muted); }
    .demo-hint strong { color:var(--gold); }
    .login-error { background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:10px; padding:12px 16px; margin-bottom:16px; font-size:0.84rem; color:#F87171; display:flex; align-items:center; gap:8px; }
    .glow-orb { position:fixed; width:400px; height:400px; border-radius:50%; background:radial-gradient(circle, rgba(212,175,55,0.06) 0%, transparent 70%); pointer-events:none; }
    .glow-orb.top { top:-100px; right:-100px; }
    .glow-orb.bottom { bottom:-100px; left:-100px; }
  </style>
</head>
<body>
<div class="bg-pattern"></div>
<div class="glow-orb top"></div>
<div class="glow-orb bottom"></div>

<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-text" style="display:inline-flex;flex-direction:column;align-items:center">
        <span class="logo-traiteur">TRAITEUR</span>
        <span class="logo-name">EL MOUSSAOUI</span>
        <span class="logo-sub">أفراح المساوي</span>
      </div>
      <p>Espace Administrateur — Panneau de contrôle</p>
    </div>

    <h2 class="login-title">Connexion</h2>
    <p class="login-sub">Accédez à votre tableau de bord</p>

    <?php if ($error): ?>
    <div class="login-error">
      <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label">Adresse email</label>
        <div class="input-icon">
          <i class="fas fa-envelope"></i>
          <input type="email" name="email" class="form-control" id="loginEmail"
                 placeholder="admin@traiteur-elmoussaoui.ma"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 required autofocus>
        </div>
      </div>

      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">Mot de passe</label>
        <div class="input-icon">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" class="form-control" id="loginPassword"
                 placeholder="••••••••" required>
          <button type="button" class="toggle-pw" id="togglePw">
            <i class="fas fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <div class="remember-row">
        <label><input type="checkbox" name="remember"> Se souvenir de moi</label>
      </div>

      <button type="submit" class="btn-primary btn-login">
        <i class="fas fa-sign-in-alt"></i> Se connecter
      </button>
    </form>

    <div class="demo-hint">
      <i class="fas fa-info-circle" style="color:var(--gold);margin-right:6px"></i>
      <span>Email : <strong>admin@traiteur-elmoussaoui.ma</strong> · MDP : <strong>Admin@2025</strong></span>
    </div>

    <div class="login-footer" style="margin-top:20px;text-align:center">
      <a href="../index.php" style="color:var(--text-muted);font-size:.82rem">
        <i class="fas fa-arrow-left"></i> Retour au site public
      </a>
    </div>
  </div>
</div>

<script>
const togglePw = document.getElementById('togglePw');
const pwInput  = document.getElementById('loginPassword');
const eyeIcon  = document.getElementById('eyeIcon');
togglePw.addEventListener('click', () => {
  const isText = pwInput.type === 'text';
  pwInput.type = isText ? 'password' : 'text';
  eyeIcon.className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
});
</script>
</body>
</html>