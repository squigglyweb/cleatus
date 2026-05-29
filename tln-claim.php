<?php
/**
 * Plugin Name: TLN Claim Form
 * Version: 1.5
 */

add_shortcode('claim_business', 'tln_claim_func');

function tln_claim_func() {
    $biz = isset($_GET['biz']) ? sanitize_text_field($_GET['biz']) : '';
    
    if (!is_user_logged_in()) {
        return '<div style="padding:2rem;background:#f8f8f8;border-radius:12px;text-align:center;"><h3>Log In Required</h3><p>Please <a href="/wp-login.php">log in</a> to claim your business.</p></div>';
    }
    
    if (isset($_POST['submit_claim'])) {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix.'tln_claims', array(
            'business_name' => sanitize_text_field($_POST['biz_name']),
            'place_id' => sanitize_text_field($_POST['pid']),
            'user_id' => get_current_user_id(),
            'claimant_name' => sanitize_text_field($_POST['cname']),
            'claimant_phone' => sanitize_text_field($_POST['cphone']),
            'tos_agreed' => sanitize_text_field($_POST['csig']),
            'tos_signed_date' => date('Y-m-d'),
            'status' => 'approved'
        ));
        
        wp_mail('bryan@thelocalnearbuy.com', 'New Claim: '.$_POST['biz_name'], $_POST['biz_name'].' claimed by '.$_POST['cname']);
        
        return '<div style="padding:2rem;background:#d4edda;border-radius:8px;text-align:center;max-width:600px;margin:0 auto;">
            <h3>Your Business Is Claimed!</h3>
            <p style="margin-bottom:1.5rem;">You can now manage your listing info. But here is the real opportunity:</p>
            <div style="background:white;border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;">
                <h4 style="color:#e63946;margin-top:0;">Reach Thousands of Local Households</h4>
                <p style="font-size:0.95rem;color:#666;">Run a postcard campaign with trackable QR codes. Every scan gives you a real lead with name, email, and phone — people who already want to visit.</p>
                <p style="font-size:0.9rem;"><strong>Campaigns from $250</strong> — includes 5,000-20,000 mailers + lead capture + QR tracking</p>
            </div>
            <a href="/campaign-pricing/" style="display:inline-block;padding:0.75rem 1.5rem;background:#e63946;color:white;text-decoration:none;border-radius:6px;font-weight:600;margin-right:0.5rem;">See Campaign Pricing</a>
            <a href="/dashboard/" style="display:inline-block;padding:0.75rem 1.5rem;background:#666;color:white;text-decoration:none;border-radius:6px;font-weight:600;">Go to Dashboard</a>
        </div>';
    }
    
    $out = '<div style="padding:2rem;background:#f8f8f8;border-radius:12px;max-width:600px;margin:0 auto;">';
    $out .= '<h2 style="margin-top:0;">Claim Your Business</h2>';
    $out .= '<form method="post">';
    $out .= '<p><label>Business Name *<br><input name="biz_name" value="'.esc_attr($biz).'" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<input type="hidden" name="pid" value="'.(isset($_GET['pid']) ? esc_attr($_GET['pid']) : '').'">';
    $out .= '<p><label>Your Name *<br><input name="cname" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<p><label>Phone *<br><input name="cphone" required style="width:100%;pagination;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<p><label><input type="checkbox" required> I agree to the <a href="/terms-of-service/" target="_blank">Terms of Service</a></label></p>';
    $out .= '<p><label>Digital Signature (type your name) *<br><input name="csig" required placeholder="Type your full legal name" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<button type="submit" name="submit_claim" style="background:#e63946;color:white;padding:1rem 2rem;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">Submit Claim</button>';
    $out .= '</form></div>';
    
    return $out;
}
