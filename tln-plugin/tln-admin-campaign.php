<?php
// tln-plugin/tln-admin-campaign.php
if ( ! defined( 'ABSPATH' ) ) exit;

// =============================================================================
// TLN Campaign Manager with Workflow Tabs & Zone Picker
// =============================================================================

/**
 * Enqueue admin styles for TLN campaigns.
 */
function tln_admin_campaign_styles() {
    $screen = get_current_screen();
    if ( ! in_array( $screen->base, array( 'tln_page_tln-campaigns', 'tln_page_tln-add-campaign', 'tln_page_tln-zones' ) ) ) {
        return;
    }
    ?>
    <style>
        /* Workflow Tab Styling */
        .tln-workflow-tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            background: #f6f7f7;
            padding: 12px;
            border-radius: 8px;
        }
        .tln-workflow-tabs .tab {
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        .tln-workflow-tabs .tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .tln-workflow-tabs .tab.active {
            background: #2271b1 !important;
            color: #fff !important;
            border-color: #2271b1;
        }
        .tln-workflow-tabs .tab.sell { background: #fce8e6; color: #c0392b; }
        .tln-workflow-tabs .tab.artwork { background: #f5eef8; color: #8e44ad; }
        .tln-workflow-tabs .tab.printing { background: #ebf5fb; color: #2980b9; }
        .tln-workflow-tabs .tab.mailed { background: #e8f8f5; color: #27ae60; }
        .tln-workflow-tabs .tab.scanning { background: #fef9e7; color: #d35400; }

        /* Progress Bar */
        .tln-progress-bar {
            background: #e9ecef;
            border-radius: 20px;
            height: 28px;
            margin-bottom: 24px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        .tln-progress-bar .fill {
            height: 100%;
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 20px;
        }
        .tln-progress-bar .label {
            position: absolute;
            top: 50%;
            left: 16px;
            transform: translateY(-50%);
            font-weight: 700;
            font-size: 13px;
            text-shadow: 0 1px 2px rgba(255,255,255,0.8);
            z-index: 1;
        }

        /* Card Styling */
        .tln-card {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            transition: all 0.2s ease;
        }
        .tln-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            border-color: #2271b1;
            transform: translateY(-2px);
        }
        .tln-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        .tln-card-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        /* Zone Status Badges */
        .tln-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .tln-badge.suggested { background: #fff3cd; color: #856404; }
        .tln-badge.approved { background: #d4edda; color: #155724; }
        .tln-badge.rejected { background: #f8d7da; color: #721c24; }

        /* Status Dropdown Styling */
        .tln-status-select {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #8c8f94;
            cursor: pointer;
        }
        .tln-status-select:focus {
            border-color: #2271b1;
            box-shadow: 0 0 0 2px rgba(34,113,177,0.2);
        }

        /* QR Code Display */
        .tln-qr-box {
            display: flex;
            align-items: center;
            gap: 24px;
            padding: 24px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            border: 1px solid #dee2e6;
            margin: 20px 0;
        }
        .tln-qr-box img {
            border: 3px solid #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
        }

        /* Responsive */
        @media screen and (max-width: 782px) {
            .tln-workflow-tabs {
                justify-content: center;
            }
            .tln-workflow-tabs .tab {
                padding: 8px 12px;
                font-size: 12px;
            }
            .tln-qr-box {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
    <?php
}
add_action( 'admin_head', 'tln_admin_campaign_styles' );

/**
 * Ensure all TLN tables exist.
 */
function tln_ensure_all_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Campaigns table (extended with workflow status)
    $campaigns_table = $wpdb->prefix . 'tln_campaigns';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$campaigns_table'" ) != $campaigns_table ) {
        $sql = "CREATE TABLE $campaigns_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            business_id bigint(20) NOT NULL,
            title text NOT NULL,
            description text NOT NULL,
            offer_text text,
            offer_valid_days int(11) DEFAULT 30,
            workflow_status varchar(50) DEFAULT 'sell',
            zone_id bigint(20) DEFAULT NULL,
            total_spots int(11) DEFAULT 9,
            filled_spots int(11) DEFAULT 0,
            price_per_spot decimal(10,2) DEFAULT 450,
            campaign_cost decimal(10,2) DEFAULT 0,
            meals_local int(11) DEFAULT 0,
            meals_global int(11) DEFAULT 0,
            meals4good_fund decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    // Zones table for USPS EDDM
    $zones_table = $wpdb->prefix . 'tln_zones';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$zones_table'" ) != $zones_table ) {
        $sql = "CREATE TABLE $zones_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            zone_name varchar(255) NOT NULL,
            zip_codes text NOT NULL,
            households int(11) DEFAULT 0,
            cost_per_mailer decimal(10,2) DEFAULT 0.50,
            status varchar(20) DEFAULT 'suggested',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    // Routes table for USPS EDDM
    $routes_table = $wpdb->prefix . 'tln_routes';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$routes_table'" ) != $routes_table ) {
        $sql = "CREATE TABLE $routes_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            route_id varchar(50) NOT NULL,
            town varchar(100) NOT NULL,
            residential int(11) DEFAULT 0,
            business int(11) DEFAULT 0,
            total_households int(11) DEFAULT 0,
            last_used date DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY route_id (route_id),
            KEY town (town)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    // Campaign Routes junction table (which routes used in which campaign)
    $campaign_routes_table = $wpdb->prefix . 'tln_campaign_routes';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$campaign_routes_table'" ) != $campaign_routes_table ) {
        $sql = "CREATE TABLE $campaign_routes_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) NOT NULL,
            route_id bigint(20) NOT NULL,
            PRIMARY KEY  (id),
            KEY campaign_id (campaign_id),
            KEY route_id (route_id)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}
add_action( 'init', 'tln_ensure_all_tables' );

/**
 * Send email alerts when campaign spots change
 */
function tln_campaign_spot_alerts($campaign_id) {
    global $wpdb;
    $campaigns_table = $wpdb->prefix . 'tln_campaigns';
    $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM $campaigns_table WHERE id = %d", $campaign_id));
    if (!$campaign) return;
    
    $total = intval($campaign->total_spots);
    $filled = intval($campaign->filled_spots);
    $remaining = $total - $filled;
    
    // Get last alert status from option
    $alert_key = 'tln_campaign_alert_' . $campaign_id;
    $last_alert = get_option($alert_key, '');
    
    // Alert when 3 spots remaining
    if ($remaining == 3 && $last_alert != '3') {
        wp_mail(
            'bryan@thelocalnearbuy.com',
            '⚠️ Campaign Spots Running Low - ' . $campaign->title,
            "Only $remaining spots left on the campaign!\n\n" .
            "Campaign: {$campaign->title}\n" .
            "Filled: $filled of $total\n" .
            "Remaining: $remaining\n\n" .
            "Log in to check: https://thelocalnearbuy.com/wp-admin/admin.php?page=tln-campaigns"
        );
        update_option($alert_key, '3');
    }
    
    // Alert when full
    if ($remaining == 0 && $last_alert != 'full') {
        wp_mail(
            'bryan@thelocalnearbuy.com',
            '🎉 Campaign Full - Ready to Print - ' . $campaign->title,
            "All $total spots are filled! The campaign is ready to print and mail.\n\n" .
            "Campaign: {$campaign->title}\n" .
            "Total Spots: $total\n" .
            "Revenue: $" . number_format($filled * floatval($campaign->price_per_spot)) . "\n\n" .
            "Log in to start printing: https://thelocalnearbuy.com/wp-admin/admin.php?page=tln-campaigns"
        );
        update_option($alert_key, 'full');
    }
}

/**
 * Register submenus.
 */
function tln_admin_menu() {
    // Main campaign list
    add_submenu_page(
        'tln-dashboard',
        'Campaigns',
        'Campaigns',
        'manage_options',
        'tln-campaigns',
        'tln_campaigns_page'
    );

    // Add new campaign
    add_submenu_page(
        'tln-dashboard',
        'Add Campaign',
        'Add Campaign',
        'manage_options',
        'tln-add-campaign',
        'tln_add_campaign_page'
    );

    // Zone manager
    add_submenu_page(
        'tln-dashboard',
        'EDDM Zones',
        'EDDM Zones',
        'manage_options',
        'tln-zones',
        'tln_zones_page'
    );

    // Route manager
    add_submenu_page(
        'tln-dashboard',
        'USPS Routes',
        'USPS Routes',
        'manage_options',
        'tln-routes',
        'tln_routes_page'
    );
}
add_action( 'admin_menu', 'tln_admin_menu' );

// Workflow stages
define( 'TLN_WORKFLOW_STAGES', json_encode( array(
    'sell'      => array( 'label' => '📢 Sell',      'color' => '#e74c3c' ),
    'artwork'   => array( 'label' => '🎨 Artwork',   'color' => '#9b59b6' ),
    'printing'  => array( 'label' => '🖨️ Printing',  'color' => '#3498db' ),
    'mailed'    => array( 'label' => '📮 Mailed',    'color' => '#2ecc71' ),
    'scanning'  => array( 'label' => '📱 Scanning',  'color' => '#f39c12' )
) ) );

/**
 * Main campaigns list with workflow tabs.
 */
function tln_campaigns_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tln_campaigns';

    // Get filter from URL
    $current_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'all';

    // Handle status update via AJAX
    if ( isset( $_POST['update_campaign_status'] ) && check_admin_referer( 'tln_update_status' ) ) {
        $campaign_id = intval( $_POST['campaign_id'] );
        $new_status = sanitize_text_field( $_POST['new_status'] );
        $wpdb->update( $table_name, array( 'workflow_status' => $new_status ), array( 'id' => $campaign_id ) );
        echo '<div class="notice notice-success"><p>Campaign updated!</p></div>';
    }

    // Build query
    if ( $current_filter != 'all' ) {
        $campaigns = $wpdb->get_results( $wpdb->prepare( 
            "SELECT c.*, cl.business_name 
             FROM {$table_name} c 
             LEFT JOIN {$wpdb->prefix}tln_claims cl ON c.business_id = cl.id 
             WHERE c.workflow_status = %s ORDER BY c.updated_at DESC", 
            $current_filter 
        ) );
    } else {
        $campaigns = $wpdb->get_results( 
            "SELECT c.*, cl.business_name 
             FROM {$table_name} c 
             LEFT JOIN {$wpdb->prefix}tln_claims cl ON c.business_id = cl.id 
             ORDER BY c.updated_at DESC" 
        );
    }

    $stages = json_decode( TLN_WORKFLOW_STAGES, true );
    ?>
    <div class="wrap">
        <h1>TLN Campaigns</h1>

        <!-- Workflow Tab Bar -->
        <div class="tln-workflow-tabs">
            <?php
            $tabs = array( 'all' => '📋 All' );
            foreach ( $stages as $key => $stage ) {
                $tabs[ $key ] = $stage['label'];
            }

            foreach ( $tabs as $key => $label ) :
                $count = ( $key == 'all' )
                    ? $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" )
                    : $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE workflow_status = %s", $key ) );
                $active = ( $current_filter == $key ) ? 'active' : '';
                $stage_class = ( in_array( $key, array_keys( $stages ) ) ) ? $key : '';
                ?>
                <a href="?page=tln-campaigns&status=<?php echo esc_attr( $key ); ?>" class="button tab <?php echo $stage_class; ?> <?php echo $active; ?>">
                    <?php echo esc_html( $label ); ?> (<?php echo $count; ?>)
                </a>
                <?php
            endforeach;
            ?>
        </div>

        <!-- Progress Bar for Selected Filter -->
        <?php if ( $current_filter != 'all' ) : ?>
            <div class="tln-progress-bar">
                <?php
                $stage_keys = array_keys( $stages );
                $current_idx = array_search( $current_filter, $stage_keys );
                $progress = ( $current_idx + 1 ) / count( $stage_keys ) * 100;
                ?>
                <div class="fill" style="width:<?php echo $progress; ?>%;background:<?php echo esc_attr( $stages[$current_filter]['color'] ); ?>;"></div>
                <div class="label">Stage <?php echo $current_idx + 1; ?> of <?php echo count( $stages ); ?>: <?php echo esc_html( $stages[$current_filter]['label'] ); ?></div>
            </div>
        <?php endif; ?>

        <!-- Campaigns Table -->
        <?php if ( ! empty( $campaigns ) ) : ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Spots</th>
                        <th>Guaranteed Meals</th>
                        <th>Actual</th>
                        <th>Routes</th>
                        <th>Created</th>
                        <th>QR</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $campaigns as $camp ) : ?>
                        <tr>
                            <td><?php echo esc_html( $camp->id ); ?></td>
                            <td>
                                <strong><?php echo esc_html( $camp->title ); ?></strong><br>
                                <small style="color:#1976d2;"><?php echo esc_html( $camp->business_name ?: 'No business linked' ); ?></small><br>
                                <small><?php echo esc_html( substr( $camp->description, 0, 60 ) ); ?>...</small>
                            </td>
                            <td>
                                <?php echo intval($camp->filled_spots); ?> / <?php echo intval($camp->total_spots); ?>
                            </td>
                            <td>
                                <?php
                                // Calculate meals: $50 per spot = 1 local (7 meals) + 66 global = 73 total
                                $spots_sold = intval($camp->filled_spots);
                                $meals_local = $spots_sold * 7; // 7 local meals per spot
                                $meals_global = $spots_sold * 66; // 66 global meals per spot
                                $meals_total = $meals_local + $meals_global;
                                $meals4good = ($spots_sold * 50) - ($meals_local * 4.30) - ($meals_global * 0.30);
                                ?>
                                <span style="color:#28a745;font-weight:bold;"><?php echo $meals_total; ?></span><br>
                                <small style="color:#666;"><?php echo $meals_local; ?> loc / <?php echo $meals_global; ?> gbl</small>
                            </td>
                            <td>
                                <?php
                                // Get actual redemptions for this campaign
                                $voucher_table = $wpdb->prefix . 'tln_vouchers';
                                $total_vouchers = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $voucher_table WHERE campaign_id = %d", $camp->id));
                                $redeemed_vouchers = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $voucher_table WHERE campaign_id = %d AND redeemed = 1", $camp->id));
                                $redemption_meals = $redeemed_vouchers * 1; // 1 meal per redemption
                                $actual_total = $meals_total + $redemption_meals;
                                ?>
                                <?php if ($total_vouchers > 0) : ?>
                                    <span style="color:#1a73e8;font-weight:bold;"><?php echo $redeemed_vouchers; ?> / <?php echo $total_vouchers; ?></span><br>
                                    <small style="color:#666;">+<?php echo $redemption_meals; ?> meals</small>
                                <?php else : ?>
                                    <span style="color:#999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" style="margin:0;">
                                    <?php wp_nonce_field( 'tln_update_status' ); ?>
                                    <input type="hidden" name="campaign_id" value="<?php echo esc_attr( $camp->id ); ?>">
                                    <select name="new_status" onchange="this.form.submit()" class="tln-status-select">
                                        <?php foreach ( $stages as $key => $stage ) : ?>
                                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $camp->workflow_status, $key ); ?>>
                                                <?php echo esc_html( $stage['label'] ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="update_campaign_status" value="1">
                                </form>
                            </td>
                            <td>
                                <?php
                                $campaign_routes_table = $wpdb->prefix . 'tln_campaign_routes';
                                $routes_table = $wpdb->prefix . 'tln_routes';
                                $campaign_routes = $wpdb->get_results( $wpdb->prepare( 
                                    "SELECT r.route_id, r.total_households FROM $campaign_routes_table cr 
                                    JOIN $routes_table r ON cr.route_id = r.id 
                                    WHERE cr.campaign_id = %d", 
                                    $camp->id 
                                ) );
                                
                                if ( ! empty( $campaign_routes ) ) {
                                    $route_ids = array_map( function($r) { return $r->route_id; }, $campaign_routes );
                                    $total_hh = array_sum( array_map( function($r) { return $r->total_households; }, $campaign_routes ) );
                                    echo '<strong>' . count( $campaign_routes ) . ' routes</strong><br>';
                                    echo '<small>' . number_format( $total_hh ) . ' HH</small>';
                                } else {
                                    echo '<span style="color:#999;">No routes</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html( $camp->created_at ); ?></td>
                            <td style="text-align:center;">
                                <?php
                                $qr_link = home_url( '/r/' . ($camp->campaign_code ?: $camp->id) );
                                $qr_api = 'https://quickchart.io/qr?size=80x80&text=' . urlencode( $qr_link );
                                ?>
                                <a href="<?php echo esc_url( $qr_link ); ?>" target="_blank">
                                    <img src="<?php echo esc_url( $qr_api ); ?>" alt="QR" style="width:50px;height:50px;">
                                </a>
                            </td>
                            <td>
                                <a href="?page=tln-add-campaign&edit=<?php echo esc_attr( $camp->id ); ?>" class="button button-small">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No campaigns found. <a href="?page=tln-add-campaign" class="button button-primary">Create First Campaign</a></p>
        <?php endif; ?>

        <p style="margin-top:20px;">
            <a href="?page=tln-add-campaign" class="button button-primary">+ Add New Campaign</a>
            <a href="?page=tln-routes" class="button">Manage Routes</a>
        </p>
    </div>
    <?php
}

/**
 * Add/Edit Campaign page.
 */
function tln_add_campaign_page() {
    global $wpdb;
    $campaigns_table = $wpdb->prefix . 'tln_campaigns';
    $zones_table = $wpdb->prefix . 'tln_zones';

    $edit_id = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : null;
    $campaign = $edit_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$campaigns_table} WHERE id = %d", $edit_id ) ) : null;

    // Handle form submission
    if ( isset( $_POST['tln_save_campaign'] ) && check_admin_referer( 'tln_save_campaign_action' ) ) {
        $business_id = intval( $_POST['business_id'] );
        if ( ! $business_id ) {
            echo '<div class="notice notice-error"><p>❌ Please select a business</p></div>';
        } else {
            $data = array(
                'business_id'      => $business_id,
                'title'            => sanitize_text_field( $_POST['title'] ),
            'description'      => wp_kses_post( $_POST['description'] ),
            'offer_text'       => sanitize_text_field( $_POST['offer_text'] ),
            'offer_valid_days' => intval( $_POST['valid_days'] ),
            'workflow_status'  => sanitize_text_field( $_POST['workflow_status'] ),
            'total_spots'      => intval( $_POST['total_spots'] ),
            'filled_spots'     => intval( $_POST['filled_spots'] ),
            'price_per_spot'    => floatval( $_POST['price_per_spot'] ),
            'campaign_cost'    => floatval( $_POST['campaign_cost'] )
        );

        if ( $edit_id ) {
            $wpdb->update( $campaigns_table, $data, array( 'id' => $edit_id ) );
            echo '<div class="notice notice-success"><p>✅ Campaign updated!</p></div>';
            tln_campaign_spot_alerts($edit_id);
        } else {
            $data['created_at'] = current_time( 'mysql' );
            
            // Generate campaign_code from business name + random suffix
            $business_name = '';
            $claim = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_claims WHERE id = %d", $business_id));
            if ($claim) {
                $business_name = sanitize_title($claim->business_name);
            }
            $suffix = strtoupper(wp_generate_password(3, false));
            $data['campaign_code'] = $business_name ? $business_name . '-' . $suffix : 'campaign-' . $suffix;
            
            $wpdb->insert( $campaigns_table, $data );
            $edit_id = $wpdb->insert_id;
            echo '<div class="notice notice-success"><p>✅ Campaign created!</p></div>';
        }
        
        // Save route selections
        $campaign_routes_table = $wpdb->prefix . 'tln_campaign_routes';
        $routes_table = $wpdb->prefix . 'tln_routes';
        
        // Clear existing route associations for this campaign
        $wpdb->delete( $campaign_routes_table, array( 'campaign_id' => $edit_id ) );
        
        // Add selected routes
        if ( ! empty( $_POST['routes'] ) && is_array( $_POST['routes'] ) ) {
            foreach ( $_POST['routes'] as $route_db_id ) {
                $route_db_id = intval( $route_db_id );
                if ( $route_db_id > 0 ) {
                    $wpdb->insert( $campaign_routes_table, array(
                        'campaign_id' => $edit_id,
                        'route_id' => $route_db_id
                    ) );
                    
                    // Update last_used date on the route
                    $wpdb->update( $routes_table, array( 'last_used' => current_time( 'mysql' ) ), array( 'id' => $route_db_id ) );
                }
            }
        }
        
        $campaign = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$campaigns_table} WHERE id = %d", $edit_id ) );
        }
    }

    $zones = $wpdb->get_results( "SELECT * FROM {$zones_table} ORDER BY zone_name" );
    $routes_table = $wpdb->prefix . 'tln_routes';
    $all_routes = $wpdb->get_results( "SELECT * FROM $routes_table ORDER BY town, route_id" );
    $stages = json_decode( TLN_WORKFLOW_STAGES, true );
    ?>
    <div class="wrap">
        <h1><?php echo $edit_id ? 'Edit Campaign' : 'Add New Campaign'; ?></h1>

        <form method="post" action="">
            <?php wp_nonce_field( 'tln_save_campaign_action' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="business_id">Business</label></th>
                    <td>
                        <select name="business_id" id="business_id" required>
                            <option value="">-- Select Business --</option>
                            <?php
                            $claims = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tln_claims WHERE status = 'approved' ORDER BY business_name");
                            foreach ($claims as $c) {
                                echo '<option value="' . esc_attr($c->id) . '"' . ($campaign && $campaign->business_id == $c->id ? ' selected' : '') . '>' . esc_html($c->business_name) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Select the business this campaign is for</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="title">Campaign Title</label></th>
                    <td><input name="title" id="title" type="text" class="regular-text" value="<?php echo $campaign ? esc_attr( $campaign->title ) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="description">Description</label></th>
                    <td><textarea name="description" id="description" rows="4" class="large-text" required><?php echo $campaign ? esc_textarea( $campaign->description ) : ''; ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="offer_text">Offer Headline</label></th>
                    <td><input name="offer_text" id="offer_text" type="text" class="regular-text" value="<?php echo $campaign ? esc_attr( $campaign->offer_text ) : ''; ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="valid_days">Valid Days</label></th>
                    <td><input name="valid_days" id="valid_days" type="number" value="<?php echo $campaign ? esc_attr( $campaign->offer_valid_days ) : 30; ?>" min="1"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="workflow_status">Workflow Stage</label></th>
                    <td>
                        <select name="workflow_status" id="workflow_status">
                            <?php foreach ( $stages as $key => $stage ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $campaign ? $campaign->workflow_status : 'sell', $key ); ?>>
                                    <?php echo esc_html( $stage['label'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label>Select Routes</label></th>
                    <td>
                        <div style="max-height:300px;overflow-y:auto;border:1px solid #ccc;padding:10px;margin-bottom:10px;">
                            <?php 
                            // Group routes by town
                            $routes_by_town = array();
                            foreach ( $all_routes as $route ) {
                                $routes_by_town[$route->town][] = $route;
                            }
                            
                            // Get currently selected routes for this campaign
                            $selected_routes = array();
                            if ( $campaign && $edit_id ) {
                                $campaign_routes_table = $wpdb->prefix . 'tln_campaign_routes';
                                $selected = $wpdb->get_results( $wpdb->prepare( "SELECT route_id FROM $campaign_routes_table WHERE campaign_id = %d", $edit_id ) );
                                foreach ( $selected as $s ) {
                                    $selected_routes[] = $s->route_id;
                                }
                            }
                            ?>
                            <table class="widefat fixed striped" style="font-size:12px;">
                                <?php foreach ( $routes_by_town as $town => $town_routes ) : ?>
                                    <tr style="background:#e0e0e0;"><td colspan="5"><strong><?php echo esc_html( $town ); ?></strong></td></tr>
                                    <?php foreach ( $town_routes as $route ) : ?>
                                        <tr>
                                            <td style="width:30px;">
                                                <input type="checkbox" name="routes[]" value="<?php echo esc_attr( $route->id ); ?>" 
                                                    class="route-checkbox" 
                                                    data-households="<?php echo esc_attr( $route->total_households ); ?>"
                                                    <?php echo in_array( $route->id, $selected_routes ) ? 'checked' : ''; ?>
                                                >
                                            </td>
                                            <td><?php echo esc_html( $route->route_id ); ?></td>
                                            <td><?php echo number_format( $route->residential ); ?></td>
                                            <td><?php echo number_format( $route->business ); ?></td>
                                            <td><strong><?php echo number_format( $route->total_households ); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <p><a href="?page=tln-routes" target="_blank">Manage Routes →</a></p>
                        
                        <div style="padding:15px;background:#f5f5f5;border-radius:8px;margin-top:10px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <span><strong>Selected Households:</strong></span>
                                <span id="route-total" style="font-size:20px;font-weight:bold;">0</span>
                            </div>
                            <div id="route-warning" style="display:none;margin-top:10px;padding:10px;background:#ffcccc;border:1px solid #ff0000;border-radius:4px;color:#cc0000;">
                                <strong>Warning:</strong> Over 5,000 households. USPS limit is 5,000 per campaign. Deselect some routes.
                            </div>
                            <div id="route-ok" style="margin-top:10px;color:green;">
                                ✓ Under 5,000 limit
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row" colspan="2" style="background:#f0f0f0;"><h4 style="margin:0.5rem 0;">Campaign Spots & Pricing</h4></th>
                </tr>
                <tr>
                    <th scope="row"><label for="total_spots">Total Spots</label></th>
                    <td><input name="total_spots" id="total_spots" type="number" value="<?php echo $campaign ? esc_attr( $campaign->total_spots ) : 16; ?>" min="1" style="width:80px;">
                        <span class="description">Number of ad spots on this postcard</span></td>
                </tr>
                <tr>
                    <th scope="row"><label for="filled_spots">Filled Spots</label></th>
                    <td><input name="filled_spots" id="filled_spots" type="number" value="<?php echo $campaign ? esc_attr( $campaign->filled_spots ) : 0; ?>" min="0" style="width:80px;">
                        <span class="description">Update this as spots sell to trigger alerts</span></td>
                </tr>
                <tr>
                    <th scope="row"><label for="price_per_spot">Price per Spot ($)</label></th>
                    <td><input name="price_per_spot" id="price_per_spot" type="number" value="<?php echo $campaign ? esc_attr( $campaign->price_per_spot ) : 450; ?>" min="0" step="0.01" style="width:100px;">
                        <span class="description">What you charge businesses per spot</span></td>
                </tr>
                <tr>
                    <th scope="row"><label for="campaign_cost">Your Cost ($)</label></th>
                    <td><input name="campaign_cost" id="campaign_cost" type="number" value="<?php echo $campaign ? esc_attr( $campaign->campaign_cost ) : 2400; ?>" min="0" step="0.01" style="width:100px;">
                        <span class="description">Total cost to print and mail this campaign</span></td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="tln_save_campaign" id="submit" class="button button-primary" value="<?php echo $edit_id ? 'Update Campaign' : 'Create Campaign'; ?>">
                <?php if ( $edit_id ) : ?>
                    <a href="?page=tln-campaigns" class="button">Back to List</a>
                <?php endif; ?>
            </p>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            function updateRouteTotal() {
                var checkboxes = document.querySelectorAll('.route-checkbox:checked');
                var total = 0;
                checkboxes.forEach(function(cb) {
                    total += parseInt(cb.getAttribute('data-households')) || 0;
                });
                document.getElementById('route-total').textContent = total.toLocaleString();
                
                if (total > 5000) {
                    document.getElementById('route-warning').style.display = 'block';
                    document.getElementById('route-ok').style.display = 'none';
                } else {
                    document.getElementById('route-warning').style.display = 'none';
                    document.getElementById('route-ok').style.display = 'block';
                }
            }
            
            document.querySelectorAll('.route-checkbox').forEach(function(cb) {
                cb.addEventListener('change', updateRouteTotal);
            });
            
            // Initial calculation
            updateRouteTotal();
        });
        </script>

        <?php if ( $campaign ) : ?>
            <hr>
            <h3>Postcard Spot Layout</h3>
            <p style="color:#666;">Visual representation of ad spots. Click to toggle availability.</p>
            
            <style>
                .tln-spot-grid { display:flex; gap:40px; margin:20px 0; flex-wrap:wrap; }
                .tln-spot-side { flex:1; min-width:300px; }
                .tln-spot-side h4 { margin-bottom:10px; text-align:center; background:#2271b1; color:#fff; padding:8px; border-radius:4px 4px 0 0; }
                .tln-spots { display:grid; grid-template-columns:repeat(4, 1fr); gap:8px; background:#f0f0f0; padding:15px; border-radius:0 0 8px 8px; }
                .tln-spot { aspect-ratio:4/3; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:11px; border-radius:4px; cursor:pointer; transition:all 0.2s; text-align:center; padding:5px; }
                .tln-spot.available { background:#4caf50; color:#fff; }
                .tln-spot.sold { background:#e74c3c; color:#fff; }
                .tln-spot:hover { transform:scale(1.05); box-shadow:0 4px 12px rgba(0,0,0,0.3); }
                .tln-spot-front h4 { background:#2271b1; }
                .tln-spot-back h4 { background:#8e44ad; }
                .tln-legend { display:flex; gap:20px; margin:15px 0; }
                .tln-legend span { display:flex; align-items:center; gap:8px; }
                .tln-legend .box { width:20px; height:20px; border-radius:4px; }
                .tln-legend .available { background:#4caf50; }
                .tln-legend .sold { background:#e74c3c; }
            </style>
            
            <div class="tln-legend">
                <span><div class="box available"></div> Available ($450)</span>
                <span><div class="box sold"></div> Sold</span>
            </div>
            
            <div class="tln-spot-grid">
                <div class="tln-spot-side tln-spot-front">
                    <h4>Front (8 spots)</h4>
                    <div class="tln-spots">
                        <?php for ($i = 1; $i <= 8; $i++): $is_sold = $i <= $campaign->filled_spots; ?>
                            <div class="tln-spot <?php echo $is_sold ? 'sold' : 'available'; ?>" title="Spot <?php echo $i; ?>">
                                <?php echo $is_sold ? 'SOLD' : 'Avail'; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="tln-spot-side tln-spot-back">
                    <h4>Back (8 spots)</h4>
                    <div class="tln-spots">
                        <?php for ($i = 9; $i <= 16; $i++): $back_sold = max(0, $campaign->filled_spots - 8); $is_sold = ($i - 8) <= $back_sold; ?>
                            <div class="tln-spot <?php echo $is_sold ? 'sold' : 'available'; ?>" title="Spot <?php echo $i; ?>">
                                <?php echo $is_sold ? 'SOLD' : 'Avail'; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <p><strong>Spots Available:</strong> <?php echo max(0, 16 - $campaign->filled_spots); ?> of 16</p>
            <p><strong>Potential Revenue:</strong> $<?php echo number_format(max(0, 16 - $campaign->filled_spots) * 450); ?></p>
            
            <hr>
            <h3>Campaign QR Code</h3>
            <?php
            $qr_link = home_url( '/r/' . ($campaign->campaign_code ?: $campaign->id) );
            $qr_api = 'https://quickchart.io/qr?size=200x200&text=' . urlencode( $qr_link );
            ?>
            <div class="tln-qr-box">
                <img src="<?php echo esc_url( $qr_api ); ?>" alt="QR Code" style="border:2px solid #ccc;padding:10px;background:#fff;">
                <div>
                    <p><strong>Dynamic QR URL:</strong><br><code><?php echo esc_url( $qr_link ); ?></code></p>
                    <p style="font-size:12px;color:#666;">Scan to test — offer updates apply to all scans (no reprint needed)</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Zone Manager page.
 */
function tln_zones_page() {
    global $wpdb;
    $zones_table = $wpdb->prefix . 'tln_zones';

    // Handle zone actions
    if ( isset( $_POST['tln_save_zone'] ) && check_admin_referer( 'tln_save_zone_action' ) ) {
        $zip_codes = sanitize_textarea_field( $_POST['zip_codes'] );
        
        // Auto-calculate households from ZIP codes (estimate ~150 HH per ZIP code)
        $zip_array = array_filter( array_map( 'trim', preg_split( '/[,\s]+/', $zip_codes ) ) );
        $households = count( $zip_array ) * 150; // Rough estimate
        
        $data = array(
            'zone_name'    => sanitize_text_field( $_POST['zone_name'] ),
            'zip_codes'    => $zip_codes,
            'households'   => $households
        );

        if ( ! empty( $_POST['zone_id'] ) ) {
            $wpdb->update( $zones_table, $data, array( 'id' => intval( $_POST['zone_id'] ) ) );
            echo '<div class="notice notice-success"><p>Zone updated!</p></div>';
        } else {
            $wpdb->insert( $zones_table, $data );
            echo '<div class="notice notice-success"><p>Zone added!</p></div>';
        }
    }

    if ( isset( $_POST['tln_delete_zone'] ) && check_admin_referer( 'tln_delete_zone_action' ) ) {
        $wpdb->delete( $zones_table, array( 'id' => intval( $_POST['zone_id'] ) ) );
        echo '<div class="notice notice-warning"><p>Zone deleted.</p></div>';
    }

    $zones = $wpdb->get_results( "SELECT * FROM {$zones_table} ORDER BY zone_name" );

    $edit_zone = isset( $_GET['edit_zone'] ) ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$zones_table} WHERE id = %d", intval( $_GET['edit_zone'] ) ) ) : null;
    ?>
    <div class="wrap">
        <h1>USPS EDDM Zones</h1>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <!-- Zone List -->
            <div>
                <h2>Zones (<?php echo count( $zones ); ?>)</h2>
                <?php if ( ! empty( $zones ) ) : ?>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Zone</th>
                                <th>ZIPs</th>
                                <th>Est. Households</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $zones as $zone ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $zone->zone_name ); ?></strong></td>
                                    <td><?php echo esc_html( substr( $zone->zip_codes, 0, 30 ) ); ?>...</td>
                                    <td><?php echo number_format( $zone->households ); ?></td>
                                    <td>
                                        <a href="?page=tln-zones&edit_zone=<?php echo esc_attr( $zone->id ); ?>" class="button button-small">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>No zones found.</p>
                <?php endif; ?>
            </div>

            <!-- Add/Edit Zone Form -->
            <div style="background:#f9f9f9;padding:20px;border-radius:8px;">
                <h2><?php echo $edit_zone ? 'Edit Zone' : 'Add New Zone'; ?></h2>
                <form method="post" action="?page=tln-zones">
                    <?php wp_nonce_field( 'tln_save_zone_action' ); ?>
                    <?php if ( $edit_zone ) : ?>
                        <input type="hidden" name="zone_id" value="<?php echo esc_attr( $edit_zone->id ); ?>">
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="zone_name">Zone Name</label></th>
                            <td><input name="zone_name" id="zone_name" type="text" class="regular-text" value="<?php echo $edit_zone ? esc_attr( $edit_zone->zone_name ) : ''; ?>" required placeholder="e.g., Waxhaw North"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="zip_codes">ZIP Codes (comma or space separated)</label></th>
                            <td><textarea name="zip_codes" id="zip_codes" rows="3" class="large-text" required placeholder="28104, 28108, 28173"><?php echo $edit_zone ? esc_textarea( $edit_zone->zip_codes ) : ''; ?></textarea>
                            <p class="description">Households are automatically estimated (~150 per ZIP code).</p></td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" name="tln_save_zone" class="button button-primary" value="<?php echo $edit_zone ? 'Update Zone' : 'Add Zone'; ?>">
                        <?php if ( $edit_zone ) : ?>
                            <a href="?page=tln-zones" class="button">Clear</a>
                            <button type="submit" name="tln_delete_zone" class="button button-link-delete" onclick="return confirm('Delete this zone?');">Delete</button>
                            <?php wp_nonce_field( 'tln_delete_zone_action' ); ?>
                            <input type="hidden" name="zone_id" value="<?php echo esc_attr( $edit_zone->id ); ?>">
                        <?php endif; ?>
                    </p>
                </form>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Route Manager page.
 */
function tln_routes_page() {
    global $wpdb;
    $routes_table = $wpdb->prefix . 'tln_routes';

    // Handle bulk import
    if ( isset( $_POST['tln_import_routes'] ) && check_admin_referer( 'tln_import_routes_action' ) ) {
        $import_data = $_POST['import_data'];
        $lines = explode( "\n", trim( $import_data ) );
        $imported = 0;
        
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( empty( $line ) ) continue;
            
            // Parse: 28173-R001    675    26    777    (tab or space separated)
            $parts = preg_split( '/[\s,]+/', $line );
            if ( count( $parts ) >= 3 ) {
                $route_id = sanitize_text_field( $parts[0] );
                $residential = intval( $parts[1] );
                $business = intval( $parts[2] );
                $total = $residential + $business;
                
                // Extract town from route ID (e.g., 28173-R001 -> Waxhaw based on ZIP)
                $zip = preg_replace( '/-R\d+/', '', $route_id );
                $town = 'Waxhaw'; // Default, can be changed
                
                // Check if route exists
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $routes_table WHERE route_id = %s", $route_id ) );
                
                if ( $exists ) {
                    $wpdb->update( $routes_table, 
                        array( 'residential' => $residential, 'business' => $business, 'total_households' => $total ),
                        array( 'route_id' => $route_id )
                    );
                } else {
                    $wpdb->insert( $routes_table, array(
                        'route_id' => $route_id,
                        'town' => $town,
                        'residential' => $residential,
                        'business' => $business,
                        'total_households' => $total
                    ) );
                }
                $imported++;
            }
        }
        echo '<div class="notice notice-success"><p>Imported ' . $imported . ' routes.</p></div>';
    }

    // Handle single route add/edit
    if ( isset( $_POST['tln_save_route'] ) && check_admin_referer( 'tln_save_route_action' ) ) {
        $data = array(
            'route_id' => sanitize_text_field( $_POST['route_id'] ),
            'town' => sanitize_text_field( $_POST['town'] ),
            'residential' => intval( $_POST['residential'] ),
            'business' => intval( $_POST['business'] ),
            'total_households' => intval( $_POST['residential'] ) + intval( $_POST['business'] )
        );

        if ( ! empty( $_POST['route_db_id'] ) ) {
            $wpdb->update( $routes_table, $data, array( 'id' => intval( $_POST['route_db_id'] ) ) );
            echo '<div class="notice notice-success"><p>Route updated!</p></div>';
        } else {
            $wpdb->insert( $routes_table, $data );
            echo '<div class="notice notice-success"><p>Route added!</p></div>';
        }
    }

    // Handle delete
    if ( isset( $_POST['tln_delete_route'] ) && check_admin_referer( 'tln_delete_route_action' ) ) {
        $wpdb->delete( $routes_table, array( 'id' => intval( $_POST['route_db_id'] ) ) );
        echo '<div class="notice notice-warning"><p>Route deleted.</p></div>';
    }

    // Filter by town
    $town_filter = isset( $_GET['town'] ) ? sanitize_text_field( $_GET['town'] ) : 'all';
    if ( $town_filter != 'all' ) {
        $routes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $routes_table WHERE town = %s ORDER BY route_id", $town_filter ) );
    } else {
        $routes = $wpdb->get_results( "SELECT * FROM $routes_table ORDER BY route_id" );
    }
    
    $towns = $wpdb->get_results( "SELECT DISTINCT town FROM $routes_table ORDER BY town" );
    
    $edit_route = isset( $_GET['edit_route'] ) ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $routes_table WHERE id = %d", intval( $_GET['edit_route'] ) ) ) : null;
    ?>
    <div class="wrap">
        <h1>USPS Routes</h1>
        <p style="color:#666;">Add routes to track which neighborhoods you've mailed to. Each campaign should stay under 5,000 households.</p>

        <!-- Bulk Import - Prominent at Top -->
        <div style="background:#e8f5e9;padding:20px;border-radius:8px;margin-bottom:20px;border:2px solid #4caf50;">
            <h2 style="margin-top:0;">Bulk Import Routes</h2>
            <form method="post" action="?page=tln-routes">
                <?php wp_nonce_field( 'tln_import_routes_action' ); ?>
                <p>Paste route data (one per line):</p>
                <p style="font-family:monospace;font-size:12px;background:#fff;padding:5px;display:inline-block;">Format: RouteID Residential Business</p>
                <textarea name="import_data" rows="8" class="large-text" placeholder="28173-R001 675 26&#10;28173-R002 585 45" style="font-family:monospace;"></textarea>
                <p class="submit"><input type="submit" name="tln_import_routes" class="button button-primary" value="Import Routes"></p>
            </form>
        </div>

        <!-- Filter by Town -->
        <div style="display:flex;gap:5px;margin-bottom:20px;flex-wrap:wrap;">
            <a href="?page=tln-routes" class="button <?php echo $town_filter == 'all' ? 'button-primary' : ''; ?>">All Towns</a>
            <?php foreach ( $towns as $t ) : ?>
                <a href="?page=tln-routes&town=<?php echo esc_attr( $t->town ); ?>" class="button <?php echo $town_filter == $t->town ? 'button-primary' : ''; ?>"><?php echo esc_html( $t->town ); ?></a>
            <?php endforeach; ?>
        </div>

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
            <!-- Route List -->
            <div>
                <h2>Routes (<?php echo count( $routes ); ?>)</h2>
                <?php if ( ! empty( $routes ) ) : $total_hh = 0; ?>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Route ID</th>
                                <th>Town</th>
                                <th>Res.</th>
                                <th>Bus.</th>
                                <th>Total</th>
                                <th>Last Used</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $routes as $route ) : $total_hh += $route->total_households; ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $route->route_id ); ?></strong></td>
                                    <td><?php echo esc_html( $route->town ); ?></td>
                                    <td><?php echo number_format( $route->residential ); ?></td>
                                    <td><?php echo number_format( $route->business ); ?></td>
                                    <td><strong><?php echo number_format( $route->total_households ); ?></strong></td>
                                    <td><?php echo $route->last_used ? esc_html( $route->last_used ) : '<span style="color:#999;">Never</span>'; ?></td>
                                    <td>
                                        <a href="?page=tln-routes&edit_route=<?php echo esc_attr( $route->id ); ?>" class="button button-small">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:#f0f0f0;">
                                <td colspan="4"><strong>Total Households</strong></td>
                                <td colspan="3"><strong><?php echo number_format( $total_hh ); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php else : ?>
                    <p>No routes found. Add routes manually or use bulk import below.</p>
                <?php endif; ?>
            </div>

            <!-- Add Single Route Form -->
            <div style="background:#f9f9f9;padding:20px;border-radius:8px;">
                <h2>Add Single Route</h2>
                <form method="post" action="?page=tln-routes">
                    <?php wp_nonce_field( 'tln_save_route_action' ); ?>
                    <?php if ( $edit_route ) : ?>
                        <input type="hidden" name="route_db_id" value="<?php echo esc_attr( $edit_route->id ); ?>">
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="route_id">Route ID</label></th>
                            <td><input name="route_id" id="route_id" type="text" class="regular-text" value="<?php echo $edit_route ? esc_attr( $edit_route->route_id ) : ''; ?>" required placeholder="e.g., 28173-R001"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="town">Town</label></th>
                            <td>
                                <select name="town" id="town">
                                    <option value="Waxhaw" <?php selected( $edit_route ? $edit_route->town : 'Waxhaw', 'Waxhaw' ); ?>>Waxhaw</option>
                                    <option value="Marvin" <?php selected( $edit_route ? $edit_route->town : '', 'Marvin' ); ?>>Marvin</option>
                                    <option value="Wesley Chapel" <?php selected( $edit_route ? $edit_route->town : '', 'Wesley Chapel' ); ?>>Wesley Chapel</option>
                                    <option value="Weddington" <?php selected( $edit_route ? $edit_route->town : '', 'Weddington' ); ?>>Weddington</option>
                                    <option value="Indian Land" <?php selected( $edit_route ? $edit_route->town : '', 'Indian Land' ); ?>>Indian Land</option>
                                    <option value="Ballantyne" <?php selected( $edit_route ? $edit_route->town : '', 'Ballantyne' ); ?>>Ballantyne</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="residential">Residential</label></th>
                            <td><input name="residential" id="residential" type="number" value="<?php echo $edit_route ? esc_attr( $edit_route->residential ) : 0; ?>" min="0"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="business">Business</label></th>
                            <td><input name="business" id="business" type="number" value="<?php echo $edit_route ? esc_attr( $edit_route->business ) : 0; ?>" min="0"></td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" name="tln_save_route" class="button button-primary" value="<?php echo $edit_route ? 'Update Route' : 'Add Route'; ?>">
                        <?php if ( $edit_route ) : ?>
                            <a href="?page=tln-routes" class="button">Clear</a>
                            <button type="submit" name="tln_delete_route" class="button button-link-delete" onclick="return confirm('Delete this route?');">Delete</button>
                            <?php wp_nonce_field( 'tln_delete_route_action' ); ?>
                            <input type="hidden" name="route_db_id" value="<?php echo esc_attr( $edit_route->id ); ?>">
                        <?php endif; ?>
                    </p>
                </form>
            </div>
        </div>
    </div>
    <?php
}