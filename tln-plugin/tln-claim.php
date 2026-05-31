<?php
/**
 * Plugin Name: TLN Claim Form
 * Version: 3.0 - Minimal working version
 */

if (!defined('ABSPATH')) exit;

add_shortcode('claim_business', 'tln_claim_func');

function tln_claim_func() {
    $biz = isset($_GET['biz']) ? sanitize_text_field($_GET['biz']) : '';
    
    if (isset($_POST['submit_claim'])) {
        $out = '<div style="padding:2rem;background:#fff3cd;border-radius:8px;text-align:center;max-width:600px;margin:0 auto;">';
        $out .= '<h3>Claim Submitted</h3>';
        $out .= '<p>Thank you for claiming <strong>'.esc_html(sanitize_text_field($_POST['biz_name'])).'</strong>.</p>';
        $out .= '<p>You will receive an email confirmation shortly.</p>';
        $out .= '</div>';
        return $out;
    }
    
    $out = '<div style="padding:2rem;background:#f8f8f8;border-radius:12px;max-width:600px;margin:0 auto;">';
    $out .= '<h2 style="margin-top:0;">Claim Your Business</h2>';
    $out .= '<form method="post">';
    $out .= '<p><label>Business Name *<br><input name="biz_name" value="'.esc_attr($biz).'" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<input type="hidden" name="pid" value="'.(isset($_GET['pid']) ? esc_attr($_GET['pid']) : '').'">';
    $out .= '<p><label>Your Name *<br><input name="cname" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<p><label>Email *<br><input name="cemail" type="email" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<p><label>Phone *<br><input name="cphone" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<p><label><input type="checkbox" required> I agree to the <a href="/terms-of-service/" target="_blank">Terms of Service</a></label></p>';
    $out .= '<p><label>Digital Signature *<br><input name="csig" required placeholder="Type your full legal name" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<button type="submit" name="submit_claim" style="background:#e63946;color:white;padding:1rem 2rem;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">Submit Claim</button>';
    $out .= '</form></div>';
    
    return $out;
}