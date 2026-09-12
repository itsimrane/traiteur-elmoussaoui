<?php
/**
 * OUTIL UNIQUE — à supprimer après usage.
 * Crée ou met à jour le compte administrateur de Younes Elmoussaoui
 * avec un mot de passe hashé (jamais stocké en clair).
 */
require_once __DIR__ . '/../includes/config.php';

if (($_GET['confirm'] ?? '') !== 'YOUNES2026') {
    die('Ajoute ?confirm=YOUNES2026 à la fin de l\'URL pour accéder à ce formulaire.');
}

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = sanitize($_POST['prenom'] ?? '');
    $nom    = sanitize($_POST['nom'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pw1    = $_POST['password'] ?? '';
    $pw2    = $_POST['password_confirm'] ?? '';

    if (!$prenom || !$nom || !$email) {
        $msg = 'Merci de remplir le prénom, nom et email.'; $msgType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Email invalide.'; $msgType = 'error';
    } elseif (strlen($pw1) < 8) {
        $msg = 'Le mot de passe doit faire au moins 8 caractères.'; $msgType = 'error';
    } elseif ($pw1 !== $pw2) {
        $msg = 'Les deux mots de passe ne correspondent pas.'; $msgType = 'error';
    } else {
        try {
            $hash = password_hash($pw1, PASSWORD_BCRYPT);

            // super_admin = role_id 1
            $existing = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $existing->execute([$email]);
            $row = $existing->fetch();

            if ($row) {
                $pdo->prepare("
                    UPDATE users SET nom=?, prenom=?, password=?, role_id=1, actif=1, deleted_at=NULL
                    WHERE id=?
                ")->execute([$nom, $prenom, $hash, $row['id']]);
                $msg = "Compte existant mis à jour avec succès (id #{$row['id']}).";
            } else {
                $pdo->prepare("
                    INSERT INTO users (role_id, nom, prenom, email, password, actif, created_at, updated_at)
                    VALUES (1, ?, ?, ?, ?, 1, NOW(), NOW())
                ")->execute([$nom, $prenom, $email, $hash]);
                $msg = 'Nouveau compte Super Administrateur créé avec succès.';
            }
            $msgType = 'success';
        } catch (Exception $e) {
            $msg = 'Erreur : ' . $e->getMessage(); $msgType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Créer le compte admin — Outil unique</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  body{background:#0a0a0f;color:#eee;font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}
  .box{background:#151318;border:1px solid #333;border-radius:14px;padding:32px;max-width:420px;width:100%}
  h1{color:#D4AF37;font-size:1.1rem;margin-bottom:6px}
  p.sub{color:#888;font-size:.82rem;margin-bottom:20px}
  label{display:block;font-size:.75rem;color:#999;text-transform:uppercase;margin-bottom:5px;margin-top:14px}
  input{width:100%;box-sizing:border-box;background:#1e1c22;border:1px solid #333;border-radius:8px;padding:10px 14px;color:#fff;font-size:.9rem}
  button{width:100%;margin-top:20px;background:#D4AF37;color:#111;border:none;border-radius:8px;padding:12px;font-weight:700;cursor:pointer;font-size:.9rem}
  .alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem}
  .alert.success{background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.3);color:#66BB6A}
  .alert.error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#EF5350}
  .warn{margin-top:20px;font-size:.78rem;color:#EF5350;text-align:center}
</style>
</head>
<body>
<div class="box">
  <h1><i class="fas fa-user-shield"></i> Créer le compte Super Admin</h1>
  <p class="sub">Le mot de passe est immédiatement hashé (bcrypt) et n'est jamais stocké en clair.</p>

  <?php if ($msg): ?>
  <div class="alert <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form method="POST">
    <label>Prénom</label>
    <input type="text" name="prenom" value="Younes" required>
    <label>Nom</label>
    <input type="text" name="nom" value="Elmoussaoui" required>
    <label>Email</label>
    <input type="email" name="email" placeholder="younes@traiteur-elmoussaoui.ma" required>
    <label>Mot de passe (8 caractères minimum)</label>
    <input type="password" name="password" required minlength="8">
    <label>Confirmer le mot de passe</label>
    <input type="password" name="password_confirm" required minlength="8">
    <button type="submit"><i class="fas fa-save"></i> Créer / Mettre à jour le compte</button>
  </form>

  <p class="warn">⚠️ Supprime ce fichier (admin/setup_admin_account.php) juste après avoir créé le compte.</p>
</div>
</body>
</html>
