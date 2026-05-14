<?php
/**
 * Plugin Name: TLN Claim Form
 * Version: 1.4
 */

if (!defined('ABSPATH')) exit;

add_shortcode('claim_business', 'tln_claim_func');

function tln_claim_func() {
    $biz = isset($_GET['biz']) ? sanitize_text_field($_GET['biz']) : '';
    
    if (isset($_POST['submit_claim'])) {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix.'tln_claims', array(
            'business_name' => sanitize_text_field($_POST['biz_name']),
            'place_id' => sanitize_text_field($_POST['pid']),
            'user_id' => is_user_logged_in() ? get_current_user_id() : 0,
            'claimant_name' => sanitize_text_field($_POST['cname']),
            'claimant_phone' => sanitize_text_field($_POST['cphone']),
            'tos_agreed' => sanitize_text_field($_POST['csig']),
            'tos_signed_date' => date('Y-m-d'),
            'status' => 'approved'
        ));
        
        wp_mail('bryan@thelocalnearbuy.com', 'New Claim: '.$_POST['biz_name'], $_POST['biz_name'].' claimed by '.$_POST['cname']);
        
        return '<div style="padding:2rem;background:#d4edda;border-radius:8px;text-align:center;"><h3>✅ Success!</h3><p>Your business has been claimed. <a href="/dashboard/">Go to Dashboard</a></p></div>';
    }
    
    $out = '<div style="padding:2rem;background:#f8f8f8;border-radius:12px;max-width:600px;margin:0 auto;">';
    $out .= '<h2 style="margin-top:0;">Claim Your Business</h2>';
    $out .= '<form method="post">';
    $out .= '<p><label>Business Name *<br><input name="biz_name" value="'.esc_attr($biz).'" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<input type="hidden" name="pid" value="'.(isset($_GET['pid']) ? esc_attr($_GET['pid']) : '').'">';
    $out .= '<p><label>Your Name *<br><input name="cname" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<p><label>Phone *<br><input name="cphone" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<p><label><input type="checkbox" required> I agree to the <a href="/terms-of-service/" target="_blank">Terms of Service</a></label></p>';
    $out .= '<p><label>Digital Signature (type your name) *<br><input name="csig" required placeholder="Type your full legal name" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<button type="submit" name="submit_claim" style="background:#e63946;color:white;padding:1rem 2rem;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">Submit Claim</button>';
    $out .= '</form></div>';
    
    return $out;
}
