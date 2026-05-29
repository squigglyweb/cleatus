<?php
/**
 * TLN Camps Shortcode
 * Provides a [tln_camps] shortcode that renders the dynamic camps list.
 */

// Register shortcode – outputs placeholder div
function tln_camps_shortcode($atts) {
    // You could allow attributes like 'category' later, but for now just output container
    return '<div id="tln-camps-output"></div>';
}
add_shortcode('tln_camps', 'tln_camps_shortcode');

// Enqueue the renderer script on front‑end
function tln_enqueue_camp_assets() {
    // Path to the JS inside the plugin folder
    wp_enqueue_script(
        'tln-camp-renderer',
        plugins_url('js/camp-renderer.js', __FILE__),
        [],
        filemtime(plugin_dir_path(__FILE__) . 'js/camp-renderer.js'),
        true
    );
    // Optionally localize the JSON URL if you store it elsewhere – we use a static path here:
    // wp_localize_script('tln-camp-renderer', 'tlnCampData', ['jsonUrl' => content_url('uploads/tln/camp-database.json')]);
}
add_action('wp_enqueue_scripts', 'tln_enqueue_camp_assets');
?>