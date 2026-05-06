<?php
/**
 * Plugin Name: TLN Business Dashboard
 * Version: 2.0
 */

if (!defined('ABSPATH')) exit;

function tln_dashboard_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="/wp-login.php">log in</a> to view your dashboard.</p>';
    }
    
    $user_id = get_current_user_id();
    global $wpdb;
    
    // Check if user has claimed any business
    $claim = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_claims WHERE user_id=%d AND status='approved'", $user_id));
    
    if (!$claim) {
        return '<div style="padding:2rem;background:#f8f8f8;border-radius:12px;text-align:center;">
            <h2>No Business Claimed</h2>
            <p>You haven\'t claimed any businesses yet.</p>
            <a href="/directory/" style="background:#7cda24;color:#fff;padding:1rem 2rem;text-decoration:none;border-radius:8px;">Browse Directory</a>
        </div>';
    }
    
    // Get current tier
    $tier = $claim->tier ?? 'free';
    
    ob_start();
    ?>
    <div class="tln-dash" style="max-width:900px;margin:0 auto;">
        <h2 style="margin-bottom:1rem;">📊 <?php echo esc_html($claim->business_name); ?> Dashboard</h2>
        
        <?php if($tier == 'free'): ?>
        <div style="background:#fff3cd;padding:1rem;border-radius:8px;margin-bottom:1rem;">
            <strong>Upgrade to Pro</strong> to add custom offers, photo gallery, and more.
            <div style="margin-top:1rem;">
                <stripe-buy-button buy-button-id="buy_btn_1TU98uBVjZYuZR8RZDcJUHkX" publishable-key="pk_live_51QDniFBVjZYuZR8RJvxA0b06ETZBhaPA6N3MNDztpX8HYSlNNyvbLDvCaTBCxBwULnPrUCmuqSOz4JJ5g83mKz8F00Vq5BABDb"></stripe-buy-button>
            </div>
        </div>
        <?php elseif($tier == 'pro'): ?>
        <div style="background:#d4edda;padding:1rem;border-radius:8px;margin-bottom:1rem;">
            <strong>✓ Pro Member</strong> — <a href="#" id="tln-upgrade-plus">Upgrade to Pro+</a> for video upload, featured placement, AI optimization
        </div>
        <?php endif; ?>
        
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem;margin-bottom:2rem;">
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:2px solid #1a1a1a;">
                <h3 style="margin-top:0;">Business Info</h3>
                <p><strong>Name:</strong> <?php echo esc_html($claim->business_name); ?></p>
                <p><strong>Status:</strong> ✓ Verified</p>
                <p><strong>Claimed:</strong> <?php echo esc_html($claim->tos_signed_date); ?></p>
            </div>
            
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:2px solid #1a1a1a;">
                <h3 style="margin-top:0;">Offers</h3>
                <?php if(!empty($claim->custom_offer)): ?>
                <p>🎁 <?php echo esc_html($claim->custom_offer); ?></p>
                <?php else: ?>
                <p style="color:#666;">No offers yet. <a href="#">Add one</a></p>
                <?php endif; ?>
            </div>
            
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:2px solid #1a1a1a;">
                <h3 style="margin-top:0;">Analytics</h3>
                <p style="color:#666;">Coming soon</p>
            </div>
        </div>
        
        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:2px solid #1a1a1a;">
            <h3 style="margin-top:0;">Photo Gallery</h3>
            <p style="color:#666;"><?php echo $tier == 'free' ? 'Upgrade to Pro to add photos' : 'Photo gallery coming soon'; ?></p>
        </div>
    </div>
    <script async src="https://js.stripe.com/v3/buy-button.js"></script>
    <?php
    return ob_get_clean();
}
add_shortcode('tln_dashboard', 'tln_dashboard_shortcode');
