<?php
/*
 * TLN Voucher System
 * Handles QR redirect, claim flow, voucher generation, validation, and dashboard.
 */

if (!defined('ABSPATH')) exit;

/**
 * Activation hook – create custom tables.
 */
function tln_voucher_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $tables = [];
    // Table to store each voucher claim
    $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_vouchers (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        campaign_id BIGINT(20) UNSIGNED NOT NULL,
        business_id BIGINT(20) UNSIGNED NOT NULL,
        lead_name VARCHAR(255) NOT NULL,
        lead_email VARCHAR(255) NOT NULL,
        lead_phone VARCHAR(50) NOT NULL,
        code VARCHAR(32) NOT NULL,
        expires DATETIME NOT NULL,
        redeemed TINYINT(1) DEFAULT 0,
        redeemed_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY code (code)
    ) $charset_collate;";

    // Table to store campaigns (basic info)
    $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_campaigns (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        business_id BIGINT(20) UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        offer_text VARCHAR(255) NULL,
        offer_valid_days INT NOT NULL DEFAULT 30,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Table to track QR scans
    $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_qr_scans (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        campaign_id BIGINT(20) UNSIGNED NOT NULL,
        scanned_at DATETIME NOT NULL,
        source VARCHAR(50) DEFAULT 'postcard',
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'tln_voucher_activate');

/**
 * Register rewrite rule for dynamic QR redirects.
 */
function tln_voucher_add_rewrite_rules() {
    add_rewrite_rule('^r/([a-zA-Z0-9_-]+)/?$', 'index.php?tln_voucher_redirect=$matches[1]', 'top');
}
add_action('init', 'tln_voucher_add_rewrite_rules');

/**
 * Add query var for the redirect.
 */
function tln_voucher_query_vars($vars) {
    $vars[] = 'tln_voucher_redirect';
    return $vars;
}
add_filter('query_vars', 'tln_voucher_query_vars');

/**
 * Handle redirect earlier - using parse_request to catch before main query
 */
function tln_voucher_parse_request($wp) {
    if (!empty($wp->query_vars['tln_voucher_redirect'])) {
        $code = $wp->query_vars['tln_voucher_redirect'];
        tln_process_voucher_redirect($code);
    }
    return $wp;
}
add_filter('parse_request', 'tln_voucher_parse_request');

/**
 * Process the redirect logic - show offer inline instead of redirecting
 */
function tln_process_voucher_redirect($code) {
    global $wpdb;
    
    // First try to find a campaign with this ID (numeric).
    if (is_numeric($code)) {
        $campaign = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tln_campaigns WHERE id = %d",
            $code
        ));
        if ($campaign) {
            // Log the scan
            $wpdb->insert(
                $wpdb->prefix . 'tln_qr_scans',
                array(
                    'campaign_id' => $campaign->id,
                    'scanned_at'  => current_time('mysql'),
                    'source'      => 'postcard'
                ),
                array('%d', '%s', '%s')
            );
            // Instead of redirecting, show the offer directly
            echo tln_render_campaign_offer($campaign);
            exit;
        }
    }
    // If not a campaign, try a voucher code.
    $voucher = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tln_vouchers WHERE code = %s",
        $code
    ));
    if ($voucher) {
        echo tln_render_redeem_page($voucher);
        exit;
    }
    // Fallback – show error
    echo '<div style="padding:2rem;text-align:center;"><h2>Offer Not Found</h2><p>This offer may have expired or been removed.</p><p><a href="/">Go Home</a></p></div>';
    exit;
}

/**
 * Render campaign offer inline
 */
function tln_render_campaign_offer($campaign) {
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
    
    // Generate session-based code for claiming
    if (!session_id()) {
        session_start();
    }
    $session_key = 'tln_offer_code_' . $campaign->id;
    if (!isset($_SESSION[$session_key])) {
        $title = $campaign->title ?? 'OFFER';
        $_SESSION[$session_key] = strtoupper(substr($title, 0, 3)) . '-' . $campaign->id . '-' . substr(md5(uniqid()), 0, 4);
    }
    $offer_code = $_SESSION[$session_key];
    
    $out = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html($campaign->title) . ' - The Local NearBuy</title></head><body>';
    $out .= '<div style="max-width:480px;margin:0 auto;padding:1.5rem;font-family:system-ui,sans-serif;background:#f5f5f5;min-height:100vh;">';
    $out .= '<div style="text-align:center;margin-bottom:2rem;">';
    $out .= '<img src="https://thelocalnearbuy.com/wp-content/uploads/2026/01/TLN-logo-V1.png" alt="TLN" style="max-width:180px;margin-bottom:1rem;">';
    $out .= '<p style="color:#666;font-size:0.9rem;">The Local NearBuy Exclusive Offer</p>';
    $out .= '</div>';
    $out .= '<div style="background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);overflow:hidden;">';
    $out .= '<div style="background:#1a73e8;padding:1.5rem;text-align:center;">';
    $out .= '<h2 style="margin:0;color:#fff;font-size:1.5rem;">' . esc_html($campaign->offer_text ?: $campaign->title) . '</h2>';
    $out .= '</div>';
    $out .= '<div style="padding:1.5rem;text-align:center;">';
    $out .= '<p style="font-size:1.1rem;line-height:1.6;color:#333;margin-bottom:1.5rem;">' . wp_kses_post($campaign->description) . '</p>';
    $out .= '<div style="background:#f8f8f8;border:2px dashed #ccc;border-radius:8px;padding:1rem;margin:1.5rem 0;">';
    $out .= '<p style="margin:0 0 0.5rem;font-size:0.85rem;color:#666;text-transform:uppercase;">Your Code</p>';
    $out .= '<p style="margin:0;font-size:1.8rem;font-weight:bold;letter-spacing:2px;color:#1a73e8;">' . esc_html($offer_code) . '</p>';
    $out .= '</div>';
    $out .= '<p style="font-size:0.9rem;color:#666;">Valid for <strong>' . $days_left . ' days</strong></p>';
    $out .= '</div></div>';
    $out .= '<p style="text-align:center;margin-top:1.5rem;font-size:0.8rem;color:#999;">Powered by <a href="https://thelocalnearbuy.com" style="color:#1a73e8;">The Local NearBuy</a></p>';
    $out .= '</div></body></html>';
    return $out;
}

/**
 * Render redemption page inline
 */
function tln_render_redeem_page($voucher) {
    $now = current_time('timestamp');
    $expire_ts = strtotime($voucher->expires);
    $seconds = $expire_ts - $now;
    
    if ($voucher->redeemed) {
        return '<div style="padding:2rem;text-align:center;"><h2>Already Redeemed</h2><p>This code has been used.</p></div>';
    } elseif ($seconds < 0) {
        return '<div style="padding:2rem;text-align:center;"><h2>Expired</h2><p>This code has expired.</p></div>';
    }
    
    $qr_src = 'https://quickchart.io/qr?size=200x200&text=' . urlencode($voucher->code);
    $out = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Redeem - The Local NearBuy</title></head><body>';
    $out .= '<div style="max-width:480px;margin:0 auto;padding:1.5rem;font-family:system-ui,sans-serif;background:#f5f5f5;min-height:100vh;">';
    $out .= '<div style="text-align:center;">';
    $out .= '<h2>Redeem Your Offer</h2>';
    $out .= '<p>Show this QR code to the staff.</p>';
    $out .= '<img src="' . esc_url($qr_src) . '" alt="QR" style="max-width:200px;border:1px solid #ddd;border-radius:8px;padding:10px;background:#fff;" />';
    $out .= '<p style="font-size:1.2rem;margin:1rem 0;"><strong>' . esc_html($voucher->code) . '</strong></p>';
    $out .= '<p id="countdown" style="color:#666;"></p>';
    $out .= '</div></div>';
    $out .= '<script>var sec=' . $seconds . ';function c(){var el=document.getElementById("countdown");if(sec<=0){el.innerHTML="Expired";return;}var d=Math.floor(sec/86400);var h=Math.floor((sec%86400)/3600);var m=Math.floor((sec%3600)/60);var s=sec%60;el.innerHTML=d+"d "+h+"h "+m+"m "+s+"s";sec--;setTimeout(c,1000);}c();</script>';
    $out .= '</body></html>';
    return $out;
}

/**
 * Handle the redirect on template_redirect (fallback).
 */
function tln_voucher_handle_redirect() {
    $code = get_query_var('tln_voucher_redirect');
    if (!$code) return;
    tln_process_voucher_redirect($code);
}
add_action('template_redirect', 'tln_voucher_handle_redirect');

/**
 * Shortcode: [tln_claim_offer] – renders claim form for a campaign.
 */
function tln_claim_offer_shortcode($atts) {
    $atts = shortcode_atts(['cid' => ''], $atts);
    $cid = intval($atts['cid']);
    if (!$cid && isset($_GET['cid'])) $cid = intval($_GET['cid']);
    if (!$cid) return '<p>Invalid campaign.</p>';

    global $wpdb;
    $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_campaigns WHERE id = %d", $cid));
    if (!$campaign) return '<p>Campaign not found.</p>';

    // Process submission
    if (isset($_POST['tln_claim_submit'])) {
        $lead_name = sanitize_text_field($_POST['lead_name']);
        $lead_email = sanitize_email($_POST['lead_email']);
        $lead_phone = sanitize_text_field($_POST['lead_phone']);
        // Generate a unique code
        $code = strtoupper(wp_generate_password(8, false));
        // Calculate expiration (offer_valid_days + 7 day grace)
        $expires = date('Y-m-d H:i:s', strtotime("+" . ($campaign->offer_valid_days + 7) . " days"));
        // Insert voucher record
        $wpdb->insert(
            $wpdb->prefix . 'tln_vouchers',
            [
                'campaign_id' => $campaign->id,
                'business_id' => $campaign->business_id,
                'lead_name' => $lead_name,
                'lead_email' => $lead_email,
                'lead_phone' => $lead_phone,
                'code' => $code,
                'expires' => $expires,
                'redeemed' => 0,
            ],
            ['%d','%d','%s','%s','%s','%s','%s','%d']
        );
        // Show the redemption QR code.
        $redeem_url = home_url('/tln-redeem?code=' . $code);
        $qr_src = 'https://quickchart.io/qr?size=200x200&text=' . urlencode($redeem_url);
        $output = '<h3>Offer Claimed!</h3>';
        $output .= '<p>Show this QR code at the business to redeem your offer. It expires on ' . date('M j, Y', strtotime($expires)) . '.</p>';
        $output .= '<img src="' . esc_url($qr_src) . '" alt="Redemption QR" style="max-width:200px;" />';
        return $output;
    }

    // Render claim form
    $form = '<h3>' . esc_html($campaign->title) . '</h3>';
    $form .= '<p>' . esc_html($campaign->description) . '</p>';
    $form .= '<form method="post">';
    $form .= '<p><label>Name:<br><input type="text" name="lead_name" required></label></p>';
    $form .= '<p><label>Email:<br><input type="email" name="lead_email" required></label></p>';
    $form .= '<p><label>Phone:<br><input type="text" name="lead_phone" required></label></p>';
    $form .= '<p><label><input type="checkbox" name="opt_in" required> I agree to receive offers from this business.</label></p>';
    $form .= '<p><button type="submit" name="tln_claim_submit">Claim Offer</button></p>';
    $form .= '</form>';
    return $form;
}
add_shortcode('tln_claim_offer', 'tln_claim_offer_shortcode');

/**
 * Shortcode: [tln_redeem] – shows the redemption QR and countdown.
 */
function tln_redeem_shortcode($atts) {
    $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
    if (!$code) return '<p>Missing redemption code.</p>';
    global $wpdb;
    $voucher = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_vouchers WHERE code = %s", $code));
    if (!$voucher) return '<p>Invalid code.</p>';
    // If already redeemed
    if ($voucher->redeemed) {
        return '<p>This code has already been redeemed.</p>';
    }
    // Countdown timer (JS)
    $now = current_time('timestamp');
    $expire_ts = strtotime($voucher->expires);
    $seconds = $expire_ts - $now;
    if ($seconds < 0) {
        return '<p>This code has expired.</p>';
    }
    $qr_src = 'https://quickchart.io/qr?size=200x200&text=' . urlencode($code);
    $output = '<h3>Redeem Your Offer</h3>';
    $output .= '<p>Show this QR code to the staff. Expires in <span id="tln-countdown"></span>.</p>';
    $output .= '<img src="' . esc_url($qr_src) . '" alt="Redemption QR" style="max-width:200px;" />';
    $output .= '<script>function tlnCountdown(){var sec=' . $seconds . ';var el=document.getElementById("tln-countdown");if(sec<=0){el.innerHTML="expired";return;}var d=Math.floor(sec/86400);var h=Math.floor((sec%86400)/3600);var m=Math.floor((sec%3600)/60);var s=sec%60;el.innerHTML=d+"d "+h+"h "+m+"m "+s+"s";sec--;setTimeout(tlnCountdown,1000);}tlnCountdown();</script>';
    return $output;
}
add_shortcode('tln_redeem', 'tln_redeem_shortcode');

/**
 * Shortcode: [tln_business_dashboard] – shows stats for logged‑in business.
 */
function tln_business_dashboard_shortcode($atts) {
    if (!is_user_logged_in()) return '<p>Please log in to view your dashboard.</p>';
    $user_id = get_current_user_id();
    global $wpdb;
    $campaigns = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_campaigns WHERE business_id = %d", $user_id));
    if (!$campaigns) return '<p>No campaigns found.</p>';
    $output = '<h3>Your Campaign Dashboard</h3>';
    foreach ($campaigns as $c) {
        $scans = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tln_qr_scans WHERE campaign_id = %d", $c->id));
        $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tln_vouchers WHERE campaign_id = %d", $c->id));
        $redeemed = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tln_vouchers WHERE campaign_id = %d AND redeemed = 1", $c->id));
        $output .= '<div style="border:1px solid #ddd;padding:10px;margin-bottom:10px;">';
        $output .= '<h4>' . esc_html($c->title) . '</h4>';
        $output .= '<p><strong>QR Scans:</strong> ' . intval($scans) . '</p>';
        $output .= '<p><strong>Leads captured:</strong> ' . intval($total) . '</p>';
        $output .= '<p><strong>Offers redeemed:</strong> ' . intval($redeemed) . '</p>';
        
        // Show redeemed vouchers with dates
        $redeemed_vouchers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tln_vouchers WHERE campaign_id = %d AND redeemed = 1 ORDER BY redeemed_at DESC",
            $c->id
        ));
        if ($redeemed_vouchers) {
            $output .= '<p><strong>Redemption History:</strong></p>';
            $output .= '<table style="width:100%;border-collapse:collapse;font-size:14px;">';
            $output .= '<tr style="background:#f5f5f5;"><th style="padding:5px;border:1px solid #ddd;">Customer</th><th style="padding:5px;border:1px solid #ddd;">Code</th><th style="padding:5px;border:1px solid #ddd;">Redeemed</th></tr>';
            foreach ($redeemed_vouchers as $v) {
                $output .= '<tr>';
                $output .= '<td style="padding:5px;border:1px solid #ddd;">' . esc_html($v->lead_name) . '</td>';
                $output .= '<td style="padding:5px;border:1px solid #ddd;">' . esc_html($v->code) . '</td>';
                $output .= '<td style="padding:5px;border:1px solid #ddd;">' . esc_html(date('M j, Y g:iA', strtotime($v->redeemed_at))) . '</td>';
                $output .= '</tr>';
            }
            $output .= '</table>';
        }
        $output .= '</div>';
    }
    return $output;
}
add_shortcode('tln_business_dashboard', 'tln_business_dashboard_shortcode');

/**
 * Endpoint for staff to validate a redemption code (simple POST).
 */
function tln_validate_endpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $code = sanitize_text_field($_POST['code'] ?? '');
    if (!$code) wp_send_json_error(['msg' => 'Missing code']);
    global $wpdb;
    $voucher = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_vouchers WHERE code = %s", $code));
    if (!$voucher) wp_send_json_error(['msg' => 'Invalid code']);
    if ($voucher->redeemed) wp_send_json_error(['msg' => 'Already redeemed']);
    // Mark as redeemed
    $wpdb->update(
        $wpdb->prefix . 'tln_vouchers',
        ['redeemed' => 1, 'redeemed_at' => current_time('mysql')],
        ['id' => $voucher->id],
        ['%d','%s'],
        ['%d']
    );
    wp_send_json_success(['msg' => 'Code validated and redeemed']);
}
add_action('wp_ajax_nopriv_tln_validate', 'tln_validate_endpoint');
add_action('wp_ajax_tln_validate', 'tln_validate_endpoint');
?>