<?php
/**
 * Plugin Name: TLN Business Dashboard
 * Version: 2.1
 */

if (!defined('ABSPATH')) exit;

function tln_dashboard_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="/wp-login.php">log in</a> to view your dashboard.</p>';
    }
    
    $user_id = get_current_user_id();
    global $wpdb;
    
    $claim = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_claims WHERE user_id=%d AND status='approved'", $user_id));
    
    if (!$claim) {
        return '<div style="padding:2rem;background:#f8f8f8;border-radius:12px;text-align:center;">
            <h2>No Business Claimed</h2>
            <p>You haven\'t claimed any businesses yet.</p>
            <a href="/directory/" style="background:#7cda24;color:#fff;padding:1rem 2rem;text-decoration:none;border-radius:8px;">Browse Directory</a>
        </div>';
    }
    
    $tier = $claim->tier ?? 'free';
    
    ob_start();
    ?>
    <div class="tln-dash" style="max-width:900px;margin:0 auto;">
        <div style="background:#fff3cd;padding:1rem;border-radius:8px;margin-bottom:1rem;border:2px solid #ffc107;">
            <strong>⚠️ Desktop Recommended</strong><br>
            <span style="font-size:0.9rem;">This dashboard works best on a desktop or tablet. Some features may be difficult to use on mobile.</span>
        </div>
        
        <h2 style="margin-bottom:1rem;">📊 <?php echo esc_html($claim->business_name); ?> Dashboard</h2>
        
        <?php if($tier == 'free'): ?>
        <div style="background:#e63946;color:#fff;padding:1.5rem;border-radius:8px;margin-bottom:1rem;text-align:center;">
            <h3 style="margin:0 0 0.5rem;">Reach Thousands of Local Households</h3>
            <p style="margin:0 0 1rem;">Run a postcard campaign with trackable QR codes. Every scan gives you a real lead.</p>
            <a href="/campaign-pricing/" style="display:inline-block;background:#fff;color:#e63946;padding:0.75rem 1.5rem;text-decoration:none;border-radius:6px;font-weight:600;">See Campaign Pricing</a>
        </div>
        <?php else: ?>
        <div style="background:#d4edda;padding:1rem;border-radius:8px;margin-bottom:1rem;">
            <strong>✓ Active Campaign</strong>
        </div>
        <?php endif; ?>
        
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem;margin-bottom:2rem;">
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:2px solid #1a1a1a;">
                <h3 style="margin-top:0;">Business Info</h3>
                <p><strong>Name:</strong> <?php echo esc_html($claim->business_name); ?></p>
                <p><strong>Status:</strong> ✓ Verified</p>
                <p><strong>Claimed:</strong> <?php echo esc_html($claim->tos_signed_date); ?></p>
            </div>
            
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:2px solid #1a1a1a;">
                <h3 style="margin-top:0;">Offers</h3>
                <?php if(!empty($claim->custom_offer)): ?>
                <p>🎁 <?php echo esc_html($claim->custom_offer); ?></p>
                <?php else: ?>
                <p style="color:#666;">No offers yet. <a href="#">Add one</a></p>
                <?php endif; ?>
            </div>
            
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:2px solid #1a1a1a;">
                <h3 style="margin-top:0;">Analytics</h3>
                <p style="color:#666;">Coming soon</p>
            </div>
        </div>
        
        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:2px solid #1a1a1a;margin-bottom:2rem;">
            <h3 style="margin-top:0;">📮 Postcard Preview</h3>
            <p style="color:#666;font-size:0.9rem;margin-bottom:1rem;">Here's how your ad will look on the postcard. The front shows your business info and a QR code. The back shows your offer that customers scan to redeem.</p>
            <div style="display:flex;gap:1.5rem;justify-content:center;flex-wrap:wrap;">
                <div style="text-align:center;">
                    <p style="font-weight:600;margin-bottom:0.5rem;">Front</p>
                    <div style="width:200px;height:130px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:8px;padding:1rem;color:#fff;display:flex;flex-direction:column;justify-content:center;align-items:center;">
                        <p style="font-size:0.8rem;margin:0;text-align:center;"><?php echo esc_html($claim->business_name); ?></p>
                        <div style="background:#fff;padding:0.5rem;border-radius:4px;margin:0.5rem 0;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=EXAMPLE" alt="QR" style="display:block;">
                        </div>
                        <p style="font-size:0.7rem;margin:0;">Scan for offer</p>
                    </div>
                </div>
                <div style="text-align:center;">
                    <p style="font-weight:600;margin-bottom:0.5rem;">Back</p>
                    <div style="width:200px;height:130px;background:#fff;border:2px solid #1a1a1a;border-radius:8px;padding:1rem;color:#1a1a1a;display:flex;flex-direction:column;justify-content:center;align-items:center;">
                        <p style="font-size:0.75rem;font-weight:700;margin:0;text-transform:uppercase;">Special Offer</p>
                        <p style="font-size:0.7rem;margin:0.5rem 0;text-align:center;">Show this code at checkout to redeem your offer</p>
                        <div style="border:2px dashed #1a1a1a;padding:0.25rem 0.5rem;font-family:monospace;font-size:0.7rem;">CODE: XXXXXX</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:2px solid #1a1a1a;">
            <h3 style="margin-top:0;">📸 Directory Photo</h3>
            <p style="color:#666;font-size:0.9rem;">This photo appears in the business directory. Use your business logo or exterior shot.</p>
            <?php if($tier == 'free'): ?>
            <p style="color:#666;">💎 Upgrade to Pro to show your photo in the directory</p>
            <?php else: ?>
            <?php $dir_photo = get_post_meta($profile_id, 'tln_directory_image', true); ?>
            <?php if($dir_photo): ?>
            <div style="margin-bottom:1rem;"><img src="<?php echo esc_url($dir_photo); ?>" style="max-width:200px;height:auto;border-radius:8px;"></div>
            <?php endif; ?>
            <form id="tln-dirphoto-form" enctype="multipart/form-data">
                <input type="file" name="tln_directory_image" accept="image/*" style="margin-bottom:0.5rem;">
                <button type="submit" style="background:#e63946;color:#fff;padding:0.5rem 1rem;border:none;border-radius:4px;cursor:pointer;">Upload Directory Photo</button>
            </form>
            <div id="tln-dirphoto-msg"></div>
            <?php endif; ?>
        </div>
        
        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:2px solid #1a1a1a;margin-top:1rem;">
            <h3 style="margin-top:0;">🖼️ Photo Gallery</h3>
            <?php if($tier == 'free'): ?>
            <p style="color:#666;">Upgrade to Pro to add photos</p>
            <?php else: ?>
            <form id="tln-gallery-form" enctype="multipart/form-data">
                <input type="file" name="tln_gallery[]" multiple accept="image/*" style="margin-bottom:0.5rem;">
                <button type="submit" style="background:#e63946;color:#fff;padding:0.5rem 1rem;border:none;border-radius:4px;cursor:pointer;">Upload Photos</button>
            </form>
            <div id="tln-gallery-msg"></div>
            <?php endif; ?>
        </div>
        
        <!-- Business Profile Editor -->
        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:2px solid #1a1a1a;margin-top:1rem;">
            <h3 style="margin-top:0;">✏️ Edit Your Profile</h3>
            <form id="tln-profile-form">
                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-weight:700;margin-bottom:0.25rem;">Custom Description</label>
                    <textarea name="description" rows="4" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:4px;" placeholder="Tell customers what makes your business special..."><?php echo esc_textarea($claim->notes ?? ''); ?></textarea>
                </div>
                
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1rem;">
                    <div>
                        <label style="display:block;font-weight:700;margin-bottom:0.25rem;">Phone</label>
                        <input type="tel" name="phone" value="<?php echo esc_attr($claim->user_phone ?? ''); ?>" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block;font-weight:700;margin-bottom:0.25rem;">Website</label>
                        <input type="url" name="website" value="<?php echo esc_attr($claim->website ?? ''); ?>" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:4px;">
                    </div>
                </div>
                
                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-weight:700;margin-bottom:0.25rem;">Special Offer (for Pro+)</label>
                    <input type="text" name="custom_offer" value="<?php echo esc_attr($claim->custom_offer ?? ''); ?>" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:4px;" placeholder="e.g., 20% OFF your first service">
                </div>
                
                <button type="submit" style="background:#28a745;color:#fff;padding:0.75rem 1.5rem;border:none;border-radius:4px;font-weight:700;cursor:pointer;">Save Profile</button>
            </form>
            <div id="tln-profile-msg" style="margin-top:1rem;"></div>
        </div>
    </div>
    <script>
    document.getElementById('tln-profile-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const btn = this.querySelector('button');
        btn.disabled = true;
        btn.textContent = 'Saving...';
        
        try {
            const res = await fetch('/wp-json/tln/v1/save-profile', {
                method: 'POST',
                body: JSON.stringify(Object.fromEntries(formData)),
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await res.json();
            document.getElementById('tln-profile-msg').innerHTML = data.success 
                ? '<span style="color:green;">✓ Profile saved successfully!</span>'
                : '<span style="color:red;">Error: ' + (data.message || 'Unknown error') + '</span>';
        } catch(err) {
            document.getElementById('tln-profile-msg').innerHTML = '<span style="color:red;">Error saving profile</span>';
        }
        btn.disabled = false;
        btn.textContent = 'Save Profile';
    });
    
    // Directory Photo Upload
    document.getElementById('tln-dirphoto-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const btn = this.querySelector('button');
        btn.disabled = true;
        btn.textContent = 'Uploading...';
        
        try {
            const res = await fetch('/wp-json/tln/v1/save-dirphoto', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            document.getElementById('tln-dirphoto-msg').innerHTML = data.success 
                ? '<span style="color:green;">✓ Directory photo updated!</span>'
                : '<span style="color:red;">Error: ' + (data.message || 'Unknown error') + '</span>';
            if(data.success && data.image_url) {
                const existingImg = document.querySelector('#tln-dirphoto-form + div img, #tln-dirphoto-form + img');
                if(existingImg) existingImg.remove();
                const imgDiv = document.createElement('div');
                imgDiv.style.marginBottom = '1rem';
                imgDiv.innerHTML = '<img src="' + data.image_url + '" style="max-width:200px;height:auto;border-radius:8px;">';
                document.getElementById('tln-dirphoto-form').parentNode.insertBefore(imgDiv, document.getElementById('tln-dirphoto-form'));
            }
        } catch(err) {
            document.getElementById('tln-dirphoto-msg').innerHTML = '<span style="color:red;">Error uploading photo</span>';
        }
        btn.disabled = false;
        btn.textContent = 'Upload Directory Photo';
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('tln_dashboard', 'tln_dashboard_shortcode');
