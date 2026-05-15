<?php
/**
 * TLN Offer Landing Page
 * Handles /r/{campaign_id} QR code redirects
 */

if (!defined('ABSPATH')) exit;

// Shortcode to display the offer
add_shortcode('tln_offer', 'tln_offer_shortcode');

function tln_offer_shortcode() {
    global $wpdb;
    
    // Get campaign ID from URL
    $campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$campaign_id) {
        return '<div style="padding:2rem;text-align:center;"><h2>Invalid Offer</h2><p>This offer link is invalid.</p></div>';
    }
    
    // Fetch campaign data
    $table = $wpdb->prefix . 'tln_campaigns';
    $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $campaign_id));
    
    if (!$campaign) {
        return '<div style="padding:2rem;text-align:center;"><h2>Offer Not Found</h2><p>This offer may have expired or been removed.</p></div>';
    }
    
    // Check if offer has expired (valid days + 7 day grace)
    $valid_days = intval($campaign->offer_valid_days);
    $grace_days = 7;
    $total_days = $valid_days + $grace_days;
    $created = strtotime($campaign->created_at);
    $expires = $created + ($total_days * 24 * 60 * 60);
    $now = current_time('timestamp');
    $days_left = ceil(($expires - $now) / (24 * 60 * 60));
    
    if ($days_left <= 0) {
        return '<div style="padding:2rem;text-align:center;"><h2>Offer Expired</h2><p>This offer is no longer valid.</p></div>';
    }
    
    // Generate unique code for this visitor (session-based)
    if (!session_id()) {
        session_start();
    }
    $session_key = 'tln_offer_code_' . $campaign_id;
    if (!isset($_SESSION[$session_key])) {
        $_SESSION[$session_key] = strtoupper(substr($campaign->title, 0, 3)) . '-' . $campaign_id . '-' . substr(md5(uniqid()), 0, 4);
    }
    $offer_code = $_SESSION[$session_key];
    
    // Build the offer display
    $output = '<div class="tln-offer-container" style="max-width:480px;margin:0 auto;padding:1.5rem;font-family:system-ui,-apple-system,sans-serif;background:#f5f5f5;min-height:100vh;box-sizing:border-box;">';
    
    // Header
    $output .= '<div style="text-align:center;margin-bottom:2rem;">';
    $output .= '<img src="https://thelocalnearbuy.com/wp-content/uploads/2026/01/TLN-logo-V1.png" alt="The Local NearBuy" style="max-width:180px;margin-bottom:1rem;">';
    $output .= '<p style="color:#666;font-size:0.9rem;margin:0;">' . get_bloginfo('name') . ' Exclusive Offer</p>';
    $output .= '</div>';
    
    // Offer card
    $output .= '<div style="background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);overflow:hidden;">';
    $output .= '<div style="background:#1a73e8;padding:1.5rem;text-align:center;">';
    $output .= '<h2 style="margin:0;color:#fff;font-size:1.5rem;">' . esc_html($campaign->offer_text ?: $campaign->title) . '</h2>';
    $output .= '</div>';
    
    $output .= '<div style="padding:1.5rem;text-align:center;">';
    $output .= '<p style="font-size:1.1rem;line-height:1.6;color:#333;margin-bottom:1.5rem;">' . wp_kses_post($campaign->description) . '</p>';
    
    // The code
    $output .= '<div style="background:#f8f8f8;border:2px dashed #ccc;border-radius:8px;padding:1rem;margin:1.5rem 0;">';
    $output .= '<p style="margin:0 0 0.5rem;font-size:0.85rem;color:#666;text-transform:uppercase;letter-spacing:1px;">Your Code</p>';
    $output .= '<p style="margin:0;font-size:1.8rem;font-weight:bold;letter-spacing:2px;color:#1a73e8;">' . esc_html($offer_code) . '</p>';
    $output .= '</div>';
    
    // Countdown
    $output .= '<p style="font-size:0.9rem;color:#666;">⏰ Valid for <strong>' . $days_left . ' days</strong> (includes 7-day grace)</p>';
    
    // Business name if available
    if (!empty($campaign->business_id)) {
        $business = get_post($campaign->business_id);
        if ($business) {
            $output .= '<p style="margin-top:1rem;color:#888;font-size:0.9rem;">Redeem at: <strong>' . esc_html($business->post_title) . '</strong></p>';
        }
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    // Footer
    $output .= '<p style="text-align:center;margin-top:1.5rem;font-size:0.8rem;color:#999;">Powered by <a href="https://thelocalnearbuy.com" style="color:#1a73e8;">The Local NearBuy</a></p>';
    $output .= '</div>';
    
    return $output;
}

// Add rewrite rule for /r/{id} URLs
function tln_offer_rewrite() {
    add_rewrite_rule('^r/([0-9]+)/?$', 'index.php?tln_offer=1&id=$matches[1]', 'top');
}
add_action('init', 'tln_offer_rewrite');

// Flush rewrite rules on activation
function tln_offer_activate() {
    tln_offer_rewrite();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'tln_offer_activate');

function tln_offer_query_vars($vars) {
    $vars[] = 'tln_offer';
    return $vars;
}
add_filter('query_vars', 'tln_offer_query_vars');

function tln_offer_template($template) {
    if (get_query_var('tln_offer')) {
        return get_shortcode_template('[tln_offer]', 'tln-offer');
    }
    return $template;
}
add_action('template_redirect', 'tln_offer_template');

// Enqueue mobile-optimized styles
function tln_offer_scripts() {
    if (get_query_var('tln_offer')) {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">';
        echo '<style>
            body { margin:0; padding:0; background:#f5f5f5; -webkit-text-size-adjust:100%; }
            @media (max-width:480px) {
                .tln-offer-container { padding:1rem; }
            }
        </style>';
    }
}
add_action('wp_head', 'tln_offer_scripts');

function get_shortcode_template($shortcode, $slug) {
    // If we're on the offer page, just return the shortcode output
    if (get_query_var('tln_offer')) {
        return do_shortcode($shortcode);
    }
    return $template;
}