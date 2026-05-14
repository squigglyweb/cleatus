<?php
/*
Plugin Name: TLN Mini Test
Description: Minimal test plugin v1.0
Version: 1.0
*/

// This will write to the error log when plugin loads
error_log('TLN MINI TEST PLUGIN LOADED');

// Add a test message to the top of every page
add_action('wp_head', function() {
    echo '<!-- TLN MINI TEST v1.0 RUNNING -->';
});

// Test on profile page specifically
add_action('wp', function() {
    if (is_page('profile')) {
        error_log('TLN: Detected profile page');
    }
});