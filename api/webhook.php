<?php
declare(strict_types=1);

/**
 * Webhook Stripe — appelé par Stripe (serveur à serveur), jamais par le navigateur
 * du client. Confirme qu'un paiement a réellement été effectué avant de marquer une
 * commande comme payée. La signature est vérifiée manuellement (HMAC-SHA256, schéma
 * documenté par Stripe) pour éviter toute dépendance au SDK sur cet hébergement.
 */

header('Content-Type: application/json; charset=utf-8');

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(503);
    exit;
}
require $configFile;

$payload = file_get_contents('php://input', false, null, 0, 500_000);
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

function verify_stripe_signature(string $payload, string $sigHeader, string $secret, int $toleranceSeconds = 300): bool {
    $parts = [];
    foreach (explode(',', $sigHeader) as $kv) {
        $pair = explode('=', $kv, 2);
        if (count($pair) === 2) {
            $parts[$pair[0]] = $pair[1];
        }
    }
    if (!isset($parts['t'], $parts['v1'])) {
        return false;
    }
    if (abs(time() - (int) $parts['t']) > $toleranceSeconds) {
        return false; // rejoue trop ancien
    }
    $expected = hash_hmac('sha256', $parts['t'] . '.' . $payload, $secret);
    return hash_equals($expected, $parts['v1']);
}

if (!verify_stripe_signature($payload ?: '', $sigHeader, STRIPE_WEBHOOK_SECRET)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_signature']);
    exit;
}

$event = json_decode($payload, true);
if (!is_array($event) || !isset($event['type'])) {
    http_response_code(400);
    exit;
}

if ($event['type'] === 'checkout.session.completed') {
    $session = $event['data']['object'] ?? [];
    $orderId = $session['metadata']['order_id'] ?? '';
    $orderId = preg_replace('/[^a-f0-9]/', '', (string) $orderId); // whitelist : hex uniquement

    if ($orderId !== '') {
        $ordersDir = __DIR__ . '/../../orders';
        $orderFile = $ordersDir . '/' . $orderId . '.json';

        if (file_exists($orderFile)) {
            $order = json_decode(file_get_contents($orderFile), true);
            if (is_array($order) && ($order['status'] ?? '') === 'pending') {
                $order['status'] = 'paid';
                $order['paidAt'] = date('c');
                $order['stripeSessionId'] = $session['id'] ?? null;
                file_put_contents($orderFile, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                send_order_notification($order);
            }
        } else {
            error_log('webhook: commande introuvable — ' . $orderId);
        }
    }
}

http_response_code(200);
echo json_encode(['received' => true]);

function send_order_notification(array $order): void {
    $total = number_format($order['totals']['total'] ?? 0, 0, ',', ' ') . ' €';
    $client = $order['client'] ?? [];
    $nom = trim(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? ''));

    $lines = [];
    foreach ($order['totals']['lines'] ?? [] as $l) {
        $lines[] = '- ' . ($l['product'] ?? '?') . ' × ' . ($l['qty'] ?? 1) . ' — ' . number_format($l['total'] ?? 0, 0, ',', ' ') . ' €';
    }

    $body = "Nouvelle commande payée — {$order['orderId']}\n\n"
        . "Client : {$nom}\n"
        . "E-mail : " . ($client['mail'] ?? '') . "\n"
        . "Téléphone : " . ($client['tel'] ?? '') . "\n"
        . "Adresse : " . ($client['adresse'] ?? '') . ' ' . ($client['cp'] ?? '') . ' ' . ($client['ville'] ?? '') . "\n\n"
        . "Articles :\n" . implode("\n", $lines) . "\n\n"
        . "Total payé : {$total}\n";

    $headers = "From: mesfenetresvolets.fr <" . ORDER_NOTIFY_EMAIL . ">\r\nContent-Type: text/plain; charset=UTF-8";
    @mail(ORDER_NOTIFY_EMAIL, 'Nouvelle commande — ' . $order['orderId'], $body, $headers);

    if (!empty($client['mail'])) {
        $clientBody = "Bonjour,\n\nVotre commande sur mesfenetresvolets.fr est confirmée.\n\n"
            . "Référence : {$order['orderId']}\n"
            . "Total payé : {$total}\n\n"
            . "Nous revenons vers vous rapidement.\n\nL'Atelier Oxygen Ouvertures";
        $clientHeaders = "From: mesfenetresvolets.fr <" . ORDER_NOTIFY_EMAIL . ">\r\nContent-Type: text/plain; charset=UTF-8";
        @mail($client['mail'], 'Confirmation de commande — mesfenetresvolets.fr', $clientBody, $clientHeaders);
    }
}
