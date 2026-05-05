<?php
/**
 * Plugin Name: TLN Business Dashboard
 * Description: Business claim system with email notifications
 * Version: 1.4
 */

register_activation_hook(__FILE__, 'tln_business_install');

function tln_business_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_claims (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        business_name varchar(255),
        place_id varchar(255),
        user_id bigint(20) NOT NULL,
        claimant_name varchar(100),
        claimant_phone varchar(50),
        proof text,
        cta_text varchar(100),
        cta_url varchar(500),
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
            'cta_text' => sanitize_text_field($_POST['cta_text']),
            'cta_url' => esc_url_raw($_POST['cta_url']),
            'tos_agreed' => sanitize_text_field($_POST['tos_signature']),
            'tos_signed_date' => $today,
            'status' => 'approved'
        ));
        
        $claim_id = $wpdb->insert_id;
        
        // Send email to Bryan
        $business_name = sanitize_text_field($_POST['business_name']);
        $claimant_name = sanitize_text_field($_POST['claimant_name']);
        $claimant_phone = sanitize_text_field($_POST['claimant_phone']);
        
        $to = 'bryan@thelocalnearbuy.com';
        $subject = '🚨 New Business Claim: ' . $business_name;
        $message = "A new business has claimed their listing!\n\n";
        $message .= "Business: " . $business_name . "\n";
        $message .= "Claimed by: " . $claimant_name . "\n";
        $message .= "Phone: " . $claimant_phone . "\n";
        $message .= "Date: " . date('F j, Y') . "\n\n";
        $message .= "View in admin: https://thelocalnearbuy.com/wp-admin/admin.php?page=tln-claims";
        
        wp_mail($to, $subject, $message);
        
        return '<div class="tln-success" style="background:#d4edda;padding:2rem;border-radius:8px;color:#155724;text-align:center;">
            <h3 style="margin-top:0;">✅ You\'re All Set!</h3>
            <p>Your business page is now live. Add your offer, photos, and customize your page.</p>
            <p style="margin-top:1.5rem;"><a href="/dashboard/" style="display:inline-block;background:#7cda24;color:#fff;padding:0.75rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:600;">Go to Your Dashboard →</a></p>
        </div>';
    }
    
    ob_start();
    ?>
    <div class="tln-claim-form" style="background:#f8f8f8;padding:2rem;border-radius:12px;max-width:800px;">
        <h2 style="margin-top:0;">Claim This Business</h2>
        
        <?php if ($url_business): ?>
        <div style="background:#1a1a1a;color:white;padding:1rem;border-radius:8px;margin-bottom:1.5rem;">
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
                
                <div style="max-height:250px;overflow-y:auto;border:1px solid #eee;padding:1rem;margin:1rem 0;font-size:0.85rem;line-height:1.6;">
                    <p><em>Posted/Revised: May 5, 2026</em></p>
                    <p><strong>PLEASE READ THESE TERMS OF SERVICE CAREFULLY. BY CLICKING "ACCEPTED AND AGREED TO," YOU AGREE TO THESE TERMS AND CONDITIONS.</strong></p>
                    
                    <p>These Terms of Service constitute an agreement between The Local Nearbuy ("Vendor," "We" or "Us") and the individual, corporation, LLC, partnership, sole proprietorship, or other business entity agreeing to these terms ("Customer" or "You").</p>
                    
                    <p><strong>1. Acceptance of Terms</strong><br>We provide online resources, information, and email services subject to these Terms of Service.</p>
                    
                    <p><strong>2. Amendment of Terms</strong><br>We may amend these Terms from time to time by posting changes on our website.</p>
                    
                    <p><strong>3. Content</strong><br>You are solely responsible for all content you post.</p>
                    
                    <p><strong>4. Third-Party Content</strong><br>Content may link to third-party websites.</p>
                    
                    <p><strong>5. Privacy</strong><br>Please review our Privacy Policy.</p>
                    
                    <p><strong>6. Paid Postings</strong><br>We may charge fees for certain postings.</p>
                    
                    <p><strong>7. Term and Termination</strong><br>You may deactivate your account at any time.</p>
                    
                    <p><strong>8. Disclaimer of Warranties</strong><br>THE SERVICE IS PROVIDED "AS IS" WITHOUT WARRANTIES.</p>
                    
                    <p><strong>9. Limitations of Liability</strong><br>OUR LIABILITY IS LIMITED TO $25.00.</p>
                    
                    <p><em>For complete Terms, visit: <a href="/terms-of-service/" target="_blank">thelocalnearbuy.com/terms-of-service</a></em></p>
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

// Business Dashboard Shortcode
add_shortcode('business_dashboard', 'tln_business_dashboard');

function tln_business_dashboard() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . wp_login_url() . '">log in</a> to view your dashboard.</p>';
    }
    
    $user_id = get_current_user_id();
    
    // Get their claimed business
    global $wpdb;
    $claim = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tln_claims WHERE user_id = %d AND status = 'approved' ORDER BY submitted_at DESC LIMIT 1",
        $user_id
    ));
    
    if (!$claim) {
        return '<div style="background:#fef3c7;padding:1.5rem;border-radius:8px;">
            <h3>No Business Claimed</h3>
            <p>You haven\'t claimed a business yet.</p>
            <a href="/directory/" style="background:#7cda24;color:white;padding:0.75rem 1.5rem;border-radius:8px;text-decoration:none;">Browse Directory →</a>
        </div>';
    }
    
    ob_start();
    ?>
    <div style="max-width:800px;margin:0 auto;padding:2rem;">
        <h2 style="margin-top:0;">Welcome, <?php echo esc_html($claim->claimant_name); ?>!</h2>
        
        <div style="background:#1a1a1a;color:white;padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;">
            <h3 style="margin-top:0;color:white;"><?php echo esc_html($claim->business_name); ?></h3>
            <p style="opacity:0.8;margin-bottom:0;">Status: ✅ Verified | Claimed <?php echo date('M j, Y', strtotime($claim->submitted_at)); ?></p>
        </div>
        
        <!-- PRO UPGRADE SECTION -->
        <div style="background:linear-gradient(135deg,#e63946,#c1121f);color:white;padding:2rem;border-radius:12px;margin-bottom:1.5rem;">
            <h3 style="margin-top:0;color:white;font-size:1.5rem;">🚀 Upgrade to Pro</h3>
            <p style="opacity:0.9;">Get a custom offer, QR code, photos, and more for just $99/month.</p>
            
            <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:1rem;">
                <a href="YOUR_STRIPE_LINK_HERE" style="flex:1;background:white;color:#e63946;padding:1rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:700;text-align:center;min-width:200px;">Upgrade Now - $99/mo</a>
            </div>
            <p style="font-size:0.85rem;opacity:0.8;margin-top:0.5rem;">Cancel anytime. Secure payment via Stripe.</p>
        </div>
        
        <!-- Quick Actions -->
        <h3 style="margin-bottom:1rem;">Quick Actions</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
            <div style="background:#f8f8f8;padding:1.5rem;border-radius:8px;text-align:center;">
                <h4 style="margin-top:0;">📝 Edit Business Info</h4>
                <p style="font-size:0.9rem;color:#666;">Update your description, hours, and details</p>
            </div>
            <div style="background:#f8f8f8;padding:1.5rem;border-radius:8px;text-align:center;">
                <h4 style="margin-top:0;">🎁 Create Offer</h4>
                <p style="font-size:0.9rem;color:#666;">Add a special deal for neighbors</p>
            </div>
            <div style="background:#f8f8f8;padding:1.5rem;border-radius:8px;text-align:center;">
                <h4 style="margin-top:0;">📷 Add Photos</h4>
                <p style="font-size:0.9rem;color:#666;">Upload your own business photos</p>
            </div>
            <div style="background:#f8f8f8;padding:1.5rem;border-radius:8px;text-align:center;">
                <h4 style="margin-top:0;">🍽️ Community Impact</h4>
                <p style="font-size:0.9rem;color:#666;">Track meals donated</p>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
