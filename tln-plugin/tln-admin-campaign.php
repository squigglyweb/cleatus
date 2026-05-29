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
        $campaigns = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE workflow_status = %s ORDER BY updated_at DESC", $current_filter ) );
    } else {
        $campaigns = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY updated_at DESC" );
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
                        <th>Zone</th>
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
                                <small><?php echo esc_html( substr( $camp->description, 0, 60 ) ); ?>...</small>
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
                                if ( $camp->zone_id ) {
                                    $zone = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tln_zones WHERE id = %d", $camp->zone_id ) );
                                    echo $zone ? esc_html( $zone->zone_name ) : 'N/A';
                                } else {
                                    echo '<span style="color:#999;">—</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html( $camp->created_at ); ?></td>
                            <td style="text-align:center;">
                                <?php
                                $qr_link = home_url( '/r/' . $camp->id );
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
            <a href="?page=tln-zones" class="button">Manage EDDM Zones</a>
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
        $data = array(
            'business_id'      => get_current_user_id(),
            'title'            => sanitize_text_field( $_POST['title'] ),
            'description'      => wp_kses_post( $_POST['description'] ),
            'offer_text'       => sanitize_text_field( $_POST['offer_text'] ),
            'offer_valid_days' => intval( $_POST['valid_days'] ),
            'workflow_status'  => sanitize_text_field( $_POST['workflow_status'] ),
            'zone_id'          => intval( $_POST['zone_id'] ) ?: null,
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
            $wpdb->insert( $campaigns_table, $data );
            $edit_id = $wpdb->insert_id;
            echo '<div class="notice notice-success"><p>✅ Campaign created!</p></div>';
        }
        $campaign = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$campaigns_table} WHERE id = %d", $edit_id ) );
    }

    $zones = $wpdb->get_results( "SELECT * FROM {$zones_table} WHERE status = 'approved' ORDER BY zone_name" );
    $stages = json_decode( TLN_WORKFLOW_STAGES, true );
    ?>
    <div class="wrap">
        <h1><?php echo $edit_id ? 'Edit Campaign' : 'Add New Campaign'; ?></h1>

        <form method="post" action="">
            <?php wp_nonce_field( 'tln_save_campaign_action' ); ?>

            <table class="form-table">
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
                    <th scope="row"><label for="zone_id">EDDM Zone</label></th>
                    <td>
                        <select name="zone_id" id="zone_id">
                            <option value="">— Select Zone —</option>
                            <?php foreach ( $zones as $zone ) : ?>
                                <option value="<?php echo esc_attr( $zone->id ); ?>" <?php selected( $campaign ? $campaign->zone_id : 0, $zone->id ); ?>>
                                    <?php echo esc_html( $zone->zone_name ); ?> (<?php echo number_format( $zone->households ); ?> HH)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><a href="?page=tln-zones" target="_blank">Manage Zones →</a></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row" colspan="2" style="background:#f0f0f0;"><h4 style="margin:0.5rem 0;">Campaign Spots & Pricing</h4></th>
                </tr>
                <tr>
                    <th scope="row"><label for="total_spots">Total Spots</label></th>
                    <td><input name="total_spots" id="total_spots" type="number" value="<?php echo $campaign ? esc_attr( $campaign->total_spots ) : 9; ?>" min="1" style="width:80px;">
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
                    <td><input name="campaign_cost" id="campaign_cost" type="number" value="<?php echo $campaign ? esc_attr( $campaign->campaign_cost ) : 2000; ?>" min="0" step="0.01" style="width:100px;">
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

        <?php if ( $campaign ) : ?>
            <hr>
            <h3>Campaign QR Code</h3>
            <?php
            $qr_link = home_url( '/r/' . $campaign->id );
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
        $data = array(
            'zone_name'    => sanitize_text_field( $_POST['zone_name'] ),
            'zip_codes'    => sanitize_textarea_field( $_POST['zip_codes'] ),
            'households'   => intval( $_POST['households'] ),
            'cost_per_mailer' => floatval( $_POST['cost_per_mailer'] ),
            'status'       => sanitize_text_field( $_POST['status'] )
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

    // Filter
    $status_filter = isset( $_GET['zone_status'] ) ? sanitize_text_field( $_GET['zone_status'] ) : 'all';
    if ( $status_filter != 'all' ) {
        $zones = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$zones_table} WHERE status = %s ORDER BY zone_name", $status_filter ) );
    } else {
        $zones = $wpdb->get_results( "SELECT * FROM {$zones_table} ORDER BY zone_name" );
    }

    $edit_zone = isset( $_GET['edit_zone'] ) ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$zones_table} WHERE id = %d", intval( $_GET['edit_zone'] ) ) ) : null;
    ?>
    <div class="wrap">
        <h1>USPS EDDM Zones</h1>

        <!-- Filter Tabs -->
        <div style="display:flex;gap:5px;margin-bottom:20px;">
            <a href="?page=tln-zones&zone_status=all" class="button <?php echo $status_filter == 'all' ? 'button-primary' : ''; ?>">All</a>
            <a href="?page=tln-zones&zone_status=suggested" class="button <?php echo $status_filter == 'suggested' ? 'button-primary' : ''; ?>">Suggested</a>
            <a href="?page=tln-zones&zone_status=approved" class="button <?php echo $status_filter == 'approved' ? 'button-primary' : ''; ?>">Approved</a>
            <a href="?page=tln-zones&zone_status=rejected" class="button <?php echo $status_filter == 'rejected' ? 'button-primary' : ''; ?>">Rejected</a>
        </div>

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
                                <th>Households</th>
                                <th>Cost</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $zones as $zone ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $zone->zone_name ); ?></strong></td>
                                    <td><?php echo esc_html( substr( $zone->zip_codes, 0, 30 ) ); ?>...</td>
                                    <td><?php echo number_format( $zone->households ); ?></td>
                                    <td>$<?php echo number_format( $zone->cost_per_mailer, 2 ); ?></td>
                                    <td>
                                        <span class="tln-badge <?php echo esc_attr( $zone->status ); ?>">
                                            <?php echo esc_html( ucfirst( $zone->status ) ); ?>
                                        </span>
                                    </td>
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
                            <td><textarea name="zip_codes" id="zip_codes" rows="3" class="large-text" required placeholder="28104, 28108, 28173"><?php echo $edit_zone ? esc_textarea( $edit_zone->zip_codes ) : ''; ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="households">Households</label></th>
                            <td><input name="households" id="households" type="number" value="<?php echo $edit_zone ? esc_attr( $edit_zone->households ) : 0; ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cost_per_mailer">Cost per Mailer ($)</label></th>
                            <td><input name="cost_per_mailer" id="cost_per_mailer" type="number" step="0.01" value="<?php echo $edit_zone ? esc_attr( $edit_zone->cost_per_mailer ) : 0.50; ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="status">Status</label></th>
                            <td>
                                <select name="status" id="status">
                                    <option value="suggested" <?php selected( $edit_zone ? $edit_zone->status : 'suggested', 'suggested' ); ?>>Suggested</option>
                                    <option value="approved" <?php selected( $edit_zone ? $edit_zone->status : '', 'approved' ); ?>>Approved</option>
                                    <option value="rejected" <?php selected( $edit_zone ? $edit_zone->status : '', 'rejected' ); ?>>Rejected</option>
                                </select>
                            </td>
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