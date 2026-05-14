<?php
/*
Template Name: TLN Profile Page
Description: Loads business profile from URL params
*/
// Get params
$biz = isset($_GET['biz']) ? $_GET['biz'] : '';
$pid = isset($_GET['pid']) ? $_GET['pid'] : '';
$tier = isset($_GET['tier']) ? $_GET['tier'] : 'free'; // For testing: ?tier=proplus

if (empty($biz) || empty($pid)) {
    echo '<div style="padding:2rem;background:#f0f0f0;border-radius:8px;"><h3>TLN Profile v1.1.0</h3><p>Add ?biz=Name&pid=PlaceID to URL. Add ?tier=pro or ?tier=proplus to test different tiers.</p></div>';
    return;
}

// Include profile template based on tier
global $tln_profile_business;
$tln_profile_business = array(
    'name' => $biz,
    'place_id' => $pid,
    'address' => 'Loading...',
    'phone' => '',
    'website' => '',
    'rating' => '',
    'hours' => array(),
    'photos' => array(),
    'reviews' => array(),
);

// Template selection
$template_file = 'profile-free.php';
if ($tier === 'pro') {
    $template_file = 'profile-proplus.php';
} elseif ($tier === 'proplus') {
    $template_file = 'profile-proplus.php';
}

include dirname(__FILE__) . '/templates/' . $template_file;