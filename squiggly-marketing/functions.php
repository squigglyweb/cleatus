<?php
/**
 * Squiggly Marketing Theme Functions
 */

function squiggly_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'squiggly-marketing'),
        'footer' => __('Footer Menu', 'squiggly-marketing'),
    ));
}
add_action('after_setup_theme', 'squiggly_theme_setup');

function squiggly_theme_scripts() {
    wp_enqueue_style('squiggly-style', get_stylesheet_uri());
    wp_enqueue_style('squiggly-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', array(), null);
    
    wp_enqueue_script('squiggly-script', get_template_directory_uri() . '/js/main.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'squiggly_theme_scripts');

function squiggly_register_widget_areas() {
    register_sidebar(array(
        'name' => __('Footer Area', 'squiggly-marketing'),
        'id' => 'footer-area',
        'description' => __('Add widgets here to appear in your footer.', 'squiggly-marketing'),
        'before_widget' => '<div class="footer-widget">',
        'after_widget' => '</div>',
        'before_title' => '<h4>',
        'after_title' => '</h4>',
    ));
}
add_action('widgets_init', 'squiggly_register_widget_areas');

// Custom login
function squiggly_custom_login_logo() {
    ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(<?php echo get_template_directory_uri(); ?>/images/logo.png);
            background-size: contain;
            width: 320px;
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'squiggly_custom_login_logo');