<?php /* Template Name: Contact */ ?>
<?php get_header(); ?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h1>Let's Talk</h1>
            <p>Ready to grow without the marketing headaches?</p>
        </div>
        
        <div class="contact-grid">
            <div class="contact-form">
                <form>
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="business">Business Name</label>
                        <input type="text" id="business" name="business" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="goal">What are you hoping to achieve?</label>
                        <select id="goal" name="goal">
                            <option value="">Select an option...</option>
                            <option value="walkin">Get more walk-in customers</option>
                            <option value="leads">Generate more leads/calls</option>
                            <option value="reviews">Better reviews</option>
                            <option value="presence">Build my online presence</option>
                            <option value="dontknow">I don't know - that's why I'm asking</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
            
            <div class="contact-info">
                <h3>Get In Touch</h3>
                <p>Fill out the form and we'll get back to you within 24 hours. Or just want to say hi? That's cool too.</p>
                
                <div class="contact-details">
                    <div class="contact-item">
                        <div class="contact-icon">📍</div>
                        <div>
                            <strong>Location</strong>
                            <p>Waxhaw, NC</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">📧</div>
                        <div>
                            <strong>Email</strong>
                            <p>hello@squigglymarketing.com</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">⏰</div>
                        <div>
                            <strong>Response Time</strong>
                            <p>We typically respond within 24 hours</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-40">
                    <h4>Serving:</h4>
                    <p>Waxhaw, Marvin, Wesley Chapel, Weddington, Indian Land, Ballantyne, Charlotte</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>