<?php
/**
 * TLN Gift Claim Template - Free Pens
 */

if (!defined('ABSPATH')) exit;

function tln_render_gift_claim() {
    $business_id = isset($_GET['business']) ? intval($_GET['business']) : 0;
    $business_name = isset($_GET['name']) ? sanitize_text_field($_GET['name']) : '';
    
    // If no business ID, show error
    if (!$business_id) {
        return '<div style="padding:2rem;background:#f8d7da;border-radius:8px;text-align:center;max-width:600px;margin:2rem auto;">
            <h3 style="color:#721c24;margin-top:0;">Invalid Link</h3>
            <p>This gift claim link appears to be invalid. Please contact us for assistance.</p>
            <p><a href="/contact" style="color:#e63946;">Contact The Local NearBuy</a></p>
        </div>';
    }
    
    // Handle form submission
    if (isset($_POST['submit_gift_claim'])) {
        $cname = sanitize_text_field($_POST['cname']);
        $cemail = sanitize_email($_POST['cemail']);
        $cphone = sanitize_text_field($_POST['cphone']);
        $time_slot = sanitize_text_field($_POST['time_slot']);
        
        // Save to gift_claims table
        global $wpdb;
        $table = $wpdb->prefix . 'tln_gift_claims';
        
        $result = $wpdb->insert($table, [
            'business_id' => $business_id,
            'business_name' => $business_name,
            'contact_name' => $cname,
            'contact_email' => $cemail,
            'contact_phone' => $cphone,
            'time_slot' => $time_slot,
            'gift_type' => 'pens',
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ]);
        
        if ($result) {
            // Show success
            $out = '<div style="padding:2rem;background:#d4edda;border-radius:8px;text-align:center;max-width:600px;margin:2rem auto;">
                <h3 style="color:#155724;margin-top:0;">Gift Claimed!</h3>
                <p>Your customized pens are on the way!</p>
                <p><strong>We\'ll drop them off during:</strong></p>
                <p style="font-size:1.5rem;background:#fff;padding:1rem;border-radius:8px;display:inline-block;">'.esc_html($time_slot).'</p>
                <p style="margin-top:1rem;">We\'ll bring them by within 2-3 business days.</p>
                <p><a href="/" style="color:#e63946;">Back to The Local NearBuy</a></p>
            </div>';
            
            // Send confirmation email
            $subject = 'Your Free Pens from The Local NearBuy';
            $message = "Hi ".esc_html($cname).",\n\n";
            $message .= "Thanks for claiming your free gift! We're bringing you a box of customized pens featuring The Local NearBuy.\n\n";
            $message .= "We'll drop them off during: ".esc_html($time_slot)."\n\n";
            $message .= "Questions? Reply to this email.\n\n";
            $message .= "Thanks,\nBryan @ The Local NearBuy";
            
            $headers = ['Content-Type: text/plain; charset=UTF-8'];
            wp_mail($cemail, $subject, $message, $headers);
            
            return $out;
        }
    }
    
    // Render the form
    $out = '<div style="padding:2rem;background:#f8f8f8;border-radius:12px;max-width:600px;margin:2rem auto;font-family:system-ui,sans-serif;">
        <div style="text-align:center;margin-bottom:1.5rem;">
            <img src="https://thelocalnearbuy.com/wp-content/uploads/2025/03/TLN-logo-V1.png" alt="The Local NearBuy" style="max-width:200px;height:auto;">
        </div>
        <h2 style="margin-top:0;text-align:center;">Claim Your Free Gift</h2>
        <p style="text-align:center;font-size:1.1rem;">We\'re bringing you a box of customized pens for your business. Just tell us when to drop them off!</p>
        
        <div style="background:#fff;padding:1.5rem;border-radius:8px;margin-bottom:1.5rem;">
            <p style="margin:0 0 0.5rem;font-weight:600;">Your Business:</p>
            <p style="margin:0;font-size:1.25rem;color:#e63946;">'.esc_html($business_name).'</p>
        </div>
        
        <form method="post" style="background:#fff;padding:1.5rem;border-radius:8px;">
            <p style="margin-bottom:1rem;">
                <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Your Name *</label>
                <input name="cname" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;box-sizing:border-box;">
            </p>
            <p style="margin-bottom:1rem;">
                <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Email *</label>
                <input name="cemail" type="email" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;box-sizing:border-box;">
            </p>
            <p style="margin-bottom:1rem;">
                <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Phone *</label>
                <input name="cphone" type="tel" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;box-sizing:border-box;">
            </p>
            <p style="margin-bottom:1.5rem;">
                <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Best Time to Drop Off *</label>
                <select name="time_slot" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;box-sizing:border-box;background:#fff;">
                    <option value="">Select a time...</option>
                    <option value="Morning (9AM - 12PM)">Morning (9AM - 12PM)</option>
                    <option value="Early Afternoon (12PM - 3PM)">Early Afternoon (12PM - 3PM)</option>
                    <option value="Late Afternoon (3PM - 6PM)">Late Afternoon (3PM - 6PM)</option>
                    <option value="Evening (6PM - 8PM)">Evening (6PM - 8PM)</option>
                </select>
            </p>
            <button type="submit" name="submit_gift_claim" style="background:#e63946;color:white;padding:1rem 2rem;border:none;border-radius:8px;font-size:1.1rem;font-weight:600;cursorpointer;width:100%;">
                CLAIM MY PENS
            </button>
        </form>
        
        <p style="text-align:center;margin-top:1rem;color:#666;font-size:0.9rem;">
            By claiming, you agree to receive a delivery from The Local NearBuy.
        </p>
    </div>';
    
    return $out;
}

add_shortcode('gift_claim', 'tln_render_gift_claim');