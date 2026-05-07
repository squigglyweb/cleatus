<?php
/**
 * Plugin Name: TLN Weather Widget
 * Version: 1.0
 */

function tln_weather_widget($atts) {
    $atts = shortcode_atts(array('location'=>'Waxhaw'), $atts);
    $location = $atts['location'];
    
    // Get weather from Open-Meteo (free, no API key)
    $lat = 34.9874; // Waxhaw, NC
    $lon = -80.7532;
    
    $url = "https://api.open-meteo.com/v1/forecast?latitude=$lat&longitude=$lon&current=temperature_2m,weather_code,wind_speed_10m&temperature_unit=fahrenheit";
    
    $response = wp_remote_get($url);
    
    if(is_wp_error($response)) {
        return '<div class="tln-weather" style="padding:1rem;background:#f8f8f8;border-radius:8px;text-align:center;">Weather unavailable</div>';
    }
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    $temp = round($data['current']['temperature_2m']);
    $code = $data['current']['weather_code'];
    $wind = round($data['current']['wind_speed_10m']);
    
    // Weather code to icon
    $icons = array(
        0 => '☀️', // Clear
        1 => '🌤️', 2 => '⛅', 3 => '☁️', // Cloudy
        45 => '🌫️', 48 => '🌫️', // Fog
        51 => '🌧️', 53 => '🌧️', 55 => '🌧️', // Drizzle
        61 => '🌧️', 63 => '🌧️', 65 => '🌧️', // Rain
        71 => '❄️', 73 => '❄️', 75 => '❄️', // Snow
        80 => '🌦️', 81 => '🌦️', 82 => '🌦️', // Showers
        95 => '⛈️', 96 => '⛈️', 99 => '⛈️' // Thunder
    );
    $icon = $icons[$code] ?? '🌤️';
    
    ob_start();
    ?>
    <div class="tln-weather" style="background:#fff;border:2px solid #1a1a1a;border-radius:12px;padding:1.5rem;max-width:300px;">
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
            <span style="font-size:3rem;"><?php echo $icon; ?></span>
            <div>
                <div style="font-size:2.5rem;font-weight:700;color:#e63946;"><?php echo $temp; ?>°F</div>
                <div style="color:#666;font-size:0.9rem;"><?php echo $location; ?></div>
            </div>
        </div>
        <div style="border-top:1px solid #eee;padding-top:0.75rem;color:#666;font-size:0.85rem;">
            💨 Wind: <?php echo $wind; ?> mph
        </div>
        <div style="text-align:center;margin-top:0.75rem;">
            <a href="https://weather.com/weather/today/l/<?php echo urlencode($location); ?>" target="_blank" style="color:#e63946;font-size:0.8rem;">Full Forecast →</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('tln_weather', 'tln_weather_widget');
