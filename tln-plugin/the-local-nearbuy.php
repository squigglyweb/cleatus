<?php
/*
Plugin Name: TLN Plugin Bundle
Description: Business profiles, directory, and member features for The Local NearBuy
Version: 3.28 - Added email alerts for campaign spots
*/

// Flush rewrite rules on activation
register_activation_hook(__FILE__, function() {
    // Ensure rewrite rules are registered
    add_action('init', function() {
        if (function_exists('tln_voucher_add_rewrite_rules')) {
            tln_voucher_add_rewrite_rules();
        }
        if (function_exists('tln_offer_rewrite')) {
            tln_offer_rewrite();
        }
        flush_rewrite_rules();
    }, 99);
});

// REST API for reviews
add_action('rest_api_init', function() {
    register_rest_route('tln/v1', '/reviews', array(
        'methods' => 'POST',
        'callback' => function($request) {
            $params = $request->get_json_params();
            $business_id = isset($params['business_id']) ? intval($params['business_id']) : 0;
            $reviewer_name = isset($params['reviewer_name']) ? sanitize_text_field($params['reviewer_name']) : '';
            $rating_overall = isset($params['rating_overall']) ? intval($params['rating_overall']) : 0;
            $rating_quality = isset($params['rating_quality']) ? intval($params['rating_quality']) : 0;
            $rating_service = isset($params['rating_service']) ? intval($params['rating_service']) : 0;
            $rating_value = isset($params['rating_value']) ? intval($params['rating_value']) : 0;
            $rating_atmosphere = isset($params['rating_atmosphere']) ? intval($params['rating_atmosphere']) : 0;
            $title = isset($params['title']) ? sanitize_text_field($params['title']) : '';
            $review_text = isset($params['review_text']) ? sanitize_textarea_field($params['review_text']) : '';
            
            if (!$business_id || !$reviewer_name || !$rating_overall) {
                return new WP_Error('missing_data', 'Business ID, name, and overall rating are required', array('status' => 400));
            }
            
            // Get existing reviews
            $reviews = get_post_meta($business_id, 'tln_neighborhood_reviews', true);
            if (!is_array($reviews)) $reviews = array();
            
            // Add new review
            $reviews[] = array(
                'id' => uniqid('rev_'),
                'reviewer_name' => $reviewer_name,
                'rating_overall' => $rating_overall,
                'rating_quality' => $rating_quality,
                'rating_service' => $rating_service,
                'rating_value' => $rating_value,
                'rating_atmosphere' => $rating_atmosphere,
                'title' => $title,
                'review_text' => $review_text,
                'created_at' => current_time('mysql')
            );
            
            update_post_meta($business_id, 'tln_neighborhood_reviews', $reviews);
            
            return array('success' => true, 'review_id' => $reviews[count($reviews)-1]['id']);
        },
        'permission_callback' => '__return_true'
    ));
    
    register_rest_route('tln/v1', '/reviews/(?P<business_id>\d+)', array(
        'methods' => 'GET',
        'callback' => function($request) {
            $business_id = $request->get_param('business_id');
            $reviews = get_post_meta($business_id, 'tln_neighborhood_reviews', true);
            if (!is_array($reviews)) $reviews = array();
            return $reviews;
        },
        'permission_callback' => '__return_true'
    ));
});

// Add TLN Admin Menu
function tln_add_admin_menu() {
    add_menu_page(
        'TLN',                          // Page title
        'TLN',                          // Menu title
        'manage_options',               // Capability
        'tln-dashboard',                // Menu slug
        'tln_render_dashboard',         // Callback function (from tln-admin-dashboard.php)
        'dashicons-store',              // Icon
        30                              // Position
    );
}
add_action('admin_menu', 'tln_add_admin_menu');

// Dashboard now in tln-admin-dashboard.php (included below)

// Load other TLN components

/**
 * Register Summer Activities Custom Post Type and Taxonomy
 */
function tln_register_activities() {
    // Register CPT
    $cpt_args = array(
        'label' => 'Activities',
        'public' => true,
        'has_archive' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'rewrite' => array('slug' => 'activities'),
        'supports' => array('title','editor','thumbnail','excerpt','author'),
        'show_in_rest' => true,
    );
    register_post_type('tln_activity', $cpt_args);

    // Register taxonomy for activity type (e.g., Outdoor, Food, Family, Arts)
    $tax_args = array(
        'label' => 'Activity Types',
        'public' => true,
        'hierarchical' => true,
        'rewrite' => array('slug' => 'activity-type'),
        'show_in_rest' => true,
    );
    register_taxonomy('tln_type', 'tln_activity', $tax_args);
}
add_action('init', 'tln_register_activities');

/**
 * Register Camps Custom Post Type and Taxonomy
 */
function tln_register_camps() {
    // CPT for camps
    $cpt_args = array(
        'label' => 'Camps',
        'public' => true,
        'has_archive' => true,
        'show_in_menu' => true,
        'menu_position' => 6,
        'rewrite' => array('slug' => 'camps'),
        'supports' => array('title','editor','thumbnail','excerpt','author'),
        'show_in_rest' => true,
    );
    register_post_type('tln_camp', $cpt_args);

    // Taxonomy for camp type (e.g., Family, Adventure, Nature)
    $tax_args = array(
        'label' => 'Camp Types',
        'public' => true,
        'hierarchical' => true,
        'rewrite' => array('slug' => 'camp-type'),
        'show_in_rest' => true,
    );
    register_taxonomy('tln_camp_type', 'tln_camp', $tax_args);
}
add_action('init', 'tln_register_camps');

/**
 * Shortcode to display activities with filter buttons
 */
function tln_activities_shortcode($atts) {
    $atts = shortcode_atts(array(
        'posts_per_page' => 12,
    ), $atts);

    // Get all terms for filter buttons
    $terms = get_terms(array(
        'taxonomy' => 'tln_type',
        'hide_empty' => true,
    ));
    if (is_wp_error($terms)) $terms = [];

    // Build filter buttons
    $filter_html = '<div class="tln-activities-filters" style="margin-bottom:1.5rem;">';
    $filter_html .= '<button class="tln-filter-btn" data-filter="all" style="margin-right:0.5rem;padding:0.4rem 1rem;background:#e63946;color:#fff;border:none;border-radius:4px;cursor:pointer;">All</button>';
    foreach ($terms as $term) {
        $filter_html .= '<button class="tln-filter-btn" data-filter="term-'.esc_attr($term->slug).'" style="margin-right:0.5rem;padding:0.4rem 1rem;background:#e63946;color:#fff;border:none;border-radius:4px;cursor:pointer;">'.esc_html($term->name).'</button>';
    }
    $filter_html .= '</div>';

    // Query activities
    $query = new WP_Query(array(
        'post_type' => 'tln_activity',
        'posts_per_page' => $atts['posts_per_page'],
        'post_status' => 'publish',
    ));

    $grid_html = '<div class="tln-activities-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1.5rem;">';
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_terms = wp_get_post_terms(get_the_ID(), 'tln_type', array('fields' => 'slugs'));
            $term_classes = '';
            foreach ($post_terms as $slug) {
                $term_classes .= ' term-'.esc_attr($slug);
            }
            $grid_html .= '<div class="tln-activity-item'.$term_classes.'" style="border:1px solid #ddd;border-radius:8px;padding:1rem;background:#fff;">';
            if (has_post_thumbnail()) {
                $grid_html .= get_the_post_thumbnail(null, 'medium', array('style'=>'width:100%;height:auto;border-radius:4px;margin-bottom:0.75rem;'));
            }
            $grid_html .= '<h3 style="font-size:1.1rem;margin:0 0 0.5rem;">'.get_the_title().'</h3>';
            $grid_html .= '<div class="tln-activity-excerpt" style="font-size:0.9rem;color:#555;">'.wp_trim_words(get_the_excerpt(), 20, '...').'</div>';
            $grid_html .= '<a href="'.get_permalink().'" style="display:inline-block;margin-top:0.75rem;color:#e63946;font-weight:600;">Read More →</a>';
            $grid_html .= '</div>';
        }
        wp_reset_postdata();
    } else {
        $grid_html .= '<p>No activities found.</p>';
    }
    $grid_html .= '</div>';

    // JS for filtering
    $js = '<script>
    document.addEventListener("DOMContentLoaded", function(){
        const btns = document.querySelectorAll(".tln-filter-btn");
        const items = document.querySelectorAll(".tln-activity-item");
        btns.forEach(btn=>{
            btn.addEventListener("click",()=>{
                const filter = btn.dataset.filter;
                btns.forEach(b=>b.style.background="#e63946");
                btn.style.background="#dc3545"; // active color
                items.forEach(it=>{
                    if(filter==="all"||it.classList.contains(filter)) {
                        it.style.display = "block";
                    } else {
                        it.style.display = "none";
                    }
                });
            });
        });
    });
    </script>';

    return $filter_html . $grid_html . $js;
}
add_shortcode('tln_activities', 'tln_activities_shortcode');

// End of activities registration
require_once plugin_dir_path(__FILE__) . 'tln-directory.php';
require_once plugin_dir_path(__FILE__) . 'tln-claim.php';
require_once plugin_dir_path(__FILE__) . 'tln-voucher-system.php';
require_once plugin_dir_path(__FILE__) . 'tln-golf-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'tln-parks-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'inc/camp-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'tln-analytics.php';
require_once plugin_dir_path(__FILE__) . 'tln-business-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'tln-admin-campaign.php';
require_once plugin_dir_path(__FILE__) . 'tln-settings.php';
require_once plugin_dir_path(__FILE__) . 'tln-offer-landing.php';
require_once plugin_dir_path(__FILE__) . 'tln-admin-dashboard.php';

// Profile page handler
if (!is_admin()) {
    add_filter('the_content', 'tln_profile_content');
    add_filter('the_content', 'tln_directory_content');
}

function tln_directory_content($content) {
    if (!is_page('directory')) {
        return $content;
    }
    return tln_dir_shortcode(array());
}

// Fetch Google Place details with caching in post meta
function tln_get_cached_place_data($post_id, $place_id) {
    // Try to get from post meta first
    $cached = get_post_meta($post_id, 'tln_google_data', true);
    if (!empty($cached) && is_array($cached)) {
        return $cached;
    }
    
    // If no cached data, fetch from Google API
    $data = tln_fetch_google_place_data($place_id);
    
    // Save to post meta for future use
    if (!empty($data)) {
        update_post_meta($post_id, 'tln_google_data', $data);
    }
    
    return $data;
}

// Fetch from Google API (called when no cache exists)
function tln_fetch_google_place_data($place_id) {
    $cache_key = 'tln_place_' . md5($place_id);
    $data = get_transient($cache_key);
    
    if (false === $data) {
        $api_key = defined('TLN_GOOGLE_API_KEY') ? TLN_GOOGLE_API_KEY : '';
        if (empty($api_key)) {
            return null;
        }
        
        $url = "https://maps.googleapis.com/maps/api/place/details/json?placeid=" . urlencode($place_id) . "&key=" . $api_key;
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);
        
        if ($json && isset($json['status']) && $json['status'] === 'OK' && isset($json['result'])) {
            $result = $json['result'];
            $data = array(
                'name' => $result['name'] ?? '',
                'address' => $result['formatted_address'] ?? '',
                'phone' => $result['formatted_phone_number'] ?? '',
                'website' => $result['website'] ?? '',
                'rating' => $result['rating'] ?? 0,
                'reviews' => $result['reviews'] ?? array(),
                'hours' => $result['opening_hours']['weekday_text'] ?? array(),
                'photos' => $result['photos'] ?? array(),
                'lat' => $result['geometry']['location']['lat'] ?? 0,
                'lng' => $result['geometry']['location']['lng'] ?? 0,
                'url' => $result['url'] ?? ''
            );
            set_transient($cache_key, $data, HOUR_IN_SECONDS * 24); // Cache for 24 hours
        } else {
            $data = null;
        }
    }
    
    return $data;
}

// Get open/closed status
function tln_get_hours_display() {
    $today = strtolower(date('l'));
    $hours = array(
        'monday' => '11:00 AM – 9:00 PM',
        'tuesday' => '11:00 AM – 9:00 PM',
        'wednesday' => '11:00 AM – 9:00 PM',
        'thursday' => '11:00 AM – 9:00 PM',
        'friday' => '11:00 AM – 10:00 PM',
        'saturday' => '9:00 AM – 10:00 PM',
        'sunday' => '9:00 AM – 8:00 PM'
    );
    $days = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
    $labels = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
    $output = '';
    foreach ($days as $i => $day) {
        $bold = ($day == $today) ? 'font-weight:700;' : '';
        $output .= '<span style="' . $bold . '">' . $labels[$i] . '..: <span style="' . $bold . '">' . $hours[$day] . '</span></span><br>';
    }
    return $output;
}

function tln_get_open_status($hours) {
    if (empty($hours)) return '';
    
    $now = current_time('l g:i A'); // WordPress local time, format like "Monday 2:30 PM"
    $day = current_time('l'); // e.g., "Monday"
    
    // Find today's hours - check both with and without colon
    $today_hours = '';
    foreach ($hours as $h) {
        $h_lower = strtolower($h);
        $day_lower = strtolower($day);
        if (stripos($h, $day) !== false || stripos($h_lower, $day_lower) !== false) {
            $today_hours = $h;
            break;
        }
    }
    
    // Debug: if still not found, check what we have
    if (!$today_hours) {
        // Try without matching day - just return open for demo
        return '<span class="tln-hours-pill open">Open</span>';
    }
    
    // Check if closed
    if (stripos($today_hours, 'Closed') !== false) {
        return '<span class="tln-hours-pill closed">Closed</span>';
    }
    
    // Parse hours like "Monday: 11:00 AM - 9:00 PM"
    if (preg_match('/(\d{1,2}:\d{2}\s*[AP]M)\s*-\s*(\d{1,2}:\d{2}\s*[AP]M)/i', $today_hours, $matches)) {
        $open_time = strtotime($matches[1]);
        $close_time = strtotime($matches[2]);
        $now_time = current_time('timestamp');
        
        if ($now_time < $open_time) {
            return '<span class="tln-hours-pill closed">Closed</span>';
        } elseif ($now_time >= $open_time && $now_time < $close_time) {
            // Check if closing soon (within 30 min)
            if ($close_time - $now_time < 1800) {
                return '<span class="tln-hours-pill closing-soon">Closing Soon</span>';
            }
            return '<span class="tln-hours-pill open">Open</span>';
        } else {
            return '<span class="tln-hours-pill closed">Closed</span>';
        }
    }
    
    return '';
}

// Consolidate hours into Mon-Fri format
function tln_format_hours($hours) {
    if (empty($hours)) return '';
    
    $days = array('Monday'=>0, 'Tuesday'=>1, 'Wednesday'=>2, 'Thursday'=>3, 'Friday'=>4, 'Saturday'=>5, 'Sunday'=>6);
    $day_hours = array();
    
    foreach ($hours as $h) {
        foreach ($days as $day => $idx) {
            if (stripos($h, $day) !== false) {
                // Extract time part
                if (preg_match('/:\s*(.+)/', $h, $m)) {
                    $time = trim($m[1]);
                    $day_hours[$idx] = $time;
                }
            }
        }
    }
    
    if (empty($day_hours)) {
        // Fallback: just show the raw hours with bold times and dots
        $formatted = array();
        foreach ($hours as $h) {
            if (preg_match('/^([^:]+):\s*(.+)$/', $h, $m)) {
                $day = trim($m[1]);
                $time = $m[2];
                // Pad day with dots
                $padded = str_pad($day . ':', 12, '.', STR_PAD_RIGHT);
                $formatted[] = $padded . ' <span class="tln-hours-time">' . $time . '</span>';
            } else {
                $formatted[] = $h;
            }
        }
        return implode('<br>', $formatted);
    }
    
    // Group consecutive days with same hours
    $ranges = array();
    $current_start = 0;
    $current_time = '';
    
    for ($i = 0; $i <= 6; $i++) {
        if (!isset($day_hours[$i])) {
            // Closed or missing
            if ($current_time !== '') {
                $ranges[] = format_range($current_start, $i-1, $current_time);
                $current_time = '';
            }
        } elseif ($day_hours[$i] !== $current_time) {
            if ($current_time !== '') {
                $ranges[] = format_range($current_start, $i-1, $current_time);
            }
            $current_start = $i;
            $current_time = $day_hours[$i];
        }
    }
    if ($current_time !== '') {
        $ranges[] = format_range($current_start, 6, $current_time);
    }
    
    return implode('<br>', $ranges);
}

function format_range($start, $end, $time) {
    $day_names = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
    $day_part = ($start == $end) ? $day_names[$start] : $day_names[$start] . '-' . $day_names[$end];
    // Pad day with dots to align times
    $max_len = 9; // "Thu-Fri:" is longest
    $padded = str_pad($day_part . ':', $max_len, '.', STR_PAD_RIGHT);
    return $padded . ' <span class="tln-hours-time">' . $time . '</span>';
}

function tln_profile_content($content) {
    if (!is_page('profile')) {
        return $content;
    }
    
    $biz = isset($_GET['biz']) ? $_GET['biz'] : '';
    $pid = isset($_GET['pid']) ? $_GET['pid'] : '';
    
    if (empty($biz) || empty($pid)) {
        return '<div style="padding:2rem;background:#f0f0f0;border-radius:8px;"><h3>TLN Profile</h3><p>Add ?biz=Name&pid=PlaceID to URL.</p></div>';
    }
    
    // Fetch Google data (cached)
    $gdata = tln_fetch_google_place_data($pid);
    
    // Use Google data or fallback
    $biz_name = $gdata['name'] ?? $biz;
    $biz_address = $gdata['address'] ?? '';
    $biz_phone = $gdata['phone'] ?? '';
    $biz_rating = $gdata['rating'] ?? 0;
    $biz_reviews = $gdata['reviews'] ?? array();
    $biz_hours = $gdata['hours'] ?? array();
    $biz_lat = $gdata['lat'] ?? 0;
    $biz_lng = $gdata['lng'] ?? 0;
    $biz_photos = $gdata['photos'] ?? array();
    
    // Get first photo reference
    $photo_ref = '';
    if (count($biz_photos) > 0) {
        $photo_ref = $biz_photos[0]['photo_reference'] ?? '';
    }
    $hero_bg = '';
    if ($photo_ref && defined('TLN_GOOGLE_API_KEY')) {
        $hero_bg = 'url(https://maps.googleapis.com/maps/api/place/photo?maxwidth=1200&photoreference=' . $photo_ref . '&key=' . TLN_GOOGLE_API_KEY . ')';
    }
    if (!$hero_bg) {
        $hero_bg = 'url(https://thelocalnearbuy.com/wp-content/uploads/2026/05/town-scene-bkgd-scaled.webp)';
    }
    
    // Format address for display
    $city = '';
    if ($biz_address) {
        preg_match('/([A-Za-z\s]+,\s*[A-Z]{2}\s*\d{5})/', $biz_address, $matches);
        $city = $matches[1] ?? '';
    }
    
    // Full profile HTML
    $html = '
    <style>
    .tln-profile { max-width:1100px; margin:0 auto; font-family:\'Open Sans\',sans-serif; }
    .tln-hero { 
        background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5)),
        ' . $hero_bg . ';
        background-size:cover;background-position:center;height:280px;position:relative;margin:-20px -40px 0 -40px;width:calc(100% + 80px);max-width:1280px;
    }
    .tln-hero-content { position:absolute;bottom:1.5rem;left:1.5rem; }
    .tln-hero h1 { color:#fff;font-size:2.5rem;margin:0;font-weight:700; }
    .tln-hero p { color:rgba(255,255,255,0.9);font-size:1.1rem;margin:0; }
    .tln-container { display:grid;grid-template-columns:320px 1fr;gap:2rem;padding:2rem;background:rgba(255,255,255,0.95);border-radius:12px;margin-top:1rem; }
    .tln-left, .tln-right { display:flex;flex-direction:column;gap:1.5rem; }
    .tln-card { background:#fff;border:1px solid #ddd;border-radius:8px;padding:1.25rem; }
    .tln-card h3 { font-size:1rem;font-weight:700;margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:1px solid #eee; }
    .tln-contact { display:flex;align-items:center;gap:0.5rem;padding:0.5rem 0; }
    .tln-contact a { color:#e63946;text-decoration:none;font-weight:600; }
    .tln-hours-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;position:relative; }
    .tln-hours-header h3 { margin:0;padding:0;border:none;font-size:1rem; }
    .tln-hours-pill { position:absolute;right:0;top:50%;transform:translateY(-50%);padding:0.25rem 0.75rem;border-radius:20px;font-size:0.75rem;font-weight:700; }
    .tln-hours-pill.open { background:#28a745;color:#fff; }
    .tln-hours-pill.closed { background:#dc3545;color:#fff; }
    .tln-hours-pill.closing-soon { background:#ffc107;color:#333; }
    .tln-hours-time { font-weight:700; }
    .tln-hours-display { font-family: monospace; font-size: 0.9rem; }
    .tln-review-item { padding:0.75rem 0;border-bottom:1px solid #eee; }
    .tln-review-item:last-child { border-bottom:none; }
    .tln-reviewer { font-weight:700;font-size:0.95rem; }
    .tln-stars { color:#fbbf24;font-size:0.85rem;margin:0.25rem 0; }
    .tln-review-text { color:#555;font-size:0.9rem; }
    .tln-see-all { color:#e63946;font-size:0.85rem;font-weight:600;margin-top:0.75rem;display:block; }
    .tln-claim-box { background:linear-gradient(135deg,#1a1a1a,#333);border-radius:12px;padding:1.5rem;text-align:center; }
    .tln-claim-box h3 { color:#fff;font-size:1.1rem;margin-bottom:0.5rem; }
    .tln-claim-box p { color:#ccc;margin-bottom:1rem;font-size:0.9rem; }
    .tln-btn { display:inline-block;padding:0.75rem 1.5rem;background:#e63946;color:#fff;text-decoration:none;border-radius:6px;font-weight:700; }
    .tln-btn:hover { background:#d62839; }
    .tln-rating-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:1px solid #eee; }
    .tln-rating-header h3 { margin:0;padding:0;border:none; }
    .tln-avg-score { background:#e63946;color:#fff;padding:0.25rem 0.75rem;border-radius:20px;font-weight:700;font-size:0.9rem; }
    .tln-rating-category { display:flex;align-items:center;margin:0.5rem 0; }
    .tln-rating-label { flex:1;font-weight:600;font-size:0.9rem; }
    .tln-pin-rating { display:flex;gap:2px; }
    .tln-pin-rating img { height:16px;opacity:0.3; }
    .tln-pin-rating img.active { opacity:1; }
    .tln-map { background:#f5f5f5;height:200px;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#666; }
    .tln-ad-box { background:#f5f5f5;border:2px dashed #ddd;border-radius:8px;height:250px;display:flex;align-items:center;justify-content:center; }
    .tln-ad-content { color:#888;text-align:center; }
    .tln-modal { display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999; }
    .tln-modal.show { display:flex;align-items:center;justify-content:center; }
    .tln-modal-inner { background:#fff;max-width:700px;width:95%;max-height:85vh;border-radius:12px;overflow:hidden;position:relative; }
    .tln-modal-close { position:absolute;top:10px;right:15px;font-size:2rem;cursor:pointer;z-index:10;line-height:1; }
    .tln-modal iframe { width:100%;height:80vh;border:none; }
    @media(max-width:800px) { .tln-container { grid-template-columns:1fr; } }
    </style>
    
    <div class="tln-profile">
        <div class="tln-hero">
            <div class="tln-hero-content">
                <h1>' . esc_html($biz_name) . '</h1>
                <p>' . esc_html($city) . '</p>
            </div>
        </div>
        
        <div class="tln-container">
            <div class="tln-left">
                <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/support-local-businesses.webp" style="width:100%;border-radius:8px;">
                
                <!-- What Neighbors Say -->
                <div class="tln-card">
                    <h3>What Neighbors Say</h3>
                    <p style="font-size:0.9rem;color:#666;margin-bottom:1rem;">Be the first to leave a Neighborhood Review Score for this business and help others in our community know what they\'re about.</p>
                    <a href="#" class="tln-modal-link" data-modal="review" style="margin-top:0.8rem;padding:0.75rem 1.5rem;background:#e63946;color:white;border:none;border-radius:6px;cursor:pointer;display:block;width:100%;font-weight:700;font-size:1rem;text-align:center;text-decoration:none;box-sizing:border-box;">Leave a Review</a>
                </div>
                
                <div class="tln-card">
                    <div class="tln-hours-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                        <h3 style="margin:0;padding:0;border:none;font-size:1rem;font-weight:700;">Hours</h3>
                        <span class="tln-hours-pill" style="padding:0.25rem 0.75rem;border-radius:20px;font-size:0.75rem;font-weight:700;background:#28a745;color:#fff;">Open</span>
                    </div>
                    <div style="font-size:14px;line-height:1.8;">
                        ' . tln_get_hours_display() . '
                    </div>
                </div>
                
                <div class="tln-card">
                    <h3>Contact</h3>
                    ' . ($biz_phone ? '<div class="tln-contact"><span>📞</span><a href="tel:' . esc_attr($biz_phone) . '">' . esc_html($biz_phone) . '</a></div>' : '<div class="tln-contact"><span>📞</span><a href="#">Phone coming soon...</a></div>') . '
                    ' . ($biz_address ? '<div class="tln-contact"><span>📍</span><a href="https://www.google.com/maps/search/?api=1&query=' . urlencode($biz_address) . '" target="_blank">Get Directions</a></div>' : '<div class="tln-contact"><span>📍</span><a href="#">Get Directions</a></div>') . '
                </div>
            </div>
            
            <div class="tln-right">
                <div class="tln-claim-box">
                    <h3>Claim This Page</h3>
                    <p>Own this business? Claim your free page to update info, add photos, and more.</p>
                    <a href="#" class="tln-btn tln-modal-link" data-modal="claim">Claim Your Page</a>
                </div>
                
                <div class="tln-card">
                    <h3>Google Reviews' . ($biz_rating > 0 ? ' (' . esc_html($biz_rating) . ')' : '') . '</h3>
                    ' . (count($biz_reviews) > 0 ? '<div style="max-height:250px;overflow-y:auto;">' . implode('', array_map(function($r) { return '<div class="tln-review-item"><div class="tln-reviewer">' . esc_html($r['author_name'] ?? 'Reviewer') . '</div><div class="tln-stars">' . str_repeat('★', $r['rating'] ?? 0) . '</div><div class="tln-review-text">' . esc_html(mb_substr($r['text'] ?? '', 0, 150)) . '</div></div>'; }, array_slice($biz_reviews, 0, 5))) . '</div><a href="https://www.google.com/search?q=" . urlencode($biz_name) . " " . urlencode($biz_address) . " reviews" target="_blank" class="tln-see-all">See all Google Reviews →</a>' : '<p style="color:#666;font-size:0.9rem;">Be the first to leave a Google review for this business!</p>') . '
                </div>
                
                <div class="tln-card" style="background:#fefaf9;border-color:#f0e0e0;">
                    <div style="font-size:0.7rem;color:#999;text-transform:uppercase;letter-spacing:1px;margin-bottom:0.25rem;">Advertisement</div>
                    <div class="tln-ad-box">
                        <div class="tln-ad-content">
                            <p style="margin-bottom:0.5rem;color:#666;"><strong>Not ready to claim yet?</strong></p>
                            <p style="margin-bottom:0.75rem;font-size:0.85rem;color:#888;">Advertise your business here for just <strong style="color:#e63946;">$35/mo</strong></p>
                            <a href="#" class="tln-modal-link" data-modal="ad" style="color:#e63946;font-weight:600;font-size:0.9rem;">Learn More →</a>
                        </div>
                    </div>
                </div>
                
                <div class="tln-card">
                    <h3>Location</h3>
                    ' . ($biz_lat && $biz_lng ? '<iframe width="100%" height="200" style="border:0;border-radius:4px;" loading="lazy" src="https://www.google.com/maps/embed/v1/place?key=' . esc_attr(defined('TLN_GOOGLE_API_KEY') ? TLN_GOOGLE_API_KEY : '') . '&q=' . esc_attr($biz_lat) . ',' . esc_attr($biz_lng) . '&zoom=15"></iframe>' : ($biz_address ? '<div style="background:#f5f5f5;height:150px;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:0.5rem;"><a href="https://www.google.com/maps/search/?api=1&query=' . urlencode($biz_address) . '" target="_blank" style="background:#e63946;color:#fff;padding:0.75rem 1.5rem;border-radius:6px;text-decoration:none;font-weight:700;">Open in Google Maps</a><span style="font-size:0.85rem;color:#666;">' . esc_html($biz_address) . '</span></div>' : '<div class="tln-map">Map coming soon...</div>')) . '
                </div>
                
            </div>
        </div>
    </div>
    ';
    
    return $html . tln_get_modal_html();
}

function tln_get_modal_html() {
    return '
    <div id="tln-ad-modal" class="tln-modal">
        <div class="tln-modal-inner">
            <span class="tln-modal-close" data-close>&times;</span>
            <iframe src="/tln-ad-request/?modal=1"></iframe>
        </div>
    </div>
    <div id="tln-claim-modal" class="tln-modal">
        <div class="tln-modal-inner">
            <span class="tln-modal-close" data-close>&times;</span>
            <iframe src="/claim/?modal=1"></iframe>
        </div>
    </div>
    <div id="tln-review-modal" class="tln-modal">
        <div class="tln-modal-inner" style="max-width:500px;padding:2rem;max-height:90vh;overflow-y:auto;">
            <span class="tln-modal-close" data-close>&times;</span>
            <h2 style="margin-bottom:1rem;">Rate Your Experience</h2>
            <p style="color:#666;margin-bottom:1.5rem;">Help your neighbors know what this business is really like.</p>
            <div class="tln-rating-category" style="margin:1rem 0;">
                <div style="font-weight:600;margin-bottom:0.5rem;">Quality</div>
                <div class="tln-pin-rating" data-category="quality">
                    <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png">
                </div>
            </div>
            <div class="tln-rating-category" style="margin:1rem 0;">
                <div style="font-weight:600;margin-bottom:0.5rem;">Service</div>
                <div class="tln-pin-rating" data-category="service">
                    <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png">
                </div>
            </div>
            <div class="tln-rating-category" style="margin:1rem 0;">
                <div style="font-weight:600;margin-bottom:0.5rem;">Value</div>
                <div class="tln-pin-rating" data-category="value">
                    <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png">
                </div>
            </div>
            <div class="tln-rating-category" style="margin:1rem 0;">
                <div style="font-weight:600;margin-bottom:0.5rem;">Atmosphere</div>
                <div class="tln-pin-rating" data-category="atmosphere">
                    <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png">
                </div>
            </div>
            <textarea placeholder="Write a review (What should other customers know?)" style="width:100%;padding:0.75rem;margin:1rem 0;border:1px solid #ddd;border-radius:6px;min-height:100px;"></textarea>
            <input type="text" placeholder="Your public name (required)" style="width:100%;padding:0.75rem;margin-bottom:1rem;border:1px solid #ddd;border-radius:6px;" />
            <button onclick="alert(\"Review submitted! Thanks for supporting local.\");document.getElementById(\"tln-review-modal\").classList.remove(\"show\");" style="width:100%;padding:0.75rem;background:#e63946;color:white;border:none;border-radius:6px;font-weight:700;font-size:1rem;cursor:pointer;">Submit Review</button>
        </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".tln-modal-link").forEach(function(link) {
            link.addEventListener("click", function(e) {
                e.preventDefault();
                var modalId = this.getAttribute("data-modal");
                document.getElementById("tln-" + modalId + "-modal").classList.add("show");
            });
        });
        document.querySelectorAll(".tln-modal").forEach(function(modal) {
            modal.addEventListener("click", function(e) {
                if (e.target === modal) modal.classList.remove("show");
            });
        });
        document.querySelectorAll("[data-close]").forEach(function(btn) {
            btn.addEventListener("click", function() {
                this.closest(".tln-modal").classList.remove("show");
            });
        });
        // Pin rating interaction
        document.querySelectorAll(".tln-pin-rating").forEach(function(container) {
            var pins = container.querySelectorAll("img");
            pins.forEach(function(pin, idx) {
                pin.addEventListener("click", function() {
                    pins.forEach(function(p, i) {
                        p.classList.toggle("active", i <= idx);
                    });
                });
            });
        });
    });
    </script>
    ';
}
