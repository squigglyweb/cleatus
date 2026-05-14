<?php
/**
 * Template Name: TLN Pro Plus Profile
 * Description: Premium business profile with advanced layout.
 */

get_header();

// Get current post ID (the profile page created by page-profile.php)
$post_id = get_the_ID();

// Basic meta
$tier = get_post_meta($post_id, 'tln_tier', true);
$phone = get_post_meta($post_id, 'tln_phone', true);
$email = get_post_meta($post_id, 'tln_email', true);
$address = get_post_meta($post_id, 'tln_address', true);
$city = get_post_meta($post_id, 'tln_city', true);
$state = get_post_meta($post_id, 'tln_state', true);
$zip = get_post_meta($post_id, 'tln_zip', true);
$website = get_post_meta($post_id, 'tln_website', true);
$google_rating = get_post_meta($post_id, 'tln_google_rating', true);
$tln_score = get_post_meta($post_id, 'tln_neighborhood_score', true);
$meals_count = get_post_meta($post_id, 'tln_meals_count', true);

// Hours stored as separate meta fields (fallback defaults)
$hours = array(
    get_post_meta($post_id, 'tln_hours_mon', true) ?: '7:00 AM - 6:00 PM',
    get_post_meta($post_id, 'tln_hours_tue', true) ?: '7:00 AM - 6:00 PM',
    get_post_meta($post_id, 'tln_hours_wed', true) ?: '7:00 AM - 6:00 PM',
    get_post_meta($post_id, 'tln_hours_thu', true) ?: '7:00 AM - 6:00 PM',
    get_post_meta($post_id, 'tln_hours_fri', true) ?: '7:00 AM - 6:00 PM',
    get_post_meta($post_id, 'tln_hours_sat', true) ?: '7:00 AM - 6:00 PM',
    get_post_meta($post_id, 'tln_hours_sun', true) ?: '7:00 AM - 6:00 PM',
);
$day_labels = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');

// Video meta (5 spots + optional extra)
$videos = array();
for ($i=1;$i<=5;$i++) {
    $videos[$i] = get_post_meta($post_id, "tln_video_$i", true);
}
$extra_video_price = get_post_meta($post_id, 'tln_extra_video_price', true);

// Services – assume a repeating meta field like tln_service_1 => name, tln_service_icon_1 => url
$services = array();
for ($i=1;$i<=6;$i++) {
    $name = get_post_meta($post_id, "tln_service_$i", true);
    $icon = get_post_meta($post_id, "tln_service_icon_$i", true);
    if ($name) $services[] = array('name'=>$name,'icon'=>$icon);
}
?>

<div class="tln-proplus-profile container" style="max-width:1200px;margin:auto;padding:1rem;">
    <!-- Badge Row -->
    <div class="badge-row" style="display:flex;gap:0.5rem;margin-bottom:1rem;">
        <?php if ($tier === 'pro' || $tier === 'proplus'): ?>
            <span class="badge" style="background:#28a745;color:white;padding:0.3rem 0.6rem;border-radius:4px;">Pro Business</span>
        <?php endif; ?>
        <?php if (get_post_meta($post_id, 'tln_featured', true)): ?>
            <span class="badge" style="background:#007bff;color:white;padding:0.3rem 0.6rem;border-radius:4px;">Featured</span>
        <?php endif; ?>
        <?php if (get_post_meta($post_id, 'tln_verified', true)): ?>
            <span class="badge" style="background:#ffc107;color:#333;padding:0.3rem 0.6rem;border-radius:4px;">Verified</span>
        <?php endif; ?>
    </div>

    <!-- Title & Scores -->
    <h1 style="margin:0 0 0.5rem;"><?php the_title(); ?></h1>
    <div class="scores" style="display:flex;gap:1rem;margin-bottom:1rem;">
        <div class="tln-score" style="background:#f8f9fa;padding:0.5rem 1rem;border-radius:4px;">
            <strong>TLN Score:</strong> <?php echo esc_html($tln_score ?: 'N/A'); ?>
        </div>
        <div class="google-score" style="background:#f8f9fa;padding:0.5rem 1rem;border-radius:4px;">
            <strong>Google Rating:</strong> <?php echo esc_html($google_rating ?: 'N/A'); ?>
        </div>
    </div>

    <!-- Impact Box -->
    <div class="impact" style="background:#e9ecef;padding:0.75rem 1rem;border-radius:4px;margin-bottom:1rem;">
        <strong>Impact:</strong> <?php echo esc_html($meals_count ?: 0); ?> meals donated
    </div>

    <!-- Hours Box -->
    <div class="hours-box" style="margin-bottom:1rem;">
        <h3 style="margin:0 0 0.5rem;">Hours</h3>
        <div class="hours-display" style="font-size:14px;line-height:1.8;">
            <?php
            $today = strtolower(date('l'));
            foreach ($hours as $idx=>$time) {
                $day = strtolower($day_labels[$idx]);
                $bold = ($day === $today) ? 'font-weight:700;' : '';
                echo "<span style=\"$bold\">{$day_labels[$idx]}..: <span class='hours-time' style=\"$bold\">$time</span></span><br>\n";
            }
            ?>
        </div>
    </div>

    <!-- Contact Box -->
    <div class="contact-box" style="margin-bottom:1rem;">
        <h3 style="margin:0 0 0.5rem;">Contact</h3>
        <p style="margin:0.2rem 0;">Phone: <?php echo esc_html($phone ?: 'N/A'); ?></p>
        <p style="margin:0.2rem 0;">Email: <?php echo esc_html($email ?: 'N/A'); ?></p>
        <p style="margin:0.2rem 0;">Address: <?php echo esc_html($address); ?> <?php echo esc_html($city); ?> <?php echo esc_html($state); ?> <?php echo esc_html($zip); ?></p>
        <?php if ($website): ?>
            <p style="margin:0.2rem 0;">Website: <a href="<?php echo esc_url($website); ?>" target="_blank"><?php echo esc_html($website); ?></a></p>
        <?php endif; ?>
    </div>

    <!-- Tabs Navigation -->
    <div class="tabs" style="margin-top:2rem;">
        <ul class="tab-nav" style="display:flex;gap:0.5rem;list-style:none;padding:0;margin:0;border-bottom:1px solid #ddd;">
            <li><a href="#overview" class="tab-link active" style="padding:0.5rem 1rem;display:block;">Overview</a></li>
            <li><a href="#offers" class="tab-link" style="padding:0.5rem 1rem;display:block;">Offers</a></li>
            <li><a href="#gallery" class="tab-link" style="padding:0.5rem 1rem;display:block;">Gallery</a></li>
            <li><a href="#reviews" class="tab-link" style="padding:0.5rem 1rem;display:block;">Reviews</a></li>
            <li><a href="#about" class="tab-link" style="padding:0.5rem 1rem;display:block;">About</a></li>
            <li><a href="#contact" class="tab-link" style="padding:0.5rem 1rem;display:block;">Contact</a></li>
        </ul>
        <!-- Tab Content -->
        <div class="tab-content" style="padding:1rem;">
            <!-- Overview -->
            <div id="overview" class="tab-panel" style="display:block;">
                <?php the_content(); ?>
            </div>
            <!-- Offers -->
            <div id="offers" class="tab-panel" style="display:none;">
                <?php echo wp_kses_post( get_post_meta($post_id, 'tln_offers', true) ); ?>
            </div>
            <!-- Gallery -->
            <div id="gallery" class="tab-panel" style="display:none;">
                <?php
                $gallery = get_post_meta($post_id, 'tln_gallery', true);
                if ($gallery) {
                    $ids = explode(',', $gallery);
                    foreach ($ids as $img_id) {
                        $img_url = wp_get_attachment_url( trim($img_id) );
                        if ($img_url) {
                            echo "<img src='" . esc_url($img_url) . "' style='max-width:200px;margin:0.5rem;' loading='lazy'>";
                        }
                    }
                }
                ?>
            </div>
            <!-- Reviews -->
            <div id="reviews" class="tab-panel" style="display:none;">
                <?php
                // Simple display of stored Google reviews if saved in meta
                $reviews = get_post_meta($post_id, 'tln_google_reviews', true);
                if ($reviews && is_array($reviews)) {
                    foreach ($reviews as $rev) {
                        echo '<div style="margin-bottom:1rem;border-bottom:1px solid #eee;padding-bottom:0.5rem;">';
                        echo '<strong>'.esc_html($rev['author']).'</strong> – <em>'.esc_html($rev['rating']).'★</em>'; 
                        echo '<p>'.esc_html($rev['text']).'</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No reviews yet.</p>';
                }
                ?>
            </div>
            <!-- About -->
            <div id="about" class="tab-panel" style="display:none;">
                <?php echo wp_kses_post( get_post_meta($post_id, 'tln_about', true) ); ?>
            </div>
            <!-- Contact (duplicate) -->
            <div id="contact" class="tab-panel" style="display:none;">
                <?php // Reuse contact info ?>
                <p>Phone: <?php echo esc_html($phone ?: 'N/A'); ?></p>
                <p>Email: <?php echo esc_html($email ?: 'N/A'); ?></p>
                <p>Address: <?php echo esc_html($address); ?> <?php echo esc_html($city); ?> <?php echo esc_html($state); ?> <?php echo esc_html($zip); ?></p>
                <?php if ($website): ?>
                    <p>Website: <a href="<?php echo esc_url($website); ?>" target="_blank"><?php echo esc_html($website); ?></a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Special Offers Section (placeholder) -->
    <div class="special-offers" style="margin-top:2rem;background:#fff3cd;padding:1rem;border-radius:4px;">
        <h3>Special Offers</h3>
        <?php echo wp_kses_post( get_post_meta($post_id, 'tln_special_offers', true) ); ?>
    </div>

    <!-- Services Grid -->
    <?php if (!empty($services)): ?>
    <div class="services-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-top:2rem;">
        <?php foreach ($services as $svc): ?>
            <div class="service-item" style="text-align:center;padding:0.5rem;background:#f8f9fa;border-radius:4px;">
                <?php if (!empty($svc['icon'])): ?>
                    <img src="<?php echo esc_url($svc['icon']); ?>" alt="<?php echo esc_attr($svc['name']); ?>" style="max-width:40px;margin-bottom:0.5rem;" loading="lazy">
                <?php endif; ?>
                <div><?php echo esc_html($svc['name']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Video Spots -->
    <div class="video-spots" style="margin-top:2rem;">
        <h3>Videos</h3>
        <div class="videos" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
            <?php foreach ($videos as $idx=>$url): ?>
                <div class="video-slot" style="background:#e9ecef;padding:0.5rem;border-radius:4px;">
                    <?php if ($url): ?>
                        <video src="<?php echo esc_url($url); ?>" controls style="width:100%;height:auto;"></video>
                    <?php else: ?>
                        <p>Upload video <?php echo $idx; ?> (optional)</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($extra_video_price): ?>
            <div class="extra-video" style="margin-top:1rem;padding:0.5rem;background:#d4edda;border-radius:4px;">
                Add a 6th video for $<?php echo esc_html($extra_video_price); ?>.
            </div>
        <?php endif; ?>
    </div>

    <!-- Review Modal (same as free) -->
    <div id="reviewModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
        <div style="background:#fff;max-width:500px;width:95%;padding:2rem;border-radius:12px;position:relative;max-height:90vh;overflow-y:auto;">
            <span onclick="document.getElementById('reviewModal').style.display='none';" style="position:absolute;top:10px;right:15px;font-size:2rem;cursor:pointer;line-height:1;">&times;</span>
            <h2 style="margin-bottom:1rem;">Rate Your Experience</h2>
            <p style="color:#666;margin-bottom:1.5rem;">Help your neighbors know what this business is really like.</p>
            <?php
            $categories = array('quality','service','value','atmosphere');
            foreach ($categories as $cat): ?>
                <div style="margin:1rem 0;"><div style="font-weight:600;margin-bottom:0.5rem;"><?php echo ucfirst($cat); ?></div>
                    <div class="pin-rating" data-cat="<?php echo $cat; ?>" style="display:flex;gap:2px;">
                        <?php for ($i=1;$i<=5;$i++): ?>
                            <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/neighborhood-score-pin.png" style="height:16px;opacity:0.3;cursor:pointer;">
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <textarea placeholder="Write a review (What should other customers know?)" style="width:100%;padding:0.75rem;margin:1rem 0;border:1px solid #ddd;border-radius:6px;min-height:100px;"></textarea>
            <input type="text" placeholder="Your public name (required)" style="width:100%;padding:0.75rem;margin-bottom:1rem;border:1px solid #ddd;border-radius:6px;" />
            <button onclick="alert('Review submitted! Thanks for supporting local.');document.getElementById('reviewModal').style.display='none';" style="width:100%;padding:0.75rem;background:#e63946;color:white;border:none;border-radius:6px;font-weight:700;cursor:pointer;">Submit Review</button>
        </div>
    </div>
    <script>
        // Simple tab switching
        document.querySelectorAll('.tab-link').forEach(function(link){
            link.addEventListener('click',function(e){
                e.preventDefault();
                var target = this.getAttribute('href').substring(1);
                // hide all panels
                document.querySelectorAll('.tab-panel').forEach(function(p){p.style.display='none';});
                // show target
                document.getElementById(target).style.display='block';
                // active class
                document.querySelectorAll('.tab-link').forEach(function(l){l.classList.remove('active');});
                this.classList.add('active');
            });
        });
        // Pin rating interaction
        document.querySelectorAll('.pin-rating').forEach(function(c){
            c.querySelectorAll('img').forEach(function(p,i){
                p.addEventListener('click',function(){
                    c.querySelectorAll('img').forEach(function(img,j){
                        img.style.opacity=j<=i?'1':'0.3';
                    });
                });
            });
        });
    </script>
</div>

<?php get_footer(); ?>
