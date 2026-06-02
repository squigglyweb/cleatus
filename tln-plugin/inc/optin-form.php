<?php
/**
 * Text Message Opt-In Form Shortcode
 */
function tln_text_optin_shortcode() {
    // Simple form output - handling submission left for future implementation
    ob_start();
    ?>
    <div class="tln-optin-form" style="max-width:600px;margin:auto;padding:20px;background:#f9f9f9;border:1px solid #ccc;border-radius:8px;">
        <h2>Contact Us</h2>
        <form method="post" action="">
            <p><label>Name:<br><input type="text" name="optin_name" required style="width:100%;"></label></p>
            <p><label>Email:<br><input type="email" name="optin_email" required style="width:100%;"></label></p>
            <p><label>Phone Number:<br><input type="tel" name="optin_phone" required style="width:100%;"></label></p>
            <p><label>Message:<br><textarea name="optin_message" rows="4" style="width:100%;"></textarea></label></p>
            <p>
                The Local Nearbuy would like your consent to send informational and/or marketing text message communications from <strong>+1 833‑632‑7289</strong> to your mobile number listed above.
                Informational messages may include responses to messages you send us, as well as information relevant to your relationship with us.
                Marketing messages may include discount codes, special deals or texts promoting our products/services.
            </p>
            <p>Consent is not a condition of purchase. Message frequency varies. Message and data rates may apply. Reply <code>STOP</code> to unsubscribe at any time. Reply <code>HELP</code> for assistance or more information.</p>
            <p>We do not share your mobile opt-in information with anyone. Our privacy policy and messaging terms and conditions are available at <a href="https://thelocalnearbuy.com/privacy-policy/" target="_blank">https://thelocalnearbuy.com/privacy-policy/</a> for more information.</p>
            <p>
                <label><input type="checkbox" name="consent_info" value="1" required> Yes, I consent to receive informational messages from The Local Nearbuy</label><br>
                <label><input type="checkbox" name="consent_marketing" value="1"> Yes, I consent to receive marketing text messages from The Local Nearbuy</label><br>
                <label><input type="checkbox" name="consent_none" value="1"> No, I do not want to receive any text messages from The Local Nearbuy</label>
            </p>
            <p><input type="submit" name="tln_optin_submit" value="Submit" class="button button-primary"></p>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
?>