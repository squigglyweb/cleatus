<?php
/*
Plugin Name: TLN Simple Claim v1.3
Version: 1.3
*/

add_shortcode('claim_business', 'tln_claim_func');

function tln_claim_func() {
    global $wpdb;
    
    // Already claimed businesses
    $claimed = $wpdb->get_results("SELECT business_name FROM {$wpdb->prefix}tln_claims WHERE status='approved'");
    $claimed_names = array();
    foreach ($claimed as $c) {
        $claimed_names[] = strtolower($c->business_name);
    }
    
    if (!is_user_logged_in()) {
        return "<div style='padding:2rem;background:#f8f8f8;border-radius:12px;text-align:center;'><h3>Log In Required</h3><p>Please <a href='/wp-login.php'>log in</a> to claim your business.</p></div>";
    }
    
    if (isset($_POST['submit_claim'])) {
        $biz_name = sanitize_text_field($_POST['biz_name']);
        $pid = sanitize_text_field($_POST['pid']);
        
        $wpdb->insert($wpdb->prefix.'tln_claims', array(
            'business_name' => $biz_name,
            'place_id' => $pid,
            'user_id' => get_current_user_id(),
            'claimant_name' => sanitize_text_field($_POST['cname']),
            'claimant_phone' => sanitize_text_field($_POST['cphone']),
            'tos_agreed' => sanitize_text_field($_POST['csig']),
            'tos_signed_date' => date('Y-m-d'),
            'status' => 'approved'
        ));
        
        wp_mail('bryan@thelocalnearbuy.com', 'New Claim: '.$biz_name, $biz_name.' claimed by '.$_POST['cname']);
        
        return "<div style='padding:2rem;background:#d4edda;border-radius:8px;text-align:center;'><h3>Success!</h3><p>Your business is claimed. <a href='/dashboard/'>Go to Dashboard</a></p></div>";
    }
    
    // Build the form
    $out = "<div style='padding:2rem;background:#f8f8f8;border-radius:12px;max-width:600px;margin:0 auto;'>";
    $out .= "<h2 style='margin-top:0;'>Claim Your Business</h2>";
    $out .= "<p>Type your business name below to claim it:</p>";
    $out .= "<form method='post'>";
    $out .= "<p><label>Business Name *<br><input name='biz_name' required placeholder='e.g. Joe\'s Pizza' style='width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;'></label></p>";
    $out .= "<p><label>Your Name *<br><input name='cname' required style='width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;'></label></p>";
    $out .= "<p><label>Phone *<br><input name='cphone' required style='width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;'></label></p>";
    $out .= "<p><label><input type='checkbox' required> I agree to the <a href='/terms-of-service/' target='_blank'>Terms of Service</a></label></p>";
    $out .= "<p><label>Signature (type your name) *<br><input name='csig' required placeholder='Type your full name' style='width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;'></label></p>";
    $out .= "<button type='submit' name='submit_claim' style='background:#e63946;color:white;padding:1rem 2rem;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;'>Submit Claim</button>";
    $out .= "</form></div>";
    
    return $out;
}
