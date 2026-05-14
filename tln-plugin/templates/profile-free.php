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
        /* Ad Slot Styles */
        .ad-slot { background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; padding: 0.5rem; text-align: center; margin-bottom: 1rem; }
        .ad-slot-label { font-size: 0.7rem; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.25rem; }
        .ad-slot-content { display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 0.85rem; }
        /* Sidebar Box Ad - 300x250 */
        .ad-box { background: #f5f5f5; border: 2px dashed #ddd; border-radius: 8px; width: 100%; height: 250px; display: flex; align-items: center; justify-content: center; }
        /* Mobile Ad - 320x100 */
        .ad-mobile { display: none; }
        @media(max-width: 800px) { .ad-mobile { display: block; } }
        @media(max-width: 800px) {
            .container { grid-template-columns: 1fr; }
            .services-grid { grid-template-columns: repeat(2, 1fr); }
            .biz-name { font-size: 1.75rem; }
            .biz-address { font-size: 0.95rem; }
        }
    </style>
    <?php wp_head(); ?>
</head>
<body>
    <!-- Ad Request Modal for Unclaimed Businesses -->
    <div id="adModal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:2000;">
        <div style="background:white;max-width:500px;width:90%;padding:2rem;border-radius:12px;position:relative;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
            <button onclick="dismissAdModal()" style="position:absolute;top:1rem;right:1rem;background:none;border:none;font-size:1.5rem;cursor:pointer;color:#666;">×</button>
            <h2 style="margin-top:0;color:var(--red);">Get Noticed by Neighbors!</h2>
            <p style="color:#666;margin-bottom:1rem;">This business page isn't claimed yet. Want to promote your business here?</p>
            <div style="background:#fef2f2;border-radius:8px;padding:1rem;margin-bottom:1.5rem;">
                <strong style="color:var(--red);">$25/month</strong> — Your ad appears on this page with a special offer for visitors.<br>
                <span style="font-size:0.85rem;color:#666;">No commitment, cancel anytime.</span>
            </div>
            <a href="/tln-ad-request.html?biz=<?php echo urlencode($biz['name']); ?>&pid=<?php echo urlencode($biz['place_id']); ?>" style="display:block;width:100%;padding:1rem;background:var(--red);color:white;text-align:center;border-radius:8px;font-weight:700;text-decoration:none;font-size:1.1rem;">Advertise Here</a>
            <button onclick="dismissAdModal()" style="display:block;width:100%;margin-top:0.75rem;padding:0.75rem;background:#f5f5f5;color:#666;border:none;border-radius:6px;cursor:pointer;">Maybe Later</button>
        </div>
    </div>
    <script>
    function dismissAdModal() {
        document.getElementById('adModal').style.display='none';
        localStorage.setItem('tln_ad_modal_dismissed', '<?php echo $biz['place_id']; ?>|' + new Date().getTime());
    }
    // Check if already dismissed for this business
    var dismissed = localStorage.getItem('tln_ad_modal_dismissed');
    if (dismissed) {
        var parts = dismissed.split('|');
        if (parts[0] === '<?php echo $biz['place_id']; ?>') {
            // Dismissed for this place - check if within 7 days
            var dismissedTime = parseInt(parts[1]);
            var now = new Date().getTime();
            if (now - dismissedTime < 7 * 24 * 60 * 60 * 1000) {
                document.getElementById('adModal').style.display='none';
            }
        }
    }
    </script>
    <!-- Mobile Ad Slot -->
    <div class="ad-mobile" style="display:none;background:#f9f9f9;padding:0.5rem;text-align:center;">
        <div class="ad-slot-label" style="font-size:0.7rem;color:#999;text-transform:uppercase;letter-spacing:1px;margin-bottom:0.25rem;">Advertisement</div>
        <div style="background:#fff;border:2px dashed #ccc;border-radius:8px;height:100px;display:flex;align-items:center;justify-content:center;color:#888;">
            <div><strong>Your Ad Here</strong><br><span style="font-size:0.75rem;">$25/mo – <a href="/tln-ad-request.html" style="color:var(--red);">Advertise your business on this page</a></span></div>
        </div>
    </div>

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
            <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/support-local-businesses.webp" alt="Support Local Businesses" style="width:100%;border-radius:8px;margin-bottom:0.5rem;">
            <a href="/tln-ad-request.html" style="display:block;text-align:center;padding:3px 0;color:#666;font-size:0.9rem;font-weight:600;"><img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/online-advertising.png" style="height:16px;vertical-align:middle;margin-right:4px;">Advertise Your Business</a>
            <!-- What Neighbors Say Section - hidden until we have real per-business data -->
            <!-- What Neighbors Say - Call to action for first review -->
            <div class="card">
                <div class="card-title">What Neighbors Say</div>
                <p style="font-size:0.9rem;color:#666;margin-bottom:1rem;">Be the first to leave a Neighborhood Review Score for this business and help others in our community know what they're about.</p>
                <button onclick="document.getElementById('reviewModal').style.display='flex';" class="claim-btn" style="display:inline-block;padding:0.75rem 1.5rem;background:var(--red);color:white;border:none;border-radius:6px;font-weight:700;cursor:pointer;width:100%;">Leave a Review</button>
            </div>

            <div class="card">
                <div class="hours-header">
                    <h3>Hours</h3>
                    <span class="hours-pill open">Open</span>
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

            <!-- Sidebar Ad -->
            <div class="ad-slot" style="background:#fefaf9;border-color:#f0e0e0;">
                <div class="ad-slot-label">Advertisement</div>
                <div class="ad-box" style="border-style:dashed;border-color:#ccc;background:#fff;">
                    <div class="ad-slot-content" style="color:#888;">
                        <strong>Your Ad Here</strong><br>
                        <span style="font-size:0.75rem;">$35/mo – <a href="/tln-ad-request.html" style="color:var(--red);">Advertise your business on this page</a></span>
                    </div>
                </div>
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

            <div class="claim-box" style="background:#f5f5f5;border:1px solid #ddd;">
                <h3 style="color:#333;">Claim This Page</h3>
                <p style="color:#666;">Not ready to claim yet? <a href="/tln-ad-request.html" style="color:var(--red);">Advertise your business on this page</a> for just <strong style="color:var(--red);">$35/mo</strong> <span style="text-decoration:line-through;color:#999;font-size:0.85rem;">$50</span></p>
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
        <textarea placeholder="Write a review (What should other customers know?)"></textarea>
        <input type="text" placeholder="Title your review (required)" />
        <input type="text" placeholder="Your public name (required)" value="Bryan Somers" />
        <button class="submit-btn">Submit</button>
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
    // Simple submit handler (placeholder)
    document.querySelector('.submit-btn').addEventListener('click', function(){
        alert('Review submitted – backend integration pending.');
        document.getElementById('reviewModal').style.display='none';
    });
</script>
<?php wp_footer(); ?>
</body>
</html>
