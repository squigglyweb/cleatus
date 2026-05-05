<?php
/**
 * Plugin Name: TLN Admin Dashboard
 * Description: Revenue tracker and business stats for Bryan
 * Version: 1.0
 */

add_action('admin_menu', 'tln_admin_dashboard_menu');

function tln_admin_dashboard_menu() {
    add_menu_page('TLN Dashboard', 'TLN Dashboard', 'manage_options', 'tln-revenue', 'tln_revenue_dashboard', 'dashicons-chart-bar', 1);
}

function tln_revenue_dashboard() {
    global $wpdb;
    
    // Get business counts by tier
    // For now, we'll track via user meta and a simple system
    // In production, add a 'tln_tier' field to business meta
    
    $costs = array(
        'website_hosting' => 30,
        'domain' => 15,
        'google_api' => 0, // free tier
        'email_service' => 20,
        'postcard_print' => 1200, // per campaign (2x year)
        'misc' => 50,
    );
    
    $monthly_costs = $costs['website_hosting'] + $costs['domain'] + $costs['email_service'] + $costs['misc'];
    $campaign_cost = $costs['postcard_print'] / 6; // monthly amortized
    
    $total_monthly_costs = $monthly_costs + $campaign_cost;
    
    // Revenue tiers
    $pro_count = 0; // TODO: connect to actual tier tracking
    $pro_plus_count = 0;
    $sponsor_count = 0;
    
    $pro_price = 99;
    $pro_plus_price = 199;
    $sponsor_price = 349;
    
    $monthly_revenue = ($pro_count * $pro_price) + ($pro_plus_count * $pro_plus_price) + ($sponsor_count * $sponsor_price);
    $profit = $monthly_revenue - $total_monthly_costs;
    
    ?>
    <div class="wrap">
        <h1>💰 TLN Revenue Dashboard</h1>
        
        <div style="display:flex;gap:20px;margin-bottom:30px;flex-wrap:wrap;">
            <!-- Revenue Card -->
            <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);flex:1;min-width:250px;">
                <h2 style="margin:0 0 10px 0;color:#666;font-size:14px;text-transform:uppercase;">Monthly Revenue</h2>
                <div style="font-size:36px;font-weight:bold;color:#22c55e;">$<?php echo number_format($monthly_revenue); ?></div>
                <div style="color:#666;font-size:13px;">from <?php echo $pro_count + $pro_plus_count + $sponsor_count; ?> paying businesses</div>
            </div>
            
            <!-- Costs Card -->
            <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);flex:1;min-width:250px;">
                <h2 style="margin:0 0 10px 0;color:#666;font-size:14px;text-transform:uppercase;">Monthly Costs</h2>
                <div style="font-size:36px;font-weight:bold;color:#e63946;">$<?php echo number_format($total_monthly_costs); ?></div>
                <div style="color:#666;font-size:13px;">Hosting + email + amortized postcard</div>
            </div>
            
            <!-- Profit Card -->
            <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);flex:1;min-width:250px;">
                <h2 style="margin:0 0 10px 0;color:#666;font-size:14px;text-transform:uppercase;">Net Profit</h2>
                <div style="font-size:36px;font-weight:bold;color:<?php echo $profit >= 0 ? '#22c55e' : '#e63946'; ?>;">$<?php echo number_format($profit); ?></div>
                <div style="color:#666;font-size:13px;"><?php echo $profit >= 0 ? '✅ In the green' : '⚠️ Need more customers'; ?></div>
            </div>
        </div>
        
        <!-- Business Breakdown -->
        <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);margin-bottom:20px;">
            <h2 style="margin-top:0;">Business Tiers</h2>
            <table class="widefat" style="max-width:600px;">
                <thead>
                    <tr>
                        <th>Tier</th>
                        <th>Count</th>
                        <th>Price</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Pro ($99)</td>
                        <td><?php echo $pro_count; ?></td>
                        <td>$99</td>
                        <td>$<?php echo number_format($pro_count * $pro_price); ?></td>
                    </tr>
                    <tr>
                        <td>Pro+ ($199)</td>
                        <td><?php echo $pro_plus_count; ?></td>
                        <td>$199</td>
                        <td>$<?php echo number_format($pro_plus_count * $pro_plus_price); ?></td>
                    </tr>
                    <tr>
                        <td>Sponsor ($349)</td>
                        <td><?php echo $sponsor_count; ?></td>
                        <td>$349</td>
                        <td>$<?php echo number_format($sponsor_count * $sponsor_price); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Cost Breakdown -->
        <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);margin-bottom:20px;">
            <h2 style="margin-top:0;">Cost Breakdown (Monthly)</h2>
            <table class="widefat" style="max-width:600px;">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Monthly Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Website Hosting</td>
                        <td>$<?php echo $costs['website_hosting']; ?></td>
                    </tr>
                    <tr>
                        <td>Domain</td>
                        <td>$<?php echo $costs['domain']; ?></td>
                    </tr>
                    <tr>
                        <td>Email Service</td>
                        <td>$<?php echo $costs['email_service']; ?></td>
                    </tr>
                    <tr>
                        <td>Postcard (amortized)</td>
                        <td>$<?php echo round($campaign_cost); ?></td>
                    </tr>
                    <tr>
                        <td>Misc</td>
                        <td>$<?php echo $costs['misc']; ?></td>
                    </tr>
                    <tr style="font-weight:bold;background:#f5f5f5;">
                        <td>Total</td>
                        <td>$<?php echo number_format($total_monthly_costs); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Quick Actions -->
        <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <h2 style="margin-top:0;">Quick Actions</h2>
            <p>Coming soon: Update tier counts, track revenue, see trends.</p>
            <p><em>Currently this is a placeholder — connect to actual business tier data to track real revenue.</em></p>
        </div>
        
        <!-- Milestones -->
        <div style="margin-top:30px;padding:20px;background:#f0fdf4;border-radius:8px;border:1px solid #22c55e;">
            <h3 style="margin-top:0;color:#166534;">🎯 Milestones</h3>
            <ul style="color:#166534;">
                <li><strong>Goal 1:</strong> First paying customer → $99/mo revenue ✅</li>
                <li><strong>Goal 2:</strong> Break even (~$300/mo) → 3 Pro businesses</li>
                <li><strong>Goal 3:</strong> $2,400/mo → Postcard campaign covered</li>
                <li><strong>Goal 4:</strong> $5,000/mo → Full-time income territory</li>
            </ul>
        </div>
    </div>
    
    <style>
    .wrap { font-family: 'Open Sans', sans-serif; }
    </style>
    <?php
}
