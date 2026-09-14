<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

header('Content-Type: text/html; charset=utf-8');
echo "<pre style='background:#111;color:#0f0;padding:20px;font-family:monospace'>";

echo "=== Ligne(s) avec reference = DEV-2026-0015 ===\n\n";
$r = $pdo->query("SELECT id, reference, client_id, created_at FROM devis WHERE reference = 'DEV-2026-0015'")->fetchAll();
print_r($r);

echo "\n=== Ligne(s) avec id = 15 ===\n\n";
$r2 = $pdo->query("SELECT id, reference, client_id, created_at FROM devis WHERE id = 15")->fetchAll();
print_r($r2);

echo "\n=== AUTO_INCREMENT actuel de la table devis ===\n\n";
$r3 = $pdo->query("SHOW TABLE STATUS LIKE 'devis'")->fetch();
echo "Auto_increment: " . $r3['Auto_increment'] . "\n";

echo "\n=== MAX(id) actuel ===\n\n";
echo $pdo->query("SELECT MAX(id) FROM devis")->fetchColumn() . "\n";

echo "\n=== Les 5 derniers devis (par id) ===\n\n";
$r4 = $pdo->query("SELECT id, reference, created_at FROM devis ORDER BY id DESC LIMIT 5")->fetchAll();
print_r($r4);

echo "</pre>";
