<?php
/**
 * Plugin Name: TLN Business Directory
 * Description: Display local businesses from Google API with claim functionality
 * Version: 1.6
 */

if (!defined('ABSPATH')) exit;

define('TLN_GOOGLE_API_KEY', 'AIzaSyAH6O3RsnDuX5rJ2OyTHCTZhYtd6s6NSWU');

$default_categories = array('Restaurant', 'Retail', 'Medical', 'Services', 'Food & Drink');

// Exclude big box chains and non-local businesses
$tln_excluded_chains = array(
    'CVS', 'Walgreens', 'Walmart', 'Target', 'Costco', 'Sam\'s Club',
    'Dollar General', 'Dollar Store', 'Family Dollar', 'McDonald\'s',
    'Burger King', 'KFC', 'Pizza Hut', 'Domino\'s', 'Papa John\'s',
    'Subway', 'Starbucks', 'Dunkin', 'Dunkin\'', 'Chipotle',
    'Chick-fil-A', 'Chick fil A', 'Wingstop', 'Taco Bell',
    'Shell', 'BP', 'Exxon', 'Chevron', 'Texaco', 'Raceway',
    'Food Lion', 'Harris Teeter', 'Aldi', 'Lidl', 'Whole Foods',
    'Trader Joe\'s', 'Publix', 'Ingles', 'PetSmart', 'Petco',
    'Best Buy', 'Home Depot', 'Lowe\'s', 'Menards', 'Ace Hardware',
    'Office Depot', 'Staples', 'FedEx', 'UPS Store',
    'Bank of America', 'Wells Fargo', 'Chase', 'PNC',
    'Verizon', 'AT&T', 'T-Mobile', 'Sprint', 'Xfinity',
    'USPS', 'United States Postal'
);

function tln_directory_styles() {
    wp_enqueue_style('tln-fonts', 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap', array(), null);
    
    wp_register_style('tln-dir', false);
    wp_enqueue_style('tln-dir');
    
    $css = '
    .tln-dir-container { max-width: 1200px; margin: 0 auto; }
    .tln-dir-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
    .tln-dir-card { background: white; border-radius: 12px; overflow: hidden; border: 2px solid #1a1a1a; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .tln-dir-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .tln-dir-card.waxhaw { border-color: #e63946; }
    .tln-dir-img-wrap { position: relative; }
    .tln-dir-img { width: 100%; height: 200px; object-fit: cover; background: #ebebeb; }
    .tln-dir-badge { position: absolute; top: 10px; right: 10px; background: #e63946; color: white; padding: 4px 12px; font-size: 0.75rem; font-weight: 700; border-radius: 4px; text-transform: uppercase; }
    .tln-dir-content { padding: 1rem; }
    .tln-dir-name-wrap { background: white; border: 2px solid #1a1a1a; padding: 0.75rem; margin-bottom: 0.5rem; border-radius: 4px; }
    .tln-dir-name { font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin: 0; }
    .tln-dir-category { color: #e63946; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; }
    .tln-dir-rating { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; }
    .tln-dir-stars { color: #FABC06; font-size: 1rem; }
    .tln-dir-reviews { color: #666; font-size: 0.9rem; }
    .tln-dir-address { color: #1a1a1a; font-size: 0.9rem; margin-bottom: 1rem; text-decoration: underline; cursor: pointer; }
    .tln-dir-address:hover { color: #e63946; }
    .tln-dir-claim { display: block; width: 100%; padding: 0.9rem; background: #7cda24; color: white; text-align: center; text-decoration: none; font-weight: 700; font-size: 0.95rem; border: 2px solid white; border-radius: 8px; text-transform: uppercase; box-sizing: border-box; }
    .tln-dir-claim:hover { background: #6bc91f; }
    .tln-dir-filters { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .tln-dir-search { flex: 1; min-width: 200px; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
    .tln-dir-filter { padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; background: white; }
    @media (max-width: 600px) { .tln-dir-grid { grid-template-columns: 1fr; } }
    ';
    
    wp_add_inline_style('tln-dir', $css);
}
add_action('wp_enqueue_scripts', 'tln_directory_styles');

function tln_is_excluded_chain($name) {
    global $tln_excluded_chains;
    $name_lower = strtolower($name);
    foreach ($tln_excluded_chains as $chain) {
        if (stripos($name_lower, strtolower($chain)) !== false) {
            return true;
        }
    }
    return false;
}

function tln_directory_shortcode($atts) {
    $api_key = defined('TLN_GOOGLE_API_KEY') ? TLN_GOOGLE_API_KEY : '';
    
    if (empty($api_key)) {
        return '<p>API key not configured.</p>';
    }
    
    $results = array();
    
    $search_queries = array(
        'Waxhaw' => array(
            'Restaurant' => 'restaurants in Waxhaw NC',
            'Retail' => 'retail stores in Waxhaw NC',
            'Medical' => 'medical in Waxhaw NC',
            'Services' => 'services in Waxhaw NC'
        )
    );
    
    foreach ($search_queries as $search_area => $categories) {
        foreach ($categories as $category => $query) {
            $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=" . urlencode($query) . "&key=" . $api_key;
            $response = wp_remote_get($url, array('timeout' => 10, 'sslverify' => false));
            
            if (is_wp_error($response)) continue;
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (empty($data['results'])) continue;
            
            foreach ($data['results'] as $place) {
                // Skip big box chains
                if (tln_is_excluded_chain($place['name'])) {
                    continue;
                }
                
                $place_id = $place['place_id'];
                
                $photo_url = '';
                $photos = $place['photos'] ?? array();
                if (!empty($photos)) {
                    $photo_ref = $photos[0]['photo_reference'];
                    $photo_url = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=600&photoreference=$photo_ref&key=$api_key";
                }
                
                $rating = $place['rating'] ?? 0;
                $full_stars = floor($rating);
                $stars = str_repeat('★', $full_stars) . ($rating - $full_stars >= 0.5 ? '★' : '');
                
                $address = $place['formatted_address'] ?? '';
                
                if (stripos($address, 'Waxhaw') !== false) {
                    $location = 'Waxhaw';
                } elseif (stripos($address, 'Weddington') !== false) {
                    $location = 'Weddington';
                } elseif (stripos($address, 'Wesley Chapel') !== false) {
                    $location = 'Wesley Chapel';
                } elseif (stripos($address, 'Marvin') !== false) {
                    $location = 'Marvin';
                } elseif (stripos($address, 'Indian Land') !== false) {
                    $location = 'Indian Land';
                } elseif (stripos($address, 'Ballantyne') !== false) {
                    $location = 'Ballantyne';
                } elseif (stripos($address, 'Charlotte') !== false) {
                    $location = 'Charlotte';
                } else {
                    $location = 'Other';
                }
                
                $results[] = array(
                    'place_id' => $place_id,
                    'name' => $place['name'],
                    'category' => $category,
                    'location' => $location,
                    'address' => $address,
                    'rating' => $rating,
                    'stars' => $stars,
                    'reviews' => $place['user_ratings_total'] ?? 0,
                    'photo' => $photo_url
                );
            }
        }
    }
    
    $seen = array();
    $filtered = array();
    foreach ($results as $r) {
        $key = strtolower($r['name']);
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $filtered[] = $r;
        }
    }
    
    usort($filtered, function($a, $b) {
        if ($a['location'] === 'Waxhaw' && $b['location'] !== 'Waxhaw') return -1;
        if ($b['location'] === 'Waxhaw' && $a['location'] !== 'Waxhaw') return 1;
        return $b['rating'] - $a['rating'];
    });
    
    $results = $filtered;
    
    if (empty($results)) {
        return '<p>No local businesses found. Please try again later.</p>';
    }
    
    $cat_options = '<option value="all">All Categories</option>';
    foreach ($default_categories as $cat) {
        $cat_options .= "<option value=\"$cat\">$cat</option>";
    }
    
    $loc_options = '<option value="all">All Locations</option><option value="Waxhaw" selected>Waxhaw</option>';
    
    ob_start();
    ?>
    <div class="tln-dir-container">
        <div class="tln-dir-filters">
            <input type="text" class="tln-dir-search" id="tln-search" placeholder="Search businesses...">
            <select class="tln-dir-filter" id="tln-category"><?php echo $cat_options; ?></select>
            <select class="tln-dir-filter" id="tln-location"><?php echo $loc_options; ?></select>
        </div>
        
        <div class="tln-dir-grid" id="tln-grid">
        <?php foreach ($results as $biz): 
        $img = $biz['photo'] ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWJlYmViIi8+PC9zdmc+';
        $maps_url = "https://www.google.com/maps/search/?api=1&query=" . urlencode($biz['address']);
        $is_waxhaw = ($biz['location'] === 'Waxhaw');
        $waxhaw_class = $is_waxhaw ? ' waxhaw' : '';
        ?>
        <div class="tln-dir-card<?php echo $waxhaw_class; ?>" data-name="<?php echo strtolower(esc_attr($biz['name'])); ?>" data-category="<?php echo esc_attr($biz['category']); ?>" data-location="<?php echo esc_attr($biz['location']); ?>">
            <div class="tln-dir-img-wrap">
                <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($biz['name']); ?>" class="tln-dir-img">
                <?php if ($is_waxhaw): ?>
                <span class="tln-dir-badge">WAXHAW</span>
                <?php endif; ?>
            </div>
            <div class="tln-dir-content">
                <div class="tln-dir-name-wrap">
                    <h3 class="tln-dir-name"><?php echo esc_html($biz['name']); ?></h3>
                </div>
                <div class="tln-dir-category"><?php echo esc_html($biz['category']); ?> • <?php echo esc_html($biz['location']); ?></div>
                <div class="tln-dir-rating">
                    <span class="tln-dir-stars"><?php echo esc_html($biz['stars']); ?></span>
                    <span class="tln-dir-reviews">(<?php echo $biz['reviews']; ?>)</span>
                </div>
                <div class="tln-dir-address" onclick="window.open('<?php echo $maps_url; ?>', '_blank')">📍 <?php echo esc_html($biz['address']); ?></div>
                <a href="/claim/?business=<?php echo urlencode($biz['name']); ?>&place_id=<?php echo $biz['place_id']; ?>" class="tln-dir-claim">Claim This Business</a>
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
                var match = (q === '' || name.indexOf(q) > -1) && (cat === 'all' || category === cat) && (loc === 'all' || location === loc);
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
