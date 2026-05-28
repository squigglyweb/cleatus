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

// Process successful checkout and create user
add_action('template_redirect', function() {
    if (is_page('member-dashboard') && isset($_GET['session_id'])) {
        tln_process_successful_checkout($_GET['session_id'], $_GET['plan'] ?? 'pro');
    }
});

// Save profile REST endpoint
add_action('rest_api_init', function() {
    register_rest_route('tln/v1', '/save-profile', array(
        'methods' => 'POST',
        'callback' => 'tln_save_profile_handler',
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));
});

function tln_save_profile_handler(WP_RESTRequest $request) {
    $user_id = get_current_user_id();
    $data = $request->get_json_body();
    
    global $wpdb;
    $table = $wpdb->prefix . 'tln_claims';
    
    $update = [
        'notes' => sanitize_textarea_field($data['description'] ?? ''),
        'user_phone' => sanitize_text_field($data['phone'] ?? ''),
        'custom_offer' => sanitize_text_field($data['custom_offer'] ?? '')
    ];
    
    $result = $wpdb->update($table, $update, ['user_id' => $user_id]);
    
    if ($result !== false) {
        return ['success' => true, 'message' => 'Profile updated'];
    }
    return new WP_Error('save_failed', 'Could not save profile', ['status' => 500]);
}

// Save directory photo REST endpoint
add_action('rest_api_init', function() {
    register_rest_route('tln/v1', '/save-dirphoto', array(
        'methods' => 'POST',
        'callback' => 'tln_save_dirphoto_handler',
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));
});

function tln_save_dirphoto_handler(WP_RESTRequest $request) {
    $user_id = get_current_user_id();
    
    global $wpdb;
    $claim = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_claims WHERE user_id=%d AND status='approved'", $user_id));
    
    if (!$claim) {
        return new WP_Error('no_claim', 'No approved business claim found', ['status' => 400]);
    }
    
    // Get the business CPT post
    $biz_posts = get_posts(array('post_type'=>'tln_business','meta_key'=>'tln_place_id','meta_value'=>$claim->place_id,'posts_per_page'=>1));
    if(empty($biz_posts)) {
        return new WP_Error('no_business', 'Business profile not found', ['status' => 400]);
    }
    
    $profile_id = $biz_posts[0]->ID;
    
    // Handle file upload
    if (!empty($_FILES['tln_directory_image'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('tln_directory_image', $profile_id);
        
        if (is_wp_error($attachment_id)) {
            return ['success' => false, 'message' => $attachment_id->get_error_message()];
        }
        
        $image_url = wp_get_attachment_url($attachment_id);
        update_post_meta($profile_id, 'tln_directory_image', $image_url);
        
        return ['success' => true, 'image_url' => $image_url, 'message' => 'Directory photo updated'];
    }
    
    return ['success' => false, 'message' => 'No image uploaded'];
}

function tln_process_successful_checkout($session_id, $plan) {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/tln-settings.php';
    
    $stripe_secret = tln_get_stripe_secret_key();
    if (empty($stripe_secret)) return;
    
    \Stripe\Stripe::setApiKey($stripe_secret);
    
    try {
        $session = \Stripe\Checkout\Session::retrieve($session_id);
        $email = $session->customer_email ?? $session->customer_details->email;
        
        if (!$email) return;
        
        // Check if user exists, create if not
        $user = get_user_by('email', $email);
        if (!$user) {
            $username = sanitize_user(explode('@', $email)[0]);
            $user_id = wp_create_user($username, wp_generate_password(), $email);
            if (is_wp_error($user_id)) return;
            $user = get_user_by('id', $user_id);
        }
        
        // Add business role
        $user->set_role('editor');
        
        // Store the claim with tier
        global $wpdb;
        $table = $wpdb->prefix . 'tln_claims';
        
        // Check if claim exists for this email
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_email = %s",
            $email
        ));
        
        if ($existing) {
            $wpdb->update($table, 
                ['tier' => $plan, 'status' => 'approved', 'user_id' => $user->ID],
                ['id' => $existing->id]
            );
        } else {
            // Create new claim record
            $wpdb->insert($table, [
                'business_id' => 0,
                'user_name' => $user->display_name,
                'user_email' => $email,
                'user_phone' => '',
                'tier' => $plan,
                'status' => 'approved',
                'user_id' => $user->ID,
                'notes' => 'Upgraded via Stripe checkout'
            ]);
        }
        
        // Log the user in
        wp_set_auth_cookie($user->ID, true);
        wp_set_current_user($user->ID);
        
    } catch (Exception $e) {
        error_log('TLN Checkout Processing Error: ' . $e->getMessage());
    }
}