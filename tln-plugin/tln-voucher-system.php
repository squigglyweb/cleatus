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
        source VARCHAR(50) DEFAULT 'postcard',
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
            // Get source from URL param
            $scan_source = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : 'postcard';
            if (!in_array($scan_source, ['postcard', 'directory', 'newsletter'])) $scan_source = 'postcard';
            
            // Log the scan
            $wpdb->insert(
                $wpdb->prefix . 'tln_qr_scans',
                array(
                    'campaign_id' => $campaign->id,
                    'scanned_at'  => current_time('mysql'),
                    'source'      => $scan_source
                ),
                array('%d', '%s', '%s')
            );
            // Instead of redirecting, show the offer directly
            echo tln_render_campaign_offer($campaign);
            exit;
        }
    }
    
    // Try campaign_code (slug) - e.g., "pizza-hut-ABC"
    $campaign = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tln_campaigns WHERE campaign_code = %s",
        sanitize_title($code)
    ));
    if ($campaign) {
        $scan_source = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : 'postcard';
        if (!in_array($scan_source, ['postcard', 'directory', 'newsletter'])) $scan_source = 'postcard';
        
        $wpdb->insert(
            $wpdb->prefix . 'tln_qr_scans',
            array(
                'campaign_id' => $campaign->id,
                'scanned_at'  => current_time('mysql'),
                'source'      => $scan_source
            ),
            array('%d', '%s', '%s')
        );
        echo tln_render_campaign_offer($campaign);
        exit;
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
 * Render campaign offer inline - with lead capture before showing code
 */
function tln_render_campaign_offer($campaign) {
    global $wpdb;
    
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
    
    // Check if already claimed in this session
    if (!session_id()) session_start();
    $claim_key = 'tln_claimed_' . $campaign->id;
    $claimed_code = isset($_SESSION[$claim_key]) ? $_SESSION[$claim_key] : null;
    
    // Handle form submission - create voucher
    if (isset($_POST['tln_claim_submit']) && !$claimed_code) {
        $lead_name = sanitize_text_field($_POST['lead_name']);
        $lead_email = sanitize_email($_POST['lead_email']);
        $lead_phone = sanitize_text_field($_POST['lead_phone']);
        $opt_in = isset($_POST['opt_in']) ? 1 : 0;
        
        if ($lead_name && $lead_email) {
            // Get source from URL param (postcard, directory, newsletter)
            $source = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : 'postcard';
            if (!in_array($source, ['postcard', 'directory', 'newsletter'])) $source = 'postcard';
            
            // Generate unique code
            $code = strtoupper(wp_generate_password(8, false));
            $expires_date = date('Y-m-d H:i:s', strtotime("+" . ($valid_days + $grace_days) . " days"));
            
            $wpdb->insert(
                $wpdb->prefix . 'tln_vouchers',
                [
                    'campaign_id' => $campaign->id,
                    'business_id' => $campaign->business_id,
                    'lead_name' => $lead_name,
                    'lead_email' => $lead_email,
                    'lead_phone' => $lead_phone,
                    'code' => $code,
                    'source' => $source,
                    'expires' => $expires_date,
                    'redeemed' => 0,
                ],
                ['%d','%d','%s','%s','%s','%s','%s','%s','%d']
            );
            
            $_SESSION[$claim_key] = $code;
            $claimed_code = $code;
        }
    }
    
    // Determine status dot color
    if ($days_left <= 0) {
        $status_color = '#999';
        $status_text = 'Expired';
        $blink = '';
    } elseif ($days_left <= 7) {
        $status_color = '#dc3545';
        $status_text = 'Expiring Soon';
        $blink = 'animation: blink-red 1s infinite;';
    } elseif ($days_left <= 10) {
        $status_color = '#ffc107';
        $status_text = 'Active';
        $blink = 'animation: blink-yellow 1.5s infinite;';
    } else {
        $status_color = '#28a745';
        $status_text = 'Active';
        $blink = 'animation: blink-green 2s infinite;';
    }
    
    // Build output
    $out = '<!DOCTYPE html><html><head>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>' . esc_html($campaign->title) . ' - The Local NearBuy</title>
    <style>
        @keyframes blink-green { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        @keyframes blink-yellow { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        @keyframes blink-red { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
    </head><body>';
    $out .= '<div style="max-width:480px;margin:0 auto;padding:1.5rem;font-family:system-ui,sans-serif;background:#f5f5f5;min-height:100vh;">';
    $out .= '<div style="text-align:center;margin-bottom:2rem;">
    <p style="color:#666;font-size:0.9rem;">The Local NearBuy Exclusive Offer</p>
    </div>';
    $out .= '<div style="background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);overflow:hidden;">';
    $out .= '<div style="background:#28a745;padding:1.5rem;text-align:center;">';
    $out .= '<h2 style="margin:0;color:#fff;font-size:1.5rem;">' . esc_html($campaign->offer_text ?: $campaign->title) . '</h2>';
    $out .= '</div>';
    $out .= '<div style="padding:1.5rem;text-align:center;">';
    $out .= '<p style="font-size:1.1rem;line-height:1.6;color:#333;margin-bottom:1.5rem;">' . wp_kses_post($campaign->description) . '</p>';
    
    // Status indicator
    $out .= '<p style="margin-bottom:1rem;"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:' . $status_color . ';' . $blink . '"></span> <span style="color:' . $status_color . ';">' . $status_text . '</span> - ' . $days_left . ' days remaining</p>';
    
    // If not claimed yet, show form
    if (!$claimed_code) {
        $out .= '<form method="post" style="text-align:left;max-width:320px;margin:0 auto;">';
        $out .= '<p style="margin-bottom:1rem;"><label style="display:block;margin-bottom:0.5rem;font-weight:600;">Your Name</label><input type="text" name="lead_name" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:8px;font-size:1rem;"></p>';
        $out .= '<p style="margin-bottom:1rem;"><label style="display:block;margin-bottom:0.5rem;font-weight:600;">Email</label><input type="email" name="lead_email" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:8px;font-size:1rem;"></p>';
        $out .= '<p style="margin-bottom:1rem;"><label style="display:block;margin-bottom:0.5rem;font-weight:600;">Phone (optional)</label><input type="tel" name="lead_phone" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:8px;font-size:1rem;"></p>';
        $out .= '<p style="margin-bottom:1rem;"><label style="display:flex;align-items:start;gap:0.5rem;cursor:pointer;"><input type="checkbox" name="opt_in" required style="margin-top:0.25rem;"><span style="font-size:0.9rem;color:#666;">Send me reminders and special offers from this business</span></label></p>';
        $out .= '<button type="submit" name="tln_claim_submit" style="width:100%;padding:1rem;background:#28a745;color:#fff;border:none;border-radius:8px;font-size:1.1rem;font-weight:600;cursor:pointer;">Get My Offer Code</button>';
        $out .= '</form>';
    } else {
        // Already claimed - show code and QR
        $qr_src = 'https://quickchart.io/qr?size=200x200&text=' . urlencode(home_url('/tln-redeem?code=' . $claimed_code . '&business=1'));
        $out .= '<div style="background:#f8f8f8;border:2px dashed #ccc;border-radius:8px;padding:1rem;margin:1.5rem 0;">';
        $out .= '<p style="margin:0 0 0.5rem;font-size:0.85rem;color:#666;text-transform:uppercase;">Your Code</p>';
        $out .= '<p style="margin:0;font-size:1.8rem;font-weight:bold;letter-spacing:2px;color:#28a745;">' . esc_html($claimed_code) . '</p>';
        $out .= '<img src="' . esc_url($qr_src) . '" alt="QR Code" style="max-width:180px;margin-top:1rem;border:1px solid #ddd;border-radius:8px;padding:10px;background:#fff;" />';
        $out .= '<p style="margin-top:0.75rem;font-size:0.8rem;color:#666;"><strong>Show this BEFORE ordering or service —</strong> this lets the business know you have a special NearBuy offer.</p>';
        $out .= '</div>';
    }
    
    $out .= '</div></div>';
    $out .= '<p style="text-align:center;margin-top:1.5rem;font-size:0.8rem;color:#999;">Powered by <a href="https://thelocalnearbuy.com" style="color:#28a745;">The Local NearBuy</a></p>';
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
    
    $qr_src = 'https://quickchart.io/qr?size=200x200&text=' . urlencode(home_url('/tln-redeem?code=' . $voucher->code . '&business=1'));
    $out = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Redeem - The Local NearBuy</title></head><body>';
    $out .= '<div style="max-width:480px;margin:0 auto;padding:1.5rem;font-family:system-ui,sans-serif;background:#f5f5f5;min-height:100vh;">';
    $out .= '<div style="text-align:center;">';
    $out .= '<h2>Redeem Your Offer</h2>';
    $out .= '<p><strong>Show this BEFORE ordering or service —</strong> this lets the business know you have a special NearBuy offer.</p>';
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
        $redeem_url = home_url('/tln-redeem?code=' . $code . '&business=1');
        $qr_src = 'https://quickchart.io/qr?size=200x200&text=' . urlencode($redeem_url);
        $output = '<h3>Offer Claimed!</h3>';
        $output .= '<p><strong>Show this BEFORE ordering or service —</strong> this lets the business know you have a special NearBuy offer. It expires on ' . date('M j, Y', strtotime($expires)) . '.</p>';
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
    $business_mode = isset($_GET['business']);
    if (!$code) return '<p>Missing redemption code.</p>';
    global $wpdb;
    $voucher = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_vouchers WHERE code = %s", $code));
    if (!$voucher) return '<p>Invalid code.</p>';
    
    // Handle business validation
    if ($business_mode && isset($_POST['tln_validate'])) {
        $wpdb->update(
            $wpdb->prefix . 'tln_vouchers',
            ['redeemed' => 1, 'redeemed_at' => current_time('mysql')],
            ['id' => $voucher->id],
            ['%d', '%s'],
            ['%d']
        );
        $voucher->redeemed = 1;
    }
    
    // If already redeemed
    if ($voucher->redeemed) {
        return '<div style="padding:2rem;text-align:center;background:#d4edda;border-radius:12px;"><h2 style="color:#155724;margin-top:0;">✓ Already Redeemed</h2><p>This voucher has been used.</p></div>';
    }
    
    // Countdown timer (JS)
    $now = current_time('timestamp');
    $expire_ts = strtotime($voucher->expires);
    $seconds = $expire_ts - $now;
    if ($seconds < 0) {
        return '<div style="padding:2rem;text-align:center;background:#f8d7da;border-radius:12px;"><h2 style="color:#721c24;margin-top:0;">Expired</h2><p>This code has expired.</p></div>';
    }
    
    // BUSINESS MODE - show validation button
    if ($business_mode) {
        $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_campaigns WHERE id = %d", $voucher->campaign_id));
        $output = '<div style="max-width:400px;margin:0 auto;padding:2rem;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);">';
        $output .= '<h2 style="color:#28a745;margin-top:0;">Validate Voucher</h2>';
        $output .= '<div style="background:#f8f9fa;padding:1rem;border-radius:8px;margin:1rem 0;">';
        $output .= '<p style="margin:0 0 0.5rem;font-size:0.9rem;color:#666;">Customer:</p>';
        $output .= '<p style="margin:0;font-size:1.2rem;font-weight:bold;">' . esc_html($voucher->lead_name) . '</p>';
        $output .= '</div>';
        $output .= '<div style="background:#f8f9fa;padding:1rem;border-radius:8px;margin:1rem 0;">';
        $output .= '<p style="margin:0 0 0.5rem;font-size:0.9rem;color:#666;">Offer:</p>';
        $output .= '<p style="margin:0;font-size:1.2rem;font-weight:bold;">' . esc_html($campaign ? $campaign->title : 'N/A') . '</p>';
        $output .= '</div>';
        $output .= '<div style="background:#f8f9fa;padding:1rem;border-radius:8px;margin:1rem 0;">';
        $output .= '<p style="margin:0 0 0.5rem;font-size:0.9rem;color:#666;">Code:</p>';
        $output .= '<p style="margin:0;font-size:1.5rem;font-weight:bold;letter-spacing:2px;color:#28a745;">' . esc_html($voucher->code) . '</p>';
        $output .= '</div>';
        $output .= '<form method="post">';
        $output .= '<button type="submit" name="tln_validate" style="width:100%;padding:1rem;background:#28a745;color:#fff;border:none;border-radius:8px;font-size:1.2rem;font-weight:bold;cursor:pointer;">✓ Redeem This Voucher</button>';
        $output .= '</form>';
        $output .= '<p style="text-align:center;margin-top:1rem;color:#666;font-size:0.9rem;">NearBuy Validation</p>';
        $output .= '</div>';
        return $output;
    }
    
    // CUSTOMER MODE - show their voucher
    $qr_src = 'https://quickchart.io/qr?size=200x200&text=' . urlencode(home_url('/tln-redeem?code=' . $code . '&business=1'));
    $output = '<h3>Redeem Your Offer</h3>';
    $output .= '<p><p><strong>Show this BEFORE ordering or service —</strong> this lets the business know you have a special NearBuy offer. Expires in <span id="tln-countdown"></span>.</p>';
    $output .= '<img src="' . esc_url($qr_src) . '" alt="Redemption QR" style="max-width:200px;" />';
    $output .= '<script>function tlnCountdown(){var sec=' . $seconds . ';var el=document.getElementById("tln-countdown");if(sec<=0){el.innerHTML="expired";return;}var d=Math.floor(sec/86400);var h=Math.floor((sec%86400)/3600);var m=Math.floor((sec%3600)/60);var s=sec%60;el.innerHTML=d+"d "+h+"h "+m+"m "+s+"s";sec--;setTimeout(tlnCountdown,1000);}tlnCountdown();</script>';
    return $output;
}
add_shortcode('tln_redeem', 'tln_redeem_shortcode');

/**
 * Shortcode: [tln_business_dashboard] – shows stats for logged‑in business + validation form
 */
function tln_business_dashboard_shortcode($atts) {
    if (!is_user_logged_in()) return '<p>Please log in to view your dashboard.</p>';
    $user_id = get_current_user_id();
    global $wpdb;
    
    // Handle code validation
    $validation_msg = '';
    if (isset($_POST['tln_validate_code'])) {
        $code = sanitize_text_field($_POST['voucher_code']);
        $voucher = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_vouchers WHERE code = %s AND business_id = %d", $code, $user_id));
        
        if (!$voucher) {
            $validation_msg = '<p style="color:#dc3545;padding:10px;background:#f8d7da;border-radius:4px;">✗ Invalid code or code not for your business.</p>';
        } elseif ($voucher->redeemed) {
            $validation_msg = '<p style="color:#dc3545;padding:10px;background:#f8d7da;border-radius:4px;">✗ Already redeemed on ' . date('M j, Y', strtotime($voucher->redeemed_at)) . '</p>';
        } else {
            $now = current_time('timestamp');
            $expire_ts = strtotime($voucher->expires);
            if ($expire_ts < $now) {
                $validation_msg = '<p style="color:#dc3545;padding:10px;background:#f8d7da;border-radius:4px;">✗ Code has expired.</p>';
            } else {
                // Mark as redeemed
                $wpdb->update(
                    $wpdb->prefix . 'tln_vouchers',
                    ['redeemed' => 1, 'redeemed_at' => current_time('mysql')],
                    ['id' => $voucher->id],
                    ['%d', '%s'],
                    ['%d']
                );
                $validation_msg = '<p style="color:#28a745;padding:10px;background:#d4edda;border-radius:4px;font-size:1.2rem;">✓ Validated! ' . esc_html($voucher->lead_name) . ' - offer redeemed.</p>';
            }
        }
    }
    
    $campaigns = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_campaigns WHERE business_id = %d", $user_id));
    if (!$campaigns) return '<p>No campaigns found.</p>';
    
    // Validation form
    $output = '<div style="background:#f8f9fa;padding:1.5rem;border-radius:12px;margin-bottom:2rem;">';
    $output .= '<h3 style="margin-top:0;">Validate Customer Voucher</h3>';
    $output .= '<p style="color:#666;margin-bottom:1rem;">Enter the code from the customer\'s phone or scan their QR code with any QR scanner app.</p>';
    $output .= '<form method="post" style="display:flex;gap:0.5rem;flex-wrap:wrap;">';
    $output .= '<input type="text" name="voucher_code" placeholder="Enter voucher code" required style="flex:1;min-width:200px;padding:0.75rem;border:1px solid #ddd;border-radius:8px;font-size:1rem;">';
    $output .= '<button type="submit" name="tln_validate_code" style="padding:0.75rem 1.5rem;background:#28a745;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">Validate</button>';
    $output .= '</form>';
    if ($validation_msg) $output .= $validation_msg;
    $output .= '</div>';
    
    $output .= '<h3>Your Campaign Dashboard</h3>';
    foreach ($campaigns as $c) {
        $scans = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tln_qr_scans WHERE campaign_id = %d", $c->id));
        $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tln_vouchers WHERE campaign_id = %d", $c->id));
        $redeemed = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tln_vouchers WHERE campaign_id = %d AND redeemed = 1", $c->id));
        $output .= '<div style="border:1px solid #ddd;padding:15px;margin-bottom:15px;border-radius:8px;">';
        $output .= '<h4 style="margin-top:0;">' . esc_html($c->title) . '</h4>';
        $output .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:1rem;margin-bottom:1rem;">';
        $output .= '<div style="text-align:center;padding:1rem;background:#e9f7ef;border-radius:8px;"><div style="font-size:1.5rem;font-weight:bold;color:#28a745;">' . intval($scans) . '</div><div style="font-size:0.85rem;color:#666;">Scans</div></div>';
        $output .= '<div style="text-align:center;padding:1rem;background:#e9f7ef;border-radius:8px;"><div style="font-size:1.5rem;font-weight:bold;color:#28a745;">' . intval($total) . '</div><div style="font-size:0.85rem;color:#666;">Leads</div></div>';
        $output .= '<div style="text-align:center;padding:1rem;background:#e9f7ef;border-radius:8px;"><div style="font-size:1.5rem;font-weight:bold;color:#28a745;">' . intval($redeemed) . '</div><div style="font-size:0.85rem;color:#666;">Redeemed</div></div>';
        $output .= '</div>';
        
        // Source breakdown
        $sources = ['postcard' => 0, 'directory' => 0, 'newsletter' => 0];
        $source_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT source, COUNT(*) as cnt FROM {$wpdb->prefix}tln_vouchers WHERE campaign_id = %d GROUP BY source",
            $c->id
        ));
        foreach ($source_counts as $s) {
            if (isset($sources[$s->source])) $sources[$s->source] = intval($s->cnt);
        }
        $output .= '<div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #eee;">';
        $output .= '<p style="margin:0 0 0.5rem;font-size:0.9rem;color:#666;"><strong>Lead Sources:</strong></p>';
        $output .= '<div style="display:flex;gap:1rem;flex-wrap:wrap;">';
        $output .= '<div style="padding:0.5rem 1rem;background:#fff3cd;border-radius:20px;font-size:0.85rem;">📮 Postcard: <strong>' . $sources['postcard'] . '</strong></div>';
        $output .= '<div style="padding:0.5rem 1rem;background:#d1ecf1;border-radius:20px;font-size:0.85rem;">📁 Directory: <strong>' . $sources['directory'] . '</strong></div>';
        $output .= '<div style="padding:0.5rem 1rem;background:#d4edda;border-radius:20px;font-size:0.85rem;">📧 Newsletter: <strong>' . $sources['newsletter'] . '</strong></div>';
        $output .= '</div></div>';
        
        // Show unredeemed leads
        $unredeemed = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tln_vouchers WHERE campaign_id = %d AND redeemed = 0 ORDER BY id DESC",
            $c->id
        ));
        if ($unredeemed) {
            $output .= '<details style="margin-top:1rem;"><summary style="cursor:pointer;color:#666;">View ' . count($unredeemed) . ' unclaimed leads</summary>';
            $output .= '<table style="width:100%;border-collapse:collapse;font-size:14px;margin-top:0.5rem;">';
            $output .= '<tr style="background:#f5f5f5;"><th style="padding:5px;border:1px solid #ddd;">Name</th><th style="padding:5px;border:1px solid #ddd;">Email</th><th style="padding:5px;border:1px solid #ddd;">Phone</th><th style="padding:5px;border:1px solid #ddd;">Code</th><th style="padding:5px;border:1px solid #ddd;">Expires</th></tr>';
            foreach ($unredeemed as $v) {
                $output .= '<tr>';
                $output .= '<td style="padding:5px;border:1px solid #ddd;">' . esc_html($v->lead_name) . '</td>';
                $output .= '<td style="padding:5px;border:1px solid #ddd;">' . esc_html($v->lead_email) . '</td>';
                $output .= '<td style="padding:5px;border:1px solid #ddd;">' . esc_html($v->lead_phone) . '</td>';
                $output .= '<td style="padding:5px;border:1px solid #ddd;font-family:monospace;">' . esc_html($v->code) . '</td>';
                $output .= '<td style="padding:5px;border:1px solid #ddd;">' . esc_html(date('M j', strtotime($v->expires))) . '</td>';
                $output .= '</tr>';
            }
            $output .= '</table></details>';
        }
        
        // Show redeemed vouchers with dates
        $redeemed_vouchers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tln_vouchers WHERE campaign_id = %d AND redeemed = 1 ORDER BY redeemed_at DESC LIMIT 20",
            $c->id
        ));
        if ($redeemed_vouchers) {
            $output .= '<details style="margin-top:1rem;"><summary style="cursor:pointer;color:#666;">View redemption history</summary>';
            $output .= '<table style="width:100%;border-collapse:collapse;font-size:14px;margin-top:0.5rem;">';
            $output .= '<tr style="background:#f5f5f5;"><th style="padding:5px;border:1px solid #ddd;">Customer</th><th style="padding:5px;border:1px solid #ddd;">Code</th><th style="padding:5px;border:1px solid #ddd;">Redeemed</th></tr>';
            foreach ($redeemed_vouchers as $v) {
                $output .= '<tr>';
                $output .= '<td style="padding:5px;border:1px solid #ddd;">' . esc_html($v->lead_name) . '</td>';
                $output .= '<td style="padding:5px;border:1px solid #ddd;font-family:monospace;">' . esc_html($v->code) . '</td>';
                $output .= '<td style="padding:5px;border:1px solid #ddd;">' . esc_html(date('M j, Y g:iA', strtotime($v->redeemed_at))) . '</td>';
                $output .= '</tr>';
            }
            $output .= '</table></details>';
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
    if (!$voucher) wp_send_json_error(['msg' => 'Invalid code. This voucher does not exist.']);
    if ($voucher->redeemed) wp_send_json_error(['msg' => 'Already redeemed on ' . date('M j, Y', strtotime($voucher->redeemed_at))]);
    
    // Get campaign and business info
    $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_campaigns WHERE id = %d", $voucher->campaign_id));
    $claim = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_claims WHERE id = %d", $voucher->business_id));
    
    // Get owner photo
    $owner_photo = $claim ? get_post_meta($claim->id, 'tln_owner_photo', true) : '';
    
    // Check expiry
    $expires = strtotime($voucher->expires);
    $now = current_time('timestamp');
    $days_left = ceil(($expires - $now) / (24 * 60 * 60));
    $is_expired = $days_left <= 0;
    
    if ($is_expired) {
        wp_send_json_error(['msg' => 'This voucher expired on ' . date('M j, Y', $expires)]);
    }
    
    // Get business name and offer
    $business_name = $claim ? $claim->business_name : 'Unknown Business';
    $offer_text = $campaign ? ($campaign->offer_text ?: $campaign->title) : 'Special Offer';
    
    // Mark as redeemed
    $wpdb->update(
        $wpdb->prefix . 'tln_vouchers',
        ['redeemed' => 1, 'redeemed_at' => current_time('mysql')],
        ['id' => $voucher->id],
        ['%d','%s'],
        ['%d']
    );
    
    wp_send_json_success([
        'msg' => 'Code validated and redeemed',
        'business_name' => $business_name,
        'offer_text' => $offer_text,
        'owner_photo' => $owner_photo,
        'customer_name' => $voucher->lead_name,
        'days_remaining' => $days_left,
        'is_valid' => true
    ]);
}
add_action('wp_ajax_nopriv_tln_validate', 'tln_validate_endpoint');
add_action('wp_ajax_tln_validate', 'tln_validate_endpoint');
?>