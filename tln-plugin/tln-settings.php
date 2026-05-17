<?php
// tln-plugin/tln-settings.php
if ( ! defined( 'ABSPATH' ) ) exit;

// =============================================================================
// TLN Settings Page - Stripe & General Config
// =============================================================================

/**
 * Register settings page.
 */
function tln_register_settings_page() {
    add_submenu_page(
        'tln-dashboard',
        'Settings',
        'Settings',
        'manage_options',
        'tln-settings',
        'tln_settings_page'
    );
}
add_action( 'admin_menu', 'tln_register_settings_page' );

/**
 * Register settings for WP Options.
 */
function tln_register_settings() {
    register_setting( 'tln_settings_group', 'tln_stripe_mode' );
    register_setting( 'tln_settings_group', 'tln_stripe_test_secret' );
    register_setting( 'tln_settings_group', 'tln_stripe_test_publishable' );
    register_setting( 'tln_settings_group', 'tln_stripe_live_secret' );
    register_setting( 'tln_settings_group', 'tln_stripe_live_publishable' );
    register_setting( 'tln_settings_group', 'tln_stripe_webhook_secret' );
}
add_action( 'admin_init', 'tln_register_settings' );

/**
 * Render settings page.
 */
function tln_settings_page() {
    $mode = get_option( 'tln_stripe_mode', 'test' );
    ?>
    <div class="wrap">
        <h1>TLN Settings</h1>

        <form method="post" action="options.php">
            <?php settings_fields( 'tln_settings_group' ); ?>
            <?php do_settings_sections( 'tln_settings_group' ); ?>

            <!-- Mode Toggle -->
            <div class="tln-card" style="max-width:800px;">
                <div class="tln-card-header">
                    <h2 class="tln-card-title">Stripe Mode</h2>
                    <span class="tln-badge <?php echo $mode === 'live' ? 'approved' : 'suggested'; ?>">
                        Currently: <?php echo strtoupper( $mode ); ?>
                    </span>
                </div>
                <p>Toggle between test and live mode. In live mode, real payments are processed.</p>
                <table class="form-table">
                    <tr>
                        <th scope="row">Mode</th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="tln_stripe_mode" value="test" <?php checked( $mode, 'test' ); ?>>
                                    <strong>Test Mode</strong> - Use test API keys (sandbox)
                                </label><br>
                                <label>
                                    <input type="radio" name="tln_stripe_mode" value="live" <?php checked( $mode, 'live' ); ?>>
                                    <strong>Live Mode</strong> - Use live API keys (production)
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Test Keys -->
            <div class="tln-card" style="max-width:800px;">
                <div class="tln-card-header">
                    <h2 class="tln-card-title">Test API Keys</h2>
                    <span class="tln-badge suggested">Stripe Dashboard</span>
                </div>
                <p>Get these from <a href="https://dashboard.stripe.com/test/apikeys" target="_blank">Stripe Dashboard → Test Keys</a></p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="tln_stripe_test_secret">Secret Key</label></th>
                        <td><input name="tln_stripe_test_secret" id="tln_stripe_test_secret" type="password" class="regular-text" value="<?php echo esc_attr( get_option( 'tln_stripe_test_secret' ) ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tln_stripe_test_publishable">Publishable Key</label></th>
                        <td><input name="tln_stripe_test_publishable" id="tln_stripe_test_publishable" type="text" class="regular-text" value="<?php echo esc_attr( get_option( 'tln_stripe_test_publishable' ) ); ?>"></td>
                    </tr>
                </table>
            </div>

            <!-- Live Keys -->
            <div class="tln-card" style="max-width:800px;">
                <div class="tln-card-header">
                    <h2 class="tln-card-title">Live API Keys</h2>
                    <span class="tln-badge approved">Production</span>
                </div>
                <p>Get these from <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripe Dashboard → Live Keys</a></p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="tln_stripe_live_secret">Secret Key</label></th>
                        <td><input name="tln_stripe_live_secret" id="tln_stripe_live_secret" type="password" class="regular-text" value="<?php echo esc_attr( get_option( 'tln_stripe_live_secret' ) ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tln_stripe_live_publishable">Publishable Key</label></th>
                        <td><input name="tln_stripe_live_publishable" id="tln_stripe_live_publishable" type="text" class="regular-text" value="<?php echo esc_attr( get_option( 'tln_stripe_live_publishable' ) ); ?>"></td>
                    </tr>
                </table>
            </div>

            <!-- Webhook -->
            <div class="tln-card" style="max-width:800px;">
                <div class="tln-card-header">
                    <h2 class="tln-card-title">Webhook Configuration</h2>
                </div>
                <p><strong>Webhook URL:</strong> <code><?php echo get_rest_url( null, 'tln/v1/stripe-webhook' ); ?></code></p>
                <p>Add this URL in your <a href="https://dashboard.stripe.com/webhooks" target="_blank">Stripe Webhooks</a> settings. Select all events.</p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="tln_stripe_webhook_secret">Webhook Secret</label></th>
                        <td><input name="tln_stripe_webhook_secret" id="tln_stripe_webhook_secret" type="password" class="regular-text" value="<?php echo esc_attr( get_option( 'tln_stripe_webhook_secret' ) ); ?>" placeholder="whsec_..."></td>
                    </tr>
                </table>
            </div>

            <?php submit_button( 'Save Settings' ); ?>
        </form>

        <hr>

        <div class="tln-card" style="max-width:800px;background:#fff3cd;">
            <h2 class="tln-card-title">🚀 Going Live Checklist</h2>
            <ul style="margin:0;padding-left:20px;">
                <li>✅ Test with test keys first</li>
                <li>✅ Fill in your live API keys above</li>
                <li>✅ Set mode to "Live"</li>
                <li>✅ Add webhook URL in Stripe (live dashboard)</li>
                <li>✅ Copy the webhook secret to the field above</li>
                <li>✅ Test a real payment</li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Helper: Get active Stripe keys based on mode.
 */
function tln_get_stripe_keys() {
    $mode = get_option( 'tln_stripe_mode', 'test' );
    if ( $mode === 'live' ) {
        return array(
            'secret'      => get_option( 'tln_stripe_live_secret' ),
            'publishable' => get_option( 'tln_stripe_live_publishable' ),
            'mode'        => 'live'
        );
    }
    return array(
        'secret'      => get_option( 'tln_stripe_test_secret' ),
        'publishable' => get_option( 'tln_stripe_test_publishable' ),
        'mode'        => 'test'
    );
}

/**
 * Helper: Get webhook secret.
 */
function tln_get_stripe_webhook_secret() {
    // Check constant first (backward compat)
    if ( defined( 'TLN_STRIPE_WEBHOOK_SECRET' ) ) {
        return TLN_STRIPE_WEBHOOK_SECRET;
    }
    return get_option( 'tln_stripe_webhook_secret', '' );
}