<?php
/*
Template Name: TLN Profile Page
Description: Loads business profile from URL params
*/
// Get params
$biz = isset($_GET['biz']) ? $_GET['biz'] : '';
$pid = isset($_GET['pid']) ? $_GET['pid'] : '';

if (empty($biz) || empty($pid)) {
    echo '<div style="padding:2rem;background:#f0f0f0;border-radius:8px;"><h3>TLN Profile v1.0.9</h3><p>Add ?biz=Name&pid=PlaceID to URL.</p></div>';
    return;
}

// Include profile template
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

include dirname(__FILE__) . '/templates/profile-free.php';