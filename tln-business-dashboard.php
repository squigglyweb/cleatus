<?php
/**
 * Plugin Name: TLN Business Dashboard
 * Description: Business claim system with TOS agreement
 * Version: 1.2
 */

register_activation_hook(__FILE__, 'tln_business_install');

function tln_business_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_claims (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        business_id bigint(20) DEFAULT 0,
        business_name varchar(255),
        place_id varchar(255),
        user_id bigint(20) NOT NULL,
        claimant_name varchar(100),
        claimant_phone varchar(50),
        proof text,
        tos_agreed varchar(255),
        tos_signed_date date,
        status varchar(20) DEFAULT 'pending',
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate");
}

add_action('admin_menu', 'tln_business_menu');

function tln_business_menu() {
    add_menu_page('TLN Business', 'TLN Business', 'manage_options', 'tln-business', 'tln_business_admin', 'dashicons-store', 30);
    add_submenu_page('tln-business', 'Claims', 'Claims', 'manage_options', 'tln-claims', 'tln_claims_page');
}

// Claim form shortcode with TOS
add_shortcode('claim_business', 'tln_claim_form');

function tln_claim_form($atts) {
    $url_business = isset($_GET['business']) ? sanitize_text_field($_GET['business']) : '';
    $url_place_id = isset($_GET['place_id']) ? sanitize_text_field($_GET['place_id']) : '';
    
    if (!is_user_logged_in()) {
        $login_url = wp_login_url(get_permalink() . '?business=' . urlencode($url_business) . '&place_id=' . $url_place_id);
        return '<div class="tln-claim-login" style="background:#f8f8f8;padding:2rem;border-radius:12px;text-align:center;">
            <h3>Log in to Claim</h3>
            <p>Please <a href="' . $login_url . '">log in</a> to claim this business.</p>
        </div>';
    }
    
    if (isset($_POST['tln_submit_claim'])) {
        global $wpdb;
        $user_id = get_current_user_id();
        $today = date('Y-m-d');
        
        $wpdb->insert($wpdb->prefix . 'tln_claims', array(
            'business_name' => sanitize_text_field($_POST['business_name']),
            'place_id' => sanitize_text_field($_POST['place_id']),
            'user_id' => $user_id,
            'claimant_name' => sanitize_text_field($_POST['claimant_name']),
            'claimant_phone' => sanitize_text_field($_POST['claimant_phone']),
            'proof' => sanitize_textarea_field($_POST['proof']),
            'tos_agreed' => sanitize_text_field($_POST['tos_signature']),
            'tos_signed_date' => $today,
            'status' => 'pending'
        ));
        
        return '<div class="tln-success" style="background:#d4edda;padding:1.5rem;border-radius:8px;color:#155724;">
            <h3>✅ Claim Submitted!</h3>
            <p>Thanks! We\'ll review your request and get back to you within 48 hours.</p>
        </div>';
    }
    
    ob_start();
    ?>
    <div class="tln-claim-form" style="background:#f8f8f8;padding:2rem;border-radius:12px;max-width:700px;">
        <h2 style="margin-top:0;">Claim This Business</h2>
        
        <?php if ($url_business): ?>
        <div style="background:#e63946;color:white;padding:1rem;border-radius:8px;margin-bottom:1.5rem;">
            You're claiming: <strong><?php echo esc_html($url_business); ?></strong>
        </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <input type="hidden" name="place_id" value="<?php echo esc_attr($url_place_id); ?>">
            
            <p style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Business Name *</label>
                <input type="text" name="business_name" required value="<?php echo esc_attr($url_business); ?>" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;" <?php echo $url_business ? 'readonly' : ''; ?>>
            </p>
            
            <p style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Your Full Name *</label>
                <input type="text" name="claimant_name" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;">
            </p>
            
            <p style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Phone *</label>
                <input type="tel" name="claimant_phone" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;">
            </p>
            
            <p style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Proof of Ownership</label>
                <small style="color:#666;">Briefly describe your connection to this business</small>
                <textarea name="proof" rows="3" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;margin-top:0.5rem;"></textarea>
            </p>
            
            <!-- TOS SECTION -->
            <div style="background:white;border:1px solid #ddd;border-radius:8px;padding:1.5rem;margin:1.5rem 0;">
                <h3 style="margin-top:0;color:#1a1a1a;">📋 Terms of Service</h3>
                <div style="max-height:200px;overflow-y:auto;border:1px solid #eee;padding:1rem;margin:1rem 0;font-size:0.9rem;line-height:1.6;">
                    <h4 style="margin-top:0;">Terms of Service - The Local NearBuy</h4>
                    <p><em>Last Updated: May 2026</em></p>
                    
                    <p><strong>1. Acceptance of Terms</strong><br>
                    By accessing and using The Local NearBuy directory, you ("Business Owner" or "Participant") agree to be bound by these Terms of Service.</p>
                    
                    <p><strong>2. Business Listings</strong><br>
                    All businesses listed must be located within the Greater Waxhaw area. Business owners must have authorization to manage their listing. Information provided must be accurate and current.</p>
                    
                    <p><strong>3. Paid Services</strong><br>
                    Pro ($99/month) and Sponsor ($349/month) subscriptions are billed monthly. Either party may terminate with 30 days written notice. Refunds are not provided for partial months.</p>
                    
                    <p><strong>4. Content & Conduct</strong><br>
                    Business owners are responsible for content they submit. No false, misleading, or deceptive information. No content that violates local laws or regulations.</p>
                    
                    <p><strong>5. Community Guidelines</strong><br>
                    All interactions must be respectful. Reviews reflect individual experiences. Businesses may not submit fake reviews.</p>
                    
                    <p><strong>6. Payment Terms</strong><br>
                    Subscription fees are due on the 1st of each month. Late payments may result in removal from paid features.</p>
                    
                    <p><strong>7. Liability</strong><br>
                    The Local NearBuy provides a platform for connecting neighbors with businesses. We do not guarantee business quality, services, or outcomes.</p>
                    
                    <p><strong>8. Contact</strong><br>
                    Questions? Contact Bryan at info@thelocalnearbuy.com</p>
                </div>
                
                <div style="background:#fef3c7;padding:1rem;border-radius:6px;margin-bottom:1rem;">
                    <label style="display:block;cursor:pointer;">
                        <input type="checkbox" name="tos_checkbox" required style="margin-right:0.5rem;">
                        I have read and agree to the Terms of Service *
                    </label>
                </div>
                
                <p style="margin-bottom:0.5rem;">
                    <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Type your full name as digital signature *</label>
                    <input type="text" name="tos_signature" required placeholder="Type your full name here"
                           style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;">
                    <small style="color:#666;">By typing your name, you agree that this constitutes your legal signature.</small>
                </p>
                
                <p>
                    <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Date</label>
                    <input type="text" value="<?php echo date('F j, Y'); ?>" disabled
                           style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;background:#f5f5f5;">
                    <input type="hidden" name="tos_date" value="<?php echo date('Y-m-d'); ?>">
                </p>
            </div>
            
            <p style="margin-bottom:1.5rem;">
                <label>
                    <input type="checkbox" name="terms" required style="margin-right:0.5rem;">
                    I certify that I am authorized to manage this business listing.
                </label>
            </p>
            
            <button type="submit" name="tln_submit_claim" value="1" 
                    style="background:#e63946;color:white;padding:1rem 2rem;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">
                Submit Claim Request
            </button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

function tln_claims_page() {
    global $wpdb;
    $claims = $wpdb->get_results("SELECT c.*, u.display_name FROM {$wpdb->prefix}tln_claims c 
        JOIN {$wpdb->users} u ON c.user_id = u.ID 
        ORDER BY c.submitted_at DESC");
    
    echo '<h1>Business Claims</h1>';
    if (empty($claims)) {
        echo '<p>No claims yet.</p>';
        return;
    }
    echo '<table class="widefat"><tr><th>Business</th><th>Owner</th><th>Phone</th><th>TOS Signed</th><th>Signed Date</th><th>Status</th><th>Action</th></tr>';
    foreach ($claims as $c) {
        echo '<tr>';
        echo '<td>' . esc_html($c->business_name) . '</td>';
        echo '<td>' . esc_html($c->claimant_name) . '</td>';
        echo '<td>' . esc_html($c->claimant_phone) . '</td>';
        echo '<td>' . esc_html($c->tos_agreed) . '</td>';
        echo '<td>' . esc_html($c->tos_signed_date) . '</td>';
        echo '<td>' . esc_html($c->status) . '</td>';
        echo '<td>';
        if ($c->status == 'pending') {
            echo '<a href="?page=tln-claims&action=approve=' . $c->id . '">Approve</a> | ';
            echo '<a href="?page=tln-claims&action=reject=' . $c->id . '">Reject</a>';
        }
        echo '</td></tr>';
    }
    echo '</table>';
    
    if (isset($_GET['action'])) {
        $id = intval(str_replace(['approve=', 'reject='], '', $_GET['action']));
        $status = strpos($_GET['action'], 'approve=') !== false ? 'approved' : 'rejected';
        $wpdb->update($wpdb->prefix . 'tln_claims', array('status' => $status), array('id' => $id));
        echo '<meta http-equiv="refresh" content="0">';
    }
}

function tln_business_admin() {
    echo '<h1>TLN Business Dashboard</h1><p>Use the submenus to manage claims.</p>';
}
