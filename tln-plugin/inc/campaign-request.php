<?php
/**
 * TLN Campaign Request Shortcode
 * Allows businesses to request postcard campaigns directly from the website
 */

add_shortcode('tln_campaign_request', 'tln_campaign_request_shortcode');

function tln_campaign_request_shortcode($atts) {
    $atts = shortcode_atts(array(
        'type' => 'all' // show specific campaign types only
    ), $atts);

    // Handle form submission
    if (isset($_POST['tln_submit_campaign']) && wp_verify_nonce($_POST['tln_campaign_nonce'], 'tln_campaign_request')) {
        return tln_process_campaign_request();
    }

    ob_start();
    ?>
    <div class="tln-campaign-request">
        <style>
            .tln-campaign-request { max-width: 600px; margin: 0 auto; font-family: 'Open Sans', sans-serif; }
            .tln-cr-header { background: #1a1a2e; color: white; padding: 2rem; text-align: center; border-radius: 12px 12px 0 0; }
            .tln-cr-header h2 { margin: 0 0 0.5rem; font-size: 1.75rem; }
            .tln-cr-header p { margin: 0; opacity: 0.85; font-size: 0.95rem; }
            .tln-cr-content { padding: 2rem; background: white; border-radius: 0 0 12px 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            .tln-cr-note { background: #f8f9fa; border-left: 4px solid #1a1a2e; padding: 1rem; margin-bottom: 1.5rem; font-size: 0.9rem; color: #555; }
            .tln-cr-note strong { color: #333; }
            .tln-cr-form-group { margin-bottom: 1.25rem; }
            .tln-cr-form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333; }
            .tln-cr-form-group input,
            .tln-cr-form-group select,
            .tln-cr-form-group textarea { width: 100%; padding: 0.75rem 1rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem; transition: border-color 0.2s; }
            .tln-cr-form-group input:focus,
            .tln-cr-form-group select:focus,
            .tln-cr-form-group textarea:focus { outline: none; border-color: #1a1a2e; }
            .tln-cr-form-group textarea { min-height: 100px; resize: vertical; }
            .tln-cr-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
            @media (max-width: 500px) { .tln-cr-row { grid-template-columns: 1fr; } }
            .tln-cr-submit { display: block; width: 100%; padding: 1rem; background: #e63946; color: white; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
            .tln-cr-submit:hover { background: #c1121f; }
            .tln-cr-submit:disabled { background: #999; cursor: not-allowed; }
            .tln-cr-optin { background: #f0f7ff; border: 1px solid #d0e3ff; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
            .tln-cr-optin label { display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 400; cursor: pointer; }
            .tln-cr-optin input { width: auto; margin-top: 0.25rem; }
            .tln-cr-optin-note { font-size: 0.75rem; color: #666; margin-top: 0.25rem; margin-left: 1.5rem; }
            .tln-cr-support { background: #fffbe6; border: 1px solid #ffe066; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.85rem; }
            .tln-cr-success { padding: 1.5rem; background: #d4edda; border-radius: 8px; color: #155724; text-align: center; }
            .tln-cr-error { padding: 1rem; background: #f8d7da; border-radius: 8px; color: #721c24; margin-bottom: 1rem; }
        </style>
        
        <div class="tln-cr-header">
            <h2>Request a Campaign</h2>
            <p>Get started with affordable local advertising</p>
        </div>
        
        <div class="tln-cr-content">
            <div class="tln-cr-note">
                <strong>How it works:</strong> Fill out this form and we'll be in touch within 24 hours to discuss your campaign options, pricing, and get you on the schedule.
            </div>
            
            <form method="post" class="tln-cr-form">
                <?php wp_nonce_field('tln_campaign_request', 'tln_campaign_nonce'); ?>
                
                <div class="tln-cr-row">
                    <div class="tln-cr-form-group">
                        <label for="tln_business_name">Business Name *</label>
                        <input type="text" id="tln_business_name" name="tln_business_name" required>
                    </div>
                    <div class="tln-cr-form-group">
                        <label for="tln_contact_name">Your Name *</label>
                        <input type="text" id="tln_contact_name" name="tln_contact_name" required>
                    </div>
                </div>
                
                <div class="tln-cr-row">
                    <div class="tln-cr-form-group">
                        <label for="tln_email">Email *</label>
                        <input type="email" id="tln_email" name="tln_email" required>
                    </div>
                    <div class="tln-cr-form-group">
                        <label for="tln_phone">Phone</label>
                        <input type="tel" id="tln_phone" name="tln_phone">
                    </div>
                </div>
                
                <div class="tln-cr-form-group">
                    <label for="tln_campaign_type">What kind of campaign?</label>
                    <select id="tln_campaign_type" name="tln_campaign_type">
                        <option value="">Select an option...</option>
                        <option value="postcard">Postcard Mailing (EDDM)</option>
                        <option value="directory">Directory Listing</option>
                        <option value="newsletter">Newsletter Sponsor</option>
                        <option value="combo">Combo Package</option>
                        <option value="not-sure">Not sure yet - need info</option>
                    </select>
                </div>
                
                <div class="tln-cr-form-group">
                    <label for="tln_message">Tell us about your business and goals</label>
                    <textarea id="tln_message" name="tln_message" placeholder="What do you want to promote? Any special offers? What's your target area?"></textarea>
                </div>
                
                <div class="tln-cr-support">
                    <strong>Need help?</strong> Text us at (704) 555-0123 — we're here to help you get the most out of your campaign.
                </div>
                
                <div class="tln-cr-optin">
                    <label>
                        <input type="checkbox" name="tln_sms_optin" value="yes">
                        <span>Yes, I want to receive text updates about my campaign</span>
                    </label>
                    <p class="tln-cr-optin-note">Message & data rates may apply. Reply STOP to opt out.</p>
                </div>
                
                <button type="submit" name="tln_submit_campaign" class="tln-cr-submit">Submit Request</button>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function tln_process_campaign_request() {
    global $wpdb;
    
    $business_name = sanitize_text_field($_POST['tln_business_name']);
    $contact_name = sanitize_text_field($_POST['tln_contact_name']);
    $email = sanitize_email($_POST['tln_email']);
    $phone = sanitize_text_field($_POST['tln_phone']);
    $campaign_type = sanitize_text_field($_POST['tln_campaign_type']);
    $message = sanitize_textarea_field($_POST['tln_message']);
    $sms_optin = isset($_POST['tln_sms_optin']) ? 'yes' : 'no';
    
    // Validation
    if (empty($business_name) || empty($contact_name) || empty($email)) {
        return '<div class="tln-cr-error">Please fill in all required fields.</div>' . tln_campaign_request_shortcode(array());
    }
    
    // Save to database
    $table_name = $wpdb->prefix . 'tln_campaign_requests';
    
    $result = $wpdb->insert($table_name, array(
        'business_name' => $business_name,
        'contact_name' => $contact_name,
        'email' => $email,
        'phone' => $phone,
        'campaign_type' => $campaign_type,
        'message' => $message,
        'sms_optin' => $sms_optin,
        'status' => 'new',
        'created_at' => current_time('mysql')
    ));
    
    if ($result === false) {
        // Table might not exist - log error but still send email
        error_log('TLN Campaign Request: Failed to insert into database - ' . $wpdb->last_error);
    }
    
    // Send notification email to Bryan
    $admin_subject = "New Campaign Request: $business_name";
    $admin_body = "New campaign request submitted:\n\n"
        . "Business: $business_name\n"
        . "Contact: $contact_name\n"
        . "Email: $email\n"
        . "Phone: $phone\n"
        . "Campaign Type: $campaign_type\n"
        . "Message: $message\n"
        . "Submitted: " . current_time('mysql') . "\n\n"
        . "Go to admin dashboard to respond.";
    
    wp_mail('bryan@thelocalnearbuy.com', $admin_subject, $admin_body);
    
    // Send confirmation to customer
    $confirm_subject = "We got your request! - The Local NearBuy";
    $confirm_body = "Hi $contact_name,\n\n"
        . "Thanks for your interest in The Local NearBuy! We've received your campaign request and will be in touch within 24 hours to discuss the details.\n\n"
        . "What you submitted:\n"
        . "- Business: $business_name\n"
        . "- Campaign type: $campaign_type\n\n"
        . "We look forward to working with you!\n\n"
        . "- The Local NearBuy Team";
    
    wp_mail($email, $confirm_subject, $confirm_body, 'From: The Local NearBuy <noreply@thelocalnearbuy.com>');
    
    ob_start();
    ?>
    <div class="tln-campaign-request">
        <div class="tln-cr-header">
            <h2>Request Received!</h2>
            <p>We'll be in touch soon</p>
        </div>
        <div class="tln-cr-content">
            <div class="tln-cr-success">
                <h3 style="margin: 0 0 1rem;">Thanks, <?php echo esc_html($contact_name); ?>!</h3>
                <p style="margin: 0;">We've received your request for <strong><?php echo esc_html($business_name); ?></strong>.</p>
                <p style="margin: 1rem 0 0;">Look for an email from us within 24 hours.</p>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Create campaign requests table on activation
register_activation_hook(__FILE__, 'tln_create_campaign_requests_table');

function tln_create_campaign_requests_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $table_name = $wpdb->prefix . 'tln_campaign_requests';
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        business_name VARCHAR(255) NOT NULL,
        contact_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        campaign_type VARCHAR(50),
        message TEXT,
        sms_optin VARCHAR(3) DEFAULT 'no',
        status VARCHAR(50) DEFAULT 'new',
        assigned_to BIGINT(20),
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY status (status),
        KEY email (email)
    ) $charset_collate";
    
    dbDelta($sql);
}