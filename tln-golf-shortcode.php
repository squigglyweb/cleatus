<?php
/**
 * Plugin Name: TLN Golf Courses
 * Description: Display golf courses near Waxhaw with live Google data
 * Version: 1.0
 */

function tln_golf_styles() {
    wp_enqueue_style('tln-fonts', 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap', array(), null);
    
    wp_register_style('tln-golf', false);
    wp_enqueue_style('tln-golf');
    
    $css = '
    .golf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
    .golf-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
    .golf-card:hover { transform: translateY(-4px); }
    .golf-image { width: 100%; height: 200px; object-fit: cover; background: #ebebeb; }
    .golf-content { padding: 1.25rem; }
    .golf-name { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; color: #1a1a1a; }
    .golf-rating { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; }
    .golf-stars { color: #e63946; }
    .golf-count { color: #666; font-size: 0.9rem; }
    .golf-address { color: #666; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
    .golf-phone { color: #666; margin-bottom: 0.5rem; }
    .golf-hours { font-size: 0.85rem; color: #666; margin-bottom: 1rem; }
    .golf-hours .open { color: #22c55e; }
    .golf-hours .closed { color: #e63946; }
    .golf-btn { display: block; width: 100%; padding: 0.9rem; background: #e63946; color: white; text-align: center; text-decoration: none; font-weight: 600; border-radius: 8px; }
    .golf-btn:hover { background: #c1121f; }
    @media (max-width: 600px) { .golf-grid { grid-template-columns: 1fr; } }
    ';
    
    wp_add_inline_style('tln-golf', $css);
}
add_action('wp_enqueue_scripts', 'tln_golf_styles');

// API Key - replace with your key
define('TLN_GOOGLE_API_KEY', 'AIzaSyAH6O3RsnDuX5rJ2OyTHCTZhYtd6s6NSWU');

// List of golf course place IDs
$golf_courses = array(
    'ChIJLwMuexIoVIgRtAZI5du3za4' => 'The Club at Longview',
    'ChIJ07ciSwAyVIgRs37Vuzt7yTw' => 'Stonebridge Golf Club',
    'ChIJqa0AAYgnVIgRxhhMbrOJVsc' => 'TPC Piper Glen',
    'ChIJ8ZCLeksjVIgRijcbkkoEdgM' => 'The Divide Golf Club',
    'ChIJzfYWh4yCVogREeT1Qb0Tte0' => 'Country Club of the Carolinas',
    'ChIJG_RuEcadVogRRyKyhv9JHlQ' => 'Quail Hollow Club',
    'ChIJ1wOxKsI8VIgRTNj4eLhT7TY' => 'Emerald Lake Golf Club',
    'ChIJ_SeZqaeBVogRpeTezQCNFpY' => 'Carolina Lakes Golf Club',
    'ChIJrcfejWOGVogRkkDZjpMoIBI' => 'Waterford Golf Club',
    'ChIJ0aJBosmQVogRoJf-xWZdzY8' => 'Ballantyne Country Club',
    'ChIJ5VzjU-2EVogRBTX5J_HxH1A' => 'Springfield Golf Club',
    'ChIJ23H3hYSmmlQR9YQ5oCRsYWc' => 'Monroe Country Club',
    'ChIJGYwlJgsnVIgRC3B-hFNE3uU' => 'Raintree Country Club',
    'ChIJBY2Or0yhVogRJJ1E8YEyLjE' => 'Sunset Hills Golf Course',
    'ChIJJ2buw9gnVIgRyyJdVEgImSs' => 'Providence Country Club',
    'ChIJ_-M36IInVIgRnG0sPkrBIHI' => 'Cedarwood Country Club',
    'ChIJOXXAhwEdVIgRya7BS0Ex_iE' => 'Tradition Golf Course',
    'ChIJ7fAXYI6dVogRS2FTBJPlKVo' => 'Carmel Country Club',
    'ChIJyz_TCu12VogRrzBtgQCIug4' => 'Edgewater Golf Club',
    'ChIJ53pfVauYVogR9f4MLtnFQaI' => 'Carolina Golf Club',
);

function tln_golf_shortcode($atts) {
    global $golf_courses;
    
    $atts = shortcode_atts(array(
        'place_id' => '',
    ), $atts);
    
    $api_key = TLN_GOOGLE_API_KEY;
    $results = array();
    
    // If specific place_id requested
    if (!empty($atts['place_id'])) {
        $place_ids = array($atts['place_id']);
    } else {
        $place_ids = array_keys($golf_courses);
    }
    
    foreach ($place_ids as $place_id) {
        $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$place_id&fields=name,formatted_address,formatted_phone_number,website,rating,user_ratings_total,opening_hours,geometry,photos&key=$api_key";
        
        $response = wp_remote_get($url);
        if (is_wp_error($response)) continue;
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $r = $data['result'] ?? null;
        if (!$r) continue;
        
        // Get photo URL if available
        $photo_url = '';
        $photos = $r['photos'] ?? array();
        if (!empty($photos)) {
            $photo_ref = $photos[0]['photo_reference'];
            $photo_url = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=600&photoreference=$photo_ref&key=$api_key";
        }
        
        // Rating stars
        $rating = $r['rating'] ?? 0;
        $stars = str_repeat('★', floor($rating)) . (fmod($rating, 1) >= 0.5 ? '½' : '');
        
        // Open/closed status
        $open_now = $r['opening_hours']['open_now'] ?? null;
        $hours_text = $open_now === true ? '<span class="open">Open now</span>' : ($open_now === false ? '<span class="closed">Closed</span>' : '');
        
        // Google Maps link
        $maps_link = "https://www.google.com/maps/place/$place_id";
        
        $results[] = array(
            'name' => $r['name'],
            'address' => $r['formatted_address'],
            'phone' => $r['formatted_phone_number'] ?? '',
            'website' => $r['website'] ?? '',
            'rating' => $rating,
            'stars' => $stars,
            'reviews' => $r['user_ratings_total'] ?? 0,
            'open_now' => $hours_text,
            'photo' => $photo_url,
            'maps_link' => $maps_link,
        );
    }
    
    if (empty($results)) {
        return '<p>No golf courses found.</p>';
    }
    
    ob_start();
    echo '<div class="golf-grid">';
    
    foreach ($results as $course) {
        $img = $course['photo'] ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWJlYmViIi8+PC9zdmc+';
        ?>
        <div class="golf-card">
            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($course['name']); ?>" class="golf-image">
            <div class="golf-content">
                <h3 class="golf-name"><?php echo esc_html($course['name']); ?></h3>
                <div class="golf-rating">
                    <span class="golf-stars"><?php echo esc_html($course['stars']); ?></span>
                    <span class="golf-count"><?php echo esc_html($course['rating'] . ' (' . $course['reviews'] . ' reviews)'); ?></span>
                </div>
                <div class="golf-address">📍 <?php echo esc_html($course['address']); ?></div>
                <?php if ($course['phone']): ?>
                <div class="golf-phone">📞 <?php echo esc_html($course['phone']); ?></div>
                <?php endif; ?>
                <div class="golf-hours"><?php echo $course['open_now']; ?></div>
                <a href="<?php echo esc_url($course['maps_link']); ?>" target="_blank" class="golf-btn">View on Google Maps →</a>
            </div>
        </div>
        <?php
    }
    
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('golf_directory', 'tln_golf_shortcode');
add_shortcode('golf_course', 'tln_golf_shortcode');