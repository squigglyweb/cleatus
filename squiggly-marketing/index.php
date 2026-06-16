<?php get_header(); ?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1>Your Marketing. Handled.</h1>
        <p class="subtitle">We run the ads, post to social, manage your reviews, and mail postcards to 15,000+ homes. You focus on your customers.</p>
        
        <a href="<?php echo home_url('/quiz'); ?>" class="btn btn-primary">See If We're a Good Fit</a>
        <a href="<?php echo home_url('/pricing'); ?>" class="btn btn-outline" style="border-color: white; color: white;">View Pricing</a>
        
        <div class="hero-stats">
            <div class="stat">
                <div class="stat-number">$2.4M+</div>
                <div class="stat-label">Revenue Generated</div>
            </div>
            <div class="stat">
                <div class="stat-number">15K+</div>
                <div class="stat-label">Postcards Mailed</div>
            </div>
            <div class="stat">
                <div class="stat-number">200+</div>
                <div class="stat-label">Local Businesses</div>
            </div>
        </div>
    </div>
</section>

<!-- Problems Section -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Does This Sound Like You?</h2>
            <p>You're busy running your business. You shouldn't have to be a marketing expert too.</p>
        </div>
        
        <div class="problems">
            <div class="problem-card">
                <div class="problem-icon">⏰</div>
                <h3>You're Too Busy</h3>
                <p>You don't have time to post on Instagram every day, let alone run Facebook ads.</p>
            </div>
            
            <div class="problem-card">
                <div class="problem-icon">📚</div>
                <h3>You're Not a Marketer</h3>
                <p>And you shouldn't have to be. You went into business to do what you love, not study algorithms.</p>
            </div>
            
            <div class="problem-card">
                <div class="problem-icon">🔄</div>
                <h3>You Tried and Failed</h3>
                <p>The "post yourself" route never worked. You need someone who actually does the work.</p>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="section section-light">
    <div class="container">
        <div class="section-title">
            <h2>What We Actually Do</h2>
            <p>We handle everything. You just answer the phone when customers call.</p>
        </div>
        
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
                <div class="service-icon">⭐</div>
                <h3>Review Management</h3>
                <p>We monitor and respond to every review. Good or bad - we handle it.</p>
            </div>
            
            <div class="service-card">
                <div class="service-icon">📧</div>
                <h3>Email Newsletter</h3>
                <p>5,000+ local inboxes, every month. Your offer gets seen.</p>
            </div>
            
            <div class="service-card">
                <div class="service-icon">📬</div>
                <h3>Direct Mail Postcards</h3>
                <p>We design, print, and mail to 15,000+ homes in your area.</p>
            </div>
            
            <div class="service-card">
                <div class="service-icon">📁</div>
                <h3>Directory Listings</h3>
                <p>You exist forever - not just when you pay. Permanent SEO.</p>
            </div>
        </div>
        
        <div class="text-center mt-40">
            <a href="<?php echo home_url('/services'); ?>" class="btn btn-secondary">See All Services</a>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>How It Works</h2>
            <p>Three simple steps. We handle everything.</p>
        </div>
        
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h3>We Audit</h3>
                <p>We look at what you're doing now and what your competitors are doing.</p>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <h3>We Build</h3>
                <p>We set up everything - profiles, pages, ads, funnels, listings.</p>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <h3>We Run</h3>
                <p>We manage it all, every month. You get simple reports.</p>
            </div>
        </div>
    </div>
</section>

<!-- Is This You -->
<section class="section section-light">
    <div class="container">
        <div class="section-title">
            <h2>Is This You?</h2>
        </div>
        
        <div class="fit-section">
            <p class="text-center">We work with local businesses who:</p>
            
            <div class="fit-list">
                <div class="fit-item">
                    <div class="fit-check">✓</div>
                    <span>Have a physical location (restaurant, salon, retail, office)</span>
                </div>
                <div class="fit-item">
                    <div class="fit-check">✓</div>
                    <span>Want more customers but don't want to do marketing themselves</span>
                </div>
                <div class="fit-item">
                    <div class="fit-check">✓</div>
                    <span>Have $500+/mo marketing budget</span>
                </div>
                <div class="fit-item">
                    <div class="fit-check">✓</div>
                    <span>Can handle 10-20 new customers per week</span>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-40">
            <a href="<?php echo home_url('/quiz'); ?>" class="btn btn-primary">Take the 2-Minute Fit Quiz</a>
        </div>
    </div>
</section>

<?php get_footer(); ?>