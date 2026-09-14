<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

header('Content-Type: text/html; charset=utf-8');
echo "<pre style='background:#111;color:#0f0;padding:20px;font-family:monospace'>";

echo "=== Ligne id=16 ===\n\n";
print_r($pdo->query("SELECT id, reference, client_id, created_at FROM reservations WHERE id=16")->fetch());

echo "\n=== Ligne(s) avec reference = RES-2026-0016 ===\n\n";
print_r($pdo->query("SELECT id, reference, client_id, created_at FROM reservations WHERE reference='RES-2026-0016'")->fetchAll());

echo "</pre>";
