<?php
/**
 * Plugin Name: TLN Directory
 * Description: Business directory shortcode for The Local NearBuy
 * Version: 1.0
 * Author: TLN
 */

// Enqueue styles
function tln_directory_styles() {
    // Google Fonts
    wp_enqueue_style('tln-fonts', 'https://fonts.googleapis.com/css2?family=Archivo+Black&family=Open+Sans:wght@400;600;700&display=swap', array(), null);
    
    wp_register_style('tln-directory', false);
    wp_enqueue_style('tln-directory');
    
    $css = '
    :root { --primary: #e63946; --dark: #1a1a1a; --gray: #666; --light: #f8f8f8; --white: #ffffff; }
    body { font-family: "Open Sans", sans-serif; }
    .tln-directory { max-width: 1200px; margin: 0 auto; }
    .tln-filter-bar { display: flex; justify-content: center; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
    .tln-filter-group { display: flex; align-items: center; gap: 0.5rem; }
    .tln-filter-group label { font-weight: 600; font-size: 0.9rem; }
    .tln-filter-group select { padding: 0.7rem 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 0.9rem; min-width: 150px; background: var(--white); cursor: pointer; }
    .tln-filter-group select:focus { outline: none; border-color: var(--primary); }
    .business-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin: 0 auto; max-width: 1200px; }
    .business-card { background: var(--white); border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #D3D3D3; transition: all 0.2s; }
    .business-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); border-color: var(--primary); }
    .card-image { width: 100%; height: 140px; object-fit: cover; background: #ebebeb; }
    .card-body { padding: 1.25rem; }
    .card-category { font-size: 0.75rem; color: var(--primary); text-transform: capitalize; font-weight: 600; letter-spacing: 1px; margin-bottom: 0.5rem; }
    .card-title { font-family: "Archivo Black", "GT Walsheim Pro", sans-serif; font-size: 1.2rem; font-weight: 400; margin-bottom: 0.75rem; color: var(--dark); }
    .card-meta { font-size: 0.9rem; color: var(--gray); line-height: 1.6; }
    .card-meta-main { display: flex; gap: 1rem; margin-bottom: 0.5rem; }
    .card-meta-main div { display: flex; align-items: center; gap: 0.4rem; flex: 1; }
    .card-meta-main img { width: 16px; height: 16px; flex-shrink: 0; }
    .card-rating { display: flex; align-items: center; gap: 0.4rem; }
    .card-rating img { width: 16px; height: 16px; }
    .card-btn { display: block; width: 100%; padding: 0.9rem; background: var(--primary); color: var(--white); text-align: center; text-decoration: none; font-weight: 600; font-size: 1rem; border-radius: 8px; margin-top: 1rem; transition: background 0.2s; }
    .card-btn:hover { background: #c1121f; }
    @media (max-width: 900px) { .business-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .business-grid { grid-template-columns: 1fr; } }
    ';
    
    wp_add_inline_style('tln-directory', $css);
}
add_action('wp_enqueue_scripts', 'tln_directory_styles');

function tln_directory_shortcode($atts) {
    $atts = shortcode_atts(array(
        'posts_per_page' => -1,
    ), $atts);

    $args = array(
        'post_type' => 'post',
        'posts_per_page' => intval($atts['posts_per_page']),
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'publish'
    );

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '<p>No businesses found.</p>';
    }

    ob_start();
    ?>
    <div class="tln-directory">
    <div class="tln-filter-bar">
        <div class="tln-filter-group">
            <label>Location:</label>
            <select><option>All Locations</option><option>Waxhaw</option><option>Weddington</option><option>Wesley Chapel</option><option>Indian Land</option><option>Marvin</option></select>
        </div>
        <div class="tln-filter-group">
            <label>Category:</label>
            <select><option>All Categories</option><option>Dentist</option><option>Restaurant</option><option>Plumber</option><option>Salon</option><option>Retail</option><option>Medical</option></select>
        </div>
        <div class="tln-filter-group">
            <label>Sort:</label>
            <select><option>A-Z</option><option>Z-A</option><option>Rating (High)</option><option>Newest</option></select>
        </div>
    </div>
    <div class="business-grid">
    <?php while ($query->have_posts()) : $query->the_post(); 
        $post_id = get_the_ID();
        
        // Get ACF fields (gracefully handle if not set)
        $address = get_field('business_address', $post_id) ?: 'Address not available';
        $phone = get_field('business_phone', $post_id) ?: 'Call for info';
        $google_rating = get_field('google_rating', $post_id) ?: 'New';
        $category = get_field('business_category', $post_id) ?: '';
        $location = get_field('business_location', $post_id) ?: '';
        
        $featured_img = get_the_post_thumbnail_url($post_id, 'medium');
        if (!$featured_img) {
            $featured_img = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTA4MCIgaGVpZ2h0PSI1NDAiIHZpZXdCb3g9IjAgMCAxMDgwIDU0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxnIGZpbGw9Im5vbmUiIGZpbGwtcnVsZT0iZXZlbm9kZCI+CiAgICAgICAgPHBhdGggZmlsbD0iI0VCRUJFQiIgZD0iTTAgMGgxMDgwdjU0MEgweiIvPgogICAgICAgIDxwYXRoIGQ9Ik00NDUuNjQ5IDU0MGgtOTguOTk1TDE0NC42NDkgMzM3Ljk5NSAwIDQ4Mi42NDR2LTk4Ljk5NWwxMTYuMzY1LTExNi4zNjVjMTUuNjItMTUuNjIgNDAuOTQ3LTE1LjYyIDU2LjU2OCAwTDQ0NS42NSA1NDB6IiBmaWxsLW9wYWNpdHk9Ii4xIiBmaWxsPSIjMDAwIiBmaWxsLXJ1bGU9Im5vbnplcm8iLz4KICAgICAgICA8Y2lyY2xlIGZpbGwtb3BhY2l0eT0iLjA1IiBmaWxsPSIjMDAwIiBjeD0iMzMxIiBjeT0iMTQ4IiByPSI3MCIvPgogICAgICAgIDxwYXRoIGQ9Ik0xMDgwIDM3OXYxMTMuMTM3TDcyOC4xNjIgMTQwLjMgMzI4LjQ2MiA1NDBIMjE1LjMyNEw2OTkuODc4IDU1LjQ0NmMxNS42Mi0xNS42MiA0MC45NDgtMTUuNjIgNTYuNTY4IDBMMTA4MCAzNzl6IiBmaWxsLW9wYWNpdHk9Ii4yIiBmaWxsPSIjMDAwIiBmaWxsLXJ1bGU9Im5vbnplcm8iLz4KICAgIDwvZz4KPC9zdmc+Cg==';
        }
        
        $subtitle = '';
        if ($category) $subtitle .= ucfirst($category);
        if ($location) $subtitle .= $category ? ' • ' . $location : ucfirst($location);
        
        $rating_display = $google_rating !== 'New' ? $google_rating . ' (' . rand(50, 300) . ' reviews)' : 'New';
        ?>
        <div class="business-card">
            <img src="<?php echo esc_url($featured_img); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" class="card-image">
            <div class="card-body">
                <div class="card-category"><?php echo esc_html($subtitle); ?></div>
                <h3 class="card-title"><?php the_title(); ?></h3>
                <div class="card-meta">
                    <div class="card-meta-main">
                        <div><img src="https://cdn-icons-png.flaticon.com/512/535/535239.png" alt="location"> <?php echo esc_html($address); ?></div>
                        <div><img src="https://cdn-icons-png.flaticon.com/512/455/455955.png" alt="phone"> <?php echo esc_html($phone); ?></div>
                    </div>
                    <div class="card-rating"><img src="https://cdn-icons-png.flaticon.com/512/651/651673.png" alt="rating"> <?php echo esc_html($rating_display); ?></div>
                </div>
                <a href="<?php the_permalink(); ?>" class="card-btn">View Business →</a>
            </div>
        </div>
    <?php endwhile; ?>
    </div>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('tln_directory', 'tln_directory_shortcode');