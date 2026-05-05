<?php
/**
 * Plugin Name: TLN Golf Courses
 * Description: Display golf courses near Waxhaw with live Google data and filtering
 * Version: 1.1
 */

function tln_golf_styles() {
    wp_enqueue_style('tln-fonts', 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap', array(), null);
    
    wp_register_style('tln-golf', false);
    wp_enqueue_style('tln-golf');
    
    $css = '
    .golf-container { max-width: 1200px; margin: 0 auto; }
    .golf-filters { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; align-items: center; }
    .golf-search { flex: 1; min-width: 200px; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; font-family: "Open Sans", sans-serif; }
    .golf-search:focus { outline: none; border-color: #e63946; }
    .golf-filter { padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; font-family: "Open Sans", sans-serif; background: white; cursor: pointer; }
    .golf-filter:focus { outline: none; border-color: #e63946; }
    .golf-sort { padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; font-family: "Open Sans", sans-serif; background: white; cursor: pointer; }
    .golf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
    .golf-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); transition: transform 0.2s; }
    .golf-card:hover { transform: translateY(-4px); }
    .golf-card.hidden { display: none; }
    .golf-image { width: 100%; height: 200px; object-fit: cover; background: #ebebeb; }
    .golf-content { padding: 1.25rem; }
    .golf-name { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; color: #1a1a1a; }
    .golf-rating { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; }
    .golf-stars { color: #e63946; }
    .golf-count { color: #666; font-size: 0.9rem; }
    .golf-address { color: #666; margin-bottom: 0.5rem; }
    .golf-phone { color: #666; margin-bottom: 0.5rem; }
    .golf-hours { font-size: 0.85rem; color: #666; margin-bottom: 1rem; }
    .golf-hours .open { color: #22c55e; }
    .golf-hours .closed { color: #e63946; }
    .golf-btn { display: block; width: 100%; padding: 0.9rem; background: #e63946; color: white; text-align: center; text-decoration: none; font-weight: 600; border-radius: 8px; }
    .golf-btn:hover { background: #c1121f; }
    .golf-location { font-size: 0.8rem; color: #e63946; font-weight: 600; margin-bottom: 0.25rem; }
    @media (max-width: 600px) { .golf-grid { grid-template-columns: 1fr; } .golf-filters { flex-direction: column; } .golf-search, .golf-filter, .golf-sort { width: 100%; } }
    ';
    
    wp_add_inline_style('tln-golf', $css);
}
add_action('wp_enqueue_scripts', 'tln_golf_styles');

// API Key
define('TLN_GOOGLE_API_KEY', 'AIzaSyAH6O3RsnDuX5rJ2OyTHCTZhYtd6s6NSWU');

// Golf courses with location tags
$golf_courses = array(
    'ChIJLwMuexIoVIgRtAZI5du3za4' => array('name' => 'The Club at Longview', 'location' => 'Waxhaw'),
    'ChIJ07ciSwAyVIgRs37Vuzt7yTw' => array('name' => 'Stonebridge Golf Club', 'location' => 'Monroe'),
    'ChIJqa0AAYgnVIgRxhhMbrOJVsc' => array('name' => 'TPC Piper Glen', 'location' => 'Charlotte'),
    'ChIJ8ZCLeksjVIgRijcbkkoEdgM' => array('name' => 'The Divide Golf Club', 'location' => 'Matthews'),
    'ChIJzfYWh4yCVogREeT1Qb0Tte0' => array('name' => 'Country Club of the Carolinas', 'location' => 'Marvin'),
    'ChIJG_RuEcadVogRRyKyhv9JHlQ' => array('name' => 'Quail Hollow Club', 'location' => 'Charlotte'),
    'ChIJ1wOxKsI8VIgRTNj4eLhT7TY' => array('name' => 'Emerald Lake Golf Club', 'location' => 'Matthews'),
    'ChIJ_SeZqaeBVogRpeTezQCNFpY' => array('name' => 'Carolina Lakes Golf Club', 'location' => 'Indian Land'),
    'ChIJrcfejWOGVogRkkDZjpMoIBI' => array('name' => 'Waterford Golf Club', 'location' => 'Rock Hill'),
    'ChIJ0aJBosmQVogRoJf-xWZdzY8' => array('name' => 'Ballantyne Country Club', 'location' => 'Charlotte'),
    'ChIJ5VzjU-2EVogRBTX5J_HxH1A' => array('name' => 'Springfield Golf Club', 'location' => 'Fort Mill'),
    'ChIJ23H3hYSmmlQR9YQ5oCRsYWc' => array('name' => 'Monroe Country Club', 'location' => 'Monroe'),
    'ChIJGYwlJgsnVIgRC3B-hFNE3uU' => array('name' => 'Raintree Country Club', 'location' => 'Charlotte'),
    'ChIJBY2Or0yhVogRJJ1E8YEyLjE' => array('name' => 'Sunset Hills Golf Course', 'location' => 'Charlotte'),
    'ChIJJ2buw9gnVIgRyyJdVEgImSs' => array('name' => 'Providence Country Club', 'location' => 'Charlotte'),
    'ChIJ_-M36IInVIgRnG0sPkrBIHI' => array('name' => 'Cedarwood Country Club', 'location' => 'Charlotte'),
    'ChIJOXXAhwEdVIgRya7BS0Ex_iE' => array('name' => 'Tradition Golf Course', 'location' => 'Charlotte'),
    'ChIJ7fAXYI6dVogRS2FTBJPlKVo' => array('name' => 'Carmel Country Club', 'location' => 'Charlotte'),
    'ChIJyz_TCu12VogRrzBtgQCIug4' => array('name' => 'Edgewater Golf Club', 'location' => 'Lancaster'),
    'ChIJ53pfVauYVogR9f4MLtnFQaI' => array('name' => 'Carolina Golf Club', 'location' => 'Charlotte'),
);

// Get unique locations for filter
$locations = array_unique(array_column($golf_courses, 'location'));
sort($locations);

function tln_golf_shortcode($atts) {
    global $golf_courses, $locations;
    
    $api_key = TLN_GOOGLE_API_KEY;
    $results = array();
    
    foreach ($golf_courses as $place_id => $info) {
        $transient_key = "tln_golf_{$place_id}";
        $cached = get_transient($transient_key);
        
        if (false === $cached) {
            $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$place_id&fields=name,formatted_address,formatted_phone_number,rating,user_ratings_total,opening_hours,photos&key=$api_key";
            $response = wp_remote_get($url);
            if (is_wp_error($response)) continue;
            $data = json_decode(wp_remote_retrieve_body($response), true);
            $cached = $data['result'] ?? null;
            // Cache for 1 hour
            set_transient($transient_key, $cached, 3600);
        }
        
        $r = $cached;
        if (!$r) continue;
        
        $photo_url = '';
        $photos = $r['photos'] ?? array();
        if (!empty($photos)) {
            $photo_ref = $photos[0]['photo_reference'];
            $photo_url = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=600&photoreference=$photo_ref&key=$api_key";
        }
        
        $rating = $r['rating'] ?? 0;
        $stars = str_repeat('★', floor($rating)) . (fmod($rating, 1) >= 0.5 ? '½' : '');
        $open_now = $r['opening_hours']['open_now'] ?? null;
        $hours_text = $open_now === true ? '<span class="open">Open now</span>' : ($open_now === false ? '<span class="closed">Closed</span>' : '');
        
        $results[] = array(
            'place_id' => $place_id,
            'name' => $r['name'],
            'location' => $info['location'],
            'address' => $r['formatted_address'],
            'phone' => $r['formatted_phone_number'] ?? '',
            'rating' => $rating,
            'stars' => $stars,
            'reviews' => $r['user_ratings_total'] ?? 0,
            'open_now' => $hours_text,
            'photo' => $photo_url,
            'maps_link' => "https://www.google.com/maps/place/$place_id",
        );
    }
    
    if (empty($results)) {
        return '<p>No golf courses found.</p>';
    }
    
    // Build location filter options
    $location_options = '<option value="all">All Locations</option>';
    foreach ($locations as $loc) {
        $location_options .= "<option value=\"$loc\">$loc</option>";
    }
    
    ob_start();
    ?>
    <div class="golf-container">
        <div class="golf-filters">
            <input type="text" class="golf-search" id="golf-search" placeholder="Search golf courses...">
            <select class="golf-filter" id="golf-location">
                <?php echo $location_options; ?>
            </select>
            <select class="golf-sort" id="golf-sort">
                <option value="rating">Highest Rated</option>
                <option value="name">Alphabetical</option>
                <option value="reviews">Most Reviews</option>
            </select>
        </div>
        <div class="golf-grid" id="golf-grid">
    <?php
    foreach ($results as $course) {
        $img = $course['photo'] ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWJlYmViIi8+PC9zdmc+';
        ?>
        <div class="golf-card" data-name="<?php echo strtolower(esc_attr($course['name'])); ?>" data-location="<?php echo esc_attr($course['location']); ?>" data-rating="<?php echo esc_attr($course['rating']); ?>" data-reviews="<?php echo esc_attr($course['reviews']); ?>">
            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($course['name']); ?>" class="golf-image">
            <div class="golf-content">
                <div class="golf-location"><?php echo esc_html($course['location']); ?></div>
                <h3 class="golf-name"><?php echo esc_html($course['name']); ?></h3>
                <div class="golf-rating">
                    <span class="golf-stars"><?php echo esc_html($course['stars']); ?></span>
                    <span class="golf-count"><?php echo esc_html($course['rating'] . ' (' . $course['reviews'] . ')'); ?></span>
                </div>
                <div class="golf-address"><?php echo esc_html($course['address']); ?></div>
                <?php if ($course['phone']): ?>
                <div class="golf-phone">📞 <?php echo esc_html($course['phone']); ?></div>
                <?php endif; ?>
                <div class="golf-hours"><?php echo $course['open_now']; ?></div>
                <a href="<?php echo esc_url($course['maps_link']); ?>" target="_blank" class="golf-btn">View on Google Maps →</a>
            </div>
        </div>
        <?php
    }
    ?>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded',function(){
        var search=document.getElementById('golf-search'),locationFilter=document.getElementById('golf-location'),sort=document.getElementById('golf-sort'),cards=document.querySelectorAll('.golf-card');
        function filter(){
            var q=search.value.toLowerCase(),loc=locationFilter.value,cardsArr=Array.from(cards);
            cards.forEach(function(c){
                var name=c.dataset.name,location=c.dataset.location,show=q==''||name.indexOf(q)>-1&&(loc=='all'||location==loc);
                c.classList.toggle('hidden',!show);
            });
            var visible=cardsArr.filter(function(c){return!c.classList.contains('hidden')});
            var sorted=visible.sort(function(a,b){
                if(sort.value=='rating')return parseFloat(b.dataset.rating)-parseFloat(a.dataset.rating);
                if(sort.value=='reviews')return parseInt(b.dataset.reviews)-parseInt(a.dataset.reviews);
                return a.dataset.name.localeCompare(b.dataset.name);
            });
            var grid=document.getElementById('golf-grid');
            sorted.forEach(function(c){grid.appendChild(c)});
        }
        search.addEventListener('input',filter);
        locationFilter.addEventListener('change',filter);
        sort.addEventListener('change',filter);
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('golf_directory', 'tln_golf_shortcode');
add_shortcode('golf_course', 'tln_golf_shortcode');