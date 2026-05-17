<?php
/**
 * TLN Admin Dashboard
 * Comprehensive dashboard for The Local NearBuy
 */

if (!defined('ABSPATH')) exit;

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
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        echo '<p>Analytics table not found. Please ensure analytics is enabled.</p>';
        echo '</div>';
        return;
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