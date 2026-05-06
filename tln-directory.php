<?php
/**
 * Plugin Name: TLN Business Directory
 * Version: 2.0
 */

if (!defined('ABSPATH')) exit;

define('TLN_GOOGLE_API_KEY', 'AIzaSyAH6O3RsnDuX5rJ2OyTHCTZhYtd6s6NSWU');

function tln_dir_styles() {
    wp_enqueue_style('tln-fonts', 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap', array(), null);
    wp_register_style('tln-dir', false);
    wp_enqueue_style('tln-dir');
    $css = '.tln-container{max-width:1200px;margin:0 auto}.tln-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.5rem}.tln-card{background:#fff;border-radius:12px;overflow:hidden;border:2px solid #1a1a1a;box-shadow:0 2px 8px rgba(0,0,0,0.1)}.tln-card:hover{transform:translateY(-2px)}.tln-card.waxhaw{border-color:#e63946}.tln-img{width:100%;height:200px;object-fit:cover;background:#ebebeb}.tln-badge{position:absolute;top:10px;right:10px;background:#e63946;color:#fff;padding:4px 12px;font-size:0.75rem;font-weight:700;border-radius:4px;text-transform:uppercase}.tln-content{padding:1rem}.tln-name-wrap{background:#fff;border:2px solid #1a1a1a;padding:0.75rem;margin-bottom:0.5rem;border-radius:4px}.tln-name{font-size:1.1rem;font-weight:700;color:#1a1a1a;margin:0}.tln-cat{color:#e63946;font-size:0.85rem;font-weight:600;margin-bottom:0.5rem}.tln-rating{display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem}.tln-stars{color:#FABC06}.tln-reviews{color:#666;font-size:0.9rem}.tln-address{color:#1a1a1a;font-size:0.9rem;margin-bottom:1rem;text-decoration:underline;cursor:pointer}.tln-btn{display:block;width:100%;padding:0.9rem;background:#7cda24;color:#fff;text-align:center;text-decoration:none;font-weight:700;font-size:0.95rem;border:2px solid #fff;border-radius:8px;text-transform:uppercase;box-sizing:border-box}.tln-btn:hover{background:#6bc91f}.tln-claim-link{font-size:0.85rem;margin-top:0.75rem}.tln-claim-link a{color:#666;text-decoration:underline}.tln-filters{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem}.tln-search{flex:1;min-width:200px;padding:0.75rem 1rem;border:1px solid #ddd;border-radius:8px;font-size:1rem}.tln-filter{padding:0.75rem 1rem;border:1px solid #ddd;border-radius:8px;font-size:1rem;background:#fff}@media(max-width:600px){.tln-grid{grid-template-columns:1fr}}';
    wp_add_inline_style('tln-dir', $css);
}
add_action('wp_enqueue_scripts', 'tln_dir_styles');

$tln_exclude = array('CVS','Walgreens','Walmart','Target','Costco','Dollar General','McDonalds','Burger King','KFC','Pizza Hut','Dominos','Papa Johns','Subway','Starbucks','Dunkin','Chipotle','Chick-fil-A','Wingstop','Taco Bell','Shell','BP','Exxon','Food Lion','Harris Teeter','Aldi','Whole Foods','Trader Joes','Best Buy','Home Depot','Lowes','Bank of America','Wells Fargo','Chase','Crossroad Grill');

function tln_is_excluded($name) {
    global $tln_exclude;
    $n = strtolower($name);
    foreach($tln_exclude as $e) {
        if(stripos($n,strtolower($e)) !== false) return true;
    }
    return false;
}

function tln_dir_shortcode($atts) {
    $api = defined('TLN_GOOGLE_API_KEY') ? TLN_GOOGLE_API_KEY : '';
    if(empty($api)) return '<p>API error</p>';
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
                elseif(stripos($addr,'Matthews')!==false) $loc2='Matthews';
                elseif(stripos($addr,'Charlotte')!==false) $loc2='Charlotte';
                else $loc2='Other';
                $results[] = array('name'=>$p['name'],'place_id'=>$p['place_id'],'cat'=>$cat,'loc'=>$loc2,'addr'=>$addr,'rating'=>$p['rating']??0,'reviews'=>$p['user_ratings_total']??0,'photo'=>'');
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
    
    ob_start();
    echo '<div class="tln-container"><div class="tln-filters">';
    echo '<input type="text" class="tln-search" id="tln-s" placeholder="Search...">';
    echo '<select class="tln-filter" id="tln-c"><option value="">All</option><option>Restaurant</option><option>Retail</option><option>Services</option></select>';
    echo '<select class="tln-filter" id="tln-l"><option value="">All</option><option selected>Waxhaw</option><option>Marvin</option><option>Wesley Chapel</option><option>Weddington</option><option>Indian Land</option></select>';
    echo '</div><div class="tln-grid" id="tln-g">';
    foreach($out as $b) {
        $wx = ($b['loc']=='Waxhaw');
        $cl = $wx ? ' waxhaw' : '';
        echo "<div class=\"tln-card$cl\" data-n=\"".strtolower($b['name'])."\" data-c=\"{$b['cat']}\" data-l=\"{$b['loc']}\">";
        echo '<div class="tln-img-wrap" style="position:relative"><img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWJlYmViIi8+PC9zdmc+" class="tln-img" alt="'.esc_attr($b['name']).'">';
        if($wx) echo '<span class="tln-badge">WAXHAW</span>';
        echo '</div><div class="tln-content">';
        echo '<div class="tln-name-wrap"><h3 class="tln-name">'.esc_html($b['name']).'</h3></div>';
        echo '<div class="tln-cat">'.esc_html($b['cat']).' &bull; '.esc_html($b['loc']).'</div>';
        echo '<div class="tln-rating"><span class="tln-stars">'.str_repeat('★',floor($b['rating'])).'</span> <span class="tln-reviews">('.$b['rating'].')</span></div>';
        echo '<div class="tln-address" onclick="window.open(\'https://www.google.com/maps/search/?api=1&query='.urlencode($b['addr']).'\',\'_blank\')">📍 '.esc_html($b['addr']).'</div>';
        echo '<a href="/claim/?biz='.urlencode($b['name']).'&pid='.$b['place_id'].'" class="tln-btn">View Profile</a>';
        echo '<div class="tln-claim-link"><a href="/claim/">Own this business? Claim it</a></div>';
        echo '</div></div>';
    }
    echo '</div></div>';
    echo "<script>document.addEventListener('DOMContentLoaded',function(){var s=document.getElementById('tln-s'),c=document.getElementById('tln-c'),l=document.getElementById('tln-l'),g=document.getElementById('tln-g'),d=g.querySelectorAll('.tln-card');function f(){var q=s.value.toLowerCase(),cc=c.value,lc=l.value;d.forEach(function(x){var m=(q==''||x.dataset.n.indexOf(q)>-1)&&(cc==''||x.dataset.c==cc)&&(lc==''||x.dataset.l==lc);x.style.display=m?'':'none'})};s.addEventListener('input',f);c.addEventListener('change',f);l.addEventListener('change',f)})</script>";
    return ob_get_clean();
}
add_shortcode('tln_directory', 'tln_dir_shortcode');
