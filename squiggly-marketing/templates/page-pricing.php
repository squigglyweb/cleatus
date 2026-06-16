<?php /* Template Name: Pricing */ ?>
<?php get_header(); ?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h1>Investment</h1>
            <p>Most local businesses spend $500-2000/mo on Google and Facebook ads alone - with no strategy. We do more, for less.</p>
        </div>
        
        <div class="pricing-grid">
            <!-- Starter Tier -->
            <div class="pricing-card">
                <div class="pricing-header">
                    <h3>Starter</h3>
                    <div class="pricing-price">$497<span>/mo</span></div>
                </div>
                <div class="pricing-features">
                    <p class="mb-20" style="color: var(--text-gray);">Best for: New businesses wanting visibility</p>
                    <ul>
                        <li><span class="check">✓</span> Directory listing (permanent)</li>
                        <li><span class="check">✓</span> Google Business Profile optimized</li>
                        <li><span class="check">✓</span> Facebook & Instagram page setup</li>
                        <li><span class="check">✓</span> 4 posts per month</li>
                        <li><span class="check">✓</span> Monthly performance report</li>
                        <li><span class="check">✓</span> Basic review monitoring</li>
                        <li><span class="check">✓</span> Directory submissions (20+ sites)</li>
                        <li><span class="x">✗</span> Ad management</li>
                        <li><span class="x">✗</span> Postcard campaigns</li>
                    </ul>
                </div>
                <div class="pricing-cta">
                    <a href="<?php echo home_url('/contact'); ?>" class="btn btn-outline">Get Started</a>
                </div>
            </div>
            
            <!-- Growth Tier -->
            <div class="pricing-card featured">
                <div class="pricing-best">Most Popular</div>
                <div class="pricing-header">
                    <h3>Growth</h3>
                    <div class="pricing-price">$997<span>/mo</span></div>
                </div>
                <div class="pricing-features">
                    <p class="mb-20" style="color: var(--text-gray);">Best for: Established businesses wanting leads</p>
                    <ul>
                        <li><span class="check">✓</span> Everything in Starter</li>
                        <li><span class="check">✓</span> 8 posts per month + Stories</li>
                        <li><span class="check">✓</span> Facebook & Instagram ad management</li>
                        <li><span class="check">✓</span> $500-2000/mo ad spend managed</li>
                        <li><span class="check">✓</span> Newsletter inclusion (1x/month)</li>
                        <li><span class="check">✓</span> Lead capture pages</li>
                        <li><span class="check">✓</span> Full review management</li>
                        <li><span class="check">✓</span> Competitor monitoring</li>
                        <li><span class="x">✗</span> Postcard campaigns</li>
                    </ul>
                </div>
                <div class="pricing-cta">
                    <a href="<?php echo home_url('/contact'); ?>" class="btn btn-primary">Get Started</a>
                </div>
            </div>
            
            <!-- Dominance Tier -->
            <div class="pricing-card">
                <div class="pricing-header">
                    <h3>Dominance</h3>
                    <div class="pricing-price">$1,997<span>/mo</span></div>
                </div>
                <div class="pricing-features">
                    <p class="mb-20" style="color: var(--text-gray);">Best for: Businesses ready to dominate</p>
                    <ul>
                        <li><span class="check">✓</span> Everything in Growth</li>
                        <li><span class="check">✓</span> TLN Postcard campaigns</li>
                        <li><span class="check">✓</span> Unlimited social posts</li>
                        <li><span class="check">✓</span> Priority support (same-day)</li>
                        <li><span class="check">✓</span> Quarterly strategy calls</li>
                        <li><span class="check">✓</span> Full reputation management</li>
                        <li><span class="check">✓</span> We manage EVERYTHING</li>
                    </ul>
                </div>
                <div class="pricing-cta">
                    <a href="<?php echo home_url('/contact'); ?>" class="btn btn-outline">Get Started</a>
                </div>
            </div>
        </div>
        
        <!-- FAQ -->
        <div class="faq">
            <h3>Frequently Asked Questions</h3>
            
            <div class="faq-item">
                <h4>What if I just need one thing?</h4>
                <p>We don't do piecemeal. Our tiers are designed to work together. Pick the tier that fits your goals.</p>
            </div>
            
            <div class="faq-item">
                <h4>Do you require contracts?</h4>
                <p>Month-to-month. But we ask for a 90-day commitment to see real results.</p>
            </div>
            
            <div class="faq-item">
                <h4>What about ad spend?</h4>
                <p>You pay directly to Facebook/Google. We don't mark up ad spend. What you see is what you get.</p>
            </div>
            
            <div class="faq-item">
                <h4>Can I upgrade or downgrade?</h4>
                <p>Yes. With 30 days notice.</p>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>