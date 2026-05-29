<?php
/*
Plugin Name: TLN Summer Camps
Version: 1.0
*/

add_shortcode('camps_directory', 'tln_camps_func');

function tln_camps_styles() {
    wp_enqueue_style('tln-fonts', 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap', array(), null);
    wp_register_style('tln-camps', false);
    wp_enqueue_style('tln-camps');
    
    $css = '
    .tln-camps-container { max-width: 1200px; margin: 0 auto; }
    .tln-camps-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
    .tln-camp-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #7cda24; }
    .tln-camp-card:hover { transform: translateY(-4px); }
    .tln-camp-card.waxhaw { border: 2px solid #e63946; }
    .tln-camp-img { width: 100%; height: 180px; object-fit: cover; background: #ebebeb; }
    .tln-camp-content { padding: 1.25rem; }
    .tln-camp-name { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; color: #1a1a1a; }
    .tln-camp-location { font-size: 0.8rem; color: #e63946; font-weight: 600; margin-bottom: 0.25rem; }
    .tln-camp-rating { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; }
    .tln-camp-stars { color: #FABC06; }
    .tln-camp-reviews { color: #666; font-size: 0.9rem; }
    .tln-camp-address { color: #666; margin-bottom: 0.5rem; }
    .tln-camp-address a { color: #e63946; text-decoration: none; }
    .tln-camp-btn { display: block; width: 100%; padding: 0.9rem; background: #e63946; color: white; text-align: center; text-decoration: none; font-weight: 600; border-radius: 8px; }
    .tln-camp-btn:hover { background: #c1121f; }
    .tln-camps-header { text-align: center; margin-bottom: 2rem; }
    .tln-camps-header h2 { font-size: 2rem; margin-bottom: 0.5rem; }
    @media (max-width: 600px) { .tln-camps-grid { grid-template-columns: 1fr; } }
    ';
    wp_add_inline_style('tln-camps', $css);
}
add_action('wp_enqueue_scripts', 'tln_camps_styles');

define('TLN_GOOGLE_API_KEY', 'AIzaSyAH6O3RsnDuX5rJ2OyTHCTZhYtd6s6NSWU');

function tln_camps_func() {
    // Try to get cached data first
    $cached = get_transient('tln_camps_data');
    if ($cached !== false) {
        return $cached;
    }
    
    $api_key = TLN_GOOGLE_API_KEY;
    $results = array();
    
    $queries = array(
        'Waxhaw' => 'summer camps in Waxhaw NC',
        'Charlotte' => 'summer camps in Charlotte NC',
        'Matthews' => 'summer camps in Matthews NC',
        'Monroe' => 'summer camps in Monroe NC'
    );
    
    foreach ($queries as $loc => $q) {
        $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=" . urlencode($q) . "&key=$api_key";
        $resp = wp_remote_get($url);
        
        if (is_wp_error($resp)) continue;
        
        $data = json_decode(wp_remote_retrieve_body($resp), true);
        
        foreach ($data['results'] as $place) {
            $pid = $place['place_id'];
            
            $photo_url = '';
            if (count($place['photos']) > 0) {
                $pref = $place['photos'][0]['photo_reference'];
                $photo_url = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=600&photoreference=$pref&key=$api_key";
            }
            
            $rating = $place['rating'] ?? 0;
            $stars = str_repeat('★', floor($rating));
            
            $results[] = array(
                'name' => $place['name'],
                'address' => $place['formatted_address'],
                'rating' => $rating,
                'stars' => $stars,
                'reviews' => $place['user_ratings_total'] ?? 0,
                'photo' => $photo_url,
                'location' => $loc,
                'maps_link' => 'https://www.google.com/maps/place/' . $pid
            );
        }
    }
    
    // Remove duplicates
    $seen = array();
    $filtered = array();
    foreach ($results as $r) {
        $k = strtolower($r['name']);
        if (!isset($seen[$k])) {
            $seen[$k] = true;
            $filtered[] = $r;
        }
    }
    
    // Sort: Waxhaw first
    usort($filtered, function($a, $b) {
        if ($a['location'] == 'Waxhaw' && $b['location'] != 'Waxhaw') return -1;
        if ($b['location'] == 'Waxhaw' && $a['location'] != 'Waxhaw') return 1;
        return $b['rating'] - $a['rating'];
    });
    
    if (count($filtered) == 0) {
        return '<p>No camps found.</p>';
    }
    
    ob_start();
    echo '<div class="tln-camps-container">';
    echo '<div class="tln-camps-header">';
    echo '<h2>Summer Camps</h2>';
    echo '<p>Fun summer programs for kids in Waxhaw and surrounding areas</p>';
    echo '</div>';
    echo '<div class="tln-camps-grid">';
    
    foreach ($filtered as $camp) {
        $is_waxhaw = ($camp['location'] == 'Waxhaw');
        $waxhaw_class = $is_waxhaw ? ' waxhaw' : '';
        $img = $camp['photo'] ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWJlYmViIi8+PC9zdmc+';
        
        echo '<div class="tln-camp-card' . $waxhaw_class . '">';
        echo '<img src="' . esc_url($img) . '" alt="' . esc_attr($camp['name']) . '" class="tln-camp-img">';
        echo '<div class="tln-camp-content">';
        echo '<div class="tln-camp-location">' . esc_html($camp['location']) . ($is_waxhaw ? ' *' : '') . '</div>';
        echo '<h3 class="tln-camp-name">' . esc_html($camp['name']) . '</h3>';
        echo '<div class="tln-camp-rating"><span class="tln-camp-stars">' . esc_html($camp['stars']) . '</span> <span class="tln-camp-reviews">(' . $camp['reviews'] . ' reviews)</span></div>';
        echo '<div class="tln-camp-address">📍 <a href="' . esc_url($camp['maps_link']) . '" target="_blank">' . esc_html($camp['address']) . '</a></div>';
        echo '<a href="' . esc_url($camp['maps_link']) . '" target="_blank" class="tln-camp-btn">Get Details</a>';
        echo '</div></div>';
    }
    
    echo '</div></div>';
    $output = ob_get_clean();
    
    // Cache for 24 hours
    set_transient('tln_camps_data', $output, HOUR_IN_SECONDS * 24);
    
    return $output;
}
