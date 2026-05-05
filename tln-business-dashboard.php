<?php
/**
 * Plugin Name: TLN Business Dashboard
 * Description: Business claim system, dashboard, offers, and community impact
 * Version: 1.1
 */

// ... [keeping existing tables and admin] ...

// Claim form shortcode - NOW WITH PRE-FILL
add_shortcode('claim_business', 'tln_claim_form');

function tln_claim_form($atts) {
    $atts = shortcode_atts(array('business_id' => 0), $atts, 'claim_business');
    
    // Get business from URL params if not specified
    $url_business = isset($_GET['business']) ? sanitize_text_field($_GET['business']) : '';
    $url_place_id = isset($_GET['place_id']) ? sanitize_text_field($_GET['place_id']) : '';
    
    if (!is_user_logged_in()) {
        $login_url = wp_login_url(get_permalink());
        $register_url = wp_registration_url();
        $redirect_to = isset($_GET['business']) ? '?business=' . urlencode($_GET['business']) . '&place_id=' . $_GET['place_id'] : '';
        
        return '<div class="tln-claim-login" style="background:#f8f8f8;padding:2rem;border-radius:12px;text-align:center;">
            <h3>Log in to Claim</h3>
            <p>Please <a href="' . $login_url . $redirect_to . '">log in</a> or <a href="' . $register_url . $redirect_to . '">register</a> to claim this business.</p>
        </div>';
    }
    
    // Check if already claimed
    global $wpdb;
    
    // If we have a place_id from URL, check if already claimed
    if (!empty($url_place_id)) {
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tln_claims WHERE place_id = %s AND status = 'approved'",
            $url_place_id
        ));
        if ($existing) {
            return '<div class="tln-already-claimed" style="background:#fef3c7;padding:1.5rem;border-radius:8px;">This business has already been claimed.</div>';
        }
    }
    
    // Handle form submission
    if (isset($_POST['tln_submit_claim'])) {
        $user_id = get_current_user_id();
        $business_name = sanitize_text_field($_POST['business_name']);
        $place_id = sanitize_text_field($_POST['place_id']);
        
        // Insert claim
        $wpdb->insert($wpdb->prefix . 'tln_claims', array(
            'business_name' => $business_name,
            'place_id' => $place_id,
            'user_id' => $user_id,
            'claimant_name' => sanitize_text_field($_POST['claimant_name']),
            'claimant_phone' => sanitize_text_field($_POST['claimant_phone']),
            'proof' => sanitize_textarea_field($_POST['proof']),
            'status' => 'pending'
        ));
        
        // Create a post for this business
        $post_id = wp_insert_post(array(
            'post_title' => $business_name,
            'post_type' => 'post', // or your business post type
            'post_status' => 'draft',
            'post_author' => $user_id
        ));
        
        // Link claim to post
        update_post_meta($post_id, 'tln_place_id', $place_id);
        update_post_meta($post_id, 'tln_claim_status', 'pending');
        
        return '<div class="tln-success" style="background:#d4edda;padding:1.5rem;border-radius:8px;color:#155724;">
            <h3>✅ Claim Submitted!</h3>
            <p>Thanks! We\'ll review your request and get back to you soon.</p>
        </div>';
    }
    
    ob_start();
    ?>
    <div class="tln-claim-form" style="background:#f8f8f8;padding:2rem;border-radius:12px;">
        <h2 style="margin-top:0;">Claim This Business</h2>
        
        <?php if ($url_business): ?>
        <div style="background:#e63946;color:white;padding:1rem;border-radius:8px;margin-bottom:1.5rem;">
            You're claiming: <strong><?php echo esc_html($url_business); ?></strong>
        </div>
        <?php endif; ?>
        
        <p>Are you the owner? Claim your business to get a Pro page with special offers, QR codes, and more.</p>
        
        <form method="post" action="" style="max-width:500px;">
            <input type="hidden" name="place_id" value="<?php echo esc_attr($url_place_id); ?>">
            
            <p style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Business Name *</label>
                <input type="text" name="business_name" required value="<?php echo esc_attr($url_business); ?>" 
                       style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;" 
                       <?php echo $url_business ? 'readonly' : ''; ?>>
            </p>
            
            <p style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Your Name *</label>
                <input type="text" name="claimant_name" required 
                       style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;">
            </p>
            
            <p style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Phone *</label>
                <input type="tel" name="claimant_phone" required 
                       style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;">
            </p>
            
            <p style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Proof of Ownership</label>
                <small style="color:#666;">Briefly describe your connection to this business</small>
                <textarea name="proof" rows="3" 
                          style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;margin-top:0.5rem;"></textarea>
            </p>
            
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

// ... [rest of existing code: dashboard, reviews, admin] ...

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
        status varchar(20) DEFAULT 'pending',
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate");
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_business_meta (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        business_id bigint(20) NOT NULL,
        meals_donated int(11) DEFAULT 0,
        custom_description text,
        video_url varchar(500),
        social_links longtext,
        show_impact tinyint(1) DEFAULT 1,
        PRIMARY KEY  (id)
    ) $charset_collate");
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_offers (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        business_id bigint(20) NOT NULL,
        title varchar(255),
        description text,
        code varchar(50),
        start_date date,
        end_date date,
        max_uses int(11) DEFAULT 0,
        uses_count int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate");
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_reviews (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        business_id bigint(20) NOT NULL,
        quality tinyint(1) NOT NULL,
        value tinyint(1) NOT NULL,
        service tinyint(1) NOT NULL,
        customer_experience tinyint(1) NOT NULL,
        review_text text,
        reviewer_name varchar(100),
        status varchar(20) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate");
}

add_action('admin_menu', 'tln_business_menu');

function tln_business_menu() {
    add_menu_page('TLN Business', 'TLN Business', 'manage_options', 'tln-business', 'tln_business_admin', 'dashicons-store', 30);
    add_submenu_page('tln-business', 'Claims', 'Claims', 'manage_options', 'tln-claims', 'tln_claims_page');
    add_submenu_page('tln-business', 'Reviews', 'Reviews', 'manage_options', 'tln-reviews', 'tln_reviews_page');
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
    echo '<table class="widefat"><tr><th>Business</th><th>Owner</th><th>Phone</th><th>Status</th><th>Date</th><th>Action</th></tr>';
    foreach ($claims as $c) {
        echo '<tr><td>' . esc_html($c->business_name) . '</td><td>' . esc_html($c->claimant_name) . '</td><td>' . esc_html($c->claimant_phone) . '</td><td>' . esc_html($c->status) . '</td><td>' . esc_html($c->submitted_at) . '</td><td>';
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

function tln_reviews_page() {
    global $wpdb;
    $reviews = $wpdb->get_results("SELECT r.*, p.post_title FROM {$wpdb->prefix}tln_reviews r 
        LEFT JOIN {$wpdb->posts} p ON r.business_id = p.ID 
        ORDER BY r.created_at DESC");
    
    echo '<h1>Neighbor Reviews</h1>';
    if (empty($reviews)) {
        echo '<p>No reviews yet.</p>';
        return;
    }
    echo '<table class="widefat"><tr><th>Business</th><th>Quality</th><th>Value</th><th>Service</th><th>Exp.</th><th>Review</th><th>Status</th><th>Action</th></tr>';
    foreach ($reviews as $r) {
        echo '<tr><td>' . esc_html($r->post_title ?? 'N/A') . '</td><td>' . $r->quality . '</td><td>' . $r->value . '</td><td>' . $r->service . '</td><td>' . $r->customer_experience . '</td><td>' . substr(esc_html($r->review_text), 0, 30) . '...</td><td>' . $r->status . '</td><td>';
        if ($r->status == 'pending') {
            echo '<a href="?page=tln-reviews&action=approve=' . $r->id . '">Approve</a> | ';
            echo '<a href="?page=tln-reviews&action=delete=' . $r->id . '">Delete</a>';
        }
        echo '</td></tr>';
    }
    echo '</table>';
    
    if (isset($_GET['action'])) {
        $id = intval(str_replace(['approve=', 'delete='], '', $_GET['action']));
        if (strpos($_GET['action'], 'approve=') !== false) {
            $wpdb->update($wpdb->prefix . 'tln_reviews', array('status' => 'approved'), array('id' => $id));
        } else {
            $wpdb->delete($wpdb->prefix . 'tln_reviews', array('id' => $id));
        }
        echo '<meta http-equiv="refresh" content="0">';
    }
}

function tln_business_admin() {
    echo '<h1>TLN Business Dashboard</h1><p>Use the submenus to manage claims and reviews.</p>';
}
