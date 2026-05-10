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
    get_post_meta(get_the_ID(), 'tln_hours_mon', true),
    get_post_meta(get_the_ID(), 'tln_hours_tue', true),
    get_post_meta(get_the_ID(), 'tln_hours_wed', true),
    get_post_meta(get_the_ID(), 'tln_hours_thu', true),
    get_post_meta(get_the_ID(), 'tln_hours_fri', true),
    get_post_meta(get_the_ID(), 'tln_hours_sat', true),
    get_post_meta(get_the_ID(), 'tln_hours_sun', true),
);

$is_verified = ($tier !== 'free');
$is_featured = in_array($tier, ['proplus', 'sponsor']);
?>

<style>
    :root { --red: #e63946; --black: #1a1a1a; }
    .hero { position: relative; height: 280px; background: #333; }
    .biz-info { position: absolute; bottom: 1.5rem; left: 2rem; }
    .biz-name { font-size: 2.5rem; font-weight: 700; color: white; }
    .badge-pills { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; }
    .badge-pill { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
    .badge-pro { background: var(--red); color: white; }
    .badge-featured { background: #ffc107; color: #333; }
    .badge-verified { background: #28a745; color: white; }
    .container { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; display: grid; grid-template-columns: 320px 1fr; gap: 2rem; }
    .left-col, .right-col { display: flex; flex-direction: column; gap: 1.5rem; }
    .card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 1.25rem; }
    .card-title { font-size: 1rem; font-weight: 700; margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem; }
    .score-box { background: #f8f8f8; border: 2px solid var(--red); border-radius: 8px; padding: 1.25rem; text-align: center; }
    .score-display { font-size: 2.5rem; font-weight: 700; color: var(--red); }
    .hours-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #f0f0f0; }
    .contact-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0; }
    .impact-box { background: var(--red); color: white; border-radius: 8px; padding: 1rem; display: flex; align-items: center; gap: 0.75rem; }
    .tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid #eee; }
    .tab-btn { background: none; border: none; padding: 0.75rem 1rem; font-weight: 600; color: #666; cursor: pointer; border-bottom: 3px solid transparent; }
    .tab-btn.active { color: var(--red); border-bottom-color: var(--red); }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    @media(max-width: 800px) { .container { grid-template-columns: 1fr; } }
</style>

<div style="position:absolute;top:1rem;right:1rem;z-index:10;">
    <a href="/business-login" style="background:rgba(0,0,0,0.7);color:white;padding:0.5rem 1rem;border-radius:6px;text-decoration:none;">Business Login</a>
</div>

<div class="hero">
    <div class="biz-info">
        <div class="badge-pills">
            <?php if ($tier !== 'free'): ?><span class="badge-pill badge-pro">Pro Business</span><?php endif; ?>
            <?php if ($is_featured): ?><span class="badge-pill badge-featured">Featured</span><?php endif; ?>
            <?php if ($is_verified): ?><span class="badge-pill badge-verified">Verified</span><?php endif; ?>
        </div>
        <div class="biz-name"><?php echo esc_html($business->post_title); ?></div>
        <div style="color:white;"><?php echo esc_html($city.', '.$state); ?></div>
    </div>
</div>

<div class="container">
    <div class="left-col">
        <div class="score-box">
            <h3>TLN Neighborhood Score</h3>
            <div class="score-display"><?php echo esc_html($tln_score ?: '4.5'); ?> <span>/ 5</span></div>
        </div>
        <div class="score-box" style="border-color:#4285f4;">
            <h3>Google Rating</h3>
            <div class="score-display" style="color:#4285f4;"><?php echo esc_html($google_rating ?: '4.0'); ?> <span>/ 5</span></div>
        </div>
        <div class="card">
            <div class="card-title">Hours</div>
            <div class="hours-row"><span>Mon</span><span><?php echo esc_html($hours[0]); ?></span></div>
            <div class="hours-row"><span>Tue</span><span><?php echo esc_html($hours[1]); ?></span></div>
            <div class="hours-row"><span>Wed</span><span><?php echo esc_html($hours[2]); ?></span></div>
            <div class="hours-row"><span>Thu</span><span><?php echo esc_html($hours[3]); ?></span></div>
            <div class="hours-row"><span>Fri</span><span><?php echo esc_html($hours[4]); ?></span></div>
            <div class="hours-row"><span>Sat</span><span><?php echo esc_html($hours[5]); ?></span></div>
            <div class="hours-row"><span>Sun</span><span><?php echo esc_html($hours[6]); ?></span></div>
        </div>
        <div class="card">
            <div class="card-title">Contact</div>
            <div class="contact-item"><?php echo esc_html($phone); ?></div>
            <div class="contact-item"><?php echo esc_html($email); ?></div>
        </div>
        <?php if ($meals_count): ?>
        <div class="impact-box">
            <strong><?php echo esc_html($meals_count); ?> Meals</strong> provided to local families
        </div>
        <?php endif; ?>
    </div>
    
    <div class="right-col">
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('overview')">Overview</button>
            <button class="tab-btn" onclick="showTab('offers')">Offers</button>
            <button class="tab-btn" onclick="showTab('gallery')">Gallery</button>
            <button class="tab-btn" onclick="showTab('reviews')">Reviews</button>
        </div>
        
        <div id="overview" class="tab-content active">
            <div class="card">
                <div class="card-title">Special Offer</div>
                <div style="background:#fff8e5;border:1px solid #ffc107;padding:1rem;border-radius:8px;">
                    <h4>15% Off Any Service</h4>
                    <p>Free multi-point inspection.</p>
                </div>
            </div>
        </div>
        
        <div id="offers" class="tab-content"></div>
        <div id="gallery" class="tab-content"></div>
        <div id="reviews" class="tab-content"></div>
    </div>
</div>

<script>
function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    event.target.classList.add('active');
}
</script>

<?php get_footer(); ?>
