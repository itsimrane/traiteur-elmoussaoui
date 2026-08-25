<?php
/**
 * Configuration — Traiteur EL MOUSSAOUI
 * includes/config.php
 * Fonctionne en local (XAMPP) ET sur Railway automatiquement
 */

// ─── Détection environnement ────────────────────────
$isRailway = isset($_ENV['RAILWAY_ENVIRONMENT_NAME']) || isset($_ENV['MYSQLHOST']);

// ─── Base de données ────────────────────────────────
if ($isRailway) {
    // Variables Railway (injectées automatiquement)
    define('DB_HOST',    $_ENV['MYSQLHOST']     ?? 'localhost');
    define('DB_NAME',    $_ENV['MYSQLDATABASE'] ?? $_ENV['MYSQL_DATABASE'] ?? 'railway');
    define('DB_USER',    $_ENV['MYSQLUSER']     ?? 'root');
    define('DB_PASS',    $_ENV['MYSQLPASSWORD'] ?? $_ENV['MYSQL_ROOT_PASSWORD'] ?? '');
    define('DB_PORT',    $_ENV['MYSQLPORT']     ?? '3306');
    define('APP_ENV',    'production');
    define('APP_DEBUG',   false);
    define('SITE_URL',   'https://' . ($_ENV['RAILWAY_PUBLIC_DOMAIN'] ?? 'localhost'));
} else {
    // Local XAMPP
    define('DB_HOST',    'localhost');
    define('DB_NAME',    'db_traiteur_elmoussaoui');
    define('DB_USER',    'root');
    define('DB_PASS',    '');
    define('DB_PORT',    '3306');
    define('APP_ENV',    'development');
    define('APP_DEBUG',   true);
    define('SITE_URL',   'http://localhost/Traiteur_Elmoussaoui');
}

define('DB_CHARSET',  'utf8mb4');

// ─── Infos site ─────────────────────────────────────
define('SITE_NAME',   'Traiteur EL MOUSSAOUI');
define('SITE_EMAIL',  'contact@traiteur-elmoussaoui.ma');
define('SITE_TEL',    '0626986533');

// ─── Chemins ────────────────────────────────────────
define('ROOT_PATH',   realpath(__DIR__ . '/..'));
define('UPLOAD_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads');
define('UPLOAD_URL',  SITE_URL . '/assets/uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMG_TYPES', ['image/jpeg','image/png','image/webp','image/gif']);

// ─── Session & Sécurité ─────────────────────────────
define('SESSION_LIFETIME', 7200);
define('CSRF_SECRET',      'elmoussaoui_secret_2025_change_this');

// ─── Email ──────────────────────────────────────────
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('MAIL_FROM', 'noreply@traiteur-elmoussaoui.ma');
define('MAIL_NAME', 'Traiteur EL MOUSSAOUI');

// ─── Connexion PDO ──────────────────────────────────
try {
    $dsn = "mysql:host=" . DB_HOST
         . ";port="      . DB_PORT
         . ";dbname="    . DB_NAME
         . ";charset="   . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    if (APP_DEBUG) {
        die('<pre style="color:red;padding:20px">
<strong>Erreur BDD :</strong> ' . $e->getMessage() . '
<strong>Host :</strong> '    . DB_HOST . '
<strong>DB :</strong> '      . DB_NAME . '
<strong>User :</strong> '    . DB_USER . '
</pre>');
    } else {
        die('Erreur de connexion. Contactez l\'administrateur.');
    }
}

// ─── Session ────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_set_cookie_params(['lifetime' => SESSION_LIFETIME]);
    session_start();
}

// ─── Fonctions utilitaires ──────────────────────────
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateRef(string $prefix, PDO $pdo): string {
    $year  = date('Y');
    $table = match($prefix) {
        'RES' => 'reservations',
        'DEV' => 'devis_generes',
        'FAC' => 'factures',
        default => 'devis_generes'
    };
    $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    return $prefix . '-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

function isAdmin(): bool {
    return isset($_SESSION['user_role'])
        && in_array($_SESSION['user_role'], ['super_admin','admin','gestionnaire']);
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
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
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
