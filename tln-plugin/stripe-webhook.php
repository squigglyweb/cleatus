<?php
/**
 * Stripe webhook endpoint for The Local NearBuy
 * Receives events, verifies signature, stores them in DB for reporting.
 * Uses settings from WP Options (test vs live mode).
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Load Stripe PHP library – assume it's installed via Composer in the plugin folder.
require_once __DIR__ . '/vendor/autoload.php';

// Include the settings helper to get our keys
require_once __DIR__ . '/tln-settings.php';

// Get webhook secret from settings
$endpoint_secret = tln_get_stripe_webhook_secret();

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (empty($endpoint_secret)) {
    // No secret defined – reject for safety.
    http_response_code(400);
    error_log('Stripe webhook secret not set. Configure in TLN Settings.');
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

// Ensure table exists
global $wpdb;
$table = $wpdb->prefix . 'tln_stripe_events';

if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table ) {
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        event_id varchar(255) NOT NULL,
        type varchar(255) NOT NULL,
        amount int(11) DEFAULT 0,
        currency varchar(10) DEFAULT '',
        customer_id varchar(255) DEFAULT '',
        subscription_id varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY event_id (event_id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// Extract useful fields
$object = $event->data->object ?? null;
$amount = $object->amount ?? 0;
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
        'created_at'      => current_time('mysql', 1)
    ],
    ['%s','%s','%d','%s','%s','%s','%s']
);

// Log successful receipt
error_log('Stripe webhook received: ' . $event->type);

// Respond 200 OK to Stripe.
http_response_code(200);
exit;