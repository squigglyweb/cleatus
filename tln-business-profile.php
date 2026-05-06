<?php
/**
 * Plugin Name: TLN Business Profile
 * Version: 1.0
 */

add_shortcode('tln_business_profile', 'tln_business_profile_func');

function tln_business_profile_func($atts) {
    $atts = shortcode_atts(array('biz'=>'','pid'=>''), $atts);
    $biz_name = isset($_GET['biz']) ? sanitize_text_field($_GET['biz']) : $atts['biz'];
    $place_id = isset($_GET['pid']) ? sanitize_text_field($_GET['pid']) : $atts['pid'];
    
    if(empty($biz_name)) {
        return '<p>No business specified. <a href="/directory/">Browse directory</a></p>';
    }
    
    $api_key = 'AIzaSyAH6O3RsnDuX5rJ2OyTHCTZhYtd6s6NSWU';
    
    // Get place details for photo
    $details_url = "https://maps.googleapis.com/maps/api/place/details/json?placeid=".urlencode($place_id)."&key=$api_key";
    $details = wp_remote_get($details_url);
    
    $photo_url = '';
    $website = '';
    $phone = '';
    $hours = '';
    
    if(!is_wp_error($details)) {
        $data = json_decode(wp_remote_retrieve_body($details), true);
        if(!empty($data['result']['photos'])) {
            $pref = $data['result']['photos'][0]['photo_reference'];
            $photo_url = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=800&photoreference=$pref&key=$api_key";
        }
        $website = $data['result']['website'] ?? '';
        $phone = $data['result']['formatted_phone_number'] ?? '';
        $hours = $data['result']['opening_hours']['weekday_text'] ?? array();
    }
    
    // Check if claimed
    global $wpdb;
    $claimed = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_claims WHERE business_name=%s AND status='approved'", $biz_name));
    
    ob_start();
    ?>
    <div class="tln-profile" style="max-width:800px;margin:0 auto;padding:2rem;">
        <?php if($photo_url): ?>
        <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($biz_name); ?>" style="width:100%;height:300px;object-fit:cover;border-radius:12px;margin-bottom:1.5rem;">
        <?php endif; ?>
        
        <h1 style="font-size:2rem;margin-bottom:0.5rem;"><?php echo esc_html($biz_name); ?></h1>
        
        <?php if($claimed): ?>
        <div style="background:#d4edda;padding:1rem;border-radius:8px;margin-bottom:1rem;">
            <strong>✓ Verified Business</strong>
            <?php if(!empty($claimed->custom_offer)): ?>
            <p style="margin:0.5rem 0 0;">🎁 <?php echo esc_html($claimed->custom_offer); ?></p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p style="background:#fff3cd;padding:1rem;border-radius:8px;margin-bottom:1rem;">
            Are you the owner? <a href="/claim/">Claim this business</a> to add custom offers, hours, and photos.
        </p>
        <?php endif; ?>
        
        <?php if($phone): ?>
        <p style="font-size:1.1rem;margin-bottom:0.5rem;">📞 <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a></p>
        <?php endif; ?>
        
        <p style="font-size:1rem;color:#666;margin-bottom:1rem;">📍 
            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($biz_name); ?>" target="_blank" style="color:#e63946;">
                View on Google Maps
            </a>
        </p>
        
        <?php if($website): ?>
        <p style="margin-bottom:1rem;">🌐 <a href="<?php echo esc_url($website); ?>" target="_blank"><?php echo esc_html($website); ?></a></p>
        <?php endif; ?>
        
        <div style="margin-top:2rem;padding-top:1rem;border-top:1px solid #eee;">
            <a href="/directory/" style="color:#666;">← Back to Directory</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('tln_profile', 'tln_business_profile_func');
