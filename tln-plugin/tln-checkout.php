<?php
/**
 * TLN Stripe Checkout Endpoint
 * Creates Stripe Checkout sessions for membership upgrades
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function() {
    register_rest_route('tln/v1', '/create-checkout-session', array(
        'methods' => 'POST',
        'callback' => 'tln_create_checkout_session',
        'permission_callback' => '__return_true'
    ));
});

function tln_create_checkout_session(WP_RESTRequest $request) {
    // Load Stripe
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/tln-settings.php';
    
    $stripe_secret = tln_get_stripe_secret_key();
    $stripe_pub = tln_get_stripe_publishable_key();
    
    if (empty($stripe_secret)) {
        return new WP_Error('no_stripe', 'Stripe not configured', array('status' => 500));
    }
    
    \Stripe\Stripe::setApiKey($stripe_secret);
    
    $params = $request->get_json_body();
    $plan = $params['plan'] ?? 'pro';
    
    // Plan configuration - replace with your actual Stripe Price IDs
    $plans = array(
        'pro' => array(
            'price_id' => 'price_PRO_PRICE_ID',      // Replace with actual price ID
            'name' => 'Pro Member',
            'amount' => 9900
        ),
        'pro_plus' => array(
            'price_id' => 'price_PRO_PLUS_PRICE_ID', // Replace with actual price ID
            'name' => 'Pro+ Member',
            'amount' => 19900
        ),
        'sponsor' => array(
            'price_id' => 'price_SPONSOR_PRICE_ID',   // Replace with actual price ID
            'name' => 'Sponsor Member',
            'amount' => 34900
        )
    );
    
    $selected_plan = $plans[$plan] ?? $plans['pro'];
    
    try {
        $domain = get_site_url();
        
        $session = \Stripe\Checkout\Session::create(array(
            'payment_method_types' => array('card'),
            'line_items' => array(
                array(
                    'price' => $selected_plan['price_id'],
                    'quantity' => 1,
                )
            ),
            'mode' => 'subscription',
            'success_url' => $domain . '/member-dashboard?session_id={CHECKOUT_SESSION_ID}&plan=' . $plan,
            'cancel_url' => $domain . '/upgrade',
            'customer_email' => '',
            'metadata' => array(
                'plan' => $plan,
                'tln_version' => '1.0'
            )
        ));
        
        return array('sessionId' => $session->id);
        
    } catch (Exception $e) {
        error_log('TLN Checkout Error: ' . $e->getMessage());
        return new WP_Error('checkout_error', $e->getMessage(), array('status' => 500));
    }
}

// Shortcode for upgrade page fallback
add_shortcode('tln_checkout_links', function() {
    return '<p>Stripe Checkout Links - Configure in TLN Settings</p>';
});