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

$is_verified = ($tier !== 'free');
$is_featured = in_array($tier, ['proplus', 'sponsor']);
$is_pro = in_array($tier, ['pro', 'proplus', 'sponsor']);
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
    .badge-featured { background: #ffc107; color: #333; }
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
    .tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid #eee; }
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
    @media(max-width: 800px) { .container { grid-template-columns: 1fr; } .services-grid { grid-template-columns: repeat(2, 1fr); } }
</style>

<div style="position:absolute;top:1rem;right:1rem;z-index:10;">
    <a href="/business-login" style="background:rgba(0,0,0,0.7);color:white;padding:0.5rem 1rem;border-radius:6px;text-decoration:none;font-size:0.85rem;font-weight:600;">Business Login</a>
</div>

<div class="hero">
    <div class="biz-info">
        <div class="badge-pills">
            <?php if ($is_pro): ?><span class="badge-pill badge-pro">Pro Business</span><?php endif; ?>
            <?php if ($is_featured): ?><span class="badge-pill badge-featured">Featured</span><?php endif; ?>
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
                <span style="<?php echo $bold; ?>"><?php echo $labels[$i]; ?>..: <span class="hours-time" style="<?php echo $bold; ?>"><?php echo esc_html($hours_arr[$i]); ?></span></span><br>
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
            <button class="tab-btn" onclick="showTab('contact')">Contact</button>
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
        
        <!-- Gallery Tab -->
        <div id="gallery" class="tab-content">
            <div class="card">
                <div class="card-title">Photo Gallery</div>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.5rem;">
                    <img src="https://images.unsplash.com/photo-1487754180451-c456f719a1fc?w=200" style="width:100%;height:100px;object-fit:cover;border-radius:4px;">
                    <img src="https://images.unsplash.com/photo-1530046339160-ce3e530c7d2f?w=200" style="width:100%;height:100px;object-fit:cover;border-radius:4px;">
                    <img src="https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?w=200" style="width:100%;height:100px;object-fit:cover;border-radius:4px;">
                    <img src="https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=200" style="width:100%;height:100px;object-fit:cover;border-radius:4px;">
                </div>
            </div>
        </div>
        
        <!-- Reviews Tab -->
        <div id="reviews" class="tab-content">
            <div class="card">
                <div class="card-title">Write a Review</div>
                <p style="font-size:0.9rem;color:#666;margin-bottom:0.5rem;">Rate this business:</p>
                <div style="display:flex;gap:0.25rem;margin:1rem 0;">
                    <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:32px;opacity:0.3;cursor:pointer;" onclick="setRating(1)">
                    <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:32px;opacity:0.3;cursor:pointer;" onclick="setRating(2)">
                    <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:32px;opacity:0.3;cursor:pointer;" onclick="setRating(3)">
                    <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:32px;opacity:0.3;cursor:pointer;" onclick="setRating(4)">
                    <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:32px;opacity:0.3;cursor:pointer;" onclick="setRating(5)">
                </div>
                <textarea placeholder="Share your experience..." style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;margin:1rem 0;"></textarea>
                <button class="redeem-btn" onclick="alert('Thank you! Your review has been submitted.')">Submit Review</button>
            </div>
            <div class="card" style="margin-top:1rem;">
                <div class="card-title">TLN Reviews</div>
                <div style="padding:0.75rem 0;border-bottom:1px solid #eee;">
                    <div style="font-weight:700;">Mike T.</div>
                    <div style="color:var(--red);">Score: 5</div>
                    <div style="color:#555;">Great service!</div>
                </div>
            </div>
        </div>
        
        <!-- About Tab -->
        <div id="about" class="tab-content">
            <div class="card">
                <div class="card-title">About <?php echo esc_html($business->post_title); ?></div>
                <p><?php echo $business->post_content ?: 'No description added yet.'; ?></p>
            </div>
        </div>
        
        <!-- Contact Tab -->
        <div id="contact" class="tab-content">
            <div class="card">
                <div class="card-title">Send a Message</div>
                <div style="background:#f8f8f8;padding:1.5rem;border-radius:8px;">
                    <input type="text" placeholder="Your Name" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;margin-bottom:0.75rem;">
                    <input type="email" placeholder="Your Email" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;margin-bottom:0.75rem;">
                    <input type="tel" placeholder="Your Phone" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;margin-bottom:0.75rem;">
                    <textarea rows="4" placeholder="Your message..." style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;margin-bottom:0.75rem;"></textarea>
                    <button class="redeem-btn" onclick="alert('Message sent!')">Send Message</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Redeem Modal -->
<div id="redeemEmailModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);align-items:center;justify-content:center;z-index:1000;">
    <div style="background:white;padding:2rem;border-radius:12px;max-width:400px;width:90%;text-align:center;">
        <h3 style="color:var(--red);margin-bottom:1rem;">Redeem Offer</h3>
        <p style="color:#666;margin-bottom:1rem;">Enter your email to receive your redemption code.</p>
        <input type="email" id="redeemEmail" placeholder="your@email.com" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;margin-bottom:1rem;">
        <button onclick="confirmRedeem()" style="background:var(--red);color:white;border:none;padding:0.75rem;width:100%;border-radius:6px;font-weight:600;cursor:pointer;">Get Code</button>
        <button onclick="document.getElementById('redeemEmailModal').style.display='none'" style="background:none;border:none;color:#666;margin-top:0.75rem;cursor:pointer;">Cancel</button>
    </div>
</div>

<script>
function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    document.getElementById(tabId).classList.add('active');
    event.target.classList.add('active');
}

function showRedeemModal() {
    document.getElementById('redeemEmailModal').style.display = 'flex';
}

function confirmRedeem() {
    var email = document.getElementById('redeemEmail').value;
    if (!email || !email.includes('@')) {
        alert('Please enter a valid email address.');
        return;
    }
    document.getElementById('redeemEmailModal').style.display = 'none';
    alert('Offer redeemed! Show this message at the business.');
}

function setRating(num) {
    alert('Thank you! You left a score of ' + num + '.');
}

// Hours status
(function() {
    var now = new Date();
    var day = now.getDay();
    var hour = now.getHours();
    var isOpen = false;
    var closingSoon = false;
    
    if (day >= 1 && day <= 5) {
        if (hour >= 7 && hour < 18) {
            isOpen = true;
            if (hour === 16 || hour === 17) closingSoon = true;
        }
    } else if (day === 6) {
        if (hour >= 8 && hour < 14) {
            isOpen = true;
            if (hour === 13) closingSoon = true;
        }
    }
    
    var el = document.getElementById('hoursStatus');
    if (el) {
        if (isOpen) {
            el.innerHTML = closingSoon ? 'Closing Soon' : 'Open';
            el.className = 'hours-pill ' + (closingSoon ? 'closing-soon' : 'open');
        } else {
            el.innerHTML = 'Closed';
            el.className = 'hours-pill closed';
        }
    }
})();
</script>

<!-- Review Modal -->
<div id="reviewModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;max-width:500px;width:95%;padding:2rem;border-radius:12px;position:relative;max-height:90vh;overflow-y:auto;">
        <span onclick="document.getElementById('reviewModal').style.display='none';" style="position:absolute;top:10px;right:15px;font-size:2rem;cursor:pointer;line-height:1;">&times;</span>
        <h2 style="margin-bottom:1rem;">Rate Your Experience</h2>
        <p style="color:#666;margin-bottom:1.5rem;">Help your neighbors know what this business is really like.</p>
        
        <div style="margin:1rem 0;"><div style="font-weight:600;margin-bottom:0.5rem;">Quality</div><div class="pin-rating" data-cat="quality" style="display:flex;gap:2px;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"></div></div>
        <div style="margin:1rem 0;"><div style="font-weight:600;margin-bottom:0.5rem;">Service</div><div class="pin-rating" data-cat="service" style="display:flex;gap:2px;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"></div></div>
        <div style="margin:1rem 0;"><div style="font-weight:600;margin-bottom:0.5rem;">Value</div><div class="pin-rating" data-cat="value" style="display:flex;gap:2px;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"></div></div>
        <div style="margin:1rem 0;"><div style="font-weight:600;margin-bottom:0.5rem;">Atmosphere</div><div class="pin-rating" data-cat="atmosphere" style="display:flex;gap:2px;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;"></div></div>
        
        <textarea placeholder="Write a review (What should other customers know?)" style="width:100%;padding:0.75rem;margin:1rem 0;border:1px solid #ddd;border-radius:6px;min-height:100px;"></textarea>
        <input type="text" placeholder="Your public name (required)" style="width:100%;padding:0.75rem;margin-bottom:1rem;border:1px solid #ddd;border-radius:6px;" />
        <input type="hidden" id="businessIdProplus" value="<?php echo get_the_ID(); ?>" />
<textarea id="reviewTextProplus" placeholder="Write a review (What should other customers know?)" style="width:100%;padding:0.75rem;margin:1rem 0;border:1px solid #ddd;border-radius:6px;min-height:100px;"></textarea>
<input type="text" id="reviewTitleProplus" placeholder="Title your review (required)" style="width:100%;padding:0.75rem;margin-bottom:1rem;border:1px solid #ddd;border-radius:6px;" />
<input type="text" id="reviewerNameProplus" placeholder="Your public name (required)" style="width:100%;padding:0.75rem;margin-bottom:1rem;border:1px solid #ddd;border-radius:6px;" value="Bryan Somers" />
<button id="submitReviewProplus" style="width:100%;padding:0.75rem;background:#e63946;color:white;border:none;border-radius:6px;font-weight:700;cursor:pointer;">Submit Review</button>
<p id="reviewStatusProplus" style="display:none;margin-top:0.5rem;font-size:0.9rem;"></p>
    </div>
</div>
<script>document.querySelectorAll('.pin-rating').forEach(function(c){c.querySelectorAll('img').forEach(function(p,i){p.addEventListener('click',function(){c.querySelectorAll('img').forEach(function(img,j){img.style.opacity=j<=i?'1':'0.3'})})})});</script>

<?php get_footer(); ?>
