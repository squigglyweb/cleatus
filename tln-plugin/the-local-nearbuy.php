<?php
/*
Plugin Name: The Local NearBuy - Business Profiles
Description: Custom post type for business profiles with tier-based access
Version: 1.0
Author: TLN
*/

// Create Business Custom Post Type
function tln_register_business_post_type() {
    $labels = array(
        'name' => 'Businesses',
        'singular_name' => 'Business',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Business',
        'edit_item' => 'Edit Business',
        'new_item' => 'New Business',
        'view_item' => 'View Business',
        'search_items' => 'Search Businesses',
        'not_found' => 'No businesses found',
        'not_found_in_trash' => 'No businesses found in trash',
    );
    
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => false,
        'show_in_nav_menus' => true,
        'rewrite' => array('slug' => 'business', 'with_front' => false),
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'menu_icon' => 'dashicons-store',
        'menu_position' => 20,
        'show_in_rest' => true,
    );
    
    register_post_type('tln_business', $args);
}
add_action('init', 'tln_register_business_post_type');

// Flush rewrite rules on plugin activation
function tln_flush_rewrite_rules() {
    tln_register_business_post_type();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'tln_flush_rewrite_rules');

// Add meta boxes for business data
function tln_business_meta_boxes() {
    add_meta_box('tln_business_details', 'Business Details', 'tln_business_details_cb', 'tln_business', 'normal', 'high');
    add_meta_box('tln_business_tier', 'Membership Tier', 'tln_business_tier_cb', 'tln_business', 'side', 'default');
}
add_action('add_meta_boxes', 'tln_business_meta_boxes');

function tln_business_details_cb($post) {
    $tier = get_post_meta($post->ID, 'tln_tier', true);
    $phone = get_post_meta($post->ID, 'tln_phone', true);
    $email = get_post_meta($post->ID, 'tln_email', true);
    $address = get_post_meta($post->ID, 'tln_address', true);
    $city = get_post_meta($post->ID, 'tln_city', true);
    $state = get_post_meta($post->ID, 'tln_state', true);
    $zip = get_post_meta($post->ID, 'tln_zip', true);
    $website = get_post_meta($post->ID, 'tln_website', true);
    $hours_mon = get_post_meta($post->ID, 'tln_hours_mon', true);
    $hours_tue = get_post_meta($post->ID, 'tln_hours_tue', true);
    $hours_wed = get_post_meta($post->ID, 'tln_hours_wed', true);
    $hours_thu = get_post_meta($post->ID, 'tln_hours_thu', true);
    $hours_fri = get_post_meta($post->ID, 'tln_hours_fri', true);
    $hours_sat = get_post_meta($post->ID, 'tln_hours_sat', true);
    $hours_sun = get_post_meta($post->ID, 'tln_hours_sun', true);
    $google_place_id = get_post_meta($post->ID, 'tln_google_place_id', true);
    $google_rating = get_post_meta($post->ID, 'tln_google_rating', true);
    $tln_score = get_post_meta($post->ID, 'tln_neighborhood_score', true);
    $meals_count = get_post_meta($post->ID, 'tln_meals_count', true);
    
    echo '<div class="tln-meta-grid">';
    echo '<p><label>Phone: <input type="text" name="tln_phone" value="'.esc_attr($phone).'" style="width:100%"></label></p>';
    echo '<p><label>Email: <input type="email" name="tln_email" value="'.esc_attr($email).'" style="width:100%"></label></p>';
    echo '<p><label>Address: <input type="text" name="tln_address" value="'.esc_attr($address).'" style="width:100%"></label></p>';
    echo '<p><label>City: <input type="text" name="tln_city" value="'.esc_attr($city).'" style="width:100%"></label></p>';
    echo '<p><label>State: <input type="text" name="tln_state" value="'.esc_attr($state).'" style="width:100%"></label></p>';
    echo '<p><label>ZIP: <input type="text" name="tln_zip" value="'.esc_attr($zip).'" style="width:100%"></label></p>';
    echo '<p><label>Website: <input type="url" name="tln_website" value="'.esc_attr($website).'" style="width:100%"></label></p>';
    echo '<p><label>Google Place ID: <input type="text" name="tln_google_place_id" value="'.esc_attr($google_place_id).'" style="width:100%"></label></p>';
    echo '<hr><h4>Hours</h4>';
    echo '<p><label>Mon: <input type="text" name="tln_hours_mon" value="'.esc_attr($hours_mon).'" placeholder="7:00 AM - 6:00 PM"></label></p>';
    echo '<p><label>Tue: <input type="text" name="tln_hours_tue" value="'.esc_attr($hours_tue).'" placeholder="7:00 AM - 6:00 PM"></label></p>';
    echo '<p><label>Wed: <input type="text" name="tln_hours_wed" value="'.esc_attr($hours_wed).'" placeholder="7:00 AM - 6:00 PM"></label></p>';
    echo '<p><label>Thu: <input type="text" name="tln_hours_thu" value="'.esc_attr($hours_thu).'" placeholder="7:00 AM - 6:00 PM"></label></p>';
    echo '<p><label>Fri: <input type="text" name="tln_hours_fri" value="'.esc_attr($hours_fri).'" placeholder="7:00 AM - 6:00 PM"></label></p>';
    echo '<p><label>Sat: <input type="text" name="tln_hours_sat" value="'.esc_attr($hours_sat).'" placeholder="8:00 AM - 2:00 PM"></label></p>';
    echo '<p><label>Sun: <input type="text" name="tln_hours_sun" value="'.esc_attr($hours_sun).'" placeholder="Closed"></label></p>';
    echo '<hr><h4>Scores</h4>';
    echo '<p><label>Google Rating: <input type="text" name="tln_google_rating" value="'.esc_attr($google_rating).'" placeholder="4.5"></label></p>';
    echo '<p><label>TLN Neighborhood Score: <input type="text" name="tln_neighborhood_score" value="'.esc_attr($tln_score).'" placeholder="4.8"></label></p>';
    echo '<p><label>Meals Provided: <input type="number" name="tln_meals_count" value="'.esc_attr($meals_count).'" placeholder="0"></label></p>';
    echo '</div>';
}

function tln_business_tier_cb($post) {
    $tier = get_post_meta($post->ID, 'tln_tier', true);
    echo '<select name="tln_tier" style="width:100%">';
    echo '<option value="free" '.selected($tier, 'free', false).'>Free</option>';
    echo '<option value="pro" '.selected($tier, 'pro', false).'>Pro ($99/mo)</option>';
    echo '<option value="proplus" '.selected($tier, 'proplus', false).'>Pro+ ($199/mo)</option>';
    echo '<option value="sponsor" '.selected($tier, 'sponsor', false).'>Sponsor ($349/mo)</option>';
    echo '</select>';
}

// Save meta box data
function tln_save_business_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $fields = array('tln_tier', 'tln_phone', 'tln_email', 'tln_address', 'tln_city', 'tln_state', 'tln_zip', 'tln_website', 'tln_google_place_id', 'tln_google_rating', 'tln_neighborhood_score', 'tln_meals_count', 'tln_hours_mon', 'tln_hours_tue', 'tln_hours_wed', 'tln_hours_thu', 'tln_hours_fri', 'tln_hours_sat', 'tln_hours_sun');
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post', 'tln_save_business_meta');

// Create business template based on tier
function tln_business_template($template) {
    if (is_singular('tln_business')) {
        $tier = get_post_meta(get_the_ID(), 'tln_tier', true);
        $custom_template = plugin_dir_path(__FILE__) . 'templates/profile-'.$tier.'.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
        // Fallback to free template
        $fallback_template = plugin_dir_path(__FILE__) . 'templates/profile-free.php';
        if (file_exists($fallback_template)) {
            return $fallback_template;
        }
    }
    return $template;
}
add_filter('template_include', 'tln_business_template', 99);

// Add rewrite rules for pretty URLs
add_action('init', 'tln_business_rewrite_rules');

// Handle query var
function tln_business_query_vars($vars) {
    $vars[] = 'tln_business';
    return $vars;
}
add_filter('query_vars', 'tln_business_query_vars');
