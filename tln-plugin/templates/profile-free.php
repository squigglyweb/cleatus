<?php
/*
Template Name: TLN Free Profile
*/
// Get business data from shortcode
global $tln_profile_business;
$biz = isset($tln_profile_business) ? $tln_profile_business : array(
    'name' => 'Business Name',
    'address' => '',
    'phone' => '',
    'rating' => '',
    'hours' => array(),
    'website' => '',
    'reviews' => array(),
    'photos' => array(),
);
$api_key = defined('TLN_GOOGLE_API_KEY') ? TLN_GOOGLE_API_KEY : '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>XYZ Repair - The Local NearBuy</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Open Sans', sans-serif; background: url('https://thelocalnearbuy.com/wp-content/uploads/2026/05/town-scene-bkgd-scaled.webp') center top no-repeat; background-size: cover; color: #1a1a1a; }
        :root { --red: #e63946; --black: #1a1a1a; }

        /* Hero Image */
        .hero {
            position: relative; height: 280px;
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.6)),
                        url('https://thelocalnearbuy.com/wp-content/uploads/2026/05/town-scene-bkgd-scaled.webp');
            background-size: cover; background-position: center;
        }

        /* Business Name & Address */
        .biz-info {
            position: absolute; bottom: 1.5rem; left: 1.5rem;
        }
        .biz-name {
            font-size: 2.5rem; font-weight: 700; color: white;
            margin-bottom: 1rem;
        }
        .biz-address {
            font-size: 1.1rem; color: white; display: flex; align-items: center; gap: 0.5rem;
        }

        /* Main Content */
        .container { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; display: grid; grid-template-columns: 320px 1fr; gap: 2rem; background: rgba(255,255,255,0.95); border-radius: 12px; margin-top: 1rem; margin-bottom: 1rem; }

        /* Left Column */
        .left-col { display: flex; flex-direction: column; gap: 1.5rem; }

        /* Right Column */
        .right-col { display: flex; flex-direction: column; gap: 1.5rem; }

        /* Cards */
        .card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 1.25rem; }
        .card-title { font-size: 1rem; font-weight: 700; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #eee; }

        /* Hours */
        .hours-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; position: relative; }
        .hours-header h3 { margin: 0; padding: 0; border: none; font-size: 1rem; }
        .hours-pill { position: absolute; right: 0; top: 50%; transform: translateY(-50%); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .hours-pill.open { background: #28a745; color: white; }
        .hours-pill.closed { background: #dc3545; color: white; }
        .hours-pill.closing-soon { background: #ffc107; color: #333; }
        .hours-time { font-weight: 700; }
        .hours-display { font-size: 14px; line-height: 1.8; }

        /* Contact */
        .contact-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0; }
        .contact-item a { color: var(--red); text-decoration: none; font-weight: 600; }

        /* Map Box */
        .map-box { background: #f5f5f5; height: 200px; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666; }

        /* Reviews */
        .review-item { padding: 0.75rem 0; border-bottom: 1px solid #eee; }
        .review-item:last-child { border-bottom: none; }
        .reviewer { font-weight: 700; font-size: 0.95rem; }
        .review-stars { color: #fbbf24; font-size: 0.85rem; margin: 0.25rem 0; }
        .review-text { color: #555; font-size: 0.9rem; }
        .see-all { color: var(--red); font-size: 0.85rem; font-weight: 600; margin-top: 0.75rem; display: block; }

        /* Services Grid */
        .services-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; }
        .service-btn {
            background: #f5f5f5; border: 1px solid #ddd;
            padding: 0.6rem 0.5rem; border-radius: 6px;
            font-size: 0.8rem; color: #333; text-align: center;
            cursor: pointer; transition: background 0.2s, border-color 0.2s;
        }
        .service-btn:hover { background: #eee; border-color: var(--red); }

        /* Claim Box */
        .claim-box {
            background: linear-gradient(135deg, #1a1a1a, #333);
            border-radius: 12px; padding: 1.5rem; text-align: center;
        }
        .claim-box h3 { color: white; font-size: 1.1rem; margin-bottom: 0.5rem; }
        .claim-box p { color: #ccc; margin-bottom: 1rem; font-size: 0.9rem; }
        .claim-btn { 
            display: inline-block; padding: 0.75rem 1.5rem; 
            background: var(--red); color: white; 
            text-decoration: none; border-radius: 6px; font-weight: 700;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .claim-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(230,57,70,0.4); }
        /* New rating section styles */
        #neighbors-section { margin-bottom: 1.5rem; }
        .rating-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #eee; }
        .rating-header .card-title { margin: 0; padding: 0; border: none; }
        .avg-score { background: var(--red); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-weight: 700; font-size: 0.9rem; }
        .rating-category { display: flex; align-items: center; margin: 0.5rem 0; }
        .rating-label { flex: 1; font-weight: 600; font-size: 0.9rem; }
        .pin-rating { display: flex; gap: 2px; }
        .pin-rating .pin { height: 16px; opacity: 0.3; }
        .pin-rating .pin.active { opacity: 1; }
        .review-btn { margin-top: 0.8rem; padding: 0.5rem 1rem; background: var(--red); color: white; border: none; border-radius: 4px; cursor: pointer; display: block; width: 100%; }
        /* Modal styles */
        .modal-backdrop { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index: 1000; }
        .modal { background:white; max-width:500px; width:90%; padding:1rem; border-radius:8px; position:relative; }
        .modal h3 { margin-top:0; }
        .modal .close-btn { position:absolute; top:0.5rem; right:0.5rem; background:none; border:none; font-size:1.2rem; cursor:pointer; }
        .modal textarea, .modal input[type="text"] { width:100%; padding:0.5rem; margin:0.5rem 0; border:1px solid #ccc; border-radius:4px; }
        .modal .submit-btn { background: var(--red); color:white; border:none; padding:0.5rem 1rem; border-radius:4px; cursor:pointer; }

        @media(max-width: 800px) {
            .container { grid-template-columns: 1fr; padding: 1rem 0.75rem; gap: 1rem; margin-top: 0.5rem; margin-bottom: 0.5rem; }
            .services-grid { grid-template-columns: repeat(2, 1fr); }
            .biz-name { font-size: 1.75rem; }
            .biz-address { font-size: 0.95rem; }
            .hero { height: 200px; }
            .biz-info { bottom: 1rem; left: 1rem; }
            .card { padding: 1rem; }
        }
        @media(max-width: 480px) {
            .container { padding: 0.75rem 0.5rem; }
            .biz-name { font-size: 1.4rem; }
            .hero { height: 180px; }
        }
    </style>
    <?php wp_head(); ?>
</head>
<body>

    <!-- Hero -->
    <div style="position:absolute;top:1rem;right:1rem;z-index:10;">
                <a href="#" style="background:rgba(0,0,0,0.7);color:white;padding:0.5rem 1rem;border-radius:6px;text-decoration:none;font-size:0.85rem;font-weight:600;">Business Login</a>
            </div>

            <div class="hero">
        <div class="biz-info">
            <div class="biz-name"><?php echo esc_html($biz['name']); ?></div>
            <?php 
            // Clean up address - remove USA and take first line only
            $address = $biz['address'];
            $address = str_replace(', USA', '', $address);
            $address_lines = explode(',', $address);
            $address = $address_lines[0]; // Just first part (Waxhaw address)
            ?>
            <div class="biz-address"><?php echo esc_html($address); ?>
                2300 E Providence Dr, Charlotte, NC 28270
            </div>
        </div>
    </div>



    <!-- Main Content -->
    <div class="container">
        <!-- Left Column -->
        <div class="left-col">
        <!-- Left Column -->
            <!-- What Neighbors Say - Call to action for first review -->
            <div class="card">
                <div class="card-title">What Neighbors Say</div>
                <p style="font-size:0.9rem;color:#666;margin-bottom:1rem;">Be the first to leave a Neighborhood Review Score for this business and help others in our community know what they're about.</p>
                <button onclick="document.getElementById('reviewModal').style.display='flex';" class="claim-btn" style="display:inline-block;padding:0.75rem 1.5rem;background:var(--red);color:white;border:none;border-radius:6px;font-weight:700;cursor:pointer;width:100%;">Leave a Review</button>
            </div>

            <div class="card">
                <div class="hours-header">
                    <h3>Hours</h3>
                    <?php
                    $is_open = false;
                    $closing_soon = false;
                    if (!empty($biz['hours'])) {
                        $today = strtolower(date('l'));
                        $now = current_time('G:i');
                        $current_day = strtolower(date('l'));
                        foreach ($biz['hours'] as $day_hours) {
                            if (preg_match('/^([^:]+):\s*(.+)$/', $day_hours, $m)) {
                                $day_name = strtolower(trim($m[1]));
                                if ($day_name === $current_day && trim($m[2]) !== 'Closed') {
                                    $hours_str = trim($m[2]);
                                    if (preg_match('/(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/', $hours_str, $hm)) {
                                        $open_time = sprintf('%02d:%02d', $hm[1], $hm[2]);
                                        $close_time = sprintf('%02d:%02d', $hm[3], $hm[4]);
                                        if ($now >= $open_time && $now < $close_time) {
                                            $is_open = true;
                                            // Check if closing within 1 hour
                                            $close_minutes = $hm[3] * 60 + $hm[4];
                                            $now_minutes = date('G') * 60 + date('i');
                                            if ($close_minutes - $now_minutes <= 60) {
                                                $closing_soon = true;
                                            }
                                        }
                                    }
                                    break;
                                }
                            }
                        }
                    }
                    ?>
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
                <?php if (!empty($biz['hours'])): ?>
                <div class="hours-display">
                    <?php $today = strtolower(date('l')); ?>
                    <?php foreach ($biz['hours'] as $i => $day_hours): ?>
                    <?php $day_name = strtolower(explode(':',$day_hours)[0]); $bold = ($day_name==$today)?'font-weight:700;':''; ?>
                    <?php if (preg_match('/^([^:]+):\s*(.+)$/', $day_hours, $m)): ?>
                    <span style="<?php echo $bold; ?>"><?php echo trim($m[1]); ?>..: <span class="hours-time" style="<?php echo $bold; ?>"><?php echo trim($m[2]); ?></span></span><br>
                    <?php else: ?>
                    <span><?php echo esc_html($day_hours); ?></span><br>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color:#666;font-size:0.9rem;">Hours not available</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-title">Contact</div>
                <?php if (!empty($biz['phone'])): ?>
                <div class="contact-item">
                    <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/call.png" style="height:18px;">
                    <a href="tel:<?php echo esc_attr($biz['phone']); ?>"><?php echo esc_html($biz['phone']); ?></a>
                </div>
                <?php endif; ?>
                <div class="contact-item">
                    <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:18px;">
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($biz['address']); ?>" target="_blank">Get Directions</a>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="right-col">
            <div class="card">
                <div class="card-title">Google Reviews</div>
                <?php if (!empty($biz['reviews'])): ?>
                    <div id="reviews-list">
                    <?php foreach (array_slice($biz['reviews'], 0, 2) as $review): ?>
                    <div class="review-item">
                        <div class="reviewer"><?php echo esc_html($review['author_name'] ?? 'Google User'); ?></div>
                        <div class="review-stars"><?php echo str_repeat('★', $review['rating'] ?? 5); ?></div>
                        <div class="review-text"><?php echo esc_html($review['text'] ?? ''); ?></div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                    <?php if (count($biz['reviews']) > 2): ?>
                    <a href="#" onclick="document.getElementById('reviews-list').innerHTML = '<?php echo addslashes(htmlspecialchars(implode('', array_map(function($r){return '<div class="review-item"><div class="reviewer">'.($r['author_name'] ?? 'Google User').'</div><div class="review-stars">'.str_repeat('★', $r['rating'] ?? 5).'</div><div class="review-text">'.($r['text'] ?? '').'</div></div>';}, $biz['reviews']))); ?>'; this.style.display='none'; return false;" class="see-all">Show all <?php echo count($biz['reviews']); ?> reviews →</a>
                    <?php endif; ?>
                <?php else: ?>
                <p style="color:#666;font-size:0.9rem;">No reviews yet.</p>
                <?php endif; ?>
            </div>

            <?php /* Services section - hidden until we have proper services data from business claims */ ?>


            <div class="card">
                <div class="card-title">Location</div>
                <iframe 
                    width="100%" 
                    height="200" 
                    style="border:0;border-radius:8px;"
                    loading="lazy" 
                    allowfullscreen 
                    referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.google.com/maps/embed/v1/place?key=&q=<?php echo urlencode($biz['address']); ?>">
                </iframe>
            </div>

            <div class="claim-box" style="background:#1a1a1a;border-radius:12px;padding:1.5rem;text-align:center;margin-top:1.5rem;">
                <h3 style="color:white;font-size:1.25rem;margin-bottom:0.5rem;">Claim Your Free Page</h3>
                <p style="color:#ccc;margin-bottom:1rem;">Claim your page and get a FREE gift plus exclusive perks for your business.</p>
                <a href="/claim/" class="claim-btn" style="background:var(--red);">Claim Your Page</a>
            </div>
        </div>
    </div>


<!-- Review Modal -->
<div class="modal-backdrop" id="reviewModal">
    <div class="modal">
        <button class="close-btn" id="closeModal">&times;</button>
        <h3>How was the business?</h3>
        <div class="rating-category">
            <span class="rating-label">Overall Experience</span>
            <div class="pin-rating" data-category="overall-modal">
                <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin">
            </div>
        </div>
        <div class="rating-category">
            <span class="rating-label">Quality</span>
            <div class="pin-rating" data-category="quality-modal">
                <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin">
            </div>
        </div>
        <div class="rating-category">
            <span class="rating-label">Service</span>
            <div class="pin-rating" data-category="service-modal">
                <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin">
            </div>
        </div>
        <div class="rating-category">
            <span class="rating-label">Value</span>
            <div class="pin-rating" data-category="value-modal">
                <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin">
            </div>
        </div>
        <div class="rating-category">
            <span class="rating-label">Atmosphere</span>
            <div class="pin-rating" data-category="atmosphere-modal">
                <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" class="pin">
            </div>
        </div>
        <input type="hidden" id="businessId" value="<?php echo get_the_ID(); ?>" />
        <textarea id="reviewText" placeholder="Write a review (What should other customers know?)"></textarea>
        <input type="text" id="reviewTitle" placeholder="Title your review (required)" />
        <input type="text" id="reviewerName" placeholder="Your public name (required)" value="Bryan Somers" />
        <button class="submit-btn" id="submitReviewBtn">Submit</button>
        <p id="reviewStatus" style="display:none;margin-top:0.5rem;font-size:0.9rem;"></p>
    </div>
</div>
<script>
    // Pin rating interaction
    document.querySelectorAll('.pin-rating').forEach(function(container){
        const pins = container.querySelectorAll('.pin');
        pins.forEach(function(pin, idx){
            pin.addEventListener('click', function(){
                pins.forEach((p,i)=>{ p.classList.toggle('active', i<=idx); });
            });
        });
    });
    // Modal open/close
    document.querySelector('.review-btn').addEventListener('click', function(){
        document.getElementById('reviewModal').style.display='flex';
    });
    document.getElementById('closeModal').addEventListener('click', function(){
        document.getElementById('reviewModal').style.display='none';
    });
    // Review submit handler
    document.getElementById('submitReviewBtn').addEventListener('click', function(){
        let businessId = document.getElementById('businessId').value;
        if (!businessId && typeof window.businessId !== 'undefined') { businessId = window.businessId; }
        const reviewerName = document.getElementById('reviewerName').value.trim();
        const reviewTitle = document.getElementById('reviewTitle').value.trim();
        const reviewText = document.getElementById('reviewText').value.trim();
        
        if (!businessId) {
            document.getElementById('reviewStatus').style.display = 'block';
            document.getElementById('reviewStatus').style.color = '#e63946';
            document.getElementById('reviewStatus').textContent = 'Error: Business ID not found.';
            return;
        }
        if (!reviewerName || !reviewTitle) {
            document.getElementById('reviewStatus').style.display = 'block';
            document.getElementById('reviewStatus').style.color = '#e63946';
            document.getElementById('reviewStatus').textContent = 'Please fill in your name and review title.';
            return;
        }
        
        // Get ratings from pin containers
        function getRating(category) {
            const container = document.querySelector('.pin-rating[data-category="' + category + '-modal"]');
            if (!container) return 0;
            const activePins = container.querySelectorAll('.pin.active');
            return activePins.length;
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
        
        document.getElementById('reviewStatus').style.display = 'block';
        document.getElementById('reviewStatus').style.color = '#666';
        document.getElementById('reviewStatus').textContent = 'Submitting...';
        
        fetch('/wp-json/tln/v1/reviews', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(ratingData)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('reviewStatus').style.color = '#2a9d8f';
                document.getElementById('reviewStatus').textContent = 'Thanks! Your review has been submitted.';
                setTimeout(function(){
                    document.getElementById('reviewModal').style.display = 'none';
                    document.getElementById('reviewText').value = '';
                    document.getElementById('reviewTitle').value = '';
                    document.querySelectorAll('.pin-rating .pin').forEach(p => p.classList.remove('active'));
                    document.getElementById('reviewStatus').style.display = 'none';
                }, 1500);
            } else {
                throw new Error(data.error || 'Unknown error');
            }
        })
        .catch(err => {
            document.getElementById('reviewStatus').style.color = '#e63946';
            document.getElementById('reviewStatus').textContent = 'Error: ' + err.message;
        });
    });
</script>
<?php wp_footer(); ?>
</body>
</html>
