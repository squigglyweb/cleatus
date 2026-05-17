<?php
// tln-plugin/tln-admin-campaign.php
if ( ! defined( 'ABSPATH' ) ) exit;

// =============================================================================
// TLN Campaign Manager with Workflow Tabs & Zone Picker
// =============================================================================

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
        <div style="display:flex;gap:5px;margin-bottom:20px;flex-wrap:wrap;">
            <?php
            $tabs = array( 'all' => '📋 All' );
            foreach ( $stages as $key => $stage ) {
                $tabs[ $key ] = $stage['label'];
            }

            foreach ( $tabs as $key => $label ) :
                $count = ( $key == 'all' )
                    ? $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" )
                    : $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE workflow_status = %s", $key ) );
                $active = ( $current_filter == $key ) ? 'style="background:#0073aa;color:#fff;"' : '';
                ?>
                <a href="?page=tln-campaigns&status=<?php echo esc_attr( $key ); ?>" class="button" <?php echo $active; ?>>
                    <?php echo esc_html( $label ); ?> (<?php echo $count; ?>)
                </a>
                <?php
            endforeach;
            ?>
        </div>

        <!-- Progress Bar for Selected Filter -->
        <?php if ( $current_filter != 'all' ) : ?>
            <div style="background:#f0f0f0;border-radius:8px;height:20px;margin-bottom:20px;overflow:hidden;position:relative;">
                <?php
                $stage_keys = array_keys( $stages );
                $current_idx = array_search( $current_filter, $stage_keys );
                $progress = ( $current_idx + 1 ) / count( $stage_keys ) * 100;
                ?>
                <div style="width:<?php echo $progress; ?>%;background:<?php echo esc_attr( $stages[$current_filter]['color'] ); ?>;height:100%;transition:width 0.3s;"></div>
                <div style="position:absolute;top:0;left:10px;font-size:12px;line-height:20px;font-weight:bold;color:#333;">
                    Stage <?php echo $current_idx + 1; ?> of <?php echo count( $stages ); ?>
                </div>
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
                                    <select name="new_status" onchange="this.form.submit()" style="font-size:12px;">
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
            'zone_id'          => intval( $_POST['zone_id'] ) ?: null
        );

        if ( $edit_id ) {
            $wpdb->update( $campaigns_table, $data, array( 'id' => $edit_id ) );
            echo '<div class="notice notice-success"><p>✅ Campaign updated!</p></div>';
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
            <div style="display:flex;align-items:center;gap:20px;padding:20px;background:#f9f9f9;border-radius:8px;">
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
                                        <span style="padding:2px 8px;border-radius:4px;background:<?php echo $zone->status == 'approved' ? '#c6efce' : ( $zone->status == 'rejected' ? '#ffc7ce' : '#ffeb9c' ); ?>;">
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