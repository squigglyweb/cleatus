<?php
/**
 * Plugin Name: TLN Business Dashboard
 * Description: Business claim system, dashboard, offers, and community impact
 * Version: 1.0
 * Author: The Local NearBuy
 */

// Create database tables on activation
register_activation_hook(__FILE__, 'tln_business_install');

function tln_business_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Claims table
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_claims (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        business_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        status varchar(20) DEFAULT 'pending',
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate");
    
    // Business meta table
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_business_meta (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        business_id bigint(20) NOT NULL,
        meals_donated int(11) DEFAULT 0,
        custom_description text,
        video_url varchar(500),
        social_links longtext,
        show_impact tinyint(1) DEFAULT 1,
        PRIMARY KEY  (id),
        UNIQUE KEY business_id (business_id)
    ) $charset_collate");
    
    // Offers table
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
    
    // Reviews table
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

// Add menu item for admin
add_action('admin_menu', 'tln_business_menu');

function tln_business_menu() {
    add_menu_page('TLN Business', 'TLN Business', 'manage_options', 'tln-business', 'tln_business_admin', 'dashicons-store', 30);
    add_submenu_page('tln-business', 'Claims', 'Claims', 'manage_options', 'tln-claims', 'tln_claims_page');
    add_submenu_page('tln-business', 'Reviews', 'Reviews', 'manage_options', 'tln-reviews', 'tln_reviews_page');
}

// Claim form shortcode
add_shortcode('claim_business', 'tln_claim_form');

function tln_claim_form($atts) {
    $atts = shortcode_atts(array('business_id' => get_the_ID()), $atts, 'claim_business');
    $business_id = $atts['business_id'];
    
    if (!is_user_logged_in()) {
        return '<div class="tln-claim-login">
            <p>Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> or <a href="' . wp_registration_url() . '">register</a> to claim this business.</p>
        </div>';
    }
    
    // Check if already claimed
    global $wpdb;
    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_claims WHERE business_id = %d AND status = 'approved'", $business_id));
    
    if ($existing) {
        return '<div class="tln-already-claimed">This business has already been claimed.</div>';
    }
    
    ob_start();
    ?>
    <div class="tln-claim-form">
        <h3>Claim This Business</h3>
        <p>Are you the owner of this business? Claim it to manage your listing, add offers, and connect with neighbors.</p>
        
        <form method="post" action="">
            <input type="hidden" name="business_id" value="<?php echo esc_attr($business_id); ?>">
            <input type="hidden" name="tln_submit_claim" value="1">
            
            <p>
                <label>Your Name *</label><br>
                <input type="text" name="claimant_name" required style="width:100%;max-width:400px;">
            </p>
            <p>
                <label>Phone *</label><br>
                <input type="tel" name="claimant_phone" required style="width:100%;max-width:400px;">
            </p>
            <p>
                <label>Proof of Ownership</label><br>
                <small>Briefly describe your connection to this business</small><br>
                <textarea name="proof" rows="3" style="width:100%;max-width:400px;"></textarea>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="terms" required> I certify that I am authorized to manage this business listing.
                </label>
            </p>
            <p>
                <button type="submit" class="tln-btn">Submit Claim Request</button>
            </p>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// Handle claim submission
add_action('init', 'tln_process_claim');

function tln_process_claim() {
    if (!isset($_POST['tln_submit_claim'])) return;
    
    global $wpdb;
    $business_id = intval($_POST['business_id']);
    $user_id = get_current_user_id();
    
    // Insert claim
    $wpdb->insert($wpdb->prefix . 'tln_claims', array(
        'business_id' => $business_id,
        'user_id' => $user_id,
        'status' => 'pending'
    ));
    
    // Update post author
    wp_update_post(array('ID' => $business_id, 'post_author' => $user_id));
    
    // Add user meta to track their business
    update_user_meta($user_id, 'tln_claimed_business', $business_id);
    
    wp_redirect(add_query_arg('claim', 'submitted', wp_get_referer()));
    exit;
}

// Business dashboard shortcode
add_shortcode('business_dashboard', 'tln_business_dashboard');

function tln_business_dashboard() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . wp_login_url() . '">log in</a> to view your dashboard.</p>';
    }
    
    $user_id = get_current_user_id();
    $business_id = get_user_meta($user_id, 'tln_claimed_business', true);
    
    if (!$business_id) {
        return '<p>You have not claimed any businesses yet. <a href="/directory">Find a business to claim</a>.</p>';
    }
    
    // Handle form updates
    if (isset($_POST['tln_update_dashboard'])) {
        global $wpdb;
        
        // Update business meta
        $meta = array(
            'meals_donated' => intval($_POST['meals_donated']),
            'custom_description' => wp_kses_post($_POST['description']),
            'video_url' => esc_url_raw($_POST['video_url']),
            'show_impact' => isset($_POST['show_impact']) ? 1 : 0
        );
        
        // Social links
        $social = array(
            'instagram' => esc_url_raw($_POST['instagram']),
            'facebook' => esc_url_raw($_POST['facebook']),
            'tiktok' => esc_url_raw($_POST['tiktok']),
            'twitter' => esc_url_raw($_POST['twitter'])
        );
        $meta['social_links'] = json_encode($social);
        
        // Insert or update
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}tln_business_meta WHERE business_id = %d", $business_id));
        
        if ($existing) {
            $wpdb->update($wpdb->prefix . 'tln_business_meta', $meta, array('business_id' => $business_id));
        } else {
            $meta['business_id'] = $business_id;
            $wpdb->insert($wpdb->prefix . 'tln_business_meta', $meta);
        }
        
        // Handle offer
        if (!empty($_POST['offer_title'])) {
            $offer = array(
                'business_id' => $business_id,
                'title' => sanitize_text_field($_POST['offer_title']),
                'description' => wp_kses_post($_POST['offer_description']),
                'code' => sanitize_text_field($_POST['offer_code']),
                'start_date' => sanitize_text_field($_POST['offer_start']),
                'end_date' => sanitize_text_field($_POST['offer_end'])
            );
            
            // Check if offer exists
            $existing_offer = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}tln_offers WHERE business_id = %d", $business_id));
            
            if ($existing_offer) {
                $wpdb->update($wpdb->prefix . 'tln_offers', $offer, array('id' => $existing_offer));
            } else {
                $wpdb->insert($wpdb->prefix . 'tln_offers', $offer);
            }
        }
        
        echo '<div class="tln-success">Dashboard updated!</div>';
    }
    
    // Get existing data
    global $wpdb;
    $meta = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_business_meta WHERE business_id = %d", $business_id));
    $offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_offers WHERE business_id = %d", $business_id));
    
    $social = $meta ? json_decode($meta->social_links, true) : array();
    
    $business = get_post($business_id);
    
    ob_start();
    ?>
    <div class="tln-dashboard">
        <h2>Dashboard: <?php echo esc_html($business->post_title); ?></h2>
        
        <form method="post" class="tln-dashboard-form">
            <input type="hidden" name="tln_update_dashboard" value="1">
            
            <!-- Business Info -->
            <div class="tln-dashboard-section">
                <h3>📝 Business Information</h3>
                <p>
                    <label>Business Name</label>
                    <input type="text" name="business_name" value="<?php echo esc_attr($business->post_title); ?>" disabled>
                    <small>Contact us to change your business name</small>
                </p>
                <p>
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Tell neighbors about your business..."><?php echo esc_textarea($meta->custom_description ?? ''); ?></textarea>
                </p>
                <p>
                    <label>Video URL (YouTube or Vimeo)</label>
                    <input type="url" name="video_url" value="<?php echo esc_attr($meta->video_url ?? ''); ?>" placeholder="https://youtube.com/...">
                </p>
            </div>
            
            <!-- Community Impact -->
            <div class="tln-dashboard-section">
                <h3>🍽️ Community Impact</h3>
                <p>
                    <label>Meals Donated</label>
                    <input type="number" name="meals_donated" value="<?php echo intval($meta->meals_donated ?? 0); ?>" min="0">
                    <small>How many meals has your business helped provide?</small>
                </p>
                <p>
                    <label>
                        <input type="checkbox" name="show_impact" <?php checked($meta->show_impact ?? 1, 1); ?>>
                        Show our impact on our page
                    </label>
                </p>
            </div>
            
            <!-- Social Media -->
            <div class="tln-dashboard-section">
                <h3>📱 Social Media</h3>
                <p>
                    <label>Instagram</label>
                    <input type="url" name="instagram" value="<?php echo esc_attr($social['instagram'] ?? ''); ?>" placeholder="https://instagram.com/...">
                </p>
                <p>
                    <label>Facebook</label>
                    <input type="url" name="facebook" value="<?php echo esc_attr($social['facebook'] ?? ''); ?>" placeholder="https://facebook.com/...">
                </p>
                <p>
                    <label>TikTok</label>
                    <input type="url" name="tiktok" value="<?php echo esc_attr($social['tiktok'] ?? ''); ?>" placeholder="https://tiktok.com/...">
                </p>
                <p>
                    <label>Twitter/X</label>
                    <input type="url" name="twitter" value="<?php echo esc_attr($social['twitter'] ?? ''); ?>" placeholder="https://twitter.com/...">
                </p>
            </div>
            
            <!-- Special Offer -->
            <div class="tln-dashboard-section">
                <h3>🎁 Special Offer for Neighbors</h3>
                <p>
                    <label>Offer Title</label>
                    <input type="text" name="offer_title" value="<?php echo esc_attr($offer->title ?? ''); ?>" placeholder="e.g., 10% off for neighbors">
                </p>
                <p>
                    <label>Description</label>
                    <textarea name="offer_description" rows="2" placeholder="Details about the offer..."><?php echo esc_textarea($offer->description ?? ''); ?></textarea>
                </p>
                <p>
                    <label>Offer Code</label>
                    <input type="text" name="offer_code" value="<?php echo esc_attr($offer->code ?? ''); ?>" placeholder="e.g., NEIGHBOR10">
                </p>
                <p>
                    <label>Start Date</label>
                    <input type="date" name="offer_start" value="<?php echo esc_attr($offer->start_date ?? ''); ?>">
                </p>
                <p>
                    <label>End Date (leave empty for evergreen)</label>
                    <input type="date" name="offer_end" value="<?php echo esc_attr($offer->end_date ?? ''); ?>">
                </p>
            </div>
            
            <p><button type="submit" class="tln-btn tln-btn-primary">Save Changes</button></p>
        </form>
    </div>
    
    <style>
    .tln-dashboard { max-width: 800px; margin: 2rem 0; }
    .tln-dashboard-section { background: #f8f8f8; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 8px; }
    .tln-dashboard-section h3 { margin-top: 0; color: #1a1a1a; }
    .tln-dashboard label { display: block; font-weight: 600; margin-bottom: 0.25rem; }
    .tln-dashboard input[type="text"], .tln-dashboard input[type="url"], .tln-dashboard input[type="number"], .tln-dashboard input[type="date"], .tln-dashboard textarea { width: 100%; max-width: 400px; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; }
    .tln-dashboard small { color: #666; }
    .tln-btn { padding: 0.75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
    .tln-btn-primary { background: #e63946; color: white; }
    .tln-btn-primary:hover { background: #c1121f; }
    .tln-success { background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
    </style>
    <?php
    return ob_get_clean();
}

// Review form shortcode
add_shortcode('tln_review', 'tln_review_form');

function tln_review_form($atts) {
    $atts = shortcode_atts(array('business_id' => get_the_ID()), $atts, 'tln_review');
    $business_id = $atts['business_id'];
    
    if (isset($_POST['tln_submit_review'])) {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix . 'tln_reviews', array(
            'business_id' => $business_id,
            'quality' => intval($_POST['quality']),
            'value' => intval($_POST['value']),
            'service' => intval($_POST['service']),
            'customer_experience' => intval($_POST['customer_experience']),
            'review_text' => sanitize_textarea_field($_POST['review_text']),
            'reviewer_name' => sanitize_text_field($_POST['reviewer_name']),
            'status' => 'pending' // Moderation queue
        ));
        
        return '<div class="tln-review-success">Thank you! Your review has been submitted and is pending approval.</div>';
    }
    
    ob_start();
    ?>
    <div class="tln-review-form">
        <h3>What Do You Think?</h3>
        <p>Rate your experience at this business.</p>
        
        <form method="post">
            <input type="hidden" name="tln_submit_review" value="1">
            
            <div class="tln-rating-group">
                <label>Quality</label>
                <div class="tln-stars">
                    <?php for ($i=1; $i<=5; $i++): ?>
                    <label><input type="radio" name="quality" value="<?php echo $i; ?>" required> <?php echo $i; ?> ★</label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="tln-rating-group">
                <label>Value</label>
                <div class="tln-stars">
                    <?php for ($i=1; $i<=5; $i++): ?>
                    <label><input type="radio" name="value" value="<?php echo $i; ?>" required> <?php echo $i; ?> ★</label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="tln-rating-group">
                <label>Service</label>
                <div class="tln-stars">
                    <?php for ($i=1; $i<=5; $i++): ?>
                    <label><input type="radio" name="service" value="<?php echo $i; ?>" required> <?php echo $i; ?> ★</label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="tln-rating-group">
                <label>Customer Experience</label>
                <div class="tln-stars">
                    <?php for ($i=1; $i<=5; $i++): ?>
                    <label><input type="radio" name="customer_experience" value="<?php echo $i; ?>" required> <?php echo $i; ?> ★</label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <p>
                <label>Your Review</label>
                <textarea name="review_text" rows="4" required placeholder="Tell neighbors about your experience..."></textarea>
            </p>
            
            <p>
                <label>Your Name</label>
                <input type="text" name="reviewer_name" required>
            </p>
            
            <button type="submit" class="tln-btn">Submit Review</button>
        </form>
    </div>
    
    <style>
    .tln-review-form { background: #f8f8f8; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; }
    .tln-rating-group { margin-bottom: 1rem; }
    .tln-rating-group label { font-weight: 600; display: block; }
    .tln-stars label { display: inline-block; margin-right: 1rem; font-weight: normal; }
    .tln-review-form input[type="text"], .tln-review-form textarea { width: 100%; max-width: 400px; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
    .tln-review-success { background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; }
    </style>
    <?php
    return ob_get_clean();
}

// Admin pages
function tln_claims_page() {
    global $wpdb;
    $claims = $wpdb->get_results("SELECT c.*, p.post_title, u.display_name FROM {$wpdb->prefix}tln_claims c 
        JOIN {$wpdb->posts} p ON c.business_id = p.ID 
        JOIN {$wpdb->users} u ON c.user_id = u.ID 
        ORDER BY c.submitted_at DESC");
    
    echo '<h1>Business Claims</h1><table><tr><th>Business</th><th>User</th><th>Status</th><th>Date</th><th>Action</th></tr>';
    foreach ($claims as $claim) {
        echo '<tr><td>' . esc_html($claim->post_title) . '</td><td>' . esc_html($claim->display_name) . '</td><td>' . esc_html($claim->status) . '</td><td>' . esc_html($claim->submitted_at) . '</td><td>';
        if ($claim->status == 'pending') {
            echo '<a href="?page=tln-claims&action=approve=' . $claim->id . '">Approve</a> | ';
            echo '<a href="?page=tln-claims&action=reject=' . $claim->id . '">Reject</a>';
        }
        echo '</td></tr>';
    }
    echo '</table>';
    
    // Handle actions
    if (isset($_GET['action'])) {
        if (strpos($_GET['action'], 'approve=') !== false) {
            $id = intval(str_replace('approve=', '', $_GET['action']));
            $wpdb->update($wpdb->prefix . 'tln_claims', array('status' => 'approved'), array('id' => $id));
        }
        if (strpos($_GET['action'], 'reject=') !== false) {
            $id = intval(str_replace('reject=', '', $_GET['action']));
            $wpdb->update($wpdb->prefix . 'tln_claims', array('status' => 'rejected'), array('id' => $id));
        }
        echo '<meta http-equiv="refresh" content="0">';
    }
}

function tln_reviews_page() {
    global $wpdb;
    $reviews = $wpdb->get_results("SELECT r.*, p.post_title FROM {$wpdb->prefix}tln_reviews r 
        JOIN {$wpdb->posts} p ON r.business_id = p.ID 
        ORDER BY r.created_at DESC");
    
    echo '<h1>Neighbor Reviews</h1><table><tr><th>Business</th><th>Quality</th><th>Value</th><th>Service</th><th>Customer Exp.</th><th>Review</th><th>Status</th><th>Action</th></tr>';
    foreach ($reviews as $r) {
        echo '<tr><td>' . esc_html($r->post_title) . '</td><td>' . $r->quality . '</td><td>' . $r->value . '</td><td>' . $r->service . '</td><td>' . $r->customer_experience . '</td><td>' . substr(esc_html($r->review_text), 0, 50) . '...</td><td>' . $r->status . '</td><td>';
        if ($r->status == 'pending') {
            echo '<a href="?page=tln-reviews&action=approve=' . $r->id . '">Approve</a> | ';
            echo '<a href="?page=tln-reviews&action=delete=' . $r->id . '">Delete</a>';
        }
        echo '</td></tr>';
    }
    echo '</table>';
    
    if (isset($_GET['action'])) {
        if (strpos($_GET['action'], 'approve=') !== false) {
            $id = intval(str_replace('approve=', '', $_GET['action']));
            $wpdb->update($wpdb->prefix . 'tln_reviews', array('status' => 'approved'), array('id' => $id));
        }
        if (strpos($_GET['action'], 'delete=') !== false) {
            $id = intval(str_replace('delete=', '', $_GET['action']));
            $wpdb->delete($wpdb->prefix . 'tln_reviews', array('id' => $id));
        }
        echo '<meta http-equiv="refresh" content="0">';
    }
}

function tln_business_admin() {
    echo '<h1>TLN Business Dashboard</h1><p>Use the submenus to manage claims and reviews.</p>';
}
