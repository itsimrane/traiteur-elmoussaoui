<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if (($_GET['confirm'] ?? '') !== 'FIXRES16') {
    die('Ajoute ?confirm=FIXRES16 à la fin de l\'URL pour lancer la correction.');
}

header('Content-Type: text/html; charset=utf-8');
echo "<pre style='background:#111;color:#0f0;padding:20px;font-family:monospace'>";

echo "=== Ligne id=11 (avant correction) ===\n\n";
print_r($pdo->query("SELECT id, reference FROM reservations WHERE id=11")->fetch());

echo "\n=== Vérification finale avant correction de id=16 ===\n\n";
$check = $pdo->query("SELECT id FROM reservations WHERE reference='RES-2026-0016' AND id != 16")->fetch();

if ($check) {
    echo "❌ ARRÊT : RES-2026-0016 est déjà utilisée par un autre id, aucune modification faite.\n";
} else {
    $pdo->prepare("UPDATE reservations SET reference='RES-2026-0016' WHERE id=16")->execute();
    echo "✅ id=16 corrigé : RES-2026-0011 → RES-2026-0016\n";
}

echo "\n=== État final de toute la table reservations ===\n\n";
print_r($pdo->query("SELECT id, reference FROM reservations ORDER BY id")->fetchAll());

echo "</pre>";
