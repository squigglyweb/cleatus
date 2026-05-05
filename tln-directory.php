<?php
/**
 * Plugin Name: TLN Business Directory
 * Description: Display local businesses from Google API with claim functionality
 * Version: 1.1
 */

function tln_directory_styles() {
    wp_enqueue_style('tln-fonts', 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap', array(), null);
    
    wp_register_style('tln-dir', false);
    wp_enqueue_style('tln-dir');
    
    $css = '
    .tln-dir-container { max-width: 1200px; margin: 0 auto; }
    .tln-dir-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
    .tln-dir-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #7cda24; }
    .tln-dir-card:hover { transform: translateY(-4px); }
    .tln-dir-img { width: 100%; height: 180px; object-fit: cover; background: #ebebeb; }
    .tln-dir-content { padding: 1.25rem; }
    .tln-dir-name { font-size: 1.2rem; font-weight: 700 !important; margin-bottom: 0.25rem; color: #1a1a1a; }
    .tln-dir-category { font-size: 0.8rem; color: #e63946; font-weight: 600; margin-bottom: 0.5rem; }
    .tln-dir-rating { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; }
    .tln-dir-stars { color: #FABC06; }
    .tln-dir-reviews { color: #666; font-size: 0.9rem; }
    .tln-dir-address { color: #666; font-size: 0.9rem; margin-bottom: 0.5rem; }
    .tln-dir-phone { color: #666; font-size: 0.9rem; margin-bottom: 0.75rem; }
    .tln-dir-phone a { color: #666; text-decoration: none; }
    .tln-dir-phone a:hover { color: #e63946; }
    .tln-dir-claim { display: inline-block; background: #7cda24; color: white; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
    .tln-dir-claim:hover { background: #6bc91f; }
    .tln-dir-filters { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .tln-dir-search { flex: 1; min-width: 200px; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; font-family: "Open Sans", sans-serif; }
    .tln-dir-filter { padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; font-family: "Open Sans", sans-serif; background: white; cursor: pointer; }
    @media (max-width: 600px) { .tln-dir-grid { grid-template-columns: 1fr; } }
    ';
    
    wp_add_inline_style('tln-dir', $css);
}
add_action('wp_enqueue_scripts', 'tln_directory_styles');

// API Key
define('TLN_GOOGLE_API_KEY', 'AIzaSyAH6O3RsnDuX5rJ2OyTHCTZhYtd6s6NSWU');

// Search areas
$search_areas = array(
    'Waxhaw' => 'Waxhaw, NC',
    'Weddington' => 'Weddington, NC',
    'Wesley Chapel' => 'Wesley Chapel, NC',
    'Marvin' => 'Marvin, NC',
    'Indian Land' => 'Indian Land, SC',
    'Ballantyne' => 'Ballantyne, Charlotte, NC'
);

// Default categories
$default_categories = array('Restaurant', 'Retail', 'Medical', 'Services', 'Food & Drink');

function tln_directory_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category' => '',
        'location' => ''
    ), $atts);
    
    $api_key = TLN_GOOGLE_API_KEY;
    $results = array();
    
    // Fetch from Google Places API
    $search_queries = array(
        'Restaurant' => 'restaurants in Waxhaw NC',
        'Retail' => 'retail stores in Waxhaw NC',
        'Medical' => 'medical offices in Waxhaw NC',
        'Services' => 'services in Waxhaw NC',
        'Food & Drink' => 'food and drink in Waxhaw NC'
    );
    
    foreach ($search_queries as $category => $query) {
        $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=" . urlencode($query) . "&key=$api_key";
        $response = wp_remote_get($url);
        
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            $places = $data['results'] ?? array();
            
            foreach ($places as $place) {
                $place_id = $place['place_id'];
                $transient_key = "tln_dir_{$place_id}";
                $cached = get_transient($transient_key);
                
                if (false === $cached) {
                    // Get more details
                    $details_url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$place_id&fields=name,formatted_address,formatted_phone_number,rating,user_ratings_total,photos,geometry&key=$api_key";
                    $details_response = wp_remote_get($details_url);
                    
                    if (!is_wp_error($details_response)) {
                        $details_data = json_decode(wp_remote_retrieve_body($details_response), true);
                        $cached = $details_data['result'] ?? $place;
                        set_transient($transient_key, $cached, 86400); // 24 hour cache
                    }
                }
                
                $r = $cached ?? $place;
                
                // Get photo
                $photo_url = '';
                $photos = $r['photos'] ?? array();
                if (!empty($photos)) {
                    $photo_ref = $photos[0]['photo_reference'];
                    $photo_url = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=$photo_ref&key=$api_key";
                }
                
                // Rating
                $rating = $r['rating'] ?? 0;
                $stars = str_repeat('★', floor($rating)) . (fmod($rating, 1) >= 0.5 ? '½' : '');
                
                $results[] = array(
                    'place_id' => $place_id,
                    'name' => $r['name'],
                    'category' => $category,
                    'address' => $r['formatted_address'] ?? '',
                    'phone' => $r['formatted_phone_number'] ?? '',
                    'rating' => $rating,
                    'stars' => $stars,
                    'reviews' => $r['user_ratings_total'] ?? 0,
                    'photo' => $photo_url,
                    'lat' => $r['geometry']['location']['lat'] ?? '',
                    'lng' => $r['geometry']['location']['lng'] ?? ''
                );
            }
        }
    }
    
    // Remove duplicates by name
    $seen = array();
    $deduped = array();
    foreach ($results as $r) {
        $key = strtolower($r['name']);
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $deduped[] = $r;
        }
    }
    $results = $deduped;
    
    if (empty($results)) {
        return '<p>No businesses found.</p>';
    }
    
    ob_start();
    ?>
    <div class="tln-dir-container">
        <div class="tln-dir-filters">
            <input type="text" class="tln-dir-search" id="tln-search" placeholder="Search businesses...">
            <select class="tln-dir-filter" id="tln-category">
                <option value="all">All Categories</option>
                <?php foreach ($default_categories as $cat): ?>
                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                <?php endforeach; ?>
            </select>
            <select class="tln-dir-filter" id="tln-location">
                <option value="all">All Locations</option>
                <?php foreach (array_keys($search_areas) as $loc): ?>
                <option value="<?php echo $loc; ?>"><?php echo $loc; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="tln-dir-grid" id="tln-grid">
        <?php foreach ($results as $biz): ?>
        <?php 
        $img = $biz['photo'] ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWJlYmViIi8+PC9zdmc+';
        ?>
        <div class="tln-dir-card" data-name="<?php echo strtolower(esc_attr($biz['name'])); ?>" data-category="<?php echo esc_attr($biz['category']); ?>" data-location="Waxhaw">
            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($biz['name']); ?>" class="tln-dir-img">
            <div class="tln-dir-content">
                <div class="tln-dir-category"><?php echo esc_html($biz['category']); ?> • Waxhaw</div>
                <h3 class="tln-dir-name"><?php echo esc_html($biz['name']); ?></h3>
                <div class="tln-dir-rating">
                    <span class="tln-dir-stars"><?php echo esc_html($biz['stars']); ?></span>
                    <span class="tln-dir-reviews">(<?php echo $biz['reviews']; ?> reviews)</span>
                </div>
                <div class="tln-dir-address">📍 <?php echo esc_html($biz['address']); ?></div>
                <?php if ($biz['phone']): ?>
                <div class="tln-dir-phone">📞 <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $biz['phone']); ?>"><?php echo esc_html($biz['phone']); ?></a></div>
                <?php endif; ?>
                <a href="/claim?business=<?php echo urlencode($biz['name']); ?>&place_id=<?php echo $biz['place_id']; ?>" class="tln-dir-claim">Claim This Business →</a>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var search = document.getElementById('tln-search');
        var catFilter = document.getElementById('tln-category');
        var locFilter = document.getElementById('tln-location');
        var cards = document.querySelectorAll('.tln-dir-card');
        
        function filter() {
            var q = search.value.toLowerCase();
            var cat = catFilter.value;
            var loc = locFilter.value;
            
            cards.forEach(function(c) {
                var name = c.dataset.name;
                var category = c.dataset.category;
                var location = c.dataset.location;
                
                var match = (q === '' || name.indexOf(q) > -1) &&
                           (cat === 'all' || category === cat) &&
                           (loc === 'all' || location === loc);
                
                c.style.display = match ? '' : 'none';
            });
        }
        
        search.addEventListener('input', filter);
        catFilter.addEventListener('change', filter);
        locFilter.addEventListener('change', filter);
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('tln_directory', 'tln_directory_shortcode');
