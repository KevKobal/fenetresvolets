<?php
declare(strict_types=1);

/**
 * Reçoit le panier depuis le navigateur, recalcule le total côté serveur (jamais
 * confiance au prix envoyé par le client), enregistre la commande en attente, puis
 * crée une session Stripe Checkout et renvoie son URL de redirection.
 *
 * Le paiement en lui-même (numéro de carte, 3-D Secure) se déroule entièrement sur
 * une page hébergée par Stripe — jamais sur ce serveur.
 */

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/pricing.php';

function json_error(int $status, string $message): never {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error(405, 'method_not_allowed');
}

$raw = file_get_contents('php://input', false, null, 0, 200_000); // 200 Ko max
$payload = json_decode($raw ?: '', true);
if (!is_array($payload) || !isset($payload['cart']) || !is_array($payload['cart'])) {
    json_error(400, 'invalid_payload');
}

$cart = $payload['cart'];
$flashRequested = !empty($payload['flash']);
$client = is_array($payload['client'] ?? null) ? $payload['client'] : [];

$totals = server_totals($cart, $flashRequested);
if (isset($totals['error'])) {
    json_error(400, $totals['error']);
}
if ($totals['total'] < 1) {
    json_error(400, 'zero_total');
}

// Configuration Stripe — absente tant que api/config.php n'a pas été déposé sur le
// serveur (jamais via git). On échoue explicitement plutôt que de planter en silence.
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    error_log('create-checkout-session: api/config.php manquant');
    json_error(503, 'payment_not_configured');
}
require $configFile;

// Référence de commande interne + enregistrement de la commande en attente (hors
// dossier public, jamais accessible par URL) — le webhook la complètera au paiement.
$orderId = bin2hex(random_bytes(12));
$order = [
    'orderId' => $orderId,
    'createdAt' => date('c'),
    'status' => 'pending',
    'totals' => $totals,
    'client' => [
        'nom' => (string) ($client['nom'] ?? ''),
        'prenom' => (string) ($client['prenom'] ?? ''),
        'adresse' => (string) ($client['adresse'] ?? ''),
        'cp' => (string) ($client['cp'] ?? ''),
        'ville' => (string) ($client['ville'] ?? ''),
        'tel' => (string) ($client['tel'] ?? ''),
        'mail' => (string) ($client['mail'] ?? ''),
    ],
];

$ordersDir = __DIR__ . '/../../orders';
if (!is_dir($ordersDir) || !is_writable($ordersDir)) {
    error_log('create-checkout-session: dossier orders/ introuvable ou non inscriptible');
    json_error(500, 'server_error');
}
file_put_contents($ordersDir . '/' . $orderId . '.json', json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Construction des line items Stripe (un par article + livraison/flash si applicable).
function line_item_name(array $line): string {
    if ($line['product'] === 'piece') {
        return $line['name'] ?: 'Pièce détachée';
    }
    if ($line['product'] === 'volet') {
        return 'Volet roulant sur mesure';
    }
    return 'Fenêtre sur mesure';
}

$lineItems = [];
foreach ($totals['lines'] as $line) {
    $lineItems[] = [
        'price_data' => [
            'currency' => 'eur',
            'unit_amount' => $line['unit'] * 100,
            'product_data' => ['name' => line_item_name($line)],
        ],
        'quantity' => $line['qty'],
    ];
}
if ($totals['livraison'] > 0) {
    $lineItems[] = [
        'price_data' => [
            'currency' => 'eur',
            'unit_amount' => $totals['livraison'] * 100,
            'product_data' => ['name' => 'Livraison à domicile'],
        ],
        'quantity' => 1,
    ];
}
if ($totals['flash'] > 0) {
    $lineItems[] = [
        'price_data' => [
            'currency' => 'eur',
            'unit_amount' => $totals['flash'] * 100,
            'product_data' => ['name' => 'Option Livraison Flash (+20%)'],
        ],
        'quantity' => 1,
    ];
}

$sessionParams = [
    'mode' => 'payment',
    'line_items' => $lineItems,
    'success_url' => SITE_URL . '/?paid=1&order=' . $orderId,
    'cancel_url' => SITE_URL . '/?canceled=1',
    'metadata' => ['order_id' => $orderId],
    'payment_method_types' => ['card'],
    'locale' => 'fr',
];
if (!empty($order['client']['mail'])) {
    $sessionParams['customer_email'] = $order['client']['mail'];
}

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($sessionParams),
    CURLOPT_USERPWD => STRIPE_SECRET_KEY . ':',
    CURLOPT_TIMEOUT => 15,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($response === false) {
    error_log('create-checkout-session: erreur cURL Stripe — ' . $curlErr);
    json_error(502, 'stripe_unreachable');
}

$stripeSession = json_decode($response, true);
if ($httpCode >= 400 || !isset($stripeSession['url'])) {
    error_log('create-checkout-session: erreur Stripe (' . $httpCode . ') — ' . $response);
    json_error(502, 'stripe_error');
}

echo json_encode(['url' => $stripeSession['url']]);
