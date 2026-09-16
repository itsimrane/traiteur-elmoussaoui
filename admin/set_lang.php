<?php
/**
 * Change la langue de l'espace admin ($_SESSION['admin_language'])
 * puis redirige vers la page d'où l'admin vient (?redirect=...) ou,
 * à défaut, vers l'en-tête Referer, ou vers le dashboard.
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$lang = $_GET['lang'] ?? 'fr';
if (!in_array($lang, ['fr', 'ar'], true)) $lang = 'fr';
$_SESSION['admin_language'] = $lang;

$redirect = $_GET['redirect'] ?? '';
if ($redirect && preg_match('#^[a-zA-Z0-9_\-]+\.php(\?[^\s]*)?$#', $redirect)) {
    header('Location: ' . $redirect);
    exit;
}

$referer = $_SERVER['HTTP_REFERER'] ?? '';
if ($referer && parse_url($referer, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')) {
    header('Location: ' . $referer);
    exit;
}

header('Location: dashboard.php');
exit;
