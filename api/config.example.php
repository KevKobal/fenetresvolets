<?php
declare(strict_types=1);

/**
 * MODÈLE de configuration — copier en api/config.php sur le serveur (JAMAIS dans
 * git : ce fichier réel contient des clés secrètes). Le fichier api/config.php
 * n'est jamais déployé automatiquement (absent du dépôt), donc jamais écrasé par
 * le déploiement GitHub Actions.
 */

// Clés API Stripe — Dashboard Stripe > Développeurs > Clés API.
// Commencer avec les clés de TEST (pk_test_… / sk_test_…) — aucun vrai paiement.
const STRIPE_SECRET_KEY = 'sk_test_...';
const STRIPE_PUBLISHABLE_KEY = 'pk_test_...';

// Secret de signature du webhook — créé après avoir enregistré l'URL du webhook
// (https://mesfenetresvolets.fr/api/webhook.php) dans Dashboard Stripe > Développeurs > Webhooks.
const STRIPE_WEBHOOK_SECRET = 'whsec_...';

// URL publique du site (utilisée pour les redirections Stripe Checkout).
const SITE_URL = 'https://mesfenetresvolets.fr';

// Adresse de notification interne (commandes reçues).
const ORDER_NOTIFY_EMAIL = 'contact@mesfenetresvolets.fr';
