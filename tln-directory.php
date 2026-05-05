<?php
/**
 * Plugin Name: TLN Business Directory
 * Description: Display local businesses from Google API with claim functionality
 * Version: 1.2
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
    .tln-dir-card.waxhaw-priority { border: 2px solid #e63946; }
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
    .tln-dir-filters { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; align-items: center; }
    .tln-dir-search { flex: 1; min-width: 200px; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; font-family: "Open Sans", sans-serif; }
    .tln-dir-filter { padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; font-family: "Open Sans", sans-serif; background: white; cursor: pointer; min-width: 150px; }
    .tln-dir-filter.waxhaw { border-color: #e63946; background: #fef3c7; }
    .tln-dir-badge { display: inline-block; background: #e63946; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 600; margin-left: 0.5rem; }
    @media (max-width: 600px) { .tln-dir-grid { grid-template-columns: 1fr; } .tln-dir-filters { flex-direction: column; } .tln-dir-search, .tln-dir-filter { width: 100%; } }
    ';
    
    wp_add_inline_style('tln-dir', $css);
}
add_action('wp_enqueue_scripts', 'tln_directory_styles');

// API Key
define('TLN_GOOGLE_API_KEY', 'AIzaSyAH6O3RsnDuX5rJ2OyTHCTZhYtd6s6NSWU');

// Search areas - WAXHAW FIRST
$search_areas = array(
    'Waxhaw' => 'Waxhaw, NC',
    'Weddington' => 'Weddington, NC',
    'Wesley Chapel' => 'Wesley Chapel, NC',
    'Marvin' => 'Marvin, NC',
    'Indian Land' => 'Indian Land, SC',
    'Ballantyne' => 'Ballantyne, Charlotte, NC'
);

// Categories
$default_categories = array('Restaurant', 'Retail', 'Medical', 'Services', 'Food & Drink');

function tln_directory_shortcode($atts) {
    $api_key = TLN_GOOGLE_API_KEY;
    $results = array();
    
    // Fetch from Google - WAXHAW FIRST
    $search_queries = array(
        'Waxhaw' => array(
            'Restaurant' => 'restaurants in Waxhaw NC',
            'Retail' => 'retail stores in Waxhaw NC',
            'Medical' => 'medical offices in Waxhaw NC',
            'Services' => 'services in Waxhaw NC',
            'Food & Drink' => 'food and drink in Waxhaw NC'
        ),
        'Weddington' => array(
            'Restaurant' => 'restaurants in Weddington NC',
            'Retail' => 'retail stores in Weddington NC'
        ),
        'Wesley Chapel' => array(
            'Restaurant' => 'restaurants in Wesley Chapel NC',
            'Retail' => 'retail stores in Wesley Chapel NC'
        ),
        'Marvin' => array(
            'Restaurant' => 'restaurants in Marvin NC',
            'Retail' => 'retail stores in Marvin NC'
        ),
        'Indian Land' => array(
            'Restaurant' => 'restaurants in Indian Land SC',
            'Retail' => 'retail stores in Indian Land SC'
        ),
        'Ballantyne' => array(
            'Restaurant' => 'restaurants in Ballantyne Charlotte NC',
            'Retail' => 'retail stores in Ballantyne Charlotte NC'
        )
    );
    
    // Fetch Waxhaw first
    foreach ($search_queries as $location => $categories) {
        foreach ($categories as $category => $query) {
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
                        $details_url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$place_id&fields=name,formatted_address,formatted_phone_number,rating,user_ratings_total,photos,geometry&key=$api_key";
                        $details_response = wp_remote_get($details_url);
                        
                        if (!is_wp_error($details_response)) {
                            $details_data = json_decode(wp_remote_retrieve_body($details_response), true);
                            $cached = $details_data['result'] ?? $place;
                            set_transient($transient_key, $cached, 86400);
                        }
                    }
                    
                    $r = $cached ?? $place;
                    
                    $photo_url = '';
                    $photos = $r['photos'] ?? array();
                    if (!empty($photos)) {
                        $photo_ref = $photos[0]['photo_reference'];
                        $photo_url = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=$photo_ref&key=$api_key";
                    }
                    
                    $rating = $r['rating'] ?? 0;
                    $stars = str_repeat('★', floor($rating)) . (fmod($rating, 1) >= 0.5 ? '½' : '');
                    
                    $results[] = array(
                        'place_id' => $place_id,
                        'name' => $r['name'],
                        'category' => $category,
                        'location' => $location,
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
    }
    
    // Remove duplicates, WAXHAW FIRST
    $seen = array();
    $waxhaw = array();
    $others = array();
    
    foreach ($results as $r) {
        $key = strtolower($r['name']);
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            if ($r['location'] === 'Waxhaw') {
                $waxhaw[] = $r;
            } else {
                $others[] = $r;
            }
        }
    }
    
    // Waxhaw first, then sort others alphabetically
    $results = array_merge($waxhaw, $others);
    
    if (empty($results)) {
        return '<p>No businesses found.</p>';
    }
    
    // Build category options
    $cat_options = '<option value="all">All Categories</option>';
    foreach ($default_categories as $cat) {
        $cat_options .= "<option value=\"$cat\">$cat</option>";
    }
    
    // Build location options - WAXHAW FIRST
    $loc_options = '<option value="all">All Locations</option>';
    foreach (array_keys($search_areas) as $loc) {
        $selected = ($loc === 'Waxhaw') ? ' selected' : '';
        $loc_options .= "<option value=\"$loc\"$selected>$loc</option>";
    }
    
    ob_start();
    ?>
    <div class="tln-dir-container">
        <div class="tln-dir-filters">
            <input type="text" class="tln-dir-search" id="tln-search" placeholder="Search businesses...">
            <select class="tln-dir-filter" id="tln-category">
                <?php echo $cat_options; ?>
            </select>
            <select class="tln-dir-filter" id="tln-location">
                <?php echo $loc_options; ?>
            </select>
            <select class="tln-dir-filter" id="tln-sort">
                <option value="waxhaw">Waxhaw First (Default)</option>
                <option value="rating">Highest Rated</option>
                <option value="reviews">Most Reviews</option>
                <option value="alpha">A-Z</option>
            </select>
        </div>
        
        <div class="tln-dir-grid" id="tln-grid">
        <?php foreach ($results as $biz): ?>
        <?php 
        $img = $biz['photo'] ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWJlYmViIi8+PC9zdmc+';
        $is_waxhaw = ($biz['location'] === 'Waxhaw');
        $waxhaw_class = $is_waxhaw ? ' waxhaw-priority' : '';
        ?>
        <div class="tln-dir-card<?php echo $waxhaw_class; ?>" data-name="<?php echo strtolower(esc_attr($biz['name'])); ?>" data-category="<?php echo esc_attr($biz['category']); ?>" data-location="<?php echo esc_attr($biz['location']); ?>" data-rating="<?php echo esc_attr($biz['rating']); ?>" data-reviews="<?php echo esc_attr($biz['reviews']); ?>" data-waxhaw="<?php echo $is_waxhaw ? 1 : 0; ?>">
            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($biz['name']); ?>" class="tln-dir-img">
            <div class="tln-dir-content">
                <div class="tln-dir-category">
                    <?php echo esc_html($biz['category']); ?> • <?php echo esc_html($biz['location']); ?>
                    <?php if ($is_waxhaw): ?>
                    <span class="tln-dir-badge">WAXHAW</span>
                    <?php endif; ?>
                </div>
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
        var sortFilter = document.getElementById('tln-sort');
        var grid = document.getElementById('tln-grid');
        var cards = Array.from(document.querySelectorAll('.tln-dir-card'));
        
        function filterAndSort() {
            var q = search.value.toLowerCase();
            var cat = catFilter.value;
            var loc = locFilter.value;
            var sort = sortFilter.value;
            
            // Filter
            var visible = cards.filter(function(c) {
                var name = c.dataset.name;
                var category = c.dataset.category;
                var location = c.dataset.location;
                
                var match = (q === '' || name.indexOf(q) > -1) &&
                           (cat === 'all' || category === cat) &&
                           (loc === 'all' || location === loc);
                return match;
            });
            
            // Hide all first
            cards.forEach(function(c) { c.style.display = 'none'; });
            
            // Sort
            visible.sort(function(a, b) {
                if (sort === 'waxhaw') {
                    // Waxhaw first, then by rating
                    var aw = parseInt(a.dataset.waxhaw);
                    var bw = parseInt(b.dataset.waxhaw);
                    if (aw !== bw) return bw - aw;
                    return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
                }
                if (sort === 'rating') return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
                if (sort === 'reviews') return parseInt(b.dataset.reviews) - parseInt(a.dataset.reviews);
                if (sort === 'alpha') return a.dataset.name.localeCompare(b.dataset.name);
                return 0;
            });
            
            // Show sorted
            visible.forEach(function(c) { c.style.display = ''; });
        }
        
        search.addEventListener('input', filterAndSort);
        catFilter.addEventListener('change', filterAndSort);
        locFilter.addEventListener('change', filterAndSort);
        sortFilter.addEventListener('change', filterAndSort);
        
        // Initial sort
        filterAndSort();
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('tln_directory', 'tln_directory_shortcode');
