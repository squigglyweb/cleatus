<?php
/**
 * Plugin Name: TLN Business Directory
 * Version: 3.5 - Clean with Caching
 */

if (!defined('ABSPATH')) exit;

define('TLN_GOOGLE_API_KEY', 'AIzaSyAH6O3RsnDuX5rJ2OyTHCTZhYtd6s6NSWU');

function tln_dir_styles() {
    wp_enqueue_style('tln-fonts', 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap', array(), null);
    wp_register_style('tln-dir', false);
    wp_enqueue_style('tln-dir');
    $css = '.tln-container{max-width:1200px;margin:0 auto}.tln-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.5rem}.tln-card{background:#fff;border-radius:12px;overflow:hidden;border:2px solid #1a1a1a;box-shadow:0 2px 8px rgba(0,0,0,0.1)}.tln-card.waxhaw{border-color:#e63946}.tln-img{width:100%;height:180px;background:#667eea;display:flex;align-items:center;justify-content:center}.tln-badge{position:absolute;top:10px;right:10px;background:#e63946;color:#fff;padding:4px 12px;font-size:0.75rem;font-weight:700;border-radius:4px;text-transform:uppercase}.tln-content{padding:1rem}.tln-name-wrap{background:#fff;border:2px solid #1a1a1a;padding:0.75rem;margin-bottom:0.5rem;border-radius:4px}.tln-name{font-size:1.1rem;font-weight:700;color:#1a1a1a;margin:0}.tln-cat{color:#e63946;font-size:0.85rem;font-weight:600;margin-bottom:0.5rem}.tln-rating{display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem}.tln-stars{color:#FABC06}.tln-reviews{color:#666;font-size:0.9rem}.tln-address{color:#1a1a1a;font-size:0.9rem;margin-bottom:1rem}.tln-btn{display:block;width:100%;padding:0.9rem;background:#7cda24;color:#fff;text-align:center;text-decoration:none;font-weight:700;font-size:0.95rem;border-radius:8px;text-transform:uppercase}.tln-claim-link{font-size:0.85rem;margin-top:0.75rem}.tln-claim-link a{color:#666;text-decoration:underline}.tln-filters{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem}.tln-search{flex:1;min-width:200px;padding:0.75rem 1rem;border:1px solid #ddd;border-radius:8px;font-size:1rem}.tln-filter{padding:0.75rem 1rem;border:1px solid #ddd;border-radius:8px;font-size:1rem;background:#fff}.tln-pager{display:flex;justify-content:center;gap:0.5rem;margin-top:2rem}.tln-pager a,.tln-pager span{padding:0.5rem 1rem;border:1px solid #ddd;background:#fff;text-decoration:none;color:#333}.tln-pager a:hover{background:#f0f0f0}.tln-pager span{background:#e63946;color:#fff;border-color:#e63946}@media(max-width:600px){.tln-grid{grid-template-columns:1fr}}';
    wp_add_inline_style('tln-dir', $css);
}
add_action('wp_enqueue_scripts', 'tln_dir_styles');

$tln_exclude = array('CVS','Walgreens','Walmart','Target','Costco','Dollar General','McDonalds','Burger King','KFC','Pizza Hut','Dominos','Papa Johns','Subway','Starbucks','Dunkin','Chipotle','Chick-fil-A','Wingstop','Taco Bell','Shell','BP','Exxon','Food Lion','Harris Teeter','Aldi','Whole Foods','Trader Joes','Best Buy','Home Depot','Lowes','Bank of America','Wells Fargo','Chase');

function tln_is_excluded($name) {
    global $tln_exclude;
    foreach($tln_exclude as $e) {
        if(stripos(strtolower($name),strtolower($e)) !== false) return true;
    }
    return false;
}

function tln_get_cached_businesses() {
    $cached = get_transient('tln_businesses');
    if($cached !== false) return $cached;
    
    $api = TLN_GOOGLE_API_KEY;
    $results = array();
    
    $queries = array(
        'Waxhaw' => array('Restaurant'=>'restaurants in Waxhaw NC','Retail'=>'retail stores in Waxhaw NC','Services'=>'services in Waxhaw NC','Food'=>'food and drink in Waxhaw NC','Health'=>'health and wellness in Waxhaw NC','Auto'=>'auto repair in Waxhaw NC','Salon'=>'salon and spa in Waxhaw NC','Fitness'=>'gym and fitness in Waxhaw NC'),
        'Marvin' => array('Restaurant'=>'restaurants in Marvin NC','Retail'=>'retail stores in Marvin NC','Services'=>'services in Marvin NC'),
        'Wesley Chapel' => array('Restaurant'=>'restaurants in Wesley Chapel NC'),
        'Weddington' => array('Restaurant'=>'restaurants in Weddington NC'),
        'Indian Land' => array('Restaurant'=>'restaurants in Indian Land SC')
    );
    
    foreach($queries as $loc => $cats) {
        foreach($cats as $cat => $q) {
            $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=".urlencode($q)."&key=$api";
            $r = wp_remote_get($url,array('timeout'=>10,'sslverify'=>false));
            if(is_wp_error($r)) continue;
            $data = json_decode(wp_remote_retrieve_body($r),true);
            if(empty($data['results'])) continue;
            foreach($data['results'] as $p) {
                if(tln_is_excluded($p['name'])) continue;
                $addr = $p['formatted_address'] ?? '';
                if(stripos($addr,'NC')===false && stripos($addr,'SC')===false) continue;
                if(stripos($addr,'Waxhaw')!==false) $loc2='Waxhaw';
                elseif(stripos($addr,'Marvin')!==false) $loc2='Marvin';
                elseif(stripos($addr,'Wesley Chapel')!==false) $loc2='Wesley Chapel';
                elseif(stripos($addr,'Weddington')!==false) $loc2='Weddington';
                elseif(stripos($addr,'Indian Land')!==false) $loc2='Indian Land';
                else continue;
                
                $photo_ref = '';
                if(!empty($p['photos'][0]['photo_reference'])) {
                    $photo_ref = $p['photos'][0]['photo_reference'];
                }
                
                $results[] = array(
                    'name'=>$p['name'],
                    'place_id'=>$p['place_id'],
                    'cat'=>$cat,
                    'loc'=>$loc2,
                    'addr'=>$addr,
                    'rating'=>$p['rating']??0,
                    'photo_ref'=>$photo_ref
                );
            }
        }
    }
    
    $seen = array();
    $out = array();
    foreach($results as $r) {
        $k = strtolower($r['name']);
        if(!isset($seen[$k])) {
            $seen[$k]=true;
            $out[]=$r;
        }
    }
    usort($out,function($a,$b){
        if($a['loc']=='Waxhaw' && $b['loc']!='Waxhaw') return -1;
        if($b['loc']=='Waxhaw' && $a['loc']!='Waxhaw') return 1;
        return $b['rating'] - $a['rating'];
    });
    
    set_transient('tln_businesses', $out, DAY_IN_SECONDS * 7);
    return $out;
}

function tln_dir_shortcode($atts) {
    $api = TLN_GOOGLE_API_KEY;
    $out = tln_get_cached_businesses();
    
    $per_page = 12;
    $page = isset($_GET['dir-page']) ? intval($_GET['dir-page']) : 1;
    if($page < 1) $page = 1;
    $total = count($out);
    $total_pages = ceil($total / $per_page);
    $start = ($page - 1) * $per_page;
    $page_items = array_slice($out, $start, $per_page);
    
    $request_uri = $_SERVER['REQUEST_URI'];
    $request_uri = remove_query_arg('dir-page', $request_uri);
    $base_url = '//' . $_SERVER['HTTP_HOST'] . $request_uri;
    if (strpos($base_url, '?') === false) $base_url .= '?';
    else $base_url .= '&';
    
    ob_start();
    echo '<div style="text-align:center;margin-bottom:2rem;"><h2 style="text-transform:uppercase;letter-spacing:2px;font-size:1.5rem;">LOCAL BUSINESSES</h2><p style="font-size:1.1rem;color:#666;max-width:600px;margin:0 auto;">From Waxhaw to Weddington to Marvin, we have got it covered. Find it near me in the Greater Waxhaw area</p></div>';
    echo '<div class="tln-container"><div class="tln-filters">';
    echo '<input type="text" class="tln-search" id="tln-s" placeholder="Search...">';
    echo '<select class="tln-filter" id="tln-c"><option value="">All</option><option>Restaurant</option><option>Retail</option><option>Services</option><option>Food</option><option>Health</option><option>Auto</option><option>Salon</option><option>Fitness</option></select>';
    echo '<select class="tln-filter" id="tln-l"><option value="">All</option><option selected>Waxhaw</option><option>Marvin</option><option>Wesley Chapel</option><option>Weddington</option><option>Indian Land</option></select>';
    echo '</div><div class="tln-grid" id="tln-g">';
    
    $icons = array('Restaurant'=>'🍽️','Cafe'=>'☕','Bar'=>'🍺','Retail'=>'🛒','Services'=>'🔧','Food'=>'🍔','Health'=>'🏥','Auto'=>'🔧','Salon'=>'💅','Fitness'=>'💪');
    
    foreach($page_items as $b) {
        $loc = $b['loc'];
        $icon = $icons[$b['cat']] ?? '🏪';
        
        if(!empty($b['photo_ref'])) {
            $photo_url = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=".$b['photo_ref']."&key=$api";
            $img = '<img src="'.esc_url($photo_url).'" style="width:100%;height:180px;object-fit:cover;">';
        } else {
            $img = '<div style="width:100%;height:180px;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;"><span style="font-size:4rem;">'.$icon.'</span></div>';
        }
        
        $claim_url = '/claim/?biz='.urlencode($b['name']).'&pid='.urlencode($b['place_id']);
        
        echo '<div class="tln-card" data-n="'.strtolower($b['name']).'" data-c="'.$b['cat'].'" data-l="'.$loc.'">';
        echo '<div class="tln-img-wrap" style="position:relative">'.$img;
        echo '<span class="tln-badge">'.strtoupper($loc).'</span>';
        echo '</div><div class="tln-content">';
        echo '<div class="tln-name-wrap"><h3 class="tln-name">'.esc_html($b['name']).'</h3></div>';
        echo '<div class="tln-cat">'.esc_html($b['cat']).' &bull; '.esc_html($loc).'</div>';
        echo '<div class="tln-rating"><span class="tln-stars">'.str_repeat('★',floor($b['rating'])).'</span> <span class="tln-reviews">('.$b['rating'].')</span></div>';
        echo '<div class="tln-address">📍 '.esc_html($b['addr']).'</div>';
        echo '<a href="/profile/?biz='.urlencode($b['name']).'&pid='.urlencode($b['place_id']).'" class="tln-btn">View Profile</a>';
        echo '<div class="tln-claim-link"><a href="'.esc_url($claim_url).'">Own this business? Claim it</a></div>';
        echo '</div></div>';
    }
    echo '</div>';
    
    if($total_pages > 1) {
        echo '<div class="tln-pager">';
        for($i=1; $i<=$total_pages; $i++) {
            if($i == $page) {
                echo '<span>'.$i.'</span>';
            } else {
                echo '<a href="'.$base_url.'dir-page='.$i.'">'.$i.'</a>';
            }
        }
        echo '</div>';
    }
    
    echo '</div>';
    ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
jQuery(document).ready(function($) {
    var search = $('#tln-s');
    var cat = $('#tln-c');
    var loc = $('#tln-l');
    var cards = $('.tln-card');
    
    function filter() {
        var q = search.val().toLowerCase();
        var c = cat.val();
        var l = loc.val();
        
        cards.each(function() {
            var card = $(this);
            var match = true;
            if (q && card.data('n').indexOf(q) === -1) match = false;
            if (c && card.data('c') !== c) match = false;
            if (l && card.data('l') !== l) match = false;
            card.toggle(match);
        });
    }
    
    search.on('input', filter);
    cat.on('change', filter);
    loc.on('change', filter);
});
</script>
    <?php
    return ob_get_clean();
}
add_shortcode('tln_directory', 'tln_dir_shortcode');
