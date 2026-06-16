<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <h3>Squiggly Marketing</h3>
                <p>We handle your marketing. You run your business. Based in Waxhaw, NC - serving the Greater Charlotte area.</p>
            </div>
            
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?php echo home_url('/services'); ?>">Services</a></li>
                    <li><a href="<?php echo home_url('/pricing'); ?>">Pricing</a></li>
                    <li><a href="<?php echo home_url('/about'); ?>">About</a></li>
                    <li><a href="<?php echo home_url('/quiz'); ?>">Take the Quiz</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>Services</h4>
                <ul>
                    <li><a href="<?php echo home_url('/services'); ?>#digital">Digital Marketing</a></li>
                    <li><a href="<?php echo home_url('/services'); ?>#reputation">Reputation</a></li>
                    <li><a href="<?php echo home_url('/services'); ?>#physical">Physical Marketing</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Squiggly Marketing. Based in Waxhaw, NC - Serving Greater Charlotte Area.</p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>