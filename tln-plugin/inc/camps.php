<?php
/**
 * TLN Camps Shortcode
 * Provides [tln_camps] shortcode that renders camps from local JSON data
 */

// Register shortcode
add_shortcode('tln_camps', 'tln_camps_shortcode');

function tln_camps_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category' => '', // optional filter by category
        'location' => '',  // optional filter by location
        'featured' => ''  // show only featured
    ), $atts);

    // Load camp data
    $json_path = plugin_dir_path(__FILE__) . '../data/camps.json';
    
    if (!file_exists($json_path)) {
        return '<div class="tln-camps-error">Camps data not available. Please check back soon.</div>';
    }
    
    $json_data = file_get_contents($json_path);
    $camps_data = json_decode($json_data, true);
    
    if (empty($camps_data['camps'])) {
        return '<div class="tln-camps-error">No camps found.</div>';
    }
    
    $camps = $camps_data['camps'];
    
    // Filter if category specified
    if (!empty($atts['category'])) {
        $camps = array_filter($camps, function($c) use ($atts) {
            return strtolower($c['category']) === strtolower($atts['category']);
        });
    }
    
    // Filter if location specified  
    if (!empty($atts['location'])) {
        $camps = array_filter($camps, function($c) use ($atts) {
            return strtolower($c['location'] ?? '') === strtolower($atts['location']);
        });
    }
    
    // Filter if featured only
    if ($atts['featured'] === 'true') {
        $camps = array_filter($camps, function($c) {
            return !empty($c['featured']);
        });
    }
    
    if (empty($camps)) {
        return '<div class="tln-camps-error">No camps match your criteria.</div>';
    }
    
    // Group by category
    $by_category = array();
    $category_order = array('Sports', 'STEM', 'Arts', 'Specialty', 'Day Camp', 'Academic', 'Other');
    
    foreach ($camps as $camp) {
        $cat = $camp['category'] ?: 'Other';
        if (!isset($by_category[$cat])) {
            $by_category[$cat] = array();
        }
        $by_category[$cat][] = $camp;
    }
    
    // Sort categories
    uksort($by_category, function($a, $b) use ($category_order) {
        $ai = array_search($a, $category_order);
        $bi = array_search($b, $category_order);
        if ($ai === false) $ai = 100;
        if ($bi === false) $bi = 100;
        return $ai - $bi;
    });
    
    // Build output
    ob_start();
    ?>
    <div class="tln-camps-container">
        <div class="tln-camps-header">
            <h2>Summer Camps & Day Programs</h2>
            <p>Greater Waxhaw Area & Surrounding Communities</p>
        </div>
        
        <?php foreach ($by_category as $category => $category_camps): ?>
        <div class="tln-camps-category">
            <h3 class="tln-category-title"><?php echo esc_html($category); ?> Camps</h3>
            <div class="tln-camps-grid">
                <?php foreach ($category_camps as $camp): ?>
                    <?php 
                    $is_waxhaw = in_array(strtolower($camp['location'] ?? ''), array('waxhaw', 'waxhaw area', 'indian land'));
                    $waxhaw_class = $is_waxhaw ? ' waxhaw' : '';
                    $website = $camp['website'] ?? '#';
                    $has_website = $website && $website !== 'TBD' && $website !== '(local)' && $website !== '(contact locally)';
                    ?>
                    <div class="tln-camp-card<?php echo $waxhaw_class; ?>">
                        <?php if (!empty($camp['logo_image'])): ?>
                        <div class="tln-camp-logo">
                            <img src="<?php echo esc_url($camp['logo_image']); ?>" alt="<?php echo esc_attr($camp['camp_name']); ?>">
                        </div>
                        <?php endif; ?>
                        
                        <div class="tln-camp-content">
                            <div class="tln-camp-location">
                                <?php echo esc_html($camp['location'] ?: 'Greater Waxhaw Area'); ?>
                                <?php if ($is_waxhaw): ?>
                                <span class="tln-waxhaw-badge">Waxhaw Area</span>
                                <?php endif; ?>
                            </div>
                            
                            <h4 class="tln-camp-name"><?php echo esc_html($camp['camp_name']); ?></h4>
                            
                            <?php if ($camp['ages'] && $camp['ages'] !== 'TBD'): ?>
                            <div class="tln-camp-meta">
                                <span class="tln-meta-ages">Ages: <?php echo esc_html($camp['ages']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($camp['dates'] && $camp['dates'] !== 'TBD'): ?>
                            <div class="tln-camp-dates"><?php echo esc_html($camp['dates']); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($camp['pricing'] && $camp['pricing'] !== 'TBD'): ?>
                            <div class="tln-camp-pricing"><?php echo esc_html($camp['pricing']); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($camp['notes']): ?>
                            <div class="tln-camp-notes"><?php echo esc_html($camp['notes']); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($has_website): ?>
                            <a href="<?php echo esc_url($website); ?>" class="tln-camp-btn" target="_blank" rel="noopener">Register</a>
                            <?php else: ?>
                            <a href="https://www.google.com/search?q=<?php echo urlencode($camp['camp_name'] . ' summer camp'); ?>" class="tln-camp-btn" target="_blank" rel="noopener">Find Out More</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
    $output = ob_get_clean();
    
    // Enqueue styles
    tln_camps_enqueue_styles();
    
    return $output;
}

function tln_camps_enqueue_styles() {
    static $enqueued = false;
    if ($enqueued) return;
    $enqueued = true;
    
    wp_enqueue_style('tln-camps', false);
    wp_add_inline_style('tln-camps', tln_camps_get_styles());
}

function tln_camps_get_styles() {
    return '
    .tln-camps-container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; font-family: "Open Sans", sans-serif; }
    .tln-camps-header { text-align: center; margin-bottom: 2.5rem; }
    .tln-camps-header h2 { font-size: 2.25rem; color: #1a1a1a; margin-bottom: 0.5rem; }
    .tln-camps-header p { font-size: 1.1rem; color: #666; }
    .tln-camps-category { margin-bottom: 2.5rem; }
    .tln-category-title { font-size: 1.5rem; color: #e63946; margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 2px solid #eee; }
    .tln-camps-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
    .tln-camp-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #e0e0e0; transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .tln-camp-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
    .tln-camp-card.waxhaw { border: 2px solid #e63946; }
    .tln-camp-logo { height: 80px; display: flex; align-items: center; justify-content: center; background: #f8f8f8; padding: 0.5rem; }
    .tln-camp-logo img { max-height: 70px; max-width: 100%; object-fit: contain; }
    .tln-camp-content { padding: 1.25rem; }
    .tln-camp-location { font-size: 0.8rem; color: #666; font-weight: 600; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
    .tln-waxhaw-badge { background: #e63946; color: #fff; font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 4px; text-transform: uppercase; }
    .tln-camp-name { font-size: 1.15rem; font-weight: 700; color: #1a1a1a; margin-bottom: 0.75rem; line-height: 1.3; }
    .tln-camp-meta { font-size: 0.9rem; color: #444; margin-bottom: 0.5rem; }
    .tln-camp-dates { font-size: 0.85rem; color: #666; margin-bottom: 0.5rem; }
    .tln-camp-pricing { font-size: 1rem; font-weight: 700; color: #2d8a2d; margin-bottom: 0.75rem; }
    .tln-camp-notes { font-size: 0.9rem; color: #555; margin-bottom: 1rem; line-height: 1.5; }
    .tln-camp-btn { display: block; width: 100%; padding: 0.85rem; background: #e63946; color: #fff; text-align: center; text-decoration: none; font-weight: 600; border-radius: 8px; transition: background 0.2s ease; }
    .tln-camp-btn:hover { background: #c1121f; }
    .tln-camps-error { max-width: 1200px; margin: 0 auto; padding: 2rem; text-align: center; color: #666; }
    @media (max-width: 600px) { 
        .tln-camps-grid { grid-template-columns: 1fr; }
        .tln-camps-header h2 { font-size: 1.75rem; }
    }
    ';
}

// Add data directory to plugin
if (!defined('TLN_PLUGIN_DATA_DIR')) {
    define('TLN_PLUGIN_DATA_DIR', plugin_dir_path(__FILE__) . '../data');
}