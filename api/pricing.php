<?php
declare(strict_types=1);

/**
 * Tarification côté serveur — portage fidèle de la logique de index.html (priceFor,
 * totals) en PHP. Le prix envoyé par le navigateur n'est JAMAIS utilisé pour
 * encaisser : on ne fait confiance qu'à ce fichier pour calculer un montant.
 *
 * Toute modification de tarif dans index.html (JS) doit être répercutée ici.
 */

const LIVRAISON_SEUIL = 3000;
const FRAIS_PIECES = 9;
const FRAIS_1 = 49;
const FRAIS_PLUS = 109;
const FLASH_PCT = 0.20;
const DELAI_FLASH = 3;
const DELAI_MENUISERIE = 6;
const DELAI_PIECES = 3;

const VOLET_PRIX_MIN = 600;
const VOLET_PRIX_MAX = 990;
const VOLET_S_MIN = 0.36;
const VOLET_S_MAX = 6.25;
const VOLET_ORIENTABLE = 300;

const FEN_PRIX = [
    '1v' => [
        ['s' => 0.36, 'blanc' => 323,  'f1' => 380,  'f2' => 400],
        ['s' => 1.44, 'blanc' => 562,  'f1' => 663,  'f2' => 697],
        ['s' => 2.64, 'blanc' => 852,  'f1' => 998,  'f2' => 1046],
    ],
    '2v' => [
        ['s' => 1.00, 'blanc' => 705,  'f1' => 823,  'f2' => 862],
        ['s' => 1.96, 'blanc' => 924,  'f1' => 1080, 'f2' => 1132],
        ['s' => 3.96, 'blanc' => 1547, 'f1' => 1800, 'f2' => 1885],
    ],
];
const FEN_SEUIL = 100;
const FEN_GRILLE = 30;
const FEN_TRIPLE_M2 = 70;

// Pièces détachées Bubendorff — id => prix unitaire TTC (source unique : PIECES en JS)
const PIECES_PRIX = [
    'mot-r10' => 238, 'mot-r33' => 335, 'mot-mi2-25' => 221, 'mot-hy-or' => 309, 'mot-rolax' => 239,
    'kit-r10' => 312, 'kit-f10' => 312, 'kit-hybrid' => 369, 'kit-solaire' => 567,
    'cmd-horloge' => 246, 'cmd-tyxia' => 88, 'cmd-idiamant' => 164,
    'adapt-id20' => 31, 'adapt-support' => 11, 'adapt-cable' => 35, 'adapt-pv' => 42,
];

function clampi($v, int $min, int $max): int {
    $v = (int) $v;
    return max($min, min($max, $v));
}

// Interpolation linéaire (avec extrapolation bornée) — miroir de lerpFen() en JS.
function lerp_fen(array $anchors, string $key, float $s): float {
    $n = count($anchors);
    $first = $anchors[0];
    $last = $anchors[$n - 1];
    if ($s <= $first['s']) {
        $b = $anchors[1];
        return $first[$key] + ($b[$key] - $first[$key]) / ($b['s'] - $first['s']) * ($s - $first['s']);
    }
    for ($i = 0; $i < $n - 1; $i++) {
        $a = $anchors[$i];
        $b = $anchors[$i + 1];
        if ($s <= $b['s']) {
            return $a[$key] + ($b[$key] - $a[$key]) * ($s - $a['s']) / ($b['s'] - $a['s']);
        }
    }
    $a = $anchors[$n - 2];
    return $last[$key] + ($last[$key] - $a[$key]) / ($last['s'] - $a['s']) * ($s - $last['s']);
}

// Prix unitaire d'un volet ou d'une fenêtre selon sa config — miroir de priceFor() en JS.
// Les dimensions sont bornées aux mêmes limites que le configurateur (400-2400 / 400-3000 mm)
// pour empêcher un client de manipuler le prix via des dimensions aberrantes.
function price_for(string $product, array $cfg): int {
    $w = clampi($cfg['w'] ?? 1000, 400, 2400);
    $h = clampi($cfg['h'] ?? 1000, 400, 3000);

    if ($product === 'volet') {
        $surfV = ($w * $h) / 1e6;
        $t = max(0.0, min(1.0, ($surfV - VOLET_S_MIN) / (VOLET_S_MAX - VOLET_S_MIN)));
        $p = VOLET_PRIX_MIN + $t * (VOLET_PRIX_MAX - VOLET_PRIX_MIN);
        if (($cfg['lames'] ?? '') === 'orientable') {
            $p += VOLET_ORIENTABLE;
        }
        return (int) (round($p / 5) * 5);
    }

    if ($product === 'fenetre') {
        $surfF = ($w * $h) / 1e6;
        $type = (($cfg['ouverture'] ?? '') === '2v') ? '2v' : '1v';
        $key = (($cfg['couleur'] ?? '') === 'blanc') ? 'blanc' : ((($cfg['faces'] ?? '') === '1') ? 'f1' : 'f2');
        $p = lerp_fen(FEN_PRIX[$type], $key, $surfF);
        if (($cfg['ouverture'] ?? '') === 'fixe') {
            $p /= 1.5;
        }
        if (($cfg['vitrage'] ?? '') === 'triple') {
            $p += FEN_TRIPLE_M2 * max(0.9, $surfF);
        }
        if (($cfg['seuil'] ?? '') === 'oui') {
            $p += FEN_SEUIL;
        }
        if (($cfg['grille'] ?? '') === 'oui') {
            $p += FEN_GRILLE;
        }
        return max(0, (int) round($p));
    }

    return 0;
}

// Recalcule le prix unitaire d'un article de panier envoyé par le client. Retourne
// null si l'article est invalide (produit/pièce inconnue, quantité hors bornes).
function server_unit_price(array $item): ?int {
    $product = $item['product'] ?? '';
    $qty = (int) ($item['qty'] ?? 0);
    if ($qty < 1 || $qty > 50) {
        return null;
    }
    if ($product === 'piece') {
        $id = $item['pieceId'] ?? '';
        return PIECES_PRIX[$id] ?? null;
    }
    if ($product === 'volet' || $product === 'fenetre') {
        $cfg = $item['cfg'] ?? null;
        if (!is_array($cfg)) {
            return null;
        }
        return price_for($product, $cfg);
    }
    return null;
}

/**
 * Recalcule le panier complet + frais de port / option Flash côté serveur — miroir
 * de totals() en JS. $cartItems provient du client (produit, cfg, qty…) mais AUCUN
 * prix qu'il contient n'est réutilisé : tout est recalculé ici.
 *
 * Retourne ['error' => '...'] si le panier est invalide/vide, sinon le détail complet.
 */
function server_totals(array $cartItems, bool $flashRequested = false): array {
    $lines = [];
    $sub = 0;
    $menuiserieQty = 0;
    $hasPieces = false;

    foreach ($cartItems as $item) {
        $unit = server_unit_price($item);
        if ($unit === null) {
            return ['error' => 'invalid_item'];
        }
        $qty = (int) $item['qty'];
        $total = $unit * $qty;
        $lines[] = [
            'product' => $item['product'],
            'qty' => $qty,
            'unit' => $unit,
            'total' => $total,
            'cfg' => $item['cfg'] ?? null,
            'pieceId' => $item['pieceId'] ?? null,
            'name' => $item['name'] ?? null,
        ];
        $sub += $total;
        if ($item['product'] === 'volet' || $item['product'] === 'fenetre') {
            $menuiserieQty += $qty;
        }
        if ($item['product'] === 'piece') {
            $hasPieces = true;
        }
    }

    if (empty($lines)) {
        return ['error' => 'empty_cart'];
    }

    $livraison = 0;
    if ($sub > 0) {
        if ($menuiserieQty >= 2) {
            $livraison = FRAIS_PLUS;
        } elseif ($menuiserieQty === 1) {
            $livraison = FRAIS_1;
        } elseif ($hasPieces) {
            $livraison = FRAIS_PIECES;
        }
    }
    $gratuite = $sub >= LIVRAISON_SEUIL && $sub > 0;
    if ($gratuite) {
        $livraison = 0;
    }

    $baseWeeks = $menuiserieQty > 0 ? DELAI_MENUISERIE : ($hasPieces ? DELAI_PIECES : 0);
    $canFlash = $baseWeeks > DELAI_FLASH && $sub > 0;
    $flashActive = $canFlash && $flashRequested;
    $flash = $flashActive ? (int) round($sub * FLASH_PCT) : 0;
    $weeks = $flashActive ? DELAI_FLASH : $baseWeeks;

    $total = $sub + $livraison + $flash;

    return [
        'lines' => $lines,
        'sub' => $sub,
        'livraison' => $livraison,
        'flash' => $flash,
        'flashActive' => $flashActive,
        'total' => $total,
        'gratuite' => $gratuite,
        'weeks' => $weeks,
        'menuiserieQty' => $menuiserieQty,
        'hasPieces' => $hasPieces,
    ];
}
