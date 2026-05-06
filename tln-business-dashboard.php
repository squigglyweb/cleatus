<?php
/**
 * Plugin Name: TLN Business Dashboard
 * Description: Business claim system with enforced TOS scrolling
 * Version: 1.5
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
        status varchar(20) DEFAULT 'approved',
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
    <div class="tln-claim-form" style="background:#f8f8f8;padding:2rem;border-radius:12px;max-width:900px;">
        <h2 style="margin-top:0;">Claim This Business</h2>
        
        <?php if ($url_business): ?>
        <div style="background:#1a1a1a;color:white;padding:1rem;border-radius:8px;margin-bottom:1.5rem;">
            You're claiming: <strong><?php echo esc_html($url_business); ?></strong>
        </div>
        <?php endif; ?>
        
        <form method="post" action="" id="tln-claim-form">
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
            
            <!-- FULL TOS - SCROLL TO AGREE -->
            <div style="background:white;border:1px solid #ddd;border-radius:8px;padding:1.5rem;margin:1.5rem 0;">
                <h3 style="margin-top:0;color:#1a1a1a;">📋 Terms of Service</h3>
                <p style="color:#e63946;font-weight:600;margin-bottom:1rem;">⬇️ You MUST scroll to the bottom to enable the agreement checkbox ⬇️</p>
                
                <div id="tln-tos-content" style="max-height:400px;overflow-y:scroll;border:1px solid #ccc;padding:1.5rem;margin:1rem 0;font-size:0.9rem;line-height:1.8;">
                    <p><em>Posted/Revised: May 5, 2026</em></p>
                    <p><strong>PLEASE READ THESE TERMS OF SERVICE CAREFULLY. BY CLICKING "ACCEPTED AND AGREED TO," YOU AGREE TO THESE TERMS AND CONDITIONS.</strong></p>
                    
                    <p>These Terms of Service constitute an agreement between The Local Nearbuy ("Vendor," "We" or "Us") and the individual, corporation, LLC, partnership, sole proprietorship, or other business entity agreeing to these terms ("Customer" or "You"). This Agreement is effective as of the date you click "Accepted and Agreed To."</p>
                    
                    <p><strong>1. Acceptance of Terms</strong><br>We provide online resources, information, and email services subject to these Terms of Service. By using the Service, you agree to comply with these terms and any guidelines we may change from time to time. If you disagree with any terms, your only recourse is to stop using the Service.</p>
                    
                    <p><strong>2. Amendment of Terms</strong><br>We may amend these Terms from time to time by posting changes on our website or sending written notice. Changes become effective 30 days after notice unless you reject them in writing. Your continued use after the effective date constitutes acceptance.</p>
                    
                    <p><strong>3. Content</strong><br>You are solely responsible for all content you post, email, or make available through the Service. You authorize us to copy, display, and distribute your content for any purpose related to the Service. By posting content, you grant us an irrevocable, perpetual, non-exclusive, worldwide license to use, copy, and distribute such content.</p>
                    
                    <p><strong>4. Third-Party Content, Sites, and Services</strong><br>Content available through the Service may link to third-party websites independent of The Local Nearbuy. We make no representations about the accuracy of third-party sites. Your interactions with third parties are solely between you and them.</p>
                    
                    <p><strong>5. Notification of Claims of Infringement</strong><br>To report copyright or intellectual property infringement, contact us at bryan@thelocalnearbuy.com with the required information.</p>
                    
                    <p><strong>6. Privacy and Information Disclosure</strong><br>Please review our Privacy Policy, incorporated by reference herein.</p>
                    
                    <p><strong>7. Conduct</strong><br>Please review our Acceptable Use Policy, incorporated by reference herein.</p>
                    
                    <p><strong>8. Paid Postings</strong><br>We may charge fees to post content in designated areas. All fees paid are non-refundable if content is removed for violating these Terms.</p>
                    
                    <p><strong>9. Limitations on Service</strong><br>We may establish limits on use, including maximum storage duration, posting size, and access frequency.</p>
                    
                    <p><strong>10. Access to the Service</strong><br>We grant you a limited, revocable, non-exclusive license to access the Service for personal use. This license does not include scraping, data mining, or commercial use without written permission.</p>
                    
                    <p><strong>11. Term and Termination of Service</strong><br>You may deactivate your account at any time. After termination, you cannot access your account or personal information.</p>
                    
                    <p><strong>12. Proprietary Rights</strong><br>The Service is protected by copyright and international treaties.</p>
                    
                    <p><strong>13. Disclaimer of Warranties</strong><br>THE SERVICE IS PROVIDED "AS IS" WITHOUT WARRANTIES OF ANY KIND. WE DISCLAIM ALL EXPRESS AND IMPLIED WARRANTIES.</p>
                    
                    <p><strong>14. Limitations of Liability</strong><br>WE SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR EXEMPLARY DAMAGES. OUR LIABILITY IS LIMITED TO $25.00.</p>
                    
                    <p><strong>15. Indemnity</strong><br>You agree to indemnify and hold us harmless from any claim arising out of your content.</p>
                    
                    <p><strong>16. General Information</strong><br>These Terms constitute the entire agreement between you and us. Any claims must be filed within one year.</p>
                    
                    <p><strong>17. Violation of Terms and Liquidated Damages</strong><br>Violations may result in liquidated damages ranging from $100 to $5,000 per incident.</p>
                    
                    <p><strong>18. Fees</strong><br>You agree to pay subscription fees until your account is deactivated. Subscriptions auto-renew until cancelled.</p>
                    
                    <p><strong>19. Transfer</strong><br>You may transfer account ownership by contacting us. A transfer fee may apply.</p>
                    
                    <p><strong>20. Notices</strong><br>Notices are deemed received 24 hours after sending via email.</p>
                    
                    <p><strong>21. Force Majeure</strong><br>Neither party is liable for delays caused by events beyond reasonable control.</p>
                    
                    <p><strong>22. Severability</strong><br>If any provision is invalid, the remaining terms continue in effect.</p>
                    
                    <p><strong>23. Choice of Law & Jurisdiction</strong><br>This Agreement is governed by the laws of the state where our principal office is located.</p>
                    
                    <p><strong>24. Entire Agreement</strong><br>These Terms set forth the entire agreement between the parties.</p>
                    
                    <p style="margin-top:2rem;padding-top:1rem;border-top:2px solid #ccc;"><em>By checking the box below, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service.</em></p>
                </div>
                
                <div style="background:#e63946;color:white;padding:1rem;border-radius:6px;margin-bottom:1rem;text-align:center;font-weight:600;" id="tln-scroll-notice">
                    ⬆️ PLEASE SCROLL UP TO READ THE TERMS ⬆️
                </div>
                
                <div style="background:#d4edda;padding:1rem;border-radius:6px;margin-bottom:1rem;" id="tln-tos-checkbox-div">
                    <label style="display:block;cursor:pointer;">
                        <input type="checkbox" name="tos_checkbox" id="tos_checkbox" required disabled style="margin-right:0.5rem;">
                        I have read and agree to the Terms of Service *
                    </label>
                </div>
                
                <p style="margin-bottom:0.5rem;">
                    <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Type your full name as digital signature *</label>
                    <input type="text" name="tos_signature" id="tos_signature" required placeholder="Type your full name here" disabled
                           style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;">
                    <small style="color:#666;">By typing your name, you agree that this constitutes your legal signature.</small>
                </p>
            </div>
            
            <p style="margin-bottom:1.5rem;">
                <label>
                    <input type="checkbox" name="terms" required style="margin-right:0.5rem;">
                    I certify that I am authorized to manage this business listing.
                </label>
            </p>
            
            <button type="submit" name="tln_submit_claim" value="1" id="tln-submit-btn" disabled
                    style="background:#ccc;color:#666;padding:1rem 2rem;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:not-allowed;">
                Submit Claim Request
            </button>
            
            <p style="color:#666;font-size:0.85rem;margin-top:1rem;">
                <em>* You must scroll through the entire Terms of Service and type your name to submit this form.</em>
            </p>
        </form>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tosContent = document.getElementById('tln-tos-content');
            var checkbox = document.getElementById('tos_checkbox');
            var signature = document.getElementById('tos_signature');
            var submitBtn = document.getElementById('tln-submit-btn');
            var scrollNotice = document.getElementById('tln-scroll-notice');
            
            tosContent.addEventListener('scroll', function() {
                var scrollTop = this.scrollTop;
                var scrollHeight = this.scrollHeight - this.clientHeight;
                
                // Check if scrolled to bottom (within 10px)
                if (scrollTop >= scrollHeight - 10) {
                    scrollNotice.style.display = 'none';
                    checkbox.disabled = false;
                    signature.disabled = false;
                    submitBtn.disabled = false;
                    submitBtn.style.background = '#e63946';
                    submitBtn.style.color = 'white';
                    submitBtn.style.cursor = 'pointer';
                }
            });
        });
        </script>
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
        } else {
            echo '<a href="?page=tln-claims&action=revoke=' . $c->id . '">Revoke</a>';
        }
        echo '</td></tr>';
    }
    echo '</table>';
    
    if (isset($_GET['action'])) {
        $id = intval(str_replace(['approve=', 'reject=', 'revoke='], '', $_GET['action']));
        if (strpos($_GET['action'], 'approve=') !== false) {
            $wpdb->update($wpdb->prefix . 'tln_claims', array('status' => 'approved'), array('id' => $id));
        } elseif (strpos($_GET['action'], 'reject=') !== false) {
            $wpdb->update($wpdb->prefix . 'tln_claims', array('status' => 'rejected'), array('id' => $id));
        } elseif (strpos($_GET['action'], 'revoke=') !== false) {
            $wpdb->update($wpdb->prefix . 'tln_claims', array('status' => 'revoked'), array('id' => $id));
        }
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
        
        <div style="background:linear-gradient(135deg,#e63946,#c1121f);color:white;padding:2rem;border-radius:12px;margin-bottom:1.5rem;">
            <h3 style="margin-top:0;color:white;font-size:1.5rem;">🚀 Upgrade to Pro</h3>
            <p style="opacity:0.9;">Get a custom offer, QR code, photos, and more for just $99/month.</p>
            <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:1rem;">
                <a href="YOUR_STRIPE_LINK_HERE" style="flex:1;background:white;color:#e63946;padding:1rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:700;text-align:center;min-width:200px;">Upgrade Now - $99/mo</a>
            </div>
            <p style="font-size:0.85rem;opacity:0.8;margin-top:0.5rem;">Cancel anytime. Secure payment via Stripe.</p>
        </div>
        
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
