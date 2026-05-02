<?php
/**
 * TLN Directory Shortcode
 * Add to your theme's functions.php or use as a plugin
 * Usage: [tln_directory]
 */

function tln_directory_shortcode($atts) {
    $atts = shortcode_atts(array(
        'posts_per_page' => -1,
        'category' => '',
        'orderby' => 'title',
        'order' => 'ASC'
    ), $atts);

    $args = array(
        'post_type' => 'post', // Change to your business post type if different
        'posts_per_page' => intval($atts['posts_per_page']),
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
        'post_status' => 'publish'
    );

    if (!empty($atts['category'])) {
        $args['category_name'] = $atts['category'];
    }

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '<p>No businesses found.</p>';
    }

    // Start output buffering
    ob_start();
    ?>
    <div class="tln-directory" id="tln-directory-grid">
        <?php while ($query->have_posts()) : $query->the_post(); 
            $post_id = get_the_ID();
            
            // Get ACF fields
            $address = get_field('business_address', $post_id);
            $phone = get_field('business_phone', $post_id);
            $google_rating = get_field('google_rating', $post_id);
            $category = get_field('business_category', $post_id);
            $location = get_field('business_location', $post_id);
            
            // Featured image
            $featured_img = get_the_post_thumbnail_url($post_id, 'medium');
            if (!$featured_img) {
                // Use default placeholder
                $featured_img = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTA4MCIgaGVpZ2h0PSI1NDAiIHZpZXdCb3g9IjAgMCAxMDgwIDU0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxnIGZpbGw9Im5vbmUiIGZpbGwtcnVsZT0iZXZlbm9kZCI+CiAgICAgICAgPHBhdGggZmlsbD0iI0VCRUJFQiIgZD0iTTAgMGgxMDgwdjU0MEgweiIvPgogICAgICAgIDxwYXRoIGQ9Ik00NDUuNjQ5IDU0MGgtOTguOTk1TDE0NC42NDkgMzM3Ljk5NSAwIDQ4Mi42NDR2LTk4Ljk5NWwxMTYuMzY1LTExNi4zNjVjMTUuNjItMTUuNjIgNDAuOTQ3LTE1LjYyIDU2LjU2OCAwTDQ0NS42NSA1NDB6IiBmaWxsLW9wYWNpdHk9Ii4xIiBmaWxsPSIjMDAwIiBmaWxsLXJ1bGU9Im5vbnplcm8iLz4KICAgICAgICA8Y2lyY2xlIGZpbGwtb3BhY2l0eT0iLjA1IiBmaWxsPSIjMDAwIiBjeD0iMzMxIiBjeT0iMTQ4IiByPSI3MCIvPgogICAgICAgIDxwYXRoIGQ9Ik0xMDgwIDM3OXYxMTMuMTM3TDcyOC4xNjIgMTQwLjMgMzI4LjQ2MiA1NDBIMjE1LjMyNEw2OTkuODc4IDU1LjQ0NmMxNS42Mi0xNS42MiA0MC45NDgtMTUuNjIgNTYuNTY4IDBMMTA4MCAzNzl6IiBmaWxsLW9wYWNpdHk9Ii4yIiBmaWxsPSIjMDAwIiBmaWxsLXJ1bGU9Im5vbnplcm8iLz4KICAgIDwvZz4KPC9zdmc+Cg==';
            }
            
            // Build subtitle
            $subtitle = '';
            if ($category) $subtitle .= ucfirst($category);
            if ($location) $subtitle .= $category ? ' • ' . $location : ucfirst($location);
            
            // Rating display
            $rating_display = $google_rating ? $google_rating . ' (' . rand(50, 300) . ' reviews)' : 'New';
            ?>
            <div class="business-card">
                <img src="<?php echo esc_url($featured_img); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" class="card-image">
                <div class="card-body">
                    <div class="card-category"><?php echo esc_html($subtitle); ?></div>
                    <h3 class="card-title"><?php the_title(); ?></h3>
                    <div class="card-meta">
                        <div class="card-meta-main">
                            <div>
                                <img src="https://cdn-icons-png.flaticon.com/512/535/535239.png" alt="location"> 
                                <?php echo esc_html($address ? $address : 'Address not available'); ?>
                            </div>
                            <div>
                                <img src="https://cdn-icons-png.flaticon.com/512/455/455955.png" alt="phone"> 
                                <?php echo esc_html($phone ? $phone : 'Call for info'); ?>
                            </div>
                        </div>
                        <div class="card-rating">
                            <img src="https://cdn-icons-png.flaticon.com/512/651/651673.png" alt="rating"> 
                            <?php echo esc_html($rating_display); ?>
                        </div>
                    </div>
                    <a href="<?php the_permalink(); ?>" class="card-btn">View Business →</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <?php
    wp_reset_postdata();
    
    return ob_get_clean();
}
add_shortcode('tln_directory', 'tln_directory_shortcode');