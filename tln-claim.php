<?php
/*
Plugin Name: TLN Simple Claim
Version: 1.2
*/

add_shortcode('claim_business', 'tln_claim_func');

function tln_claim_func() {
    $biz = isset($_GET['biz']) ? $_GET['biz'] : '';
    $pid = isset($_GET['pid']) ? $_GET['pid'] : '';
    
    if (!is_user_logged_in()) {
        return "<p>Please <a href='/wp-login.php'>log in</a> to claim a business.</p>";
    }
    
    if (isset($_POST['submit'])) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix.'tln_claims', array(
            'business_name' => $biz,
            'place_id' => $pid,
            'user_id' => get_current_user_id(),
            'claimant_name' => $_POST['cname'],
            'claimant_phone' => $_POST['cphone'],
            'tos_agreed' => $_POST['csig'],
            'tos_signed_date' => date('Y-m-d'),
            'status' => 'approved'
        ));
        return "<p style='background:#d4edda;padding:1rem;border-radius:8px;'>Done! <a href='/dashboard/'>Go to dashboard</a></p>";
    }
    
    $out = "<div style='background:#f8f8f8;padding:2rem;border-radius:12px;max-width:500px;'>";
    if ($biz) $out .= "<p>Claiming: <strong>$biz</strong></p>";
    $out .= "<form method='post'>";
    $out .= "<input type='hidden' name='pid' value='$pid'>";
    $out .= "<p><label>Business<br><input name='business_name' value='$biz' style='width:100%;padding:0.5rem;'></label></p>";
    $out .= "<p><label>Your Name<br><input name='cname' required style='width:100%;padding:0.5rem;'></label></p>";
    $out .= "<p><label>Phone<br><input name='cphone' required style='width:100%;padding:0.5rem;'></label></p>";
    $out .= "<p><label><input type='checkbox' required> I agree to TOS</label></p>";
    $out .= "<p><label>Signature<br><input name='csig' required placeholder='Type your name' style='width:100%;padding:0.5rem;'></label></p>";
    $out .= "<button type='submit' name='submit' style='background:#e63946;color:white;padding:0.75rem 1.5rem;border:none;border-radius:6px;cursor:pointer;'>Submit</button>";
    $out .= "</form></div>";
    return $out;
}
