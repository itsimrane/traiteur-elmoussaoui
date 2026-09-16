<?php
/**
 * Système de traduction de l'espace admin (FR/AR).
 * Indépendant du système JS data-fr/data-ar du site public (qui bascule
 * sans recharger la page) : l'admin recharge déjà la page à chaque
 * navigation, donc une traduction 100% serveur via $_SESSION est plus
 * simple et plus fiable ici — pas de désynchronisation possible entre
 * ce que PHP a rendu et ce que le JS afficherait.
 *
 * Inclus une seule fois par includes/config.php, après session_start().
 */

if (!isset($_SESSION['admin_language']) || !in_array($_SESSION['admin_language'], ['fr', 'ar'], true)) {
    $_SESSION['admin_language'] = 'fr'; // Français par défaut
}

$GLOBALS['__admin_lang'] = $_SESSION['admin_language'];
$GLOBALS['__admin_dict'] = require __DIR__ . '/../lang/' . $GLOBALS['__admin_lang'] . '.php';

/**
 * Traduit une clé selon la langue admin courante.
 * Si la clé n'existe pas dans le dictionnaire, la clé elle-même est
 * retournée (visible et facile à repérer plutôt qu'un texte vide).
 */
function t(string $key): string
{
    return $GLOBALS['__admin_dict'][$key] ?? $key;
}

/** Code langue admin courant : 'fr' ou 'ar'. */
function adminLang(): string
{
    return $GLOBALS['__admin_lang'];
}

/** Sens d'écriture correspondant : 'rtl' ou 'ltr'. */
function adminDir(): string
{
    return adminLang() === 'ar' ? 'rtl' : 'ltr';
}

/** Classe CSS à ajouter sur .admin-layout pour activer le RTL admin. */
function adminRtlClass(): string
{
    return adminLang() === 'ar' ? 'admin-rtl' : '';
}

/**
 * Traduction ponctuelle inline (complète t()) : pour un texte spécifique
 * à une seule page (titre, message précis) qu'il est plus simple d'écrire
 * directement sur place plutôt que d'ajouter une clé au dictionnaire.
 * Usage : <?= tt('Gestion Clients', 'إدارة العملاء') ?>
 */
function tt(string $fr, string $ar): string
{
    return adminLang() === 'ar' ? $ar : $fr;
}
