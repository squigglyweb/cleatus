<?php
/**
 * Plugin Name: TLN Claim Form
 * Version: 2.0 - Auto-create user account
 */

if (!defined('ABSPATH')) exit;

add_shortcode('claim_business', 'tln_claim_func');

function tln_claim_func() {
    $biz = isset($_GET['biz']) ? sanitize_text_field($_GET['biz']) : '';
    
    if (isset($_POST['submit_claim'])) {
        global $wpdb;
        
        $business_name = sanitize_text_field($_POST['biz_name']);
        $claimant_name = sanitize_text_field($_POST['cname']);
        $claimant_email = sanitize_email($_POST['cemail']);
        $claimant_phone = sanitize_text_field($_POST['cphone']);
        $place_id = sanitize_text_field($_POST['pid']);
        $password = wp_generate_password(12, false);
        
        // Check if user already exists
        $user_id = email_exists($claimant_email);
        
        if (!$user_id) {
            // Create new user
            $user_id = wp_insert_user(array(
                'user_login' => $claimant_email,
                'user_pass' => $password,
                'user_email' => $claimant_email,
                'display_name' => $claimant_name,
                'role' => 'subscriber'
            ));
            
            if (is_wp_error($user_id)) {
                return '<div style="padding:2rem;background:#f8d7da;border-radius:8px;"><h3>❌ Error</h3><p>'.esc_html($user_id->get_error_message()).'</p></div>';
            }
        }
        
        // Save claim to database
        $wpdb->insert($wpdb->prefix.'tln_claims', array(
            'business_name' => $business_name,
            'place_id' => $place_id,
            'user_id' => $user_id,
            'claimant_name' => $claimant_name,
            'claimant_email' => $claimant_email,
            'claimant_phone' => $claimant_phone,
            'tier' => 'free',
            'tos_agreed' => sanitize_text_field($_POST['csig']),
            'tos_signed_date' => date('Y-m-d'),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ));
        
        $claim_id = $wpdb->insert_id;
        
        // Create or update business profile page
        $post_slug = sanitize_title($business_name) . '-' . sanitize_title($place_id);
        $existing = get_page_by_path($post_slug, OBJECT, 'page');
        
        if (!$existing) {
            $profile_id = wp_insert_post(array(
                'post_title' => $business_name,
                'post_name' => $post_slug,
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_content' => '[tln_business_profile]'
            ));
        } else {
            $profile_id = $existing->ID;
        }
        
        // Link claim to profile
        update_post_meta($profile_id, 'tln_claim_id', $claim_id);
        update_post_meta($profile_id, 'tln_tier', 'free');
        update_post_meta($profile_id, 'tln_user_id', $user_id);
        
        // Send pending approval email
        $message = "Hi $claimant_name,\n\n";
        $message .= "We've received your claim for $business_name.\n\n";
        $message .= "Your claim is currently pending review. You'll receive an email once it's approved.\n\n";
        $message .= "The Local NearBuy Team";
        
        wp_mail($claimant_email, "Claim Received - $business_name", $message);
        
        // Notify Bryan of new pending claim
        wp_mail('bryan@thelocalnearbuy.com', 'Pending Claim: '.$business_name, "New claim requires approval:\n\nBusiness: $business_name\nClaimant: $claimant_name\nEmail: $claimant_email\nPhone: $claimant_phone\n\nGo to admin dashboard to approve.");
        
        // Show pending message
        $out = '<div style="padding:2rem;background:#fff3cd;border-radius:8px;text-align:center;max-width:600px;margin:0 auto;">
            <h3>Claim Submitted - Pending Approval</h3>
            <p style="margin-bottom:1rem;">Thank you for claiming <strong>'.esc_html($business_name).'</strong>.</p>
            <p style="margin-bottom:1.5rem;">Your claim is now pending review. You'll receive an email once it's approved.</p>
            <p style="font-size:0.9rem;color:#666;">Questions? Email bryan@thelocalnearbuy.com</p>
        </div>';
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
    $out .= '<div style="max-height:200px;overflow-y:scroll;border:1px solid #ddd;border-radius:6px;padding:1rem;background:#fff;font-size:0.85rem;line-height:1.6;margin-bottom:1rem;" id="tln-tos-box">';
    $out .= '<h3 style="font-size:1rem;margin-bottom:0.75rem;">Terms of Service</h3>';
    $out .= '<p><strong>Last Updated: May 2026</strong></p>';
    $out .= '<p>By claiming this business listing on The Local NearBuy, you agree to the following terms:</p>';
    $out .= '<p><strong>1. Accuracy of Information</strong><br>You agree to provide accurate and complete information about your business. You warrant that you are the authorized representative of the business being claimed.</p>';
    $out .= '<p><strong>2. Business Authorization</strong><br>You represent and warrant that you have the authority to bind the business to this Agreement.</p>';
    $out .= '<p><strong>3. Content Guidelines</strong><br>You agree to keep your business information current and accurate. All content you submit must be lawful, appropriate, and not misleading.</p>';
    $out .= '<p><strong>4. Privacy</strong><br>You consent to the collection and use of your business information for the operation of The Local NearBuy platform.</p>';
    $out .= '<p><strong>5. Advertising Terms</strong><br>If you advertise with us, you agree to our advertising policies and payment terms.</p>';
    $out .= '<p><strong>6. Disclaimer</strong><br>The Local NearBuy provides listings as-is. We do not guarantee any specific results from advertising.</p>';
    $out .= '<p><strong>7. Termination</strong><br>We reserve the right to remove any listing that violates these terms.</p>';
    $out .= '<p><strong>8. Contact</strong><br>Questions? Email bryan@thelocalnearbuy.com</p>';
    $out .= '</div>';
    $out .= '<p><label><input type="checkbox" id="tln-tos-check" required disabled> I have read and agree to the <a href="/terms-of-service/" target="_blank">Terms of Service</a></label></p>';
    $out .= '<script>document.getElementById("tln-tos-box").addEventListener("scroll",function(){if(this.scrollHeight-this.scrollTop<=this.clientHeight+50){document.getElementById("tln-tos-check").disabled=false}});</script>';
    $out .= '<p><label>Digital Signature (type your name) *<br><input name="csig" required placeholder="Type your full legal name" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;"></label></p>';
    $out .= '<button type="submit" name="submit_claim" style="background:#e63946;color:white;padding:1rem 2rem;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">Submit Claim</button>';
    $out .= '</form></div>';
    
    return $out;
}