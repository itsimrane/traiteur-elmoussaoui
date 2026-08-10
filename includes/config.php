<?php
/**
 * Configuration base de données — Traiteur EL MOUSSAOUI
 * Fichier : includes/config.php
 * À modifier selon votre environnement XAMPP
 */

// ─── Base de données ────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_NAME',     'db_traiteur_elmoussaoui');
define('DB_USER',     'root');         // Utilisateur XAMPP par défaut
define('DB_PASS',     '');             // Mot de passe XAMPP (vide par défaut)
define('DB_CHARSET',  'utf8mb4');

// ─── URL du site ────────────────────────────────────
define('SITE_URL',    'http://localhost/Traiteur_Elmoussaoui');
define('SITE_NAME',   'Traiteur EL MOUSSAOUI');
define('SITE_EMAIL',  'contact@traiteur-elmoussaoui.ma');
define('SITE_TEL',    '0626986533');

// ─── Configuration e-mail ───────────────────────────
define('SMTP_HOST',   'smtp.gmail.com');
define('SMTP_PORT',   587);
define('SMTP_USER',   '');   // Votre email Gmail
define('SMTP_PASS',   '');   // Mot de passe application Gmail
define('MAIL_FROM',   'noreply@traiteur-elmoussaoui.ma');
define('MAIL_NAME',   'Traiteur EL MOUSSAOUI');

// ─── Chemins ────────────────────────────────────────
define('ROOT_PATH',   realpath(__DIR__ . '/..'));
define('UPLOAD_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads');
define('UPLOAD_URL',  SITE_URL . '/assets/uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 Mo
define('ALLOWED_IMG_TYPES', ['image/jpeg','image/png','image/webp','image/gif']);

// ─── Session & Sécurité ────────────────────────────
define('SESSION_LIFETIME', 7200); // 2 heures
define('CSRF_SECRET',      'elmoussaoui_secret_2025_change_this');

// ─── Environnement ──────────────────────────────────
define('APP_ENV',   'development'); // 'development' | 'production'
define('APP_DEBUG', true);          // false en production

// ─── Connexion PDO ──────────────────────────────────
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    if (APP_DEBUG) {
        die('<pre style="color:red">Erreur BDD : ' . $e->getMessage() . '</pre>');
    } else {
        die('Une erreur de connexion est survenue. Contactez l\'administrateur.');
    }
}

// ─── Démarrage session ─────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_set_cookie_params(['lifetime' => SESSION_LIFETIME]);
    session_start();
}

// ─── Fonctions utilitaires ─────────────────────────
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateRef(string $prefix, PDO $pdo): string {
    $year = date('Y');
    $table = match($prefix) {
        'RES' => 'reservations',
        'DEV' => 'devis',
        'FAC' => 'factures',
        default => 'reservations'
    };
    $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
    $count = $stmt->fetchColumn();
    return $prefix . '-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

function isAdmin(): bool {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['super_admin','admin','gestionnaire']);
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
