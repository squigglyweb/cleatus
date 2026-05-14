<?php
/**
 * Plugin Name: TLN Modal Helper
 * Description: Hides header/footer when ?modal=1 is in URL
 * Version: 1.1
 */

function tln_hide_header_footer() {
    if (isset($_GET['modal'])) {
        add_filter('et_show_nav', '__return_false');
        add_filter('et_show_header', '__return_false');
        add_filter('et_show_footer', '__return_false');
        add_filter('et_get_theme_modules', '__return_empty_array');
        
        // Extra theme specific
        add_filter('extra_show_top_header', '__return_false');
        add_filter('extra_show_main_header', '__return_false');
        add_filter('extra_show_footer', '__return_false');
        
        // Hide via CSS
        add_action('wp_head', function() {
            echo '<style>
                body.modal-mode #page-container #main-header,
                body.modal-mode #page-container header.et-header,
                body.modal-mode #page-container .et-fixed-header,
                body.modal-mode #page-container #main-footer,
                body.modal-mode #page-container footer.et-footer,
                body.modal-mode .et-header,
                body.modal-mode .et-footer,
                body.modal-mode #top-navigation,
                body.modal-mode #main-footer,
                #page-container header:not(#tln-ad-modal):not(#tln-claim-modal),
                #page-container footer,
                .et_fixed_nav #main-header,
                .et_fixed_nav #top-navigation,
                #main-header,
                #main-footer,
                .et-header,
                .et-footer,
                header.et-header,
                footer.et-footer {
                    display: none !important;
                    visibility: hidden !important;
                    height: 0 !important;
                    overflow: hidden !important;
                }
                body.modal-mode {
                    background: #fff !important;
                }
            </style>';
            echo '<script>document.body.classList.add("modal-mode");</script>';
        });
    }
}
add_action('wp', 'tln_hide_header_footer');