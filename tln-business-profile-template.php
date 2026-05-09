<?php
/**
 * Template Name: TLN Business Profile
 * Dynamic profile page that displays business info based on tier
 */

if (!defined('ABSPATH')) exit;

// Get business from URL param
$place_id = isset($_GET['place_id']) ? sanitize_text_field($_GET['place_id']) : '';
$biz_slug = isset($_GET['biz']) ? sanitize_text_field($_GET['biz']) : '';

// Get all cached businesses
businesses = tln_get_cached_businesses();
$business = null;

// Find the business
foreach ($businesses as $b) {
    if (!empty($place_id) && $b['place_id'] === $place_id) {
        $business = $b;
        break;
    }
    if (!empty($biz_slug) && sanitize_title($b['name']) === $biz_slug) {
        $business = $b;
        break;
    }
}

// If not found, show error
if (!$business) {
    echo '<div class="tln-container"><p>Business not found.</p></div>';
    return;
}

// Get tier from post meta (if claimed)
$tier = 'free'; // default
$claimed = false;

// Check if this place_id was claimed
$args = array(
    'post_type' => 'tln_claimed',
    'meta_key' => 'place_id',
    'meta_value' => $business['place_id'],
    'posts_per_page' => 1
);
$claimed_posts = get_posts($args);

if (!empty($claimed_posts)) {
    $claimed = true;
    $tier = get_post_meta($claimed_posts[0]->ID, 'tier', true) ?: 'free';
}

// Get additional data from Google
$details = tln_get_place_details($business['place_id']);
$hours = $details['hours'] ?? array();
$phone = $details['phone'] ?? '';
$website = $details['website'] ?? '';

// Get TLN-specific data
$offers = get_post_meta($claimed_posts[0]->ID, 'offers', true) ?: array();
$reviews = get_post_meta($claimed_posts[0]->ID, 'reviews', true) ?: array();
$meals = get_post_meta($claimed_posts[0]->ID, 'meals_donated', true) ?: 0;

// Build the profile based on tier
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($business['name']); ?> | The Local NearBuy</title>
    <?php wp_head(); ?>
    <style>
        /* TLN Profile Styles - Dynamic based on tier */
        :root { --red: #e63946; --black: #1a1a1a; }
        
        .tln-top-bar { background: var(--black); color: white; padding: 0.6rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .tln-top-bar .logo { font-weight: 700; font-size: 1rem; }
        .tln-top-bar .login-link { color: white; text-decoration: none; font-size: 0.9rem; }
        
        .tln-hero { 
            background: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.35)), 
            url('<?php echo $business['photo_url'] ?? ''; ?>'); 
            background-size: cover; background-position: center; height: 280px; position: relative; 
        }
        
        .tln-hero-content { position: absolute; bottom: 1.5rem; left: 2rem; display: flex; flex-direction: column; gap: 0.75rem; }
        
        .tln-profile-logo { width: 180px; height: 90px; object-fit: contain; background: white; border-radius: 8px; padding: 5px; }
        
        .tln-profile-info h1 { font-size: 2rem; color: white; font-weight: 700; margin-bottom: 0.5rem; }
        .tln-badges { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .tln-badge { background: var(--red); color: white; padding: 0.3rem 0.75rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .tln-badge.verified { background: #22c55e; }
        .tln-badge.featured { background: #fbbf24; color: var(--black); }
        
        .tln-content-wrapper { display: grid; grid-template-columns: 300px 1fr; gap: 2rem; max-width: 1100px; margin: 2rem auto; padding: 0 1.5rem; }
        
        .tln-sidebar, .tln-main { display: flex; flex-direction: column; gap: 1.5rem; }
        
        .tln-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 1.25rem; }
        .tln-card h3 { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; color: #555; margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 0.75rem; font-weight: 600; }
        
        .tln-contact-list { list-style: none; }
        .tln-contact-list li { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0; font-size: 0.9rem; border-bottom: 1px solid #f0f0f0; }
        .tln-contact-list a { color: var(--red); text-decoration: none; }
        
        .tln-impact-box { background: linear-gradient(135deg, var(--red), #c1121f); color: white; padding: 1rem; border-radius: 8px; display: flex; align-items: center; gap: 0.75rem; font-size: 0.9rem; }
        
        /* Tabs - Pro+ only */
        .tln-tabs { background: #f5f5f5; border-bottom: 1px solid #ddd; padding: 0 2rem; display: <?php echo $tier === 'pro_plus' ? 'block' : 'none'; ?>; }
        .tln-tab-list { display: flex; list-style: none; gap: 0; overflow-x: auto; }
        .tln-tab-list li { padding: 1rem 1.5rem; cursor: pointer; font-weight: 600; color: #666; border-bottom: 3px solid transparent; white-space: nowrap; }
        .tln-tab-list li.active { color: var(--red); border-bottom-color: var(--red); }
        
        .tln-tab-content { display: none; }
        .tln-tab-content.active { display: block; }
        
        .tln-offer-banner { background: #f8f8f8; border: 2px solid var(--red); border-radius: 8px; padding: 1.25rem; }
        .tln-offer-banner h4 { color: var(--red); font-size: 1rem; margin-bottom: 0.5rem; }
        
        .tln-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
        .tln-detail-item { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #f5f5f5; font-size: 0.85rem; }
        
        .tln-services-list { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; list-style: none; }
        .tln-services-list li { background: #f5f5f5; padding: 0.4rem 0.6rem; border-radius: 4px; font-size: 0.8rem; text-align: center; }
        
        .tln-map-container { background: #f5f5f5; height: 180px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999; }
        
        .tln-ad-space { background: #f5f5f5; border: 2px dashed #ccc; padding: 1.5rem; text-align: center; color: #999; font-size: 0.8rem; border-radius: 8px; }
        
        @media(max-width: 750px) {
            .tln-content-wrapper { grid-template-columns: 1fr; }
            .tln-services-list { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    
    <!-- Top Bar -->
    <div class="tln-top-bar">
        <div class="logo">The Local NearBuy</div>
        <a href="/dashboard/" class="login-link">Business Login</a>
    </div>
    
    <!-- Hero -->
    <div class="tln-hero">
        <div class="tln-hero-content">
            <?php if ($tier !== 'free' && !empty($claimed_posts[0])): ?>
                <?php $logo = get_post_meta($claimed_posts[0]->ID, 'logo_url', true); ?>
                <?php if ($logo): ?>
                    <img src="<?php echo esc_url($logo); ?>" class="tln-profile-logo" alt="<?php echo esc_html($business['name']); ?>">
                <?php endif; ?>
            <?php endif; ?>
            <div class="tln-profile-info">
                <h1><?php echo esc_html($business['name']); ?></h1>
                <div class="tln-badges">
                    <?php if ($claimed): ?>
                        <span class="tln-badge verified">✓ Verified</span>
                    <?php endif; ?>
                    <?php if ($tier === 'pro'): ?>
                        <span class="tln-badge">Pro Partner</span>
                    <?php elseif ($tier === 'pro_plus'): ?>
                        <span class="tln-badge">Pro+ Partner</span>
                        <span class="tln-badge featured">★ Featured</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabs (Pro+ only) -->
    <?php if ($tier === 'pro_plus'): ?>
    <div class="tln-tabs">
        <ul class="tln-tab-list">
            <li class="active" data-tab="overview">Overview</li>
            <li data-tab="offers">Offers</li>
            <li data-tab="gallery">Gallery</li>
            <li data-tab="reviews">Reviews</li>
            <li data-tab="about">About</li>
            <li data-tab="appointment">Make an Appointment</li>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <div class="tln-content-wrapper">
        <!-- Sidebar -->
        <div class="tln-sidebar">
            <div class="tln-card">
                <h3>Contact</h3>
                <ul class="tln-contact-list">
                    <?php if (!empty($phone)): ?>
                    <li>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#e63946"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                        <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                    </li>
                    <?php endif; ?>
                    <li>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#e63946"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        <a href="#">Get Directions</a>
                    </li>
                    <?php if (!empty($website)): ?>
                    <li>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#e63946"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                        <a href="<?php echo esc_url($website); ?>" target="_blank">Visit Website</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <?php if ($meals > 0): ?>
            <div class="tln-impact-box">
                <span>🍽️</span>
                <span><strong><?php echo number_format($meals); ?> Meals</strong> provided to local families</span>
            </div>
            <?php endif; ?>
            
            <div class="tln-ad-space">Advertisement<br><small>Your Ad Here</small></div>
        </div>
        
        <!-- Main Content -->
        <div class="tln-main">
            <!-- Special Offer (Pro and Pro+) -->
            <?php if (in_array($tier, array('pro', 'pro_plus')) && !empty($offers)): ?>
            <div class="tln-card">
                <h3>Special Offer</h3>
                <?php foreach ($offers as $offer): ?>
                    <div class="tln-offer-banner">
                        <h4><?php echo esc_html($offer['title']); ?></h4>
                        <p><?php echo esc_html($offer['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Company Details -->
            <div class="tln-card">
                <h3>Company Details</h3>
                <div class="tln-details-grid">
                    <div class="tln-detail-item"><span>Category</span><span><?php echo esc_html($business['cat']); ?></span></div>
                    <div class="tln-detail-item"><span>Location</span><span><?php echo esc_html($business['loc']); ?></span></div>
                </div>
            </div>
            
            <!-- Hours -->
            <?php if (!empty($hours)): ?>
            <div class="tln-card">
                <h3>Hours</h3>
                <div class="tln-details-grid">
                    <?php foreach ($hours as $day => $time): ?>
                    <div class="tln-detail-item"><span><?php echo esc_html($day); ?></span><span><?php echo esc_html($time); ?></span></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Services (if available) -->
            <?php if (!empty($details['types'])): ?>
            <div class="tln-card">
                <h3>Services</h3>
                <ul class="tln-services-list">
                    <?php foreach (array_slice($details['types'], 0, 9) as $type): ?>
                    <li><?php echo esc_html(ucwords(str_replace('_', ' ', $type))); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- Map -->
            <div class="tln-card">
                <h3>Location</h3>
                <div class="tln-map-container">[Google Map - <?php echo esc_html($business['addr']); ?>]</div>
            </div>
            
            <!-- Google Reviews -->
            <?php if (!empty($details['reviews'])): ?>
            <div class="tln-card">
                <h3>Google Reviews</h3>
                <?php foreach (array_slice($details['reviews'], 0, 3) as $review): ?>
                    <div style="padding: 0.75rem 0; border-bottom: 1px solid #eee;">
                        <div style="font-weight: 600; font-size: 0.9rem;"><?php echo esc_html($review['author']); ?></div>
                        <div style="color: #e63946; font-size: 0.8rem;"><?php echo str_repeat('★', $review['rating']); ?></div>
                        <div style="color: #555; font-size: 0.85rem;"><?php echo esc_html($review['text']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    // Tab switching for Pro+
    document.querySelectorAll('.tln-tab-list li').forEach(tab => {
        tab.addEventListener('click', function() {
            var tabId = this.getAttribute('data-tab');
            document.querySelectorAll('.tln-tab-list li').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tln-tab-content').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
    </script>
    
    <?php wp_footer(); ?>
</body>
</html>
