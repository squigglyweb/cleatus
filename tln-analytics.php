<?php
/**
 * Plugin Name: TLN Analytics
 * Description: Track page views and CTA clicks with UTM data
 * Version: 1.0
 */

register_activation_hook(__FILE__, 'tln_analytics_install');

function tln_analytics_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_analytics (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        business_id bigint(20) NOT NULL,
        event_type varchar(50) NOT NULL,
        source varchar(100),
        medium varchar(100),
        campaign varchar(100),
        referrer varchar(500),
        user_agent varchar(500),
        ip_address varchar(45),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY business_id (business_id),
        KEY event_type (event_type),
        KEY created_at (created_at)
    ) $charset_collate");
}

// Shortcode to track and display analytics
add_shortcode('tln_analytics', 'tln_analytics_display');

function tln_track_event($business_id, $event_type) {
    global $wpdb;
    
    $source = isset($_GET['utm_source']) ? sanitize_text_field($_GET['utm_source']) : 'direct';
    $medium = isset($_GET['utm_medium']) ? sanitize_text_field($_GET['utm_medium']) : 'web';
    $campaign = isset($_GET['utm_campaign']) ? sanitize_text_field($_GET['utm_campaign']) : '';
    $referrer = isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field($_SERVER['HTTP_REFERER']) : '';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
    
    $wpdb->insert($wpdb->prefix . 'tln_analytics', array(
        'business_id' => $business_id,
        'event_type' => $event_type,
        'source' => $source,
        'medium' => $medium,
        'campaign' => $campaign,
        'referrer' => $referrer,
        'user_agent' => $user_agent,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
    ));
}

function tln_analytics_display($atts) {
    $atts = shortcode_atts(array('business_id' => 0), $atts, 'tln_analytics');
    $business_id = $atts['business_id'];
    
    if (!$business_id) {
        return '<p>Business ID required for analytics.</p>';
    }
    
    global $wpdb;
    
    // Get totals
    $total_views = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tln_analytics WHERE business_id = %d AND event_type = 'view'",
        $business_id
    ));
    
    $total_clicks = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tln_analytics WHERE business_id = %d AND event_type = 'click'",
        $business_id
    ));
    
    // Get by source
    $by_source = $wpdb->get_results($wpdb->prepare(
        "SELECT source, COUNT(*) as count FROM {$wpdb->prefix}tln_analytics 
        WHERE business_id = %d GROUP BY source ORDER BY count DESC",
        $business_id
    ));
    
    // This week
    $week_views = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tln_analytics 
        WHERE business_id = %d AND event_type = 'view' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)",
        $business_id
    ));
    
    $week_clicks = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tln_analytics 
        WHERE business_id = %d AND event_type = 'click' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)",
        $business_id
    ));
    
    ob_start();
    ?>
    <div style="background:white;padding:1.5rem;border-radius:12px;box-shadow:0 2px8px rgba(0,0,0,0.1);">
        <h3 style="margin-top:0;">📊 Your Analytics</h3>
        
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:1.5rem;">
            <div style="background:#f8f8f8;padding:1rem;border-radius:8px;text-align:center;">
                <div style="font-size:2rem;font-weight:700;color:#e63946;"><?php echo number_format($total_views); ?></div>
                <div style="color:#666;font-size:0.9rem;">Total Views</div>
            </div>
            <div style="background:#f8f8f8;padding:1rem;border-radius:8px;text-align:center;">
                <div style="font-size:2rem;font-weight:700;color:#7cda24;"><?php echo number_format($total_clicks); ?></div>
                <div style="color:#666;font-size:0.9rem;">CTA Clicks</div>
            </div>
        </div>
        
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:1.5rem;">
            <div style="background:#e63946;color:white;padding:1rem;border-radius:8px;text-align:center;">
                <div style="font-size:1.5rem;font-weight:700;"><?php echo $week_views; ?></div>
                <div style="opacity:0.9;font-size:0.85rem;">Views This Week</div>
            </div>
            <div style="background:#7cda24;color:white;padding:1rem;border-radius:8px;text-align:center;">
                <div style="font-size:1.5rem;font-weight:700;"><?php echo $week_clicks; ?></div>
                <div style="opacity:0.9;font-size:0.85rem;">Clicks This Week</div>
            </div>
        </div>
        
        <h4 style="margin-bottom:0.5rem;">Views by Source</h4>
        <?php if (empty($by_source)): ?>
        <p style="color:#666;font-size:0.9rem;">No data yet. Share your link to start tracking!</p>
        <?php else: ?>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <?php foreach ($by_source as $s): ?>
            <span style="background:#f0f0f0;padding:0.4rem 0.8rem;border-radius:20px;font-size:0.85rem;">
                <?php echo esc_html(ucfirst($s->source)); ?> (<?php echo $s->count; ?>)
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <p style="margin-top:1rem;font-size:0.8rem;color:#999;">
            <em>To track properly, use these UTMs: ?utm_source=directory&utm_medium=web</em>
        </p>
    </div>
    <?php
    return ob_get_clean();
}

// Add tracking to directory links
add_action('wp_footer', 'tln_tracking_footer');

function tln_tracking_footer() {
    if (!is_page('directory')) return;
    
    // Simple JS tracking - could be enhanced
}

// Create tracking URL helper
function tln_tracking_url($business_id, $business_name, $source = 'directory', $medium = 'web', $campaign = '') {
    $base_url = get_permalink($business_id);
    $params = array(
        'utm_source' => $source,
        'utm_medium' => $medium,
    );
    if ($campaign) {
        $params['utm_campaign'] = $campaign;
    }
    
    return add_query_arg($params, $base_url);
}
