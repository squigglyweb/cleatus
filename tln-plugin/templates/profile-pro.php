<?php get_header(); 

$business = get_post(get_the_ID());
$tier = get_post_meta(get_the_ID(), 'tln_tier', true);
$phone = get_post_meta(get_the_ID(), 'tln_phone', true);
$email = get_post_meta(get_the_ID(), 'tln_email', true);
$address = get_post_meta(get_the_ID(), 'tln_address', true);
$city = get_post_meta(get_the_ID(), 'tln_city', true);
$state = get_post_meta(get_the_ID(), 'tln_state', true);
$zip = get_post_meta(get_the_ID(), 'tln_zip', true);
$website = get_post_meta(get_the_ID(), 'tln_website', true);
$google_rating = get_post_meta(get_the_ID(), 'tln_google_rating', true);
$tln_score = get_post_meta(get_the_ID(), 'tln_neighborhood_score', true);
$meals_count = get_post_meta(get_the_ID(), 'tln_meals_count', true);

$hours = array(
    get_post_meta(get_the_ID(), 'tln_hours_mon', true) ?: '7:00 AM - 6:00 PM',
    get_post_meta(get_the_ID(), 'tln_hours_tue', true) ?: '7:00 AM - 6:00 PM',
    get_post_meta(get_the_ID(), 'tln_hours_wed', true) ?: '7:00 AM - 6:00 PM',
    get_post_meta(get_the_ID(), 'tln_hours_thu', true) ?: '7:00 AM - 6:00 PM',
    get_post_meta(get_the_ID(), 'tln_hours_fri', true) ?: '7:00 AM - 6:00 PM',
    get_post_meta(get_the_ID(), 'tln_hours_sat', true) ?: '8:00 AM - 2:00 PM',
    get_post_meta(get_the_ID(), 'tln_hours_sun', true) ?: 'Closed',
);

$is_pro = in_array($tier, ['pro', 'proplus', 'sponsor']);
$is_verified = ($tier !== 'free');
?>

<style>
    :root { --red: #e63946; --black: #1a1a1a; }
    .hero { position: relative; height: 280px; background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1487754180451-c456f719a1fc?w=1200'); background-size: cover; background-position: center; }
    .biz-info { position: absolute; bottom: 1.5rem; left: 2rem; right: 2rem; }
    .biz-name { font-size: 2.5rem; font-weight: 700; color: white; margin-bottom: 0.25rem; }
    .biz-address { font-size: 1.1rem; color: white; display: flex; align-items: center; gap: 0.5rem; }
    .badge-pills { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap; }
    .badge-pill { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
    .badge-pro { background: var(--red); color: white; }
    .badge-verified { background: #28a745; color: white; }
    .container { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; display: grid; grid-template-columns: 320px 1fr; gap: 2rem; }
    .left-col, .right-col { display: flex; flex-direction: column; gap: 1.5rem; }
    .card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 1.25rem; }
    .card-title { font-size: 1rem; font-weight: 700; margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem; }
    .score-box { background: #f8f8f8; border: 2px solid var(--red); border-radius: 8px; padding: 1.25rem; text-align: center; }
    .score-box h3 { font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; }
    .score-display { font-size: 2.5rem; font-weight: 700; color: var(--red); }
    .hours-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; position: relative; }
    .hours-header h3 { margin: 0; padding: 0; border: none; font-size: 1rem; }
    .hours-pill { position: absolute; right: 0; top: 50%; transform: translateY(-50%); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
    .hours-pill.open { background: #28a745; color: white; }
    .hours-pill.closed { background: #dc3545; color: white; }
    .hours-pill.closing-soon { background: #ffc107; color: #333; }
    .hours-time { font-weight: 700; }
    .hours-display { font-size: 14px; line-height: 1.8; }
    .contact-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0; }
    .contact-item a { color: var(--red); text-decoration: none; font-weight: 600; }
    .impact-box { background: var(--red); color: white; border-radius: 8px; padding: 1rem; display: flex; align-items: center; gap: 0.75rem; }
    .tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid #eee; flex-wrap: wrap; }
    .tab-btn { background: none; border: none; padding: 0.75rem 1rem; font-weight: 600; color: #666; cursor: pointer; border-bottom: 3px solid transparent; }
    .tab-btn.active { color: var(--red); border-bottom-color: var(--red); }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .offer-card { background: #fff8e5; border: 1px solid #ffc107; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
    .offer-card h4 { color: #856404; margin-bottom: 0.5rem; }
    .offer-card p { font-size: 0.85rem; color: #666; margin-bottom: 0.75rem; }
    .redeem-btn { background: var(--red); color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; font-weight: 600; cursor: pointer; }
    .directions-btn { display: block; width: 100%; background: var(--red); color: white; padding: 0.75rem; border-radius: 6px; text-align: center; font-weight: 600; text-decoration: none; margin-top: 0.5rem; }
    .services-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; }
    .service-btn { background: #f5f5f5; border: 1px solid #ddd; padding: 0.6rem 0.5rem; border-radius: 4px; font-size: 0.8rem; text-align: center; }
    .map-box { background: #f5f5f5; height: 250px; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666; }
    .gallery-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem; }
    .gallery-grid img { width: 100%; height: 100px; object-fit: cover; border-radius: 4px; }
    @media(max-width: 800px) { .container { grid-template-columns: 1fr; } .services-grid { grid-template-columns: repeat(2, 1fr); } .gallery-grid { grid-template-columns: repeat(2, 1fr); } }
</style>

<div style="position:absolute;top:1rem;right:1rem;z-index:10;">
    <a href="/business-login" style="background:rgba(0,0,0,0.7);color:white;padding:0.5rem 1rem;border-radius:6px;text-decoration:none;font-size:0.85rem;font-weight:600;">Business Login</a>
</div>

<div class="hero">
    <div class="biz-info">
        <div class="badge-pills">
            <?php if ($is_pro): ?><span class="badge-pill badge-pro">Pro Business</span><?php endif; ?>
            <?php if ($is_verified): ?><span class="badge-pill badge-verified">Verified</span><?php endif; ?>
        </div>
        <div class="biz-name"><?php echo esc_html($business->post_title); ?></div>
        <div class="biz-address"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:18px;"> <?php echo esc_html($city.', '.$state); ?></div>
    </div>
</div>

<div class="container">
    <div class="left-col">
        <!-- TLN Neighborhood Score -->
        <div class="score-box">
            <h3>TLN Neighborhood Score</h3>
            <div class="score-display"><?php echo esc_html($tln_score ?: '4.5'); ?> <span style="font-size:1rem;color:#666;font-weight:400;">/ 5</span></div>
            <p style="font-size:0.8rem;color:#666;margin-top:0.25rem;">Based on TLN reviews</p>
            <button onclick="showTab('reviews'); document.querySelectorAll('.tab-btn')[3].click();" style="background:var(--red);color:white;border:none;padding:0.5rem 1rem;border-radius:4px;font-weight:600;cursor:pointer;margin-top:0.5rem;width:100%;">Write a Review</button>
        </div>
        
        <!-- Google Rating -->
        <div class="score-box" style="border-color:#4285f4;">
            <h3>Google Rating</h3>
            <div class="score-display" style="color:#4285f4;"><?php echo esc_html($google_rating ?: '4.0'); ?> <span style="font-size:1rem;color:#666;font-weight:400;">/ 5</span></div>
            <p style="font-size:0.8rem;color:#666;margin-top:0.25rem;">Based on Google reviews</p>
            <button onclick="document.getElementById('reviewModal').style.display='flex';" style="background:#e63946;color:white;border:none;padding:0.5rem 1rem;border-radius:4px;font-weight:600;cursor:pointer;margin-top:0.5rem;width:100%;">Leave a Review</button>
        </div>
        
        <!-- Hours -->
        <?php
        $is_open = false;
        $closing_soon = false;
        $days = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
        $current_day_idx = (int)date('w');
        $current_day = $days[$current_day_idx];
        $current_time_str = date('H:i');
        $today_hours = $hours[$current_day_idx];
        if ($today_hours && $today_hours !== 'Closed' && $today_hours !== 'closed') {
            // Parse hours like "7:00 AM - 6:00 PM"
            if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)\s*-\s*(\d{1,2}):(\d{2})\s*(AM|PM)/i', $today_hours, $hm)) {
                $open_h = (int)$hm[1]; $open_m = (int)$hm[2]; $open_ap = strtoupper($hm[3]);
                $close_h = (int)$hm[4]; $close_m = (int)$hm[5]; $close_ap = strtoupper($hm[6]);
                if ($open_ap === 'PM' && $open_h !== 12) $open_h += 12;
                if ($open_ap === 'AM' && $open_h === 12) $open_h = 0;
                if ($close_ap === 'PM' && $close_h !== 12) $close_h += 12;
                if ($close_ap === 'AM' && $close_h === 12) $close_h = 0;
                $open_time = sprintf('%02d:%02d', $open_h, $open_m);
                $close_time = sprintf('%02d:%02d', $close_h, $close_m);
                if ($current_time_str >= $open_time && $current_time_str < $close_time) {
                    $is_open = true;
                    $close_minutes = $close_h * 60 + $close_m;
                    $now_minutes = date('G') * 60 + date('i');
                    if ($close_minutes - $now_minutes <= 60) {
                        $closing_soon = true;
                    }
                }
            }
        }
        ?>
        <div class="card">
            <div class="hours-header">
                <h3>Hours</h3>
                <?php if ($is_open): ?>
                    <?php if ($closing_soon): ?>
                    <span class="hours-pill closing-soon">Closing Soon</span>
                    <?php else: ?>
                    <span class="hours-pill open">Open</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="hours-pill closed">Closed</span>
                <?php endif; ?>
            </div>
            <div class="hours-display">
                <?php $today = strtolower(date('l')); $days = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday'); $labels = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun'); $hours_arr = array($hours[0],$hours[1],$hours[2],$hours[3],$hours[4],$hours[5],$hours[6]); ?>
                <?php foreach($days as $i=>$day): $bold = ($day==$today)?'font-weight:700;':''; ?>
                <span style="<?php echo $bold; ?>"><?php echo $labels[$i]; ?>: <span class="hours-time" style="<?php echo $bold; ?>"><?php echo esc_html($hours_arr[$i]); ?></span></span><br>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Contact -->
        <div class="card">
            <div class="card-title">Contact</div>
            <div class="contact-item"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/call.png" style="height:18px;"> <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a></div>
            <div class="contact-item"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/email.png" style="height:18px;"> <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></div>
            <a href="https://maps.google.com/?q=<?php echo urlencode($address.' '.$city.' '.$state.' '.$zip); ?>" class="directions-btn" target="_blank"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/location-pin.png" style="height:18px;margin-right:0.5rem;"> Get Directions</a>
        </div>
        
        <?php if ($meals_count): ?>
        <div class="impact-box">
            <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/white-utensils.png" style="height:28px;">
            <span><strong><?php echo esc_html($meals_count); ?></strong> Meals provided to local families</span>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="right-col">
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('overview')">Overview</button>
            <button class="tab-btn" onclick="showTab('offers')">Offers</button>
            <button class="tab-btn" onclick="showTab('gallery')">Gallery</button>
            <button class="tab-btn" onclick="showTab('reviews')">Reviews</button>
            <button class="tab-btn" onclick="showTab('about')">About</button>
        </div>
        
        <!-- Overview Tab -->
        <div id="overview" class="tab-content active">
            <div class="card">
                <div class="card-title">Special Offer</div>
                <div class="offer-card">
                    <h4>15% Off Any Service</h4>
                    <p>Free multi-point inspection. Can't combine with other offers.</p>
                    <button class="redeem-btn" onclick="showRedeemModal()">Redeem Offer</button>
                </div>
            </div>
            <div class="card">
                <div class="card-title">Services</div>
                <div class="services-grid">
                    <div class="service-btn">Engine Diagnostics</div>
                    <div class="service-btn">Brake Service</div>
                    <div class="service-btn">Oil Change</div>
                    <div class="service-btn">Tire Rotation</div>
                    <div class="service-btn">Air Conditioning</div>
                    <div class="service-btn">Transmission</div>
                </div>
            </div>
            <div class="card">
                <div class="card-title">Location</div>
                <div class="map-box">[Google Map]</div>
            </div>
        </div>
        
        <!-- Offers Tab -->
        <div id="offers" class="tab-content">
            <div class="card">
                <div class="offer-card">
                    <h4>15% Off Any Service</h4>
                    <p>Valid on all services over $50. Expires 12/31/2026.</p>
                    <button class="redeem-btn">Redeem Offer</button>
                </div>
            </div>
        </div>
        
        <!-- Gallery Tab (Limited to 4 photos for Pro) -->
        <div id="gallery" class="tab-content">
            <div class="card">
                <div class="card-title">Photo Gallery (4 Photos)</div>
                <div class="gallery-grid">
                    <img src="https://images.unsplash.com/photo-1487754180451-c456f719a1fc?w=200" alt="Photo 1">
                    <img src="https://images.unsplash.com/photo-1530046339160-ce3e530c7d2f?w=200" alt="Photo 2">
                    <img src="https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?w=200" alt="Photo 3">
                    <img src="https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=200" alt="Photo 4">
                </div>
                <p style="margin-top:1rem;font-size:0.85rem;color:#666;">Upgrade to Pro+ for unlimited photos and video</p>
            </div>
        </div>

        <!-- Reviews Tab -->
        <div id="reviews" class="tab-content">
            <div class="card">
                <div class="card-title">Customer Reviews</div>
                <p style="color:#666;">No reviews yet. Be the first to review!</p>
            </div>
        </div>
        
        <!-- About Tab -->
        <div id="about" class="tab-content">
            <div class="card">
                <div class="card-title">About This Business</div>
                <p style="line-height:1.6;color:#333;"><?php echo esc_html($business->post_content ?: 'Welcome to ' . $business->post_title . '. We look forward to serving you!'); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(function(el) { el.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(el) { el.classList.remove('active'); });
    document.getElementById(tabId).classList.add('active');
    event.target.classList.add('active');
}
function showRedeemModal() {
    alert('Offer redemption coming soon!');
}
</script>

<!-- Review Modal for Pro -->
<style>
.review-modal-backdrop { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; }
.review-modal-backdrop.active { display: flex; }
.review-modal { background: white; border-radius: 12px; padding: 1.5rem; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; position: relative; }
.review-modal-close { position: absolute; top: 10px; right: 15px; font-size: 1.5rem; cursor: pointer; border: none; background: none; }
.review-modal h3 { margin-bottom: 1rem; color: #1a1a1a; }
.rating-category { margin-bottom: 1rem; }
.rating-category .rating-label { display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem; }
.pin-rating { display: flex; gap: 4px; }
.pin-rating .pin { height: 20px; opacity: 0.3; cursor: pointer; }
.pin-rating .pin.active { opacity: 1; }
.review-modal textarea, .review-modal input[type="text"] { width: 100%; padding: 0.75rem; margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; }
.review-modal .submit-btn { width: 100%; padding: 0.75rem; background: #e63946; color: white; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; }
#reviewStatusPro { margin-top: 0.5rem; font-size: 0.9rem; }
</style>
<div class="review-modal-backdrop" id="reviewModalPro">
    <div class="review-modal">
        <button class="review-modal-close" onclick="document.getElementById('reviewModalPro').classList.remove('active');">&times;</button>
        <h3>How was the business?</h3>
        <input type="hidden" id="businessIdPro" value="<?php echo get_the_ID(); ?>" />
        <div class="rating-category"><span class="rating-label">Overall Experience</span><div class="pin-rating" data-category="overall-pro"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"></div></div>
        <div class="rating-category"><span class="rating-label">Quality</span><div class="pin-rating" data-category="quality-pro"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"></div></div>
        <div class="rating-category"><span class="rating-label">Service</span><div class="pin-rating" data-category="service-pro"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"></div></div>
        <div class="rating-category"><span class="rating-label">Value</span><div class="pin-rating" data-category="value-pro"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"></div></div>
        <div class="rating-category"><span class="rating-label">Atmosphere</span><div class="pin-rating" data-category="atmosphere-pro"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"></div></div>
        <textarea id="reviewTextPro" placeholder="Write a review (What should other customers know?)"></textarea>
        <input type="text" id="reviewTitlePro" placeholder="Title your review (required)" />
        <input type="text" id="reviewerNamePro" placeholder="Your public name (required)" value="Bryan Somers" />
        <button class="submit-btn" id="submitReviewPro">Submit</button>
        <p id="reviewStatusPro" style="display:none;"></p>
    </div>
</div>
<script>
document.querySelectorAll('.pin-rating').forEach(function(container){
    const pins = container.querySelectorAll('.pin');
    pins.forEach(function(pin, idx){
        pin.addEventListener('click', function(){
            pins.forEach((p,i)=>{ p.classList.toggle('active', i<=idx); });
        });
    });
});
document.querySelectorAll('[onclick*="reviewModal"]').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.getElementById('reviewModalPro').classList.add('active');
    });
});
document.getElementById('submitReviewPro').addEventListener('click', function(){
    const businessId = document.getElementById('businessIdPro').value;
    const reviewerName = document.getElementById('reviewerNamePro').value.trim();
    const reviewTitle = document.getElementById('reviewTitlePro').value.trim();
    const reviewText = document.getElementById('reviewTextPro').value.trim();
    
    if (!businessId || !reviewerName || !reviewTitle) {
        document.getElementById('reviewStatusPro').style.display = 'block';
        document.getElementById('reviewStatusPro').style.color = '#e63946';
        document.getElementById('reviewStatusPro').textContent = 'Please fill in your name and review title.';
        return;
    }
    
    function getRating(category) {
        const container = document.querySelector('.pin-rating[data-category="' + category + '-pro"]');
        if (!container) return 0;
        return container.querySelectorAll('.pin.active').length;
    }
    
    const ratingData = {
        business_id: parseInt(businessId),
        reviewer_name: reviewerName,
        rating_overall: getRating('overall'),
        rating_quality: getRating('quality'),
        rating_service: getRating('service'),
        rating_value: getRating('value'),
        rating_atmosphere: getRating('atmosphere'),
        title: reviewTitle,
        review_text: reviewText
    };
    
    document.getElementById('reviewStatusPro').style.display = 'block';
    document.getElementById('reviewStatusPro').style.color = '#666';
    document.getElementById('reviewStatusPro').textContent = 'Submitting...';
    
    fetch('/wp-json/tln/v1/reviews', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(ratingData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('reviewStatusPro').style.color = '#2a9d8f';
            document.getElementById('reviewStatusPro').textContent = 'Thanks! Your review has been submitted.';
            setTimeout(function(){
                document.getElementById('reviewModalPro').classList.remove('active');
                document.getElementById('reviewTextPro').value = '';
                document.getElementById('reviewTitlePro').value = '';
                document.querySelectorAll('.pin-rating .pin').forEach(p => p.classList.remove('active'));
                document.getElementById('reviewStatusPro').style.display = 'none';
            }, 1500);
        } else {
            throw new Error(data.error || 'Unknown error');
        }
    })
    .catch(err => {
        document.getElementById('reviewStatusPro').style.color = '#e63946';
        document.getElementById('reviewStatusPro').textContent = 'Error: ' + err.message;
    });
});
</script>

<?php get_footer(); ?>