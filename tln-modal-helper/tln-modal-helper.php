<?php
/**
 * Plugin Name: TLN Modal Helper
 * Description: Hides header/footer when ?modal=1 is in URL
 * Version: 1.0
 */

function tln_hide_header_footer() {
    if (isset($_GET['modal'])) {
        add_filter('et_show_nav', '__return_false');
        add_filter('et_show_header', '__return_false');
        add_filter('et_show_footer', '__return_false');
        add_filter('et_get_theme_modules', '__return_empty_array');
    }
}
add_action('wp', 'tln_hide_header_footer');