<?php
/**
 * Plugin Name: TLN Support Tickets
 * Description: Help desk ticket system
 * Version: 1.0
 */

register_activation_hook(__FILE__, 'tln_support_install');

function tln_support_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tln_support_tickets (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        subject varchar(200) NOT NULL,
        category varchar(50),
        message text NOT NULL,
        status varchar(20) DEFAULT 'open',
        reply text,
        replied_at datetime,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate");
}

add_action('admin_menu', 'tln_support_menu');

function tln_support_menu() {
    add_submenu_page('tln-business', 'Support Tickets', 'Support', 'manage_options', 'tln-support', 'tln_support_page');
}

add_shortcode('tln_support', 'tln_support_form');

function tln_support_form() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . wp_login_url() . '">log in</a> to submit a support ticket.</p>';
    }
    
    $user = wp_get_current_user();
    
    if (isset($_POST['tln_submit_ticket'])) {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix . 'tln_support_tickets', array(
            'user_id' => $user->ID,
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'subject' => sanitize_text_field($_POST['subject']),
            'category' => sanitize_text_field($_POST['category']),
            'message' => sanitize_textarea_field($_POST['message']),
            'status' => 'open'
        ));
        
        // Email to Bryan
        $to = 'bryan@thelocalnearbuy.com';
        $subject = '🎫 New Support Ticket: ' . sanitize_text_field($_POST['subject']);
        $message = "New support ticket from " . sanitize_text_field($_POST['name']) . "\n\n";
        $message .= "Category: " . sanitize_text_field($_POST['category']) . "\n";
        $message .= "Message: " . sanitize_textarea_field($_POST['message']) . "\n\n";
        $message .= "View in admin: https://thelocalnearbuy.com/wp-admin/admin.php?page=tln-support";
        
        wp_mail($to, $subject, $message);
        
        return '<div style="background:#d4edda;padding:1.5rem;border-radius:8px;color:#155724;text-align:center;">
            <h3 style="margin-top:0;">✅ Ticket Submitted!</h3>
            <p>We\'ll get back to you within 24-48 hours.</p>
            <p><a href="/support/" style="color:#155724;">Submit another ticket</a></p>
        </div>';
    }
    
    ob_start();
    ?>
    <div style="max-width:600px;margin:0 auto;padding:2rem;background:#f8f8f8;border-radius:12px;">
        <h2 style="margin-top:0;">🛟 Support Center</h2>
        <p style="color:#666;margin-bottom:1.5rem;">Having trouble? Submit a ticket and we'll help.</p>
        
        <form method="post" style="background:white;padding:1.5rem;border-radius:8px;">
            <p style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Your Name *</label>
                <input type="text" name="name" value="<?php echo esc_attr($user->display_name); ?>" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;">
            </p>
            
            <p style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Email *</label>
                <input type="email" name="email" value="<?php echo esc_attr($user->user_email); ?>" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;">
            </p>
            
            <p style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Category *</label>
                <select name="category" required style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;">
                    <option value="">Select a category</option>
                    <option value="technical">Technical Issue</option>
                    <option value="billing">Billing/Payments</option>
                    <option value="listing">My Business Listing</option>
                    <option value="account">Account Help</option>
                    <option value="other">Other</option>
                </select>
            </p>
            
            <p style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Subject *</label>
                <input type="text" name="subject" required placeholder="Brief description of the issue" style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;">
            </p>
            
            <p style="margin-bottom:1.5rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.5rem;">Message *</label>
                <textarea name="message" rows="6" required placeholder="Describe your issue in detail..." style="width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:6px;"></textarea>
            </p>
            
            <button type="submit" name="tln_submit_ticket" value="1" style="background:#e63946;color:white;padding:1rem 2rem;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">
                Submit Ticket
            </button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

function tln_support_page() {
    global $wpdb;
    $tickets = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tln_support_tickets ORDER BY created_at DESC");
    
    echo '<h1>🛟 Support Tickets</h1>';
    
    if (isset($_POST['tln_reply_ticket'])) {
        $ticket_id = intval($_POST['ticket_id']);
        $reply = sanitize_textarea_field($_POST['reply']);
        
        $wpdb->update($wpdb->prefix . 'tln_support_tickets', array(
            'reply' => $reply,
            'replied_at' => current_time('mysql'),
            'status' => 'closed'
        ), array('id' => $ticket_id));
        
        // Send reply email
        $ticket = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tln_support_tickets WHERE id = %d", $ticket_id));
        if ($ticket) {
            wp_mail($ticket->email, 'Re: ' . $ticket->subject, $reply);
        }
        
        echo '<div style="background:#d4edda;padding:1rem;border-radius:6px;margin-bottom:1rem;">Reply sent!</div>';
    }
    
    if (empty($tickets)) {
        echo '<p>No tickets yet.</p>';
        return;
    }
    
    foreach ($tickets as $t) {
        $status_color = ($t->status === 'open') ? '#fef3c7' : '#d4edda';
        
        echo '<div style="background:' . $status_color . ';padding:1rem;margin-bottom:1rem;border-radius:8px;">';
        echo '<div style="display:flex;justify-content:space-between;align-items:center;">';
        echo '<strong>' . esc_html($t->subject) . '</strong>';
        echo '<span style="background:#1a1a1a;color:white;padding:0.2rem 0.6rem;border-radius:4px;font-size:0.8rem;">' . esc_html(ucfirst($t->status)) . '</span>';
        echo '</div>';
        echo '<p style="margin:0.5rem 0;color:#666;font-size:0.9rem;">';
        echo '<strong>' . esc_html($t->name) . '</strong> (' . esc_html($t->email) . ') - ' . esc_html($t->category);
        echo '</p>';
        echo '<p style="margin:0.5rem 0;font-size:0.95rem;">' . nl2br(esc_html($t->message)) . '</p>';
        
        if ($t->reply) {
            echo '<div style="background:#e8f5e9;padding:0.75rem;border-radius:6px;margin-top:0.5rem;font-size:0.9rem;">';
            echo '<strong>Your reply:</strong> ' . nl2br(esc_html($t->reply));
            echo '</div>';
        } else {
            echo '<form method="post" style="margin-top:1rem;">';
            echo '<input type="hidden" name="ticket_id" value="' . $t->id . '">';
            echo '<textarea name="reply" rows="3" placeholder="Type your reply..." style="width:100%;padding:0.5rem;border:1px solid #ddd;border-radius:6px;margin-bottom:0.5rem;"></textarea>';
            echo '<button type="submit" name="tln_reply_ticket" value="1" style="background:#1a1a1a;color:white;padding:0.5rem 1rem;border:none;border-radius:4px;cursor:pointer;">Send Reply</button>';
            echo '</form>';
        }
        
        echo '<p style="margin:0.5rem 0 0;font-size:0.8rem;color:#999;">' . $t->created_at . '</p>';
        echo '</div>';
    }
}
