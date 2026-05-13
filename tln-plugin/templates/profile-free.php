<?php
/*
Template Name: TLN Free Profile
*/
// Check if data was passed from shortcode (Google Places API)
global $tln_profile_business;
$biz = isset($tln_profile_business) ? $tln_profile_business : array(
    'name' => the_title('', '', false),
    'address' => get_post_meta(get_the_ID(), 'tln_address', true),
    'phone' => get_post_meta(get_the_ID(), 'tln_phone', true),
    'rating' => get_post_meta(get_the_ID(), 'tln_google_rating', true),
    'hours' => array(
        get_post_meta(get_the_ID(), 'tln_hours_mon', true),
        get_post_meta(get_the_ID(), 'tln_hours_sat', true),
        get_post_meta(get_the_ID(), 'tln_hours_sun', true),
    ),
    'website' => get_post_meta(get_the_ID(), 'tln_website', true),
);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php the_title(); ?> - The Local NearBuy</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Open Sans', sans-serif; background: url('https://thelocalnearbuy.com/wp-content/uploads/2026/05/town-scene-bkgd-scaled.webp') center top no-repeat; background-size: cover; color: #1a1a1a; }
        :root { --red: #e63946; --black: #1a1a1a; }
        .hero { position: relative; height: 280px; background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5)), url('https://thelocalnearbuy.com/wp-content/uploads/2026/05/town-scene-bkgd-scaled.webp'); background-size: cover; background-position: center; }
        .biz-info { position: absolute; bottom: 1.5rem; left: 1.5rem; }
        .biz-name { font-size: 2.5rem; font-weight: 700; color: white; margin-bottom: 0.25rem; }
        .biz-address { font-size: 1.1rem; color: white; display: flex; align-items: center; gap: 0.5rem; }
        .container { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; display: grid; grid-template-columns: 320px 1fr; gap: 2rem; background: rgba(255,255,255,0.95); border-radius: 12px; margin-top: 1rem; margin-bottom: 1rem; }
        .left-col { display: flex; flex-direction: column; gap: 1.5rem; }
        .right-col { display: flex; flex-direction: column; gap: 1.5rem; }
        .card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 1.25rem; }
        .card-title { font-size: 1rem; font-weight: 700; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #eee; }
        .hours-pill { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .hours-pill.open { background: #28a745; color: white; }
        .hours-pill.closed { background: #dc3545; color: white; }
        .hours-pill.closing-soon { background: #ffc107; color: #333; }
        .hours-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #f0f0f0; font-size: 0.9rem; }
        .hours-row:last-child { border-bottom: none; }
        .contact-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0; }
        .contact-item a { color: var(--red); text-decoration: none; font-weight: 600; }
        .ad-mobile { display: none; }
        @media(max-width: 800px) { .ad-mobile { display: block; } .container { grid-template-columns: 1fr; } .services-grid { grid-template-columns: repeat(2, 1fr); } .biz-name { font-size: 1.75rem; } .biz-address { font-size: 0.95rem; } }
    </style>
    <?php wp_head(); ?>
</head>
<body>
    <!-- Mobile Ad Slot -->
    <div class="ad-mobile" style="background:#f9f9f9;padding:0.5rem;text-align:center;">
        <div class="ad-slot-label" style="font-size:0.7rem;color:#999;text-transform:uppercase;letter-spacing:1px;margin-bottom:0.25rem;">Advertisement</div>
        <div style="background:#fff;border:2px dashed #ccc;border-radius:8px;height:100px;display:flex;align-items:center;justify-content:center;color:#888;">
            <div><strong>Your Ad Here</strong><br><span style="font-size:0.75rem;">$25/mo – <a href="/tln-ad-request.html" style="color:var(--red);">Advertise your business on this page</a></span></div>
        </div>
    </div>

    <!-- Hero -->
    <?php 
    $hero_bg = 'https://thelocalnearbuy.com/wp-content/uploads/2026/05/town-scene-bkgd-scaled.webp';
    $api_key = defined('TLN_GOOGLE_API_KEY') ? TLN_GOOGLE_API_KEY : '';
    if (!empty($biz['photos']) && is_array($biz['photos']) && isset($biz['photos'][0]['photo_reference']) && $api_key) {
        $hero_bg = 'https://maps.googleapis.com/maps/api/place/photo?maxwidth=1200&photoreference=' . $biz['photos'][0]['photo_reference'] . '&key=' . $api_key;
    }
    ?>
    <div class="hero" style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5)), url('<?php echo $hero_bg; ?>'); background-size: cover; background-position: center;">
        <div class="biz-info">
            <div class="biz-name"><?php echo esc_html($biz['name']); ?></div>
            <div class="biz-address">
                <?php echo esc_html($biz['address']); ?>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="left-col">
            <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/support-local-businesses.webp" alt="Support Local Businesses" style="width:100%;border-radius:8px;margin-bottom:0.5rem;">
            <a href="/tln-ad-request.html" style="display:block;text-align:center;padding:3px 0;color:#666;font-size:0.9rem;font-weight:600;">Advertise Your Business</a>

            <!-- Hours Card -->
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                    <span class="card-title">Hours</span>
                    <span id="hoursStatus" class="hours-pill open">Open Now</span>
                </div>
                <?php if (!empty($biz['hours'])): ?>
                    <?php foreach ($biz['hours'] as $day_hours): ?>
                    <div class="hours-row"><span><?php echo esc_html($day_hours); ?></span></div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="hours-row"><span>Monday – Friday</span><span><?php echo esc_html( get_post_meta( get_the_ID(), 'tln_hours_mon', true ) ); ?></span></div>
                <div class="hours-row"><span>Saturday</span><span><?php echo esc_html( get_post_meta( get_the_ID(), 'tln_hours_sat', true ) ); ?></span></div>
                <div class="hours-row"><span>Sunday</span><span><?php echo esc_html( get_post_meta( get_the_ID(), 'tln_hours_sun', true ) ); ?></span></div>
                <?php endif; ?>
            </div>

            <!-- Contact Card -->
            <div class="card">
                <div class="card-title">Contact</div>
                <div class="contact-item">
                    <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/call.png" style="height:18px;">
                    <a href="tel:<?php echo esc_html($biz['phone']); ?>"><?php echo esc_html($biz['phone']); ?></a>
                </div>
                <div class="contact-item">
                    <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:18px;">
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($biz['address']); ?>" target="_blank">Get Directions</a>
                </div>
            </div>
        </div>

        <div class="right-col">
            <!-- Map -->
            <div class="card">
                <div class="card-title">Location</div>
                <div style="background:#f5f5f5;height:200px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($biz['address']); ?>" target="_blank" style="text-align:center;">
                        <img src="https://www.gstatic.com/images/branding/product/2x/maps_2020i4.png" alt="Open in Google Maps" style="width:48px;height:48px;">
                        <div style="font-size:0.9rem;color:var(--red);font-weight:600;margin-top:0.5rem;">View on Google Maps</div>
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title">Google Reviews</div>
                <?php if (!empty($biz['reviews'])): ?>
                    <?php foreach (array_slice($biz['reviews'], 0, 3) as $review): ?>
                    <div style="border-bottom:1px solid #eee;padding:0.5rem 0;">
                        <div style="font-size:0.85rem;"><?php echo esc_html($review['text'] ?? ''); ?></div>
                        <div style="font-size:0.75rem;color:#666;margin-top:0.25rem;"><?php echo esc_html($review['author_name'] ?? ''); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <a href="#" class="see-all">See all Google Reviews →</a>
                <?php endif; ?>
            </div>
            <!-- Sidebar ad (kept as static for now) -->
            <div class="ad-slot">
                <div class="ad-slot-label">Advertisement</div>
                <div class="ad-slot-content">Your Sidebar Ad Here – $35/mo – <a href="/tln-ad-request.html" style="color:var(--red);">Advertise your business on this page</a></div>
            </div>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
