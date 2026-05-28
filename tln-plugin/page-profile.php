<?php
/*
Template Name: TLN Profile Page
Description: Loads business profile from URL params
*/
// Get params
$biz = isset($_GET['biz']) ? $_GET['biz'] : '';
$pid = isset($_GET['pid']) ? $_GET['pid'] : '';
// Get tier from stored business data (not URL) — fallback to 'free'
$business = get_posts([
    'post_type' => 'tln_business',
    'meta_key' => 'tln_place_id',
    'meta_value' => $pid,
    'posts_per_page' => 1
]);
$tier = 'free';
if (!empty($business)) {
    $tier = get_post_meta($business[0]->ID, 'tln_membership_tier', true) ?: 'free';
}

if (empty($biz) || empty($pid)) {
    echo '<div style="padding:2rem;background:#f0f0f0;border-radius:8px;"><h3>TLN Profile v1.1.0</h3><p>Add ?biz=Name&pid=PlaceID to URL. Add ?tier=pro or ?tier=proplus to test different tiers.</p></div>';
    return;
}

// Create or get the profile post for this business
$post_slug = sanitize_title($biz) . '-' . sanitize_title($pid);
$existing = get_page_by_path($post_slug, OBJECT, 'page');

if (!$existing) {
    // Create new profile post
    $post_id = wp_insert_post(array(
        'post_title' => $biz,
        'post_name' => $post_slug,
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_content' => ''
    ));
} else {
    $post_id = $existing->ID;
}

// Store tier and basic data as post meta
update_post_meta($post_id, 'tln_tier', $tier);
update_post_meta($post_id, 'tln_place_id', $pid);
update_post_meta($post_id, 'tln_business_name', $biz);

// Fetch Google data and cache in meta
if (function_exists('tln_fetch_google_place_data')) {
    $gdata = tln_fetch_google_place_data($pid);
    if ($gdata) {
        update_post_meta($post_id, 'tln_phone', $gdata['phone'] ?? '');
        update_post_meta($post_id, 'tln_address', $gdata['address'] ?? '');
        update_post_meta($post_id, 'tln_rating', $gdata['rating'] ?? 0);
        update_post_meta($post_id, 'tln_hours', $gdata['hours'] ?? array());
        update_post_meta($post_id, 'tln_google_data', $gdata);
    }
}

// Set up WordPress post context for the template
global $post, $tln_profile_business;
$post = get_post($post_id);
setup_postdata($post);

// Pass business ID to template
$tln_profile_business['post_id'] = $post_id;
$tln_profile_business['place_id'] = $pid;

// Template selection based on tier
$template_file = 'profile-free.php';
if ($tier === 'pro') {
    $template_file = 'profile-pro.php';
} elseif (in_array($tier, ['proplus', 'sponsor'])) {
    $template_file = 'profile-proplus.php';
}

include dirname(__FILE__) . '/templates/' . $template_file;
wp_reset_postdata();