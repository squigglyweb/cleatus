<?php
/**
 * Stripe webhook endpoint for The Local NearBuy
 * Receives events, verifies signature, stores them in DB for reporting.
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Load Stripe PHP library – assume it's installed via Composer in the plugin folder.
require_once __DIR__ . '/vendor/autoload.php';

// Load the webhook secret – you can set it in wp-config or fallback to a constant.
if (defined('TLN_STRIPE_WEBHOOK_SECRET')) {
    $endpoint_secret = TLN_STRIPE_WEBHOOK_SECRET;
} else {
    // As a fallback, read from a file you can edit manually.
    $endpoint_secret = '';
}

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (empty($endpoint_secret)) {
    // No secret defined – reject for safety.
    http_response_code(400);
    error_log('Stripe webhook secret not set');
    exit();
}

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $endpoint_secret
    );
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    error_log('Invalid webhook payload: ' . $e->getMessage());
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    error_log('Invalid webhook signature: ' . $e->getMessage());
    exit();
}

global $wpdb;
$table = $wpdb->prefix . 'tln_stripe_events';

// Extract useful fields – not every event has all of them.
$object = $event->data->object ?? null;
$amount = $object->amount ?? 0; // amount in cents for most events
$currency = $object->currency ?? '';
$customer_id = $object->customer ?? '';
$subscription_id = $object->subscription ?? '';

$wpdb->insert(
    $table,
    [
        'event_id'        => $event->id,
        'type'            => $event->type,
        'amount'          => $amount,
        'currency'        => $currency,
        'customer_id'     => $customer_id,
        'subscription_id' => $subscription_id,
        'created_at'      => current_time('mysql', true)
    ],
    ['%s','%s','%d','%s','%s','%s','%s']
);

// Respond 200 OK to Stripe.
http_response_code(200);
?>