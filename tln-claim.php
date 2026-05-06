<?php
/**
 * Plugin Name: TLN Claim Business
 * Description: Simple claim business shortcode
 * Version: 1.0
 */

add_shortcode('claim_business', 'tln_simple_claim_form');

function tln_simple_claim_form() {
    $business = isset($_GET['business']) ? sanitize_text_field($_GET['business']) : '';
    $place_id = isset($_GET['place_id']) ? sanitize_text_field($_GET['place_id']) : '';
    
    if (!is_user_logged_in()) {
        return '<div style="padding:2rem;background:#f8f8f8;border-radius:12px;text-align:center;">
            <h3>Log in to Claim</h3>
            <p>Please <a href="' . wp_login_url() . '">log in</a> or <a href="' . wp_registration_url() . '">register</a> to claim this business.</p>
        </div>';
    }
    
    if (isset($_POST['tln_submit_claim'])) {
        global $wpdb;
        $user_id = get_current_user_id();
        
        $wpdb->insert($wpdb->prefix . 'tln_claims', array(
            'business_name' => sanitize_text_field($_POST['business_name']),
            'place_id' => sanitize_text_field($_POST['place_id']),
            'user_id' => $user_id,
            'claimant_name' => sanitize_text_field($_POST['claimant_name']),
            'claimant_phone' => sanitize_text_field($_POST['claimant_phone']),
            'tos_agreed' => sanitize_text_field($_POST['tos_signature']),
            'tos_signed_date' => date('Y-m-d'),
            'status' => 'approved'
        ));
        
        wp_mail('bryan@thelocalnearbuy.com', 'New Claim: ' . $_POST['business_name'], 'Business: ' . $_POST['business_name'] . ' by ' . $_POST['claimant_name']);
        
        return '<div style="padding:2rem;background:#d4edda;border-radius:8px;color:#155724;text-align:center;">
            <h3>You\'re All Set!</h3>
            <p>Your business page is now live.</p>
            <p><a href="/dashboard/">Go to Your Dashboard</a></p>
        </div>';
    }
    
    ob_start();
    echo '<div style="max-width:600px;margin:0 auto;padding:2rem;background:#f8f8f8;border-radius:12px;">';
    
    if ($business) {
        echo '<div style="background:#1a1a1a;color:white;padding:1rem;border-radius:8px;margin-bottom:1rem;">';
        echo 'You\'re claiming: <strong>' . esc_html($business) . '</strong>';
        echo '</div>';
    }
    
    echo '<form method="post">';
    echo '<input type="hidden" name="place_id" value="' . esc_attr($place_id) . '">';
    
    echo '<p><label style="display:block;font-weight:600;margin-bottom:0.5rem;">Business Name *</label>';
    echo '<input type="text" name="business_name" value="' . esc_attr($business) . '" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></p>';
    
    echo '<p><label style="display:block;font-weight:600;margin-bottom:0.5rem;">Your Name *</label>';
    echo '<input type="text" name="claimant_name" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></p>';
    
    echo '<p><label style="display:block;font-weight:600;margin-bottom:0.5rem;">Phone *</label>';
    echo '<input type="tel" name="claimant_phone" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></p>';
    
    echo '<div style="background:#fff;padding:1rem;border:1px solid #ddd;border-radius:8px;margin:1rem 0;max-height:200px;overflow-y:scroll;font-size:0.85rem;">';
    echo '<p><strong>Terms of Service</strong></p>';
    echo '<p>By checking the box below, you agree to our Terms of Service.</p>';
    echo '</div>';
    
    echo '<p><label><input type="checkbox" name="tos_checkbox" required> I agree to the Terms of Service *</label></p>';
    
    echo '<p><label style="display:block;font-weight:600;margin-bottom:0.5rem;">Type your name as digital signature *</label>';
    echo '<input type="text" name="tos_signature" required placeholder="Type your full name" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></p>';
    
    echo '<button type="submit" name="tln_submit_claim" value="1" style="background:#e63946;color:white;padding:1rem 2rem;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">Submit Claim</button>';
    echo '</form>';
    echo '</div>';
    
    return ob_get_clean();
}
