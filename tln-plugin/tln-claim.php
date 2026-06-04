<?php
/**
 * Plugin Name: TLN Claim Form
 * Version: 3.8 - Added email notification, text opt-in, UI fixes
 */

if (!defined('ABSPATH')) exit;

add_shortcode('claim_business', 'tln_claim_func');

function tln_claim_func() {
    $biz = isset($_GET['biz']) ? sanitize_text_field($_GET['biz']) : '';
    
    if (isset($_POST['submit_claim'])) {
        $biz_name = sanitize_text_field($_POST['biz_name']);
        $cname = sanitize_text_field($_POST['cname']);
        $cemail = sanitize_email($_POST['cemail']);
        $cphone = sanitize_text_field($_POST['cphone']);
        $csig = sanitize_text_field($_POST['csig']);
        $text_optin = isset($_POST['text_optin']) ? 1 : 0;
        
        // Save to claims table
        global $wpdb;
        $table = $wpdb->prefix . 'tln_claims';
        
        $wpdb->insert($table, [
            'business_name' => $biz_name,
            'claimant_name' => $cname,
            'claimant_email' => $cemail,
            'claimant_phone' => $cphone,
            'signature' => $csig,
            'text_optin' => $text_optin,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ]);
        
        // Send email to business owner about their claim
        $subject = 'Your Claim for ' . $biz_name . ' Has Been Submitted';
        $message = "Hello,\n\n";
        $message .= "Great work! You've successfully submitted your claim for \"$biz_name\" on The Local NearBuy.\n\n";
        $message .= "WHAT'S NEXT:\n";
        $message .= "Your claim is now being reviewed by our team. You'll receive another email within 24-48 hours once your claim is approved.\n\n";
        $message .= "Once approved, you'll be able to:\n";
        $message .= "- Update your business profile\n";
        $message .= "- Request advertising campaigns\n";
        $message .= "- Access your business dashboard\n\n";
        $message .= "Questions? Reply to this email and we'd be happy to help.\n\n";
        $message .= "Thanks,\nThe Local NearBuy Team";
        
        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        wp_mail($cemail, $subject, $message, $headers);
        
        $out = '<div style="padding:2rem;background:#fff3cd;border-radius:8px;text-align:center;max-width:600px;margin:0 auto;">';
        $out .= '<h3>Claim Submitted</h3>';
        $out .= '<p>Thank you for claiming <strong>'.esc_html($biz_name).'</strong>.</p>';
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
    $out .= '<p><label><input type="checkbox" required> I agree to the <a href="/terms-of-service/" target="_blank" style="color:#e63946;text-decoration:underline;">Terms of Service</a></label></p>';
    $out .= '<p><label><input type="checkbox" name="text_optin"> Yes, I want to receive text messages about my claim and campaign updates</label></p>';
    $out .= '<p><label>Digital Signature *<br><input name="csig" required placeholder="Type Full Name" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<div style="margin-top:1.5rem;"><button type="submit" name="submit_claim" style="background:#e63946;color:white;padding:1rem 2rem;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;width:100%;">SUBMIT</button></div>';
    $out .= '</form></div>';
    
    return $out;
}