<?php
/**
 * Plugin Name: TLN Parks & Splash Pads
 * Description: Display parks and splash pads with live Google data
 * Version: 1.1
 */

function tln_parks_styles() {
    wp_enqueue_style('tln-fonts', 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap', array(), null);
    
    wp_register_style('tln-parks', false);
    wp_enqueue_style('tln-parks');
    
    $css = '
    .tln-parks-container { max-width: 1200px; margin: 0 auto; }
    .tln-parks-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
    .tln-park-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #7cda24; }
    .tln-park-card:hover { transform: translateY(-4px); }
    .tln-park-card.waxhaw { border: 2px solid #e63946; }
    .tln-park-img { width: 100%; height: 200px; object-fit: cover; background: #ebebeb; }
    .tln-park-content { padding: 1.25rem; }
    .tln-park-name { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; color: #1a1a1a; }
    .tln-park-location { font-size: 0.8rem; color: #e63946; font-weight: 600; margin-bottom: 0.25rem; }
    .tln-park-rating { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; }
    .tln-park-stars { color: #FABC06; }
    .tln-park-count { color: #666; font-size: 0.9rem; }
    .tln-park-address { color: #666; margin-bottom: 0.5rem; }
    .tln-park-address a { color: #e63946; text-decoration: none; }
    .tln-park-address a:hover { text-decoration: underline; }
    .tln-park-status-pill { display: inline-block; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.75rem; }
    .tln-park-status-open { background: #7cda24; color: white; }
    .tln-park-status-closed { background: #e63946; color: white; }
    .tln-park-btn { display: block; width: 100%; padding: 0.9rem; background: #e63946; color: white; text-align: center; text-decoration: none; font-weight: 600; border-radius: 8px; }
    .tln-park-btn:hover { background: #c1121f; }
    .tln-parks-header { text-align: center; margin-bottom: 2rem; }
    .tln-parks-header h2 { font-size: 2rem; margin-bottom: 0.5rem; }
    .tln-parks-header p { color: #666; }
    @media (max-width: 600px) { .tln-parks-grid { grid-template-columns: 1fr; } }
    ';
    
    wp_add_inline_style('tln-parks', $css);
}
add_action('wp_enqueue_scripts', 'tln_parks_styles');

if (!defined('TLN_GOOGLE_API_KEY')) {
    define('TLN_GOOGLE_API_KEY', 'AIzaSyAH6O3RsnDuX5rJ2OyTHCTZhYtd6s6NSWU');
}

$parks_locations = array(
    'ChIJP5t9RwArVIgR1CkofWvy-OU' => array('name' => 'Waxhaw Downtown Park', 'type' => 'Waxhaw'),
    'ChIJVy3wpCXTVYgRMsOjCq6ouhY' => array('name' => 'Cane Creek Park', 'type' => 'Waxhaw'),
    'ChIJszENlQEoVIgREE5ZYct6f8c' => array('name' => 'Veterans Park', 'type' => 'Waxhaw'),
    'ChIJbc7XimcrVIgRhYWS-_7ZpAw' => array('name' => 'Town Creek Park', 'type' => 'Waxhaw'),
    'ChIJb7RqR74rVIgRM00Rc9reBEc' => array('name' => 'Waxhaw Park', 'type' => 'Waxhaw'),
    'ChIJLcn5EiUvVIgRUGGAodiaLBQ' => array('name' => 'Dogwood Park at Wesley Chapel', 'type' => 'Wesley Chapel'),
    'ChIJgwJJqYSfVogRMxvRPzB8ki8' => array('name' => 'Latta Park', 'type' => 'Charlotte'),
    'ChIJr30fWgmhVogRlxHQ9PRZ0sA' => array('name' => 'Ray\'s Splash Planet', 'type' => 'Charlotte'),
    'ChIJK7ERWU8kVIgRfE7M_CBLhUk' => array('name' => 'Stallings Municipal Park', 'type' => 'Stallings'),
    'ChIJCwkPoXY7VIgRK-hec0X1T2Q' => array('name' => 'Crooked Creek Park', 'type' => 'Indian Trail'),
    'ChIJDaZ4hSQxVIgRbH8I1iKgGoI' => array('name' => 'Belk-Tonawanda Park Splash Pad', 'type' => 'Monroe'),
    'ChIJG_dblgMoVIgRan_FeALhUW8' => array('name' => 'Park at Blakeney', 'type' => 'Charlotte'),
);

function tln_parks_shortcode($atts) {
    global $parks_locations;
    $api_key = TLN_GOOGLE_API_KEY;
    $results = array();
    
    foreach ($parks_locations as $place_id => $info) {
        $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$place_id&fields=name,formatted_address,rating,user_ratings_total,opening_hours,photos&key=$api_key";
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            continue;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $r = $data['result'] ?? null;
        
        if (empty($r)) {
            continue;
        }
        
        $photo_url = '';
        $photos = $r['photos'] ?? array();
        if (count($photos) > 0) {
            $photo_ref = $photos[0]['photo_reference'];
            $photo_url = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=600&photoreference=$photo_ref&key=$api_key";
        }
        
        $rating = $r['rating'] ?? 0;
        $star_count = floor($rating);
        $stars = str_repeat('★', $star_count);
        
        $open_now = $r['opening_hours']['open_now'] ?? null;
        if ($open_now === true) {
            $status = 'Open Now';
            $status_class = 'tln-park-status-open';
        } elseif ($open_now === false) {
            $status = 'Closed';
            $status_class = 'tln-park-status-closed';
        } else {
            $status = 'Check Hours';
            $status_class = 'tln-park-status-closed';
        }
        
        $results[] = array(
            'place_id' => $place_id,
            'name' => $r['name'],
            'type' => $info['type'],
            'address' => $r['formatted_address'],
            'rating' => $rating,
            'stars' => $stars,
            'reviews' => $r['user_ratings_total'] ?? 0,
            'status' => $status,
            'status_class' => $status_class,
            'photo' => $photo_url,
            'maps_link' => 'https://www.google.com/maps/place/' . $place_id,
        );
    }
    
    if (count($results) == 0) {
        return '<p>No parks found.</p>';
    }
    
    // Sort: Waxhaw first, then by rating
    usort($results, function($a, $b) {
        if ($a['type'] == 'Waxhaw' && $b['type'] != 'Waxhaw') {
            return -1;
        }
        if ($b['type'] == 'Waxhaw' && $a['type'] != 'Waxhaw') {
            return 1;
        }
        return $b['rating'] - $a['rating'];
    });
    
    ob_start();
    echo '<div class="tln-parks-container">';
    echo '<div class="tln-parks-header">';
    echo '<h2>Parks & Splash Pads</h2>';
    echo '<p>Fun for the whole family in Waxhaw and surrounding areas</p>';
    echo '</div>';
    echo '<div class="tln-parks-grid">';
    
    foreach ($results as $park) {
        $is_waxhaw = ($park['type'] == 'Waxhaw');
        $waxhaw_class = $is_waxhaw ? ' waxhaw' : '';
        
        if ($park['photo']) {
            $img = $park['photo'];
        } else {
            $img = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWJlYmViIi8+PC9zdmc+';
        }
        
        echo '<div class="tln-park-card' . $waxhaw_class . '">';
        echo '<img src="' . esc_url($img) . '" alt="' . esc_attr($park['name']) . '" class="tln-park-img">';
        echo '<div class="tln-park-content">';
        echo '<div class="tln-park-location">' . esc_html($park['type']);
        if ($is_waxhaw) {
            echo ' 📍';
        }
        echo '</div>';
        echo '<h3 class="tln-park-name">' . esc_html($park['name']) . '</h3>';
        echo '<div class="tln-park-rating">';
        echo '<span class="tln-park-stars">' . esc_html($park['stars']) . '</span>';
        echo '<span class="tln-park-count">' . $park['rating'] . ' (' . $park['reviews'] . ')</span>';
        echo '</div>';
        echo '<span class="tln-park-status-pill ' . $park['status_class'] . '">' . esc_html($park['status']) . '</span>';
        echo '<div class="tln-park-address">📍 <a href="' . esc_url($park['maps_link']) . '" target="_blank">' . esc_html($park['address']) . '</a></div>';
        echo '<a href="' . esc_url($park['maps_link']) . '" target="_blank" class="tln-park-btn">Get Directions</a>';
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    
    return ob_get_clean();
}

add_shortcode('parks_directory', 'tln_parks_shortcode');
add_shortcode('tln_parks', 'tln_parks_shortcode');
