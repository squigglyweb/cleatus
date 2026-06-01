<?php
/**
 * TLN Camps Shortcode
 * Provides [tln_camps] shortcode that renders camps from local JSON data
 */

// Register shortcode
add_shortcode('tln_camps', 'tln_camps_shortcode');

function tln_camps_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category' => '',    // optional filter by category
        'location' => '',    // optional filter by location
        'featured' => '',    // show only featured
        'per_page' => 12    // camps per page
    ), $atts);

    $per_page = intval($atts['per_page']) ?: 12;
    $page = isset($_GET['camp_page']) ? max(1, intval($_GET['camp_page'])) : 1;

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
    
    // Flatten for pagination (by category order)
    $flat_camps = array();
    foreach ($by_category as $cat_camps) {
        foreach ($cat_camps as $c) {
            $flat_camps[] = $c;
        }
    }
    
    $total_camps = count($flat_camps);
    $total_pages = ceil($total_camps / $per_page);
    $offset = ($page - 1) * $per_page;
    $page_camps = array_slice($flat_camps, $offset, $per_page);
    
    // Re-group the page camps by category
    $page_by_category = array();
    foreach ($page_camps as $camp) {
        $cat = $camp['category'] ?: 'Other';
        if (!isset($page_by_category[$cat])) {
            $page_by_category[$cat] = array();
        }
        $page_by_category[$cat][] = $camp;
    }
    
    // Build output
    ob_start();
    ?>
    <div class="tln-camps-container">
        <div class="tln-camps-header">
            <h2>Summer Camps & Day Programs</h2>
            <p>Greater Waxhaw Area & Surrounding Communities</p>
        </div>
        
        <?php foreach ($page_by_category as $category => $category_camps): ?>
        <div class="tln-camps-category">
            <h3 class="tln-category-title"><?php echo esc_html($category); ?> Camps</h3>
            <div class="tln-camps-list">
                <?php 
                $row_idx = 0;
                foreach ($category_camps as $camp): 
                    $row_idx++;
                    $is_waxhaw = in_array(strtolower($camp['location'] ?? ''), array('waxhaw', 'waxhaw area', 'indian land'));
                    $row_class = ($row_idx % 2 === 0) ? ' even' : ' odd';
                    $waxhaw_class = $is_waxhaw ? ' waxhaw' : '';
                    $website = $camp['website'] ?? '#';
                    $has_website = $website && $website !== 'TBD' && $website !== '(local)' && $website !== '(contact locally)';
                ?>
                    <div class="tln-camp-row<?php echo $row_class . $waxhaw_class; ?>">
                        <div class="tln-camp-main">
                            <?php if (!empty($camp['logo_image'])): ?>
                            <div class="tln-camp-logo">
                                <img src="<?php echo esc_url($camp['logo_image']); ?>" alt="<?php echo esc_attr($camp['camp_name']); ?>">
                            </div>
                            <?php endif; ?>
                            
                            <div class="tln-camp-info">
                                <div class="tln-camp-location">
                                    <?php echo esc_html($camp['location'] ?: 'Greater Waxhaw Area'); ?>
                                    <?php if ($is_waxhaw): ?>
                                    <span class="tln-waxhaw-badge">Waxhaw Area</span>
                                    <?php endif; ?>
                                </div>
                                
                                <h4 class="tln-camp-name">
                                    <?php if ($has_website): ?>
                                        <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener"><?php echo esc_html($camp['camp_name']); ?></a>
                                    <?php else: ?>
                                        <a href="https://www.google.com/search?q=<?php echo urlencode($camp['camp_name'] . ' summer camp'); ?>" target="_blank" rel="noopener"><?php echo esc_html($camp['camp_name']); ?></a>
                                    <?php endif; ?>
                                </h4>
                            </div>
                        </div>
                        
                        <div class="tln-camp-details">
                            <div class="tln-camp-detail-item">
                                <span class="tln-detail-label">Ages</span>
                                <span class="tln-detail-value"><?php echo $camp['ages'] && $camp['ages'] !== 'TBD' ? esc_html($camp['ages']) : '—'; ?></span>
                            </div>
                            
                            <div class="tln-camp-detail-item">
                                <span class="tln-detail-label">Dates</span>
                                <span class="tln-detail-value"><?php echo $camp['dates'] && $camp['dates'] !== 'TBD' ? esc_html($camp['dates']) : '—'; ?></span>
                            </div>
                            
                            <div class="tln-camp-detail-item">
                                <span class="tln-detail-label">Price</span>
                                <span class="tln-detail-value pricing"><?php echo $camp['pricing'] && $camp['pricing'] !== 'TBD' ? esc_html($camp['pricing']) : '—'; ?></span>
                            </div>
                            
                            <div class="tln-camp-action">
                                <?php if ($has_website): ?>
                                <a href="<?php echo esc_url($website); ?>" class="tln-camp-btn" target="_blank" rel="noopener">Register Now</a>
                                <?php else: ?>
                                <a href="https://www.google.com/search?q=<?php echo urlencode($camp['camp_name'] . ' summer camp'); ?>" class="tln-camp-btn" target="_blank" rel="noopener">Find Out More</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($camp['notes']): ?>
                        <div class="tln-camp-notes"><?php echo esc_html($camp['notes']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if ($total_pages > 1): ?>
        <div class="tln-camps-pagination">
            <?php
            $base_url = $_SERVER['REQUEST_URI'];
            $base_url = strtok($base_url, '?'); // remove query string
            $query_params = $_GET;
            unset($query_params['camp_page']);
            
            // Prev button
            if ($page > 1): 
                $query_params['camp_page'] = $page - 1;
                $prev_url = $base_url . '?' . http_build_query($query_params);
            ?>
                <a href="<?php echo esc_url($prev_url); ?>" class="tln-page-btn tln-page-prev">&larr; Previous</a>
            <?php endif; ?>
            
            <span class="tln-page-info">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
            </span>
            
            <?php if ($page < $total_pages): 
                $query_params['camp_page'] = $page + 1;
                $next_url = $base_url . '?' . http_build_query($query_params);
            ?>
                <a href="<?php echo esc_url($next_url); ?>" class="tln-page-btn tln-page-next">Next &rarr;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="tln-camps-count">
            Showing <?php echo count($page_camps); ?> of <?php echo $total_camps; ?> camps
        </div>
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
    .tln-camps-header h2 { font-size: 2.25rem; color: #1a1a1a; margin-bottom: 0.5rem; text-decoration: underline; text-underline-offset: 8px; }
    .tln-camps-header p { font-size: 1.1rem; color: #666; }
    .tln-camps-category { margin-bottom: 2.5rem; }
    .tln-category-title { font-size: 1.5rem; color: #e63946; margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 2px solid #eee; text-decoration: underline; text-underline-offset: 6px; }
    
    /* Two-column list layout */
    .tln-camps-list { display: flex; flex-direction: column; gap: 0; }
    .tln-camp-row { 
        display: flex; 
        flex-wrap: wrap; 
        gap: 1.5rem; 
        padding: 1.5rem; 
        border-bottom: 1px solid #e8e8e8;
        transition: background 0.2s ease;
    }
    .tln-camp-row.odd { background: #fafafa; }
    .tln-camp-row.even { background: #fff; }
    .tln-camp-row:hover { background: #f0f4f8; }
    .tln-camp-row.waxhaw { border-left: 4px solid #e63946; }
    
    /* Left side - main info */
    .tln-camp-main { flex: 1; min-width: 280px; display: flex; gap: 1rem; align-items: flex-start; }
    .tln-camp-logo { flex-shrink: 0; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; background: #fff; border-radius: 12px; border: 1px solid #e0e0e0; padding: 0.5rem; }
    .tln-camp-logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .tln-camp-info { flex: 1; }
    .tln-camp-location { font-size: 0.85rem; color: #666; font-weight: 600; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
    .tln-waxhaw-badge { background: #e63946; color: #fff; font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 4px; text-transform: uppercase; font-weight: 700; }
    .tln-camp-name { font-size: 1.3rem; font-weight: 700; color: #1a1a1a; margin: 0; line-height: 1.3; }
    .tln-camp-name a { color: #1a1a1a; text-decoration: none; transition: color 0.2s ease; }
    .tln-camp-name a:hover { color: #e63946; text-decoration: underline; }
    
    /* Right side - details */
    .tln-camp-details { 
        flex: 1; 
        min-width: 280px; 
        display: flex; 
        flex-wrap: wrap; 
        gap: 1rem 2rem; 
        align-items: center;
    }
    .tln-camp-detail-item { display: flex; flex-direction: column; gap: 2px; }
    .tln-detail-label { font-size: 0.75rem; color: #888; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
    .tln-detail-value { font-size: 0.95rem; color: #333; font-weight: 600; }
    .tln-detail-value.pricing { color: #2d8a2d; font-weight: 700; font-size: 1.05rem; }
    
    .tln-camp-action { margin-left: auto; }
    .tln-camp-btn { 
        display: inline-block; 
        padding: 0.75rem 1.5rem; 
        background: linear-gradient(135deg, #e63946 0%, #c1121f 100%); 
        color: #fff; 
        text-align: center; 
        text-decoration: none !important; 
        font-weight: 700; 
        border-radius: 8px; 
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(230, 57, 70, 0.3);
    }
    .tln-camp-btn:hover { 
        background: linear-gradient(135deg, #c1121f 0%, #a60d18 100%); 
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(230, 57, 70, 0.4);
    }
    
    /* Notes */
    .tln-camp-notes { width: 100%; font-size: 0.9rem; color: #555; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed #ddd; font-style: italic; }
    
    /* Pagination */
    .tln-camps-pagination { display: flex; justify-content: center; align-items: center; gap: 1rem; margin: 2rem 0 1rem; }
    .tln-page-btn { 
        padding: 0.6rem 1.2rem; 
        background: #fff; 
        border: 2px solid #e63946; 
        color: #e63946; 
        text-decoration: none; 
        font-weight: 700; 
        border-radius: 6px; 
        transition: all 0.2s ease;
    }
    .tln-page-btn:hover { background: #e63946; color: #fff; }
    .tln-page-info { font-size: 0.9rem; color: #666; font-weight: 600; }
    .tln-camps-count { text-align: center; font-size: 0.85rem; color: #999; margin-bottom: 2rem; }
    
    /* Responsive */
    @media (max-width: 768px) { 
        .tln-camp-row { flex-direction: column; }
        .tln-camp-details { margin-top: 1rem; }
        .tln-camp-action { margin-left: 0; margin-top: 0.5rem; width: 100%; }
        .tln-camp-btn { width: 100%; text-align: center; }
        .tln-camps-header h2 { font-size: 1.75rem; }
    }
    ';
}

// Add data directory to plugin
if (!defined('TLN_PLUGIN_DATA_DIR')) {
    define('TLN_PLUGIN_DATA_DIR', plugin_dir_path(__FILE__) . '../data');
}