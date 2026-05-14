<?php
/*
Plugin Name: TLN Plugin Bundle
Description: Business profiles, directory, and member features for The Local NearBuy
Version: 2.8 - Simplified
*/

// Load other TLN components
require_once plugin_dir_path(__FILE__) . 'tln-directory.php';
require_once plugin_dir_path(__FILE__) . 'tln-claim.php';

// Profile page handler
if (!is_admin()) {
    add_filter('the_content', 'tln_profile_content');
    add_filter('the_content', 'tln_directory_content');
}

function tln_directory_content($content) {
    if (!is_page('directory')) {
        return $content;
    }
    return tln_dir_shortcode(array());
}

function tln_profile_content($content) {
    if (!is_page('profile')) {
        return $content;
    }
    
    $biz = isset($_GET['biz']) ? $_GET['biz'] : '';
    $pid = isset($_GET['pid']) ? $_GET['pid'] : '';
    
    if (empty($biz) || empty($pid)) {
        return '<div style="padding:2rem;background:#f0f0f0;border-radius:8px;"><h3>TLN Profile</h3><p>Add ?biz=Name&pid=PlaceID to URL.</p></div>';
    }
    
    // Full profile HTML
    $html = '
    <style>
    .tln-profile { max-width:1100px; margin:0 auto; font-family:\'Open Sans\',sans-serif; }
    .tln-hero { 
        background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5)),
        url(\'https://thelocalnearbuy.com/wp-content/uploads/2026/05/town-scene-bkgd-scaled.webp\');
        background-size:cover;background-position:center;height:280px;position:relative;margin:-20px -40px 0 -40px;width:calc(100% + 80px);max-width:1280px;
    }
    .tln-hero-content { position:absolute;bottom:1.5rem;left:1.5rem; }
    .tln-hero h1 { color:#fff;font-size:2.5rem;margin:0;font-weight:700; }
    .tln-hero p { color:rgba(255,255,255,0.9);font-size:1.1rem;margin:0; }
    .tln-container { display:grid;grid-template-columns:320px 1fr;gap:2rem;padding:2rem;background:rgba(255,255,255,0.95);border-radius:12px;margin-top:1rem; }
    .tln-left, .tln-right { display:flex;flex-direction:column;gap:1.5rem; }
    .tln-card { background:#fff;border:1px solid #ddd;border-radius:8px;padding:1.25rem; }
    .tln-card h3 { font-size:1rem;font-weight:700;margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:1px solid #eee; }
    .tln-contact { display:flex;align-items:center;gap:0.5rem;padding:0.5rem 0; }
    .tln-contact a { color:#e63946;text-decoration:none;font-weight:600; }
    .tln-review-item { padding:0.75rem 0;border-bottom:1px solid #eee; }
    .tln-review-item:last-child { border-bottom:none; }
    .tln-reviewer { font-weight:700;font-size:0.95rem; }
    .tln-stars { color:#fbbf24;font-size:0.85rem;margin:0.25rem 0; }
    .tln-review-text { color:#555;font-size:0.9rem; }
    .tln-see-all { color:#e63946;font-size:0.85rem;font-weight:600;margin-top:0.75rem;display:block; }
    .tln-claim-box { background:linear-gradient(135deg,#1a1a1a,#333);border-radius:12px;padding:1.5rem;text-align:center; }
    .tln-claim-box h3 { color:#fff;font-size:1.1rem;margin-bottom:0.5rem; }
    .tln-claim-box p { color:#ccc;margin-bottom:1rem;font-size:0.9rem; }
    .tln-btn { display:inline-block;padding:0.75rem 1.5rem;background:#e63946;color:#fff;text-decoration:none;border-radius:6px;font-weight:700; }
    .tln-btn:hover { background:#d62839; }
    .tln-rating-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:1px solid #eee; }
    .tln-rating-header h3 { margin:0;padding:0;border:none; }
    .tln-avg-score { background:#e63946;color:#fff;padding:0.25rem 0.75rem;border-radius:20px;font-weight:700;font-size:0.9rem; }
    .tln-rating-category { display:flex;align-items:center;margin:0.5rem 0; }
    .tln-rating-label { flex:1;font-weight:600;font-size:0.9rem; }
    .tln-pin-rating { display:flex;gap:2px; }
    .tln-pin-rating img { height:16px;opacity:0.3; }
    .tln-pin-rating img.active { opacity:1; }
    .tln-map { background:#f5f5f5;height:200px;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#666; }
    .tln-ad-box { background:#f5f5f5;border:2px dashed #ddd;border-radius:8px;height:250px;display:flex;align-items:center;justify-content:center; }
    .tln-ad-content { color:#888;text-align:center; }
    .tln-modal { display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999; }
    .tln-modal.show { display:flex;align-items:center;justify-content:center; }
    .tln-modal-inner { background:#fff;max-width:700px;width:95%;max-height:85vh;border-radius:12px;overflow:hidden;position:relative; }
    .tln-modal-close { position:absolute;top:10px;right:15px;font-size:2rem;cursor:pointer;z-index:10;line-height:1; }
    .tln-modal iframe { width:100%;height:80vh;border:none; }
    @media(max-width:800px) { .tln-container { grid-template-columns:1fr; } }
    </style>
    
    <div class="tln-profile">
        <div class="tln-hero">
            <div class="tln-hero-content">
                <h1>' . esc_html($biz) . '</h1>
                <p>Waxhaw, NC</p>
            </div>
        </div>
        
        <div class="tln-container">
            <div class="tln-left">
                <img src="https://thelocalnearbuy.com/wp-content/uploads/2026/05/support-local-businesses.webp" style="width:100%;border-radius:8px;">
                
                <!-- What Neighbors Say -->
                <div class="tln-card">
                    <h3>What Neighbors Say</h3>
                    <p style="font-size:0.9rem;color:#666;margin-bottom:1rem;">Be the first to leave a Neighborhood Review Score for this business and help others in our community know what they\'re about.</p>
                    <button style="margin-top:0.8rem;padding:0.75rem 1.5rem;background:#e63946;color:white;border:none;border-radius:6px;cursor:pointer;display:block;width:100%;font-weight:700;font-size:1rem;">Leave a Review</button>
                </div>
                
                <div class="tln-card">
                    <h3>Hours</h3>
                    <p style="font-size:0.9rem;color:#666;">Coming soon...</p>
                </div>
                
                <div class="tln-card">
                    <h3>Contact</h3>
                    <div class="tln-contact">
                        <span>📞</span>
                        <a href="#">Coming soon...</a>
                    </div>
                    <div class="tln-contact">
                        <span>📍</span>
                        <a href="#">Get Directions</a>
                    </div>
                </div>
            </div>
            
            <div class="tln-right">
                <div class="tln-card">
                    <h3>Google Reviews</h3>
                    <p style="color:#666;font-size:0.9rem;">Be the first to leave a Google review for this business!</p>
                </div>
                
                <div class="tln-card" style="background:#fefaf9;border-color:#f0e0e0;">
                    <div style="font-size:0.7rem;color:#999;text-transform:uppercase;letter-spacing:1px;margin-bottom:0.25rem;">Advertisement</div>
                    <div class="tln-ad-box">
                        <div class="tln-ad-content">
                            <p style="margin-bottom:0.5rem;color:#666;"><strong>Not ready to claim yet?</strong></p>
                            <p style="margin-bottom:0.75rem;font-size:0.85rem;color:#888;">Advertise your business here for just <strong style="color:#e63946;">$35/mo</strong></p>
                            <a href="#" class="tln-modal-link" data-modal="ad" style="color:#e63946;font-weight:600;font-size:0.9rem;">Learn More →</a>
                        </div>
                    </div>
                </div>
                
                <div class="tln-card">
                    <h3>Location</h3>
                    <div class="tln-map">[Google Map Placeholder]</div>
                </div>
                
                <div class="tln-claim-box">
                    <h3>Claim This Page</h3>
                    <p>Own this business? Claim your free page to update info, add photos, and more.</p>
                    <a href="#" class="tln-btn tln-modal-link" data-modal="claim">Claim Your Page</a>
                </div>
            </div>
        </div>
    </div>
    ';
    
    return $html . tln_get_modal_html();
}

function tln_get_modal_html() {
    return '
    <div id="tln-ad-modal" class="tln-modal">
        <div class="tln-modal-inner">
            <span class="tln-modal-close" data-close>&times;</span>
            <iframe src="/tln-ad-request.html?modal=1"></iframe>
        </div>
    </div>
    <div id="tln-claim-modal" class="tln-modal">
        <div class="tln-modal-inner">
            <span class="tln-modal-close" data-close>&times;</span>
            <iframe src="/claim/?modal=1"></iframe>
        </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".tln-modal-link").forEach(function(link) {
            link.addEventListener("click", function(e) {
                e.preventDefault();
                var modalId = this.getAttribute("data-modal");
                document.getElementById("tln-" + modalId + "-modal").classList.add("show");
            });
        });
        document.querySelectorAll(".tln-modal").forEach(function(modal) {
            modal.addEventListener("click", function(e) {
                if (e.target === modal) modal.classList.remove("show");
            });
        });
        document.querySelectorAll("[data-close]").forEach(function(btn) {
            btn.addEventListener("click", function() {
                this.closest(".tln-modal").classList.remove("show");
            });
        });
    });
    </script>
    ';
}