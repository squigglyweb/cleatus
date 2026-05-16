<?php
// tln-plugin/tln-admin-campaign.php
if ( ! defined( 'ABSPATH' ) ) exit;

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Auto-create campaigns table if it doesn't exist.
 */
function tln_ensure_campaigns_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tln_campaigns';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            business_id bigint(20) NOT NULL,
            title text NOT NULL,
            description text NOT NULL,
            offer_text text,
            offer_valid_days int(11) DEFAULT 30,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}
add_action( 'init', 'tln_ensure_campaigns_table' );

/**
 * Register a submenu under the TLN top‑level menu.
 */
function tln_admin_menu() {
    add_submenu_page(
        'tln-dashboard',            // parent slug (the TLN plugin menu)
        'Add Campaign',             // page title
        'Add Campaign',             // menu title
        'manage_options',           // capability
        'tln-add-campaign',         // slug
        'tln_add_campaign_page'    // callback
    );
}
add_action( 'admin_menu', 'tln_admin_menu' );

/**
 * Render the form and handle submission.
 */
function tln_add_campaign_page() {
    global $wpdb;
    
    // Ensure table exists before anything
    $table_name = $wpdb->prefix . 'tln_campaigns';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            business_id bigint(20) NOT NULL,
            title text NOT NULL,
            description text NOT NULL,
            offer_text text,
            offer_valid_days int(11) DEFAULT 30,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    // Only admins can use it
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to access this page.' );
    }

    // Process the form if it was submitted
    if ( isset( $_POST['tln_new_campaign'] ) && check_admin_referer( 'tln_new_campaign_action' ) ) {

        global $wpdb;
        $table = $wpdb->prefix . 'tln_campaigns';

        // Grab and sanitize fields
        $business_id = get_current_user_id(); // you can change this if you want the owner picker
        $title       = sanitize_text_field( $_POST['title'] );
              $description = wp_kses_post( $_POST['description'] );
        $offer_text  = sanitize_text_field( $_POST['offer_text'] );
        $valid_days  = intval( $_POST['valid_days'] );

        // DEBUG output
        echo '<div style="background:#ffffcc;padding:10px;margin:10px 0;"><strong>DEBUG - Inserting:</strong><br>';
        echo 'business_id: ' . $business_id . '<br>';
        echo 'title: ' . $title . '<br>';
        echo 'description: ' . substr( $description, 0, 30 ) . '...<br>';
        echo 'offer_text: ' . $offer_text . '<br>';
        echo 'valid_days: ' . $valid_days . '<br>';
        echo '</div>';
        
        // Insert the row
        $result = $wpdb->insert(
            $table,
            array(
                'business_id'      => $business_id,
                'title'            => $title,
                'description'      => $description,
                'offer_text'       => $offer_text,
                'offer_valid_days' => $valid_days,
                'created_at'       => current_time( 'mysql' ),
            ),
                      array( '%d', '%s', '%s', '%s', '%d', '%s' )
        );

        // Check if insert worked
        if ( $result === false ) {
            echo '<div style="background:#ffcccc;padding:10px;margin:10px 0;">
            <strong>INSERT FAILED:</strong> ' . $wpdb->last_error . '<br>
            </div>';
        }
        
        // Grab the new auto‑increment ID
        $new_id = $wpdb->insert_id;

        // Show a success message with the QR‑link you need
        $campaign_url = home_url( '/r/' . $new_id );
        
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>✅ Campaign created!</strong> Campaign ID: <strong>' . esc_html( $new_id ) . '</strong></p>';
        echo '<p><strong>Postcard QR URL:</strong> <code>' . esc_url( $campaign_url ) . '</code></p>';
        echo '<p>Use this URL to generate a QR code at any QR generator (like qrserver.com or bitly.com)</p>';
        echo '</div>';
    }

    // DEBUG: Check table existence and data
    $table_exists = ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name );
    echo '<div style="background:#f0f0f0;padding:10px;margin:10px 0;">';
    echo '<strong>DEBUG:</strong> Table exists: ' . ( $table_exists ? 'YES' : 'NO' ) . '<br>';
    echo 'Table name: ' . $table_name . '<br>';
    
    if ( $table_exists ) {
        $campaigns = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY created_at DESC" );
        echo 'Campaign count: ' . count( $campaigns ) . '<br>';
        if ( ! empty( $wpdb->last_error ) ) {
            echo 'SQL Error: ' . $wpdb->last_error . '<br>';
        }
    } else {
        $campaigns = array();
    }
    echo '</div>';

    // Show table of existing campaigns
    if ( ! empty( $campaigns ) ) {
        echo '<h2>Existing Campaigns</h2>';
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Title</th><th>Description</th><th>Offer Text</th><th>Valid Days</th><th>Created</th><th>QR Link</th></tr></thead>';
        echo '<tbody>';
        foreach ( $campaigns as $camp ) {
            $qr_link = home_url( '/r/' . $camp->id );
            echo '<tr>';
            echo '<td>' . esc_html( $camp->id ) . '</td>';
            echo '<td>' . esc_html( $camp->title ) . '</td>';
            echo '<td>' . esc_html( substr( $camp->description, 0, 50 ) ) . ( strlen( $camp->description ) > 50 ? '...' : '' ) . '</td>';
            echo '<td>' . esc_html( $camp->offer_text ) . '</td>';
            echo '<td>' . esc_html( $camp->offer_valid_days ) . '</td>';
            echo '<td>' . esc_html( $camp->created_at ) . '</td>';
            echo '<td><a href="' . esc_url( $qr_link ) . '" target="_blank">' . esc_html( $qr_link ) . '</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<p><em>Use the QR Link column to generate QR codes at any QR generator site.</em></p>';
    }

    // ----- Form HTML -----
    ?>
      <div class="wrap">
        <h1>Add New TLN Campaign</h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'tln_new_campaign_action' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="title">Campaign Title</label></th>
                    <td><input name="title" id="title" type="text" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="description">Description (what the customer will see)</label></th>
                    <td><textarea name="description" id="description" rows="4" class="large-text" required></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="offer_text">Offer Text (short headline)</label></th>
                      <td><input name="offer_text" id="offer_text" type="text" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="valid_days">Offer valid days (before 7‑day grace)</label></th>
                    <td><input name="valid_days" id="valid_days" type="number" value="30" min="1" required></td>
                </tr>
            </table>

            <p class="submit"><input type="submit" name="tln_new_campaign" id="submit" class="button button-primary" value="Create Campaign"></p>
        </form>
    </div>
    <?php
}
