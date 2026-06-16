<?php /* Template Name: Services */ ?>
<?php get_header(); ?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h1>What We Handle</h1>
            <p>Three categories. Everything you need to grow.</p>
        </div>
        
        <!-- Digital Marketing -->
        <div class="mb-40" id="digital">
            <h2 class="text-center mb-40">Digital Marketing</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">📱</div>
                    <h3>Social Media Management</h3>
                    <p>We post to your Facebook and Instagram. Every day. No more blank pages.</p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">📢</div>
                    <h3>Facebook & Instagram Ads</h3>
                    <p>We run the ads. You get leads. We optimize for results, not just spend.</p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">🔍</div>
                    <h3>Google Business Profile</h3>
                    <p>You show up when locals search. We optimize and keep it updated.</p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">🌐</div>
                    <h3>Website & SEO</h3>
                    <p>We build or optimize your website to rank in local searches.</p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">📧</div>
                    <h3>Email Marketing</h3>
                    <p>We write, design, and send newsletters your customers actually read.</p>
                </div>
            </div>
        </div>
        
        <!-- Reputation -->
        <div class="mb-40" id="reputation">
            <h2 class="text-center mb-40">Reputation</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">👁️</div>
                    <h3>Review Monitoring</h3>
                    <p>We watch Google, Yelp, Facebook, TripAdvisor - so you don't have to.</p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">💬</div>
                    <h3>Review Response</h3>
                    <p>We respond to every review - good or bad - professionally.</p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">⭐</div>
                    <h3>Review Generation</h3>
                    <p>We set up systems to get more 5-star reviews from happy customers.</p>
                </div>
            </div>
        </div>
        
        <!-- Physical Marketing -->
        <div id="physical">
            <h2 class="text-center mb-40">Physical Marketing</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">📬</div>
                    <h3>EDDM Postcard Campaigns</h3>
                    <p>We design, print, and mail postcards to 15,000+ homes in your area.</p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">🎨</div>
                    <h3>Design & Print</h3>
                    <p>Menus, flyers, banners - we handle the creative.</p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">📁</div>
                    <h3>Directory Listings</h3>
                    <p>You exist forever - not just when you pay. Permanent SEO across 20+ directories.</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-40">
            <a href="<?php echo home_url('/pricing'); ?>" class="btn btn-primary">See Pricing</a>
        </div>
    </div>
</section>

<?php get_footer(); ?>