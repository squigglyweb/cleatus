<?php
/**
 * Plugin Name: TLN Business Directory
 * Version: 2.8
 */

if (!defined('ABSPATH')) exit;

define('TLN_GOOGLE_API_KEY', 'AIzaSyAH6O3RsnDuX5rJ2OyTHCTZhYtd6s6NSWU');

function tln_dir_styles() {
    wp_enqueue_style('tln-fonts', 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap', array(), null);
    wp_register_style('tln-dir', false);
    wp_enqueue_style('tln-dir');
    $css = '.tln-container{max-width:1200px;margin:0 auto}.tln-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.5rem}.tln-card{background:#fff;border-radius:12px;overflow:hidden;border:2px solid #1a1a1a;box-shadow:0 2px 8px rgba(0,0,0,0.1)}.tln-card.waxhaw{border-color:#e63946}.tln-img{width:100%;height:180px;object-fit:cover;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);display:flex;align-items:center;justify-content:center}.tln-badge{position:absolute;top:10px;right:10px;background:#e63946;color:#fff;padding:4px 12px;font-size:0.75rem;font-weight:700;border-radius:4px;text-transform:uppercase}.tln-content{padding:1rem}.tln-name-wrap{background:#fff;border:2px solid #1a1a1a;padding:0.75rem;margin-bottom:0.5rem;border-radius:4px}.tln-name{font-size:1.1rem;font-weight:700;color:#1a1a1a;margin:0}.tln-cat{color:#e63946;font-size:0.85rem;font-weight:600;margin-bottom:0.5rem}.tln-rating{display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem}.tln-stars{color:#FABC06}.tln-reviews{color:#666;font-size:0.9rem}.tln-address{color:#1a1a1a;font-size:0.9rem;margin-bottom:1rem}.tln-btn{display:block;width:100%;padding:0.9rem;background:#7cda24;color:#fff;text-align:center;text-decoration:none;font-weight:700;font-size:0.95rem;border-radius:8px;text-transform:uppercase}.tln-claim-link{font-size:0.85rem;margin-top:0.75rem}.tln-claim-link a{color:#666;text-decoration:underline}.tln-filters{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem}.tln-search{flex:1;min-width:200px;padding:0.75rem 1rem;border:1px solid #ddd;border-radius:8px;font-size:1rem}.tln-filter{padding:0.75rem 1rem;border:1px solid #ddd;border-radius:8px;font-size:1rem;background:#fff}.tln-pagination{display:flex;justify-content:center;gap:0.5rem;margin-top:2rem}.tln-page-btn{padding:0.5rem 1rem;background:#fff;border:1px solid #ddd;border-radius:4px;cursor:pointer;display:inline-block}.tln-page-btn.active{background:#e63946;color:#fff;border-color:#e63946}@media(max-width:600px){.tln-grid{grid-template-columns:1fr}}';
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

function tln_get_icon($cat) {
    $icons = array('Restaurant'=>'🍽️','Cafe'=>'☕','Bar'=>'🍺','Retail'=>'🛒','Services'=>'🔧');
    return $icons[$cat] ?? '🏪';
}

function tln_dir_shortcode($atts) {
    $api = TLN_GOOGLE_API_KEY;
    $results = array();
    
    $queries = array(
        'Waxhaw' => array('Restaurant'=>'restaurants in Waxhaw NC','Retail'=>'retail stores in Waxhaw NC','Services'=>'services in Waxhaw NC'),
        'Marvin' => array('Restaurant'=>'restaurants in Marvin NC','Retail'=>'retail stores in Marvin NC'),
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
                if(stripos($addr,'Waxhaw')!==false) $loc2='Waxhaw';
                elseif(stripos($addr,'Marvin')!==false) $loc2='Marvin';
                elseif(stripos($addr,'Wesley Chapel')!==false) $loc2='Wesley Chapel';
                elseif(stripos($addr,'Weddington')!==false) $loc2='Weddington';
                elseif(stripos($addr,'Indian Land')!==false) $loc2='Indian Land';
                else continue;
                
                $results[] = array(
                    'name'=>$p['name'],
                    'place_id'=>$p['place_id'],
                    'cat'=>$cat,
                    'loc'=>$loc2,
                    'addr'=>$addr,
                    'rating'=>$p['rating']??0
                );
            }
        }
    }
    
    // Dedupe
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
    
    $per_page = 12;
    $total = count($out);
    $total_pages = ceil($total / $per_page);
    
    // Pass all data to JavaScript
    $json_data = json_encode($out);
    
    ob_start();
    ?>
    <div class="tln-container">
    <div class="tln-filters">
    <input type="text" class="tln-search" id="tln-s" placeholder="Search...">
    <select class="tln-filter" id="tln-c"><option value="">All</option><option>Restaurant</option><option>Retail</option><option>Services</option></select>
    <select class="tln-filter" id="tln-l"><option value="">All</option><option selected>Waxhaw</option><option>Marvin</option><option>Wesley Chapel</option><option>Weddington</option><option>Indian Land</option></select>
    </div>
    <div class="tln-grid" id="tln-g"></div>
    <div class="tln-pagination" id="tln-p"></div>
    </div>
    <script>
    var tln_data = <?php echo $json_data; ?>;
    var tln_per_page = <?php echo $per_page; ?>;
    var tln_current = 1;
    var tln_api = '<?php echo $api; ?>';
    
    function tln_render(page) {
        var s = document.getElementById('tln-s').value.toLowerCase();
        var c = document.getElementById('tln-c').value;
        var l = document.getElementById('tln-l').value;
        
        var filtered = tln_data.filter(function(x) {
            return (s==='' || x.name.toLowerCase().indexOf(s)>-1) && (c==='' || x.cat===c) && (l==='' || x.loc===l);
        });
        
        var total_pages = Math.ceil(filtered.length / tln_per_page);
        var start = (page - 1) * tln_per_page;
        var items = filtered.slice(start, start + tln_per_page);
        
        var g = document.getElementById('tln-g');
        var icons = {'Restaurant':'🍽️','Cafe':'☕','Bar':'🍺','Retail':'🛒','Services':'🔧'};
        
        g.innerHTML = items.map(function(b) {
            var wx = b.loc === 'Waxhaw';
            var icon = icons[b.cat] || '🏪';
            return '<div class="tln-card'+(wx?' waxhaw':'')+'" data-n="'+b.name.toLowerCase()+'" data-c="'+b.cat+'" data-l="'+b.loc+'">' +
                '<div class="tln-img-wrap" style="position:relative"><div class="tln-img"><span style="font-size:4rem;">'+icon+'</span></div>' +
                (wx ? '<span class="tln-badge">WAXHAW</span>' : '') + '</div>' +
                '<div class="tln-content"><div class="tln-name-wrap"><h3 class="tln-name">'+b.name+'</h3></div>' +
                '<div class="tln-cat">'+b.cat+' • '+b.loc+'</div>' +
                '<div class="tln-rating"><span class="tln-stars">'+'★'.repeat(Math.floor(b.rating))+'</span> <span class="tln-reviews">('+b.rating+')</span></div>' +
                '<div class="tln-address">📍 '+b.addr+'</div>' +
                '<a href="/profile/?biz='+encodeURIComponent(b.name)+'&pid='+b.place_id+'" class="tln-btn">View Profile</a>' +
                '<div class="tln-claim-link"><a href="/claim/?biz='+encodeURIComponent(b.name)+'&pid='+b.place_id+'">Own this business? Claim it</a></div>' +
                '</div></div>';
        }).join('');
        
        // Render pagination
        var p = document.getElementById('tln-p');
        var btns = '';
        for(var i=1; i<=total_pages; i++) {
            btns += '<button class="tln-page-btn'+(i===page?' active':'')+'" onclick="tln_render('+i+')">'+i+'</button>';
        }
        p.innerHTML = btns;
        tln_current = page;
    }
    
    document.getElementById('tln-s').addEventListener('input', function() { tln_render(1); });
    document.getElementById('tln-c').addEventListener('change', function() { tln_render(1); });
    document.getElementById('tln-l').addEventListener('change', function() { tln_render(1); });
    
    tln_render(1);
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('tln_directory', 'tln_dir_shortcode');
