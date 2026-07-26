<?php
declare(strict_types=1);

/**
 * Statut d'une commande, pour la page de confirmation après redirection Stripe.
 * L'identifiant de commande est un aléa 96 bits (random_bytes(12)) — impossible à
 * deviner — mais on ne renvoie volontairement aucune donnée personnelle du client
 * (nom, adresse, téléphone), seulement le récapitulatif produit/prix.
 */

header('Content-Type: application/json; charset=utf-8');

$orderId = preg_replace('/[^a-f0-9]/', '', (string) ($_GET['order'] ?? ''));
if ($orderId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'missing_order']);
    exit;
}

$file = __DIR__ . '/../../orders/' . $orderId . '.json';
if (!file_exists($file)) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found']);
    exit;
}

$order = json_decode(file_get_contents($file), true);
if (!is_array($order)) {
    http_response_code(500);
    echo json_encode(['error' => 'corrupt_order']);
    exit;
}

echo json_encode([
    'status' => $order['status'] ?? 'pending',
    'total' => $order['totals']['total'] ?? null,
    'weeks' => $order['totals']['weeks'] ?? null,
    'flashActive' => $order['totals']['flashActive'] ?? false,
    'lines' => $order['totals']['lines'] ?? [],
]);
