<?php
/**
 * TLN Admin Dashboard
 * Comprehensive dashboard for The Local NearBuy
 */

if (!defined('ABSPATH')) exit;

// Register settings for the Directory Settings page so it shows a usable form
add_action('admin_init', function() {
    // Register option placeholders
    register_setting('tln-directory-group', 'tln_directory_google_api_key');
    register_setting('tln-directory-group', 'tln_directory_cache_ttl');
    // Add a settings section
    add_settings_section('tln_directory_main', 'Directory Settings', null, 'tln-directory');
    // Google API Key field
    add_settings_field('tln_directory_google_api_key', 'Google Places API Key', function() {
        $val = get_option('tln_directory_google_api_key', '');
        echo '<input type="text" name="tln_directory_google_api_key" value="' . esc_attr($val) . '" class="regular-text" />';
    }, 'tln-directory', 'tln_directory_main');
    // Cache TTL field (minutes)
    add_settings_field('tln_directory_cache_ttl', 'Cache TTL (minutes)', function() {
        $val = get_option('tln_directory_cache_ttl', '60');
        echo '<input type="number" name="tln_directory_cache_ttl" value="' . esc_attr($val) . '" class="small-text" min="1" />';
    }, 'tln-directory', 'tln_directory_main');
});

/**
 * Register the admin dashboard pages
 */
function tln_register_dashboard_page() {
    // Main Dashboard
    add_submenu_page(
        'tln-dashboard',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'tln-dashboard',
        'tln_render_dashboard'
    );
    
    // Vouchers
    add_submenu_page(
        'tln-dashboard',
        'Vouchers',
        'View All Vouchers',
        'manage_options',
        'tln-vouchers',
        'tln_render_vouchers'
    );
    
    // Directory Settings
    add_submenu_page(
        'tln-dashboard',
        'Directory',
        'Directory Settings',
        'manage_options',
        'tln-directory',
        'tln_render_directory_settings'
    );
    
    // Analytics
    add_submenu_page(
        'tln-dashboard',
        'Analytics',
        'Analytics',
        'manage_options',
        'tln-analytics',
        'tln_render_analytics'
    );
    
    // Claims Management
    add_submenu_page(
        'tln-dashboard',
        'Claims',
        'Manage Claims',
        'manage_options',
        'tln-claims',
        'tln_render_claims'
    );
    
    // Campaign Requests
    add_submenu_page(
        'tln-dashboard',
        'Campaign Requests',
        'Campaign Requests',
        'manage_options',
        'tln-campaign-requests',
        'tln_render_campaign_requests'
    );
    
    // Gift Claims (Pens)
    add_submenu_page(
        'tln-dashboard',
        'Gift Claims',
        'Gift Claims',
        'manage_options',
        'tln-gift-claims',
        'tln_render_gift_claims'
    );
    
    // Directory Management
    add_submenu_page(
        'tln-dashboard',
        'Directory',
        'Directory Mgmt',
        'manage_options',
        'tln-directory-mgmt',
        'tln_render_directory_mgmt'
    );
}
add_action('admin_menu', 'tln_register_dashboard_page');

/**
 * Render the main dashboard
 */
function tln_render_dashboard() {
    global $wpdb;
    
    // Ensure tables exist
    tln_ensure_tables();
    
    // Get stats
    $stats = tln_get_stats();
    $campaigns = tln_get_recent_campaigns(10);
    $recent_vouchers = tln_get_recent_vouchers(10);
    $recent_scans = tln_get_recent_scans(10);
    
    ?>
    <div class="wrap tln-dashboard">
        <h1>TLN Dashboard — The Local NearBuy</h1>
        
        <!-- Stats Cards -->
        <div class="tln-stats-row">
            <div class="tln-stat-card">
                <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/postcard.png" class="tln-stat-icon" alt="Campaigns" />
                <div class="tln-stat-content">
                    <span class="tln-stat-number"><?php echo intval($stats['campaigns']); ?></span>
                    <span class="tln-stat-label">Campaigns</span>
                </div>
            </div>
            <div class="tln-stat-card">
                <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/voucher.png" class="tln-stat-icon" alt="Vouchers" />
                <div class="tln-stat-content">
                    <span class="tln-stat-number"><?php echo intval($stats['vouchers']); ?></span>
                    <span class="tln-stat-label">Vouchers Issued</span>
                </div>
            </div>
            <div class="tln-stat-card">
                <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/discount.png" class="tln-stat-icon" alt="Redeemed" />
                <div class="tln-stat-content">
                    <span class="tln-stat-number"><?php echo intval($stats['redeemed']); ?></span>
                    <span class="tln-stat-label">Redeemed</span>
                </div>
            </div>
            <div class="tln-stat-card">
                <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/qr-code.png" class="tln-stat-icon" alt="Scans" />
                <div class="tln-stat-content">
                    <span class="tln-stat-number"><?php echo intval($stats['scans']); ?></span>
                    <span class="tln-stat-label">QR Scans</span>
                </div>
            </div>
            <div class="tln-stat-card">
                <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/user-engagement.png" class="tln-stat-icon" alt="Leads" />
                <div class="tln-stat-content">
                    <span class="tln-stat-number"><?php echo intval($stats['leads']); ?></span>
                    <span class="tln-stat-label">Leads Captured</span>
                </div>
            </div>
            <div class="tln-stat-card">
                <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/directory-listing.png" class="tln-stat-icon" alt="Listings" />
                <div class="tln-stat-content">
                    <span class="tln-stat-number"><?php echo intval($stats['businesses']); ?></span>
                    <span class="tln-stat-label">Directory Listings</span>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="tln-quick-actions">
            <a href="?page=tln-add-campaign" class="button button-primary">+ New Campaign</a>
            <a href="?page=tln-vouchers" class="button">View All Vouchers</a>
            <a href="?page=tln-directory" class="button">Directory Settings</a>
            <a href="?page=tln-analytics" class="button">Analytics</a>
        </div>
        
        <?php
        // Get current active campaign status
        $campaigns_table = $wpdb->prefix . 'tln_campaigns';
        $current_campaign = $wpdb->get_row("SELECT * FROM $campaigns_table WHERE workflow_status IN ('sell','filling','full') ORDER BY created_at DESC LIMIT 1");
        if ($current_campaign) {
            $total = intval($current_campaign->total_spots);
            $filled = intval($current_campaign->filled_spots);
            $remaining = $total - $filled;
            $percent = $total > 0 ? round(($filled / $total) * 100) : 0;
            $cost = floatval($current_campaign->campaign_cost);
            ?>
        <!-- Campaign Spots Status -->
        <div class="tln-campaign-status" style="background:white;border-radius:8px;padding:1.5rem;margin:1.5rem 0;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <h2 style="margin-top:0;margin-bottom:1rem;">Current Campaign — <?php echo esc_html($current_campaign->title); ?></h2>
            <div style="display:flex;gap:2rem;flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;">
                    <div style="background:#f0f0f0;border-radius:8px;height:24px;overflow:hidden;">
                        <div style="background:#e63946;height:100%;width:<?php echo $percent; ?>%;border-radius:8px;"></div>
                    </div>
                    <p style="margin-top:0.5rem;font-size:0.9rem;color:#666;">
                        <strong><?php echo $filled; ?></strong> of <strong><?php echo $total; ?></strong> spots filled (<strong><?php echo $remaining; ?></strong> remaining)
                    </p>
                </div>
                <div style="text-align:center;padding:0.5rem 1rem;background:#f8f8f8;border-radius:8px;">
                    <div style="font-size:1.5rem;font-weight:700;color:#e63946;">$<?php echo number_format($cost); ?></div>
                    <div style="font-size:0.8rem;color:#666;">Your Cost</div>
                </div>
                <div style="text-align:center;padding:0.5rem 1rem;background:#f8f8f8;border-radius:8px;">
                    <div style="font-size:1.5rem;font-weight:700;color:#28a745;">$<?php echo number_format($filled * floatval($current_campaign->price_per_spot)); ?></div>
                    <div style="font-size:0.8rem;color:#666;">Revenue</div>
                </div>
                <?php if ($remaining <= 3 && $remaining > 0) : ?>
                <div style="padding:0.5rem 1rem;background:#fff3cd;border-radius:8px;border:1px solid #ffc107;">
                    <strong style="color:#856404;">Only <?php echo $remaining; ?> spots left!</strong><br>
                    <span style="font-size:0.85rem;color:#856404;">Consider running when full</span>
                </div>
                <?php elseif ($remaining == 0) : ?>
                <div style="padding:0.5rem 1rem;background:#d4edda;border-radius:8px;border:1px solid #28a745;">
                    <strong style="color:#155724;">Campaign Full!</strong><br>
                    <span style="font-size:0.85rem;color:#155724;">Ready to print and mail</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php } ?>
        
        <!-- Main Content Grid -->
        <div class="tln-dashboard-grid">
            <!-- Recent Campaigns -->
            <div class="tln-panel">
                <h2>Recent Campaigns</h2>
                <?php if (empty($campaigns)) : ?>
                    <p>No campaigns yet. <a href="?page=tln-add-campaign">Create your first campaign</a></p>
                <?php else : ?>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Business</th>
                                <th>Created</th>
                                <th>QR Code</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $c) : 
                                $qr_url = home_url('/r/' . $c->id);
                                $qr_api = 'https://quickchart.io/qr?size=150x150&text=' . urlencode($qr_url);
                                $voucher_count = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}tln_vouchers WHERE campaign_id = %d",
                                    $c->id
                                ));
                            ?>
                            <tr>
                                <td><?php echo esc_html($c->id); ?></td>
                                <td>
                                    <strong><?php echo esc_html($c->title); ?></strong>
                                    <br><small><?php echo esc_html($voucher_count); ?> vouchers</small>
                                </td>
                                <td><?php echo esc_html($c->business_id); ?></td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($c->created_at))); ?></td>
                                <td style="text-align:center;">
                                    <img src="<?php echo esc_url($qr_api); ?>" alt="QR Code" style="width:80px;height:80px;" />
                                    <br><small><a href="<?php echo esc_url($qr_url); ?>" target="_blank">View URL</a></small>
                                </td>
                                <td>
                                    <a href="?page=tln-campaign&id=<?php echo intval($c->id); ?>" class="button button-small">Edit</a>
                                    <a href="<?php echo wp_nonce_url('?page=tln-dashboard&delete_campaign=' . intval($c->id), 'tln_delete_' . $c->id); ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('Delete this campaign and all its vouchers?');">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Recent Vouchers -->
            <div class="tln-panel">
                <h2>Recent Voucher Claims</h2>
                <?php if (empty($recent_vouchers)) : ?>
                    <p>No vouchers claimed yet.</p>
                <?php else : ?>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th>Claimed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_vouchers as $v) : ?>
                            <tr>
                                <td><?php echo esc_html($v->lead_name); ?></td>
                                <td><?php echo esc_html($v->lead_email); ?></td>
                                <td><code><?php echo esc_html(substr($v->code, 0, 8)); ?></code></td>
                                <td>
                                    <?php if ($v->redeemed) : ?>
                                        <span style="color:green;">✓ Redeemed</span>
                                    <?php else : ?>
                                        <span style="color:orange;">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(date('M j, g:ia', strtotime($v->expires) - (30 * 3600))); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- QR Scan Activity -->
            <div class="tln-panel">
                <h2>Recent QR Scans</h2>
                <?php if (empty($recent_scans)) : ?>
                    <p>No scans recorded yet.</p>
                <?php else : ?>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Time</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_scans as $s) : 
                                $campaign = $wpdb->get_row($wpdb->prepare(
                                    "SELECT title FROM {$wpdb->prefix}tln_campaigns WHERE id = %d",
                                    $s->campaign_id
                                ));
                            ?>
                            <tr>
                                <td><?php echo $campaign ? esc_html($campaign->title) : 'Unknown'; ?></td>
                                <td><?php echo esc_html(date('M j, g:ia', strtotime($s->scanned_at))); ?></td>
                                <td><?php echo esc_html($s->source); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Directory Preview -->
        <div class="tln-panel" style="margin-top: 20px;">
            <h2>Directory Overview</h2>
            <?php 
            $cached = get_transient('tln_businesses');
            if ($cached && is_array($cached)) {
                $by_location = [];
                foreach ($cached as $b) {
                    $loc = isset($b['location']) ? $b['location'] : 'Unknown';
                    if (!isset($by_location[$loc])) $by_location[$loc] = 0;
                    $by_location[$loc]++;
                }
                echo '<p><strong>Locations covered:</strong> ' . count($by_location) . '</p>';
                echo '<p><strong>Total listings cached:</strong> ' . count($cached) . '</p>';
                echo '<p style="color:#666;"><em>Data refreshed from Google Places API. Last sync: ' . get_transient('tln_businesses_last_sync') . '</em></p>';
            } else {
                echo '<p>No directory data cached yet. <a href="?page=tln-directory&refresh=1">Refresh from Google</a></p>';
            }
            ?>
        </div>
    </div>
    
    <style>
    .tln-dashboard { padding: 20px; }
    .tln-stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    .tln-stat-card {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .tln-stat-icon {
        width: 48px;
        height: 48px;
        object-fit: contain;
    }
    .tln-stat-content {
        display: flex;
        flex-direction: column;
    }
    .tln-stat-number {
        font-size: 28px;
        font-weight: 700;
        color: #1a1a1a;
    }
    .tln-stat-label {
        font-size: 13px;
        color: #666;
    }
    .tln-quick-actions {
        margin-bottom: 25px;
        padding: 15px;
        background: #f0f6ff;
        border-radius: 8px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .tln-dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
    }
    .tln-panel {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
    }
    .tln-panel h2 {
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        font-size: 18px;
    }
    </style>
    <?php
}

/**
 * Render the vouchers page
 */
function tln_render_vouchers() {
    global $wpdb;
    $table = $wpdb->prefix . 'tln_vouchers';
    
    // Get all vouchers
    $vouchers = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 100");
    
    echo '<div class="wrap">';
    echo '<h1>All Vouchers</h1>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Code</th><th>Business</th><th>Status</th><th>Created</th><th>Redeemed</th></tr></thead>';
    echo '<tbody>';
    
    if ($vouchers) {
        foreach ($vouchers as $v) {
            $status = $v->redeemed ? '<span style="color:green;">Redeemed</span>' : '<span style="color:orange;">Pending</span>';
            echo '<tr>';
            echo '<td>' . esc_html($v->id) . '</td>';
            echo '<td>' . esc_html($v->code) . '</td>';
            echo '<td>' . esc_html($v->business_name) . '</td>';
            echo '<td>' . $status . '</td>';
            echo '<td>' . esc_html($v->created_at) . '</td>';
            echo '<td>' . ($v->redeemed_at ? esc_html($v->redeemed_at) : '-') . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6">No vouchers found.</td></tr>';
    }
    
    echo '</tbody></table></div>';
}

/**
 * Render directory settings page
 */
function tln_render_directory_settings() {
    echo '<div class="wrap">';
    echo '<h1>Directory Settings</h1>';
    echo '<p>Configure your business directory settings below.</p>';
    echo '<form method="post" action="options.php">';
    settings_fields('tln-directory-group');
    do_settings_sections('tln-directory');
    submit_button('Save Settings');
    echo '</form></div>';
}

/**
 * Render analytics page
 */
function tln_render_analytics() {
    global $wpdb;
    $table = $wpdb->prefix . 'tln_analytics';
    
    echo '<div class="wrap">';
    echo '<h1>TLN Analytics</h1>';
    
    // Ensure analytics table exists – create on the fly if missing
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        if (function_exists('tln_analytics_install')) {
            tln_analytics_install();
        }
        // Re‑check after attempting install
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            echo '<p>Analytics table not found and could not be created. Please ensure analytics is enabled.</p>';
            echo '</div>';
            return;
        }
    }
    
    $total_views = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE event_type = 'page_view'");
    $total_clicks = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE event_type = 'cta_click'");
    
    echo '<div style="display:flex;gap:2rem;margin-bottom:2rem;">';
    echo '<div style="background:#fff;padding:1.5rem;border-radius:8px;border:1px solid #ddd;flex:1;">';
    echo '<h3 style="margin:0;color:#666;">Total Page Views</h3>';
    echo '<div style="font-size:2.5rem;font-weight:700;color:#e63946;">' . number_format($total_views) . '</div>';
    echo '</div>';
    echo '<div style="background:#fff;padding:1.5rem;border-radius:8px;border:1px solid #ddd;flex:1;">';
    echo '<h3 style="margin:0;color:#666;">Total CTA Clicks</h3>';
    echo '<div style="font-size:2.5rem;font-weight:700;color:#e63946;">' . number_format($total_clicks) . '</div>';
    echo '</div>';
    echo '</div>';
    
    // Recent events
    $recent = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 20");
    
    if ($recent) {
        echo '<h2>Recent Events</h2>';
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Time</th><th>Event</th><th>Business</th><th>Source</th></tr></thead>';
        echo '<tbody>';
        foreach ($recent as $r) {
            echo '<tr>';
            echo '<td>' . esc_html($r->created_at) . '</td>';
            echo '<td>' . esc_html($r->event_type) . '</td>';
            echo '<td>' . esc_html($r->business_id) . '</td>';
            echo '<td>' . esc_html($r->source ?: '-') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    
    echo '</div>';
}

/**
 * Ensure all required tables exist
 */
function tln_ensure_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'tln_stripe_events';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id VARCHAR(255) NOT NULL,
        type VARCHAR(255) NOT NULL,
        amount BIGINT DEFAULT 0,
        currency VARCHAR(10) DEFAULT '' ,
        customer_id VARCHAR(255) DEFAULT '' ,
        subscription_id VARCHAR(255) DEFAULT '' ,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY event_id (event_id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Campaigns table
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_campaigns (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        business_id BIGINT(20) UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        offer_text VARCHAR(255),
        offer_valid_days INT DEFAULT 30,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate");
    
    // Vouchers table
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_vouchers (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        campaign_id BIGINT(20) UNSIGNED NOT NULL,
        business_id BIGINT(20) UNSIGNED NOT NULL,
        lead_name VARCHAR(255) NOT NULL,
        lead_email VARCHAR(255) NOT NULL,
        lead_phone VARCHAR(50),
        code VARCHAR(32) NOT NULL,
        expires DATETIME NOT NULL,
        redeemed TINYINT(1) DEFAULT 0,
        redeemed_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY code (code)
    ) $charset_collate");
    
    // QR Scans table
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_qr_scans (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        campaign_id BIGINT(20) UNSIGNED NOT NULL,
        scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        source VARCHAR(50) DEFAULT 'postcard',
        PRIMARY KEY (id)
    ) $charset_collate");
}

/**
 * Get dashboard statistics
 */
function tln_get_stats() {
    global $wpdb;
    
    $stats = [
        'campaigns' => 0,
        'vouchers' => 0,
        'redeemed' => 0,
        'scans' => 0,
        'leads' => 0,
        'businesses' => 0
    ];
    
    // Campaign count
    $stats['campaigns'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tln_campaigns");
    
    // Voucher stats
    $stats['vouchers'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tln_vouchers");
    $stats['redeemed'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tln_vouchers WHERE redeemed = 1");
    $stats['leads'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tln_vouchers");
    
    // Scan count
    $stats['scans'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tln_qr_scans");
    
    // Business listings (from cached data)
    $cached = get_transient('tln_businesses');
    if ($cached && is_array($cached)) {
        $stats['businesses'] = count($cached);
    }
    
    return $stats;
}

/**
 * Get recent campaigns
 */
function tln_get_recent_campaigns($limit = 10) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tln_campaigns ORDER BY created_at DESC LIMIT %d",
        $limit
    ));
}

/**
 * Get recent vouchers
 */
function tln_get_recent_vouchers($limit = 10) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tln_vouchers ORDER BY id DESC LIMIT %d",
        $limit
    ));
}

/**
 * Get recent QR scans
 */
function tln_get_recent_scans($limit = 10) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tln_qr_scans ORDER BY scanned_at DESC LIMIT %d",
        $limit
    ));
}

/**
 * Handle delete campaign action
 */
function tln_handle_dashboard_actions() {
    global $wpdb;
    
    if (isset($_GET['delete_campaign']) && current_user_can('manage_options')) {
        $campaign_id = intval($_GET['delete_campaign']);
        check_admin_referer('tln_delete_' . $campaign_id);
        
        // Delete related vouchers and scans
        $wpdb->delete($wpdb->prefix . 'tln_vouchers', ['campaign_id' => $campaign_id]);
        $wpdb->delete($wpdb->prefix . 'tln_qr_scans', ['campaign_id' => $campaign_id]);
        $wpdb->delete($wpdb->prefix . 'tln_campaigns', ['id' => $campaign_id]);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Campaign deleted successfully.</p></div>';
        });
    }
}
add_action('admin_init', 'tln_handle_dashboard_actions');

/**
 * Render claims management page
 */
function tln_render_claims() {
    global $wpdb;
    
    // Handle claim actions
    if (isset($_GET['action']) && isset($_GET['claim_id'])) {
        $claim_id = intval($_GET['claim_id']);
        $action = sanitize_text_field($_GET['action']);
        
        if ($action === 'approve') {
            $wpdb->update(
                $wpdb->prefix . 'tln_claims',
                ['status' => 'approved', 'approved_at' => current_time('mysql'), 'approved_by' => get_current_user_id()],
                ['id' => $claim_id]
            );
            
            // Get claim info for email
            $claim = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_claims WHERE id = %d", $claim_id));
            if ($claim) {
                $user = get_userdata($claim->user_id);
                if ($user) {
                    $login_url = wp_login_url('/dashboard/');
                    $message = "Hi {$claim->claimant_name},\n\n";
                    $message .= "Great news! Your claim for {$claim->business_name} has been approved.\n\n";
                    $message .= "Login to your dashboard:\n$login_url\n\n";
                    $message .= "Email: {$claim->claimant_email}\n";
                    $message .= "Password: (use the password you set when claiming)\n\n";
                    $message .= "The Local NearBuy Team";
                    wp_mail($claim->claimant_email, "Claim Approved - {$claim->business_name}", $message);
                }
            }
            echo '<div class="notice notice-success"><p>Claim approved. Business owner has been notified.</p></div>';
        } elseif ($action === 'reject') {
            $claim = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_claims WHERE id = %d", $claim_id));
            if ($claim) {
                $message = "Hi {$claim->claimant_name},\n\n";
                $message .= "Unfortunately, your claim for {$claim->business_name} could not be approved at this time.\n\n";
                $message .= "Please contact us for more information: bryan@thelocalnearbuy.com\n\n";
                $message .= "The Local NearBuy Team";
                wp_mail($claim->claimant_email, "Claim Update - {$claim->business_name}", $message);
            }
            $wpdb->delete($wpdb->prefix . 'tln_claims', ['id' => $claim_id]);
            echo '<div class="notice notice-info"><p>Claim rejected and removed.</p></div>';
        } elseif ($action === 'unverify') {
            $claim = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_claims WHERE id = %d", $claim_id));
            if ($claim) {
                $message = "Hi {$claim->claimant_name},\n\n";
                $message .= "Your verification for {$claim->business_name} has been removed from The Local NearBuy.\n\n";
                $message .= "If you'd like to re-verify, please contact us: bryan@thelocalnearbuy.com\n\n";
                $message .= "The Local NearBuy Team";
                wp_mail($claim->claimant_email, "Verification Removed - {$claim->business_name}", $message);
            }
            $wpdb->delete($wpdb->prefix . 'tln_claims', ['id' => $claim_id]);
            echo '<div class="notice notice-warning"><p>Business verification removed. Claim record deleted.</p></div>';
        }
    }
    
    // Get all claims
    $claims = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tln_claims ORDER BY created_at DESC");
    $pending = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tln_claims WHERE status = 'pending' ORDER BY created_at DESC");
    $approved = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tln_claims WHERE status = 'approved' ORDER BY approved_at DESC");
    
    ?>
    <div class="wrap tln-claims">
        <h1>Manage Business Claims</h1>
        
        <?php if (count($pending) > 0): ?>
        <div style="background:#fff3cd;border:2px solid #ffc107;border-radius:8px;padding:1.5rem;margin-bottom:2rem;">
            <h2 style="margin-top:0;color:#856404;"><?php echo count($pending); ?> Pending Claim<?php echo count($pending) > 1 ? 's' : ''; ?></h2>
            <table class="widefat" style="background:white;">
                <thead>
                    <tr>
                        <th>Business</th>
                        <th>Claimant</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $claim): ?>
                    <tr>
                        <td><strong><?php echo esc_html($claim->business_name); ?></strong></td>
                        <td><?php echo esc_html($claim->claimant_name); ?></td>
                        <td><a href="mailto:<?php echo esc_attr($claim->claimant_email); ?>"><?php echo esc_html($claim->claimant_email); ?></a></td>
                        <td><?php echo esc_html($claim->claimant_phone); ?></td>
                        <td><?php echo esc_html($claim->created_at); ?></td>
                        <td>
                            <a href="?page=tln-claims&action=approve&claim_id=<?php echo $claim->id; ?>" class="button button-primary" style="background:#28a745;margin-right:0.5rem;" onclick="return confirm('Approve this claim?')">Approve</a>
                            <a href="?page=tln-claims&action=reject&claim_id=<?php echo $claim->id; ?>" class="button" style="background:#dc3545;color:white;" onclick="return confirm('Reject and remove this claim?')">Reject</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="background:#d4edda;border-radius:8px;padding:1.5rem;margin-bottom:2rem;">
            <p style="margin:0;"><strong>No pending claims</strong> - all caught up!</p>
        </div>
        <?php endif; ?>
        
        <h2 style="margin-bottom:1rem;">Verified Businesses (<?php echo count($approved); ?>)</h2>
        <?php if (count($approved) > 0): ?>
        <table class="widefat" style="background:white;">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Claimant</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Verified</th>
                    <th>Tier</th>
                    <th>Admin Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($approved as $claim): ?>
                <tr>
                    <td><strong><?php echo esc_html($claim->business_name); ?></strong></td>
                    <td><?php echo esc_html($claim->claimant_name); ?></td>
                    <td><a href="mailto:<?php echo esc_attr($claim->claimant_email); ?>"><?php echo esc_html($claim->claimant_email); ?></a></td>
                    <td><?php echo esc_html($claim->claimant_phone); ?></td>
                    <td><?php echo esc_html($claim->approved_at); ?></td>
                    <td><?php echo esc_html($claim->tier); ?></td>
                    <td>
                        <a href="?page=tln-claims&action=unverify&claim_id=<?php echo $claim->id; ?>" class="button" style="background:#6c7575;color:white;" onclick="return confirm('Unverify this business? They will lose access to their dashboard.');">Unverify</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:#666;">No approved claims yet.</p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render Campaign Requests page
 */
function tln_render_campaign_requests() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'tln_campaign_requests';
    
    // Handle status update
    if (isset($_POST['tln_update_status']) && isset($_POST['request_id'])) {
        $request_id = intval($_POST['request_id']);
        $new_status = sanitize_text_field($_POST['status']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        $wpdb->update(
            $table_name,
            array('status' => $new_status, 'notes' => $notes, 'updated_at' => current_time('mysql')),
            array('id' => $request_id)
        );
        echo '<div class="notice notice-success"><p>Request updated.</p></div>';
    }
    
    // Get all requests
    $requests = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    $new_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'new'");
    $contacted_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'contacted'");
    $scheduled_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'scheduled'");
    $completed_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'completed'");
    
    ?>
    <div class="wrap tln-campaign-requests">
        <h1>Campaign Requests</h1>
        
        <!-- Status Summary -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem;">
            <div style="background:#fff3cd;border-radius:8px;padding:1rem;text-align:center;">
                <div style="font-size:2rem;font-weight:bold;color:#856404;"><?php echo intval($new_count); ?></div>
                <div style="color:#856404;">New</div>
            </div>
            <div style="background:#cce5ff;border-radius:8px;padding:1rem;text-align:center;">
                <div style="font-size:2rem;font-weight:bold;color:#004085;"><?php echo intval($contacted_count); ?></div>
                <div style="color:#004085;">Contacted</div>
            </div>
            <div style="background:#d4edda;border-radius:8px;padding:1rem;text-align:center;">
                <div style="font-size:2rem;font-weight:bold;color:#155724;"><?php echo intval($scheduled_count); ?></div>
                <div style="color:#155724;">Scheduled</div>
            </div>
            <div style="background:#e2e3e5;border-radius:8px;padding:1rem;text-align:center;">
                <div style="font-size:2rem;font-weight:bold;color:#383d41;"><?php echo intval($completed_count); ?></div>
                <div style="color:#383d41;">Completed</div>
            </div>
        </div>
        
        <?php if (count($requests) > 0): ?>
        <table class="widefat" style="background:white;">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Contact</th>
                    <th>Email / Phone</th>
                    <th>Campaign Type</th>
                    <th>SMS</th>
                    <th>Message</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                <tr>
                    <td><strong><?php echo esc_html($req->business_name); ?></strong></td>
                    <td><?php echo esc_html($req->contact_name); ?></td>
                    <td>
                        <a href="mailto:<?php echo esc_attr($req->email); ?>"><?php echo esc_html($req->email); ?></a>
                        <?php if ($req->phone): ?><br><?php echo esc_html($req->phone); ?><?php endif; ?>
                    </td>
                    <td><?php echo esc_html($req->campaign_type); ?></td>
                    <td><?php echo $req->sms_optin === 'yes' ? '<span style="background:#d4edda;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.75rem;">Yes</span>' : '<span style="color:#999;">No</span>'; ?></td>
                    <td><?php echo esc_html($req->message); ?></td>
                    <td><?php echo esc_html($req->created_at); ?></td>
                    <td>
                        <?php
                        $status_colors = array(
                            'new' => '#fff3cd',
                            'contacted' => '#cce5ff',
                            'scheduled' => '#d4edda',
                            'completed' => '#e2e3e5'
                        );
                        $bg = isset($status_colors[$req->status]) ? $status_colors[$req->status] : '#f8f9fa';
                        ?>
                        <span style="background:<?php echo $bg; ?>;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.85rem;">
                            <?php echo esc_html(ucfirst($req->status)); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($req->notes); ?></td>
                    <td>
                        <button class="button" onclick="jQuery('#edit-<?php echo $req->id; ?>').toggle()">Edit</button>
                        <div id="edit-<?php echo $req->id; ?>" style="display:none;position:absolute;background:white;border:1px solid #ccc;padding:1rem;border-radius:8px;z-index:100;width:300px;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                            <form method="post">
                                <input type="hidden" name="request_id" value="<?php echo $req->id; ?>">
                                <div style="margin-bottom:0.75rem;">
                                    <label style="display:block;font-weight:600;margin-bottom:0.25rem;">Status</label>
                                    <select name="status" style="width:100%;">
                                        <option value="new" <?php selected($req->status, 'new'); ?>>New</option>
                                        <option value="contacted" <?php selected($req->status, 'contacted'); ?>>Contacted</option>
                                        <option value="scheduled" <?php selected($req->status, 'scheduled'); ?>>Scheduled</option>
                                        <option value="completed" <?php selected($req->status, 'completed'); ?>>Completed</option>
                                    </select>
                                </div>
                                <div style="margin-bottom:0.75rem;">
                                    <label style="display:block;font-weight:600;margin-bottom:0.25rem;">Notes</label>
                                    <textarea name="notes" rows="3" style="width:100%;"><?php echo esc_textarea($req->notes); ?></textarea>
                                </div>
                                <button type="submit" name="tln_update_status" class="button button-primary">Save</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="background:#d4edda;border-radius:8px;padding:2rem;text-align:center;">
            <p style="margin:0;font-size:1.1rem;"><strong>No campaign requests yet.</strong></p>
            <p style="margin:0.5rem 0 0;color:#666;">Share your campaign request form to start getting submissions!</p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
/**
 * Render Gift Claims page (Pens)
 */
function tln_render_gift_claims() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'tln_gift_claims';
    
    // Handle status update (mark as delivered)
    if (isset($_POST['tln_mark_delivered']) && isset($_POST['claim_id'])) {
        $claim_id = intval($_POST['claim_id']);
        $wpdb->update(
            $table_name,
            array('delivered' => 1, 'delivered_at' => current_time('mysql')),
            array('id' => $claim_id)
        );
        echo '<div class="notice notice-success"><p>Marked as delivered.</p></div>';
    }
    
    // Get all gift claims
    $claims = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE delivered = 0");
    $delivered_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE delivered = 1");
    
    ?>

    <div style="padding:1rem;">
        <h1 style="margin-top:0;">Gift Claims (Pens)</h1>
        
        <div style="display:flex;gap:1rem;margin-bottom:1.5rem;">
            <div style="background:#fff3cd;padding:1rem 1.5rem;border-radius:8px;">
                <strong style="font-size:1.5rem;"><?php echo intval($pending_count); ?></strong> Pending Delivery
            </div>
            <div style="background:#d4edda;padding:1rem 1.5rem;border-radius:8px;">
                <strong style="font-size:1.5rem;"><?php echo intval($delivered_count); ?></strong> Delivered
            </div>
        </div>
        
        <?php if (!empty($claims)): ?>
        <table class="widefat fixed striped" style="width:100%;max-width:1200px;">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Contact</th>
                    <th>Phone</th>
                    <th>Time Slot</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($claims as $claim): ?>
                <tr>
                    <td><strong><?php echo esc_html($claim->business_name); ?></strong></td>
                    <td><?php echo esc_html($claim->contact_name); ?><br><small><?php echo esc_html($claim->contact_email); ?></small></td>
                    <td><?php echo esc_html($claim->contact_phone); ?></td>
                    <td><?php echo esc_html($claim->time_slot); ?></td>
                    <td>
                        <?php if ($claim->delivered): ?>
                        <span style="background:#d4edda;color:#155724;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.85rem;">Delivered</span>
                        <?php else: ?>
                        <span style="background:#fff3cd;color:#856404;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.85rem;">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($claim->created_at)); ?></td>
                    <td>
                        <?php if (!$claim->delivered): ?>
                        <form method="post">
                            <input type="hidden" name="claim_id" value="<?php echo intval($claim->id); ?>">
                            <button type="submit" name="tln_mark_delivered" class="button button-primary" onclick="return confirm('Mark as delivered?');">Delivered</button>
                        </form>
                        <?php else: ?>
                        <span style="color:#666;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="background:#f8f8f8;border-radius:8px;padding:2rem;text-align:center;">
            <p style="margin:0;font-size:1.1rem;"><strong>No gift claims yet.</strong></p>
            <p style="margin:0.5rem 0 0;color:#666;">Share your gift claim link with businesses!</p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render Directory Management page
 */
function tln_render_directory_mgmt() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'tln_directory_mgmt';
    $api_key = defined('TLN_GOOGLE_API_KEY') ? TLN_GOOGLE_API_KEY : get_option('tln_directory_google_api_key', '');
    
    // Handle actions
    if (isset($_POST['tln_add_business']) && !empty($_POST['business_query'])) {
        $query = sanitize_text_field($_POST['business_query']);
        $location = sanitize_text_field($_POST['business_location']);
        $full_query = $query . ' in ' . $location . ' NC';
        
        // Search Google Places
        $url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?query=' . urlencode($full_query) . '&key=' . $api_key;
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            echo '<div class="notice notice-error"><p>Error searching Google: ' . esc_html($response->get_error_message()) . '</p></div>';
        } else {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($body['results'])) {
                $result = $body['results'][0]; // Take first result
                
                // Determine location
                $addr = $result['formatted_address'] ?? '';
                $loc = 'Waxhaw';
                if (stripos($addr, 'Marvin') !== false) $loc = 'Marvin';
                elseif (stripos($addr, 'Wesley Chapel') !== false) $loc = 'Wesley Chapel';
                elseif (stripos($addr, 'Weddington') !== false) $loc = 'Weddington';
                elseif (stripos($addr, 'Indian Land') !== false) $loc = 'Indian Land';
                
                // Get photo reference
                $photo_ref = '';
                if (!empty($result['photos'][0]['photo_reference'])) {
                    $photo_ref = $result['photos'][0]['photo_reference'];
                }
                
                // Check if already exists
                $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE place_id = %s", $result['place_id']));
                
                if ($exists) {
                    echo '<div class="notice notice-warning"><p>This business is already in your directory.</p></div>';
                } else {
                    // Determine category
                    $category = 'Services';
                    $types = $result['types'] ?? [];
                    foreach ($types as $type) {
                        if (in_array($type, ['restaurant', 'cafe', 'bar'])) { $category = 'Restaurant'; break; }
                        if (in_array($type, ['store', 'shopping_mall'])) { $category = 'Retail'; break; }
                        if (in_array($type, ['gym', 'health_club'])) { $category = 'Fitness'; break; }
                        if (in_array($type, ['salon', 'spa'])) { $category = 'Salon'; break; }
                        if (in_array($type, ['car_repair', ['car_wash']])) { $category = 'Auto'; break; }
                    }
                    
                    $wpdb->insert($table_name, [
                        'place_id' => $result['place_id'],
                        'name' => $result['name'],
                        'address' => $addr,
                        'phone' => $result['formatted_phone_number'] ?? '',
                        'category' => $category,
                        'location' => $loc,
                        'rating' => $result['rating'] ?? 0,
                        'photo_ref' => $photo_ref,
                        'source' => 'manual',
                        'is_hidden' => 0,
                        'created_at' => current_time('mysql')
                    ]);
                    
                    echo '<div class="notice notice-success"><p><strong>' . esc_html($result['name']) . '</strong> added to your directory!</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>No results found. Try a different search.</p></div>';
            }
        }
    }
    
    // Handle hide/show/delete
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $action = sanitize_text_field($_GET['action']);
        
        if ($action === 'hide') {
            $wpdb->update($table_name, ['is_hidden' => 1], ['id' => $id]);
            echo '<div class="notice notice-success"><p>Business hidden from directory.</p></div>';
        } elseif ($action === 'show') {
            $wpdb->update($table_name, ['is_hidden' => 0], ['id' => $id]);
            echo '<div class="notice notice-success"><p>Business restored to directory.</p></div>';
        } elseif ($action === 'delete') {
            $wpdb->delete($table_name, ['id' => $id]);
            echo '<div class="notice notice-info"><p>Business removed from management.</p></div>';
        }
    }
    
    // Get all managed businesses
    $businesses = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    $hidden_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_hidden = 1");
    $active_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_hidden = 0");
    ?>

    <div style="padding:1rem;">
        <h1 style="margin-top:0;">Directory Management</h1>
        
        <!-- Add Business Form -->
        <div style="background:#fff;padding:1.5rem;border-radius:8px;margin-bottom:2rem;border:2px solid #e63946;">
            <h2 style="margin-top:0;">Add Business from Google</h2>
            <p style="margin-bottom:1rem;color:#666;">Search for a business by name - we'll pull the info and image from Google automatically.</p>
            
            <form method="post" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
                <div style="flex:1;min-width:250px;">
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Business Name</label>
                    <input type="text" name="business_query" required placeholder="e.g. Joe's Pizza" style="width:100%;padding:0.5rem;font-size:1rem;">
                </div>
                <div style="flex:1;min-width:150px;">
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Location</label>
                    <select name="business_location" style="width:100%;padding:0.5rem;font-size:1rem;">
                        <option value="Waxhaw">Waxhaw</option>
                        <option value="Marvin">Marvin</option>
                        <option value="Wesley Chapel">Wesley Chapel</option>
                        <option value="Weddington">Weddington</option>
                        <option value="Indian Land">Indian Land</option>
                    </select>
                </div>
                <button type="submit" name="tln_add_business" class="button button-primary" style="background:#e63946;">Search & Add</button>
            </form>
        </div>
        
        <!-- Stats -->
        <div style="display:flex;gap:1rem;margin-bottom:1.5rem;">
            <div style="background:#d4edda;padding:1rem 1.5rem;border-radius:8px;">
                <strong style="font-size:1.5rem;"><?php echo intval($active_count); ?></strong> Active
            </div>
            <div style="background:#f8d7da;padding:1rem 1.5rem;border-radius:8px;">
                <strong style="font-size:1.5rem;"><?php echo intval($hidden_count); ?></strong> Hidden
            </div>
        </div>
        
        <!-- List -->
        <?php if (!empty($businesses)): ?>
        <table class="widefat fixed striped" style="width:100%;max-width:1200px;">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Location</th>
                    <th>Category</th>
                    <th>Rating</th>
                    <th>Photo</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($businesses as $biz): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($biz->name); ?></strong><br>
                        <small><?php echo esc_html($biz->address); ?></small>
                    </td>
                    <td><?php echo esc_html($biz->location); ?></td>
                    <td><?php echo esc_html($biz->category); ?></td>
                    <td><?php echo floatval($biz->rating); ?></td>
                    <td>
                        <?php if (!empty($biz->photo_ref)): ?>
                        <img src="https://maps.googleapis.com/maps/api/place/photo?maxwidth=100&photoreference=<?php echo esc_attr($biz->photo_ref); ?>&key=<?php echo esc_attr($api_key); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:4px;">
                        <?php else: ?>
                        <span style="color:#999;">No photo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($biz->is_hidden): ?>
                        <span style="background:#f8d7da;color:#721c24;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.85rem;">Hidden</span>
                        <?php else: ?>
                        <span style="background:#d4edda;color:#155724;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.85rem;">Active</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($biz->is_hidden): ?>
                        <a href="?page=tln-directory-mgmt&action=show&id=<?php echo intval($biz->id); ?>" class="button button-small" style="background:#28a745;color:#fff;">Show</a>
                        <?php else: ?>
                        <a href="?page=tln-directory-mgmt&action=hide&id=<?php echo intval($biz->id); ?>" class="button button-small" style="background:#ffc107;color:#000;">Hide</a>
                        <?php endif; ?>
                        <a href="?page=tln-directory-mgmt&action=delete&id=<?php echo intval($biz->id); ?>" class="button button-small" style="background:#dc3545;color:#fff;" onclick="return confirm('Remove this business entirely?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="background:#f8f8f8;border-radius:8px;padding:2rem;text-align:center;">
            <p style="margin:0;font-size:1.1rem;"><strong>No manually added businesses yet.</strong></p>
            <p style="margin:0.5rem 0 0;color:#666;">Use the form above to add businesses from Google.</p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
