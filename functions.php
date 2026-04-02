<?php

function lindo_child_enqueue_styles() {
	wp_enqueue_style( 'lindo-child-style', get_stylesheet_uri() );
}

add_action( 'wp_enqueue_scripts', 'lindo_child_enqueue_styles', 100 );


//include_once get_stylesheet_directory() . '/gold.php';



/**
 * Serial verification form shortcode
 * Usage: [serial_verification]
 */

add_action('wp_enqueue_scripts', 'child_enqueue_verification_assets');
function child_enqueue_verification_assets() {
   

    wp_enqueue_script(
        'child-verification-script',
        get_stylesheet_directory_uri() . '/verification.js',
        array('jquery'),
        '1.0',
        true
    );

    wp_localize_script('child-verification-script', 'verification_ajax_obj', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('serial_verification_nonce'),
    ));
}

add_shortcode('serial_verification', 'child_serial_verification_shortcode');
function child_serial_verification_shortcode() {
    ob_start();
    ?>

<div class="verification-wrapper">
    <img src="<?php echo esc_url_raw( get_stylesheet_directory_uri().'/images/verification.jpeg'); ?>"
        alt="Verification Icon" class="verification-icon"  />

    <div class="verification-box">
        <h2>Verification For Bar</h2>
        <p class="verification-subtitle">Item Serial Number</p>

        <form id="serial-verification-form">
            <input type="text" id="serial_number" name="serial_number" placeholder="Enter serial number" required />
            <button type="submit">VIEW CERTIFICATE</button>
        </form>

        <div id="serial-verification-result"></div>
    </div>
</div>
<?php
    return ob_get_clean();
}

/**
 * AJAX search handler
 */
add_action('wp_ajax_serial_verification_search', 'child_serial_verification_search');
add_action('wp_ajax_nopriv_serial_verification_search', 'child_serial_verification_search');

function child_serial_verification_search() {
    check_ajax_referer('serial_verification_nonce', 'nonce');

    $serial_number = isset($_POST['serial_number']) ? sanitize_text_field($_POST['serial_number']) : '';

    if (empty($serial_number)) {
        wp_send_json_error(array(
            'message' => 'Please enter a serial number.'
        ));
    }

    /*
     * Change these if needed:
     * post_type = verified
     * meta_key  = serial_number
     */
    $query = new WP_Query(array(
        'post_type'      => 'verified',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array(
                'key'     => 'serial_number', // change to your actual meta key
                'value'   => $serial_number,
                'compare' => '='
            )
        )
    ));

    if ($query->have_posts()) {
        $query->the_post();

        $post_id = get_the_ID();

        // Certificate URL logic:
        // 1. If you have a custom field with external/internal certificate URL, use it
        // 2. Otherwise fallback to the post permalink
        $certificate_url = get_post_meta($post_id, 'certificate_url', true);
        if (empty($certificate_url)) {
            $certificate_url = get_permalink($post_id);
        }

        wp_reset_postdata();

        wp_send_json_success(array(
            'message' => 'Congratulations! Your product has been successfully verified. ✅',
            'url'     => esc_url($certificate_url),
            'title'   => get_the_title($post_id),
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'No certificate found for this serial number.'
        ));
    }
}




//define('GOLD_API_KEY', 'QWOIJUPFGXI8MSHKQLZW205HKQLZW');



if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| API KEY
|--------------------------------------------------------------------------
*/
//define('GOLD_API_KEY', 'QWOIJUPFGXI8MSHKQLZW205HKQLZW');


/*
|--------------------------------------------------------------------------
| REGISTER CUSTOM POST TYPE
|--------------------------------------------------------------------------
*/
function pmx_register_gold_rates_cpt() {
    register_post_type('gold_rate', array(
        'labels' => array(
            'name'          => 'Gold Rates',
            'singular_name' => 'Gold Rate',
        ),
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-chart-line',
        'supports'     => array('title', 'editor'),
        'show_in_rest' => true,
    ));
}
add_action('init', 'pmx_register_gold_rates_cpt');


/*
|--------------------------------------------------------------------------
| ADD DAILY CRON SCHEDULE IF NEEDED
|--------------------------------------------------------------------------
*/
function pmx_gold_rates_activate_cron() {
    if (!wp_next_scheduled('pmx_fetch_gold_rates_daily')) {
        wp_schedule_event(time(), 'daily', 'pmx_fetch_gold_rates_daily');
    }
}
add_action('wp', 'pmx_gold_rates_activate_cron');


/*
|--------------------------------------------------------------------------
| CLEAR CRON ON THEME SWITCH (OPTIONAL)
|--------------------------------------------------------------------------
*/
function pmx_clear_gold_rates_cron() {
    $timestamp = wp_next_scheduled('pmx_fetch_gold_rates_daily');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'pmx_fetch_gold_rates_daily');
    }
}
add_action('switch_theme', 'pmx_clear_gold_rates_cron');


/*
|--------------------------------------------------------------------------
| FETCH API AND SAVE TO CUSTOM POST TYPE
|--------------------------------------------------------------------------
*/
function pmx_fetch_and_store_gold_rates() {

    $url = add_query_arg(array(
        'api_key'  => 'QWOIJUPFGXI8MSHKQLZW205HKQLZW',
        'currency' => 'PKR',
        'unit'     => 'g',
    ), 'https://api.metals.dev/v1/latest');

    $response = wp_remote_get($url, array(
        'timeout' => 30,
        'headers' => array(
            'Accept' => 'application/json',
        ),
    ));

    if (is_wp_error($response)) {
        error_log('Gold API Error: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data) || !isset($data['status']) || $data['status'] !== 'success') {
        error_log('Gold API Invalid Response');
        return false;
    }

    $metal_time    = isset($data['timestamps']['metal']) ? $data['timestamps']['metal'] : current_time('mysql');
    $currency_time = isset($data['timestamps']['currency']) ? $data['timestamps']['currency'] : current_time('mysql');

    $gold      = isset($data['metals']['gold']) ? $data['metals']['gold'] : '';
    $silver    = isset($data['metals']['silver']) ? $data['metals']['silver'] : '';
    $platinum  = isset($data['metals']['platinum']) ? $data['metals']['platinum'] : '';
    $palladium = isset($data['metals']['palladium']) ? $data['metals']['palladium'] : '';

    $post_id = wp_insert_post(array(
        'post_type'   => 'gold_rate',
        'post_status' => 'publish',
        'post_title'  => 'Gold Rate - ' . current_time('Y-m-d H:i:s'),
        'post_content'=> wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    ));

    if (is_wp_error($post_id) || !$post_id) {
        error_log('Gold Rate Post Insert Failed');
        return false;
    }

    update_post_meta($post_id, 'gold_price', $gold);
    update_post_meta($post_id, 'silver_price', $silver);
    update_post_meta($post_id, 'platinum_price', $platinum);
    update_post_meta($post_id, 'palladium_price', $palladium);

    update_post_meta($post_id, 'currency', isset($data['currency']) ? $data['currency'] : '');
    update_post_meta($post_id, 'unit', isset($data['unit']) ? $data['unit'] : '');

    update_post_meta($post_id, 'metal_timestamp', $metal_time);
    update_post_meta($post_id, 'currency_timestamp', $currency_time);

    update_post_meta($post_id, 'full_api_response', $data);

    return $post_id;
}
add_action('pmx_fetch_gold_rates_daily', 'pmx_fetch_and_store_gold_rates');


function pmx_gold_silver_marquee_shortcode() {
    // Get today's latest rate
    $today_posts = get_posts(array(
        'post_type'      => 'gold_rate',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'date_query'     => array(
            array(
                'year'  => date('Y'),
                'month' => date('m'),
                'day'   => date('d'),
            ),
        ),
    ));

    // Get yesterday's first rate (or last rate from yesterday)
    $yesterday_posts = get_posts(array(
        'post_type'      => 'gold_rate',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'date_query'     => array(
            array(
                'year'  => date('Y', strtotime('-1 day')),
                'month' => date('m', strtotime('-1 day')),
                'day'   => date('d', strtotime('-1 day')),
            ),
        ),
    ));

    if (empty($today_posts)) {
        return 'No rates found.';
    }

    $today_post_id = $today_posts[0]->ID;
    
    // Get today's prices
    $gold_today      = get_post_meta($today_post_id, 'gold_price', true);
    $silver_today    = get_post_meta($today_post_id, 'silver_price', true);
    $platinum_today  = get_post_meta($today_post_id, 'platinum_price', true);
    $palladium_today = get_post_meta($today_post_id, 'palladium_price', true);
    
    // Get yesterday's prices
    $gold_yesterday = $silver_yesterday = $platinum_yesterday = $palladium_yesterday = 0;
    if (!empty($yesterday_posts)) {
        $yesterday_post_id = $yesterday_posts[0]->ID;
        $gold_yesterday      = get_post_meta($yesterday_post_id, 'gold_price', true);
        $silver_yesterday    = get_post_meta($yesterday_post_id, 'silver_price', true);
        $platinum_yesterday  = get_post_meta($yesterday_post_id, 'platinum_price', true);
        $palladium_yesterday = get_post_meta($yesterday_post_id, 'palladium_price', true);
    }
    
    $unit = get_post_meta($today_post_id, 'unit', true);
    $currency = get_post_meta($today_post_id, 'currency', true);

    // Convert Gram → Tola
    $tola = 11.6638038;
    
    $gold_today      = round($gold_today * $tola, 2);
    $silver_today    = round($silver_today * $tola, 2);
    $platinum_today  = round($platinum_today * $tola, 2);
    $palladium_today = round($palladium_today * $tola, 2);
    
    $gold_yesterday      = round($gold_yesterday * $tola, 2);
    $silver_yesterday    = round($silver_yesterday * $tola, 2);
    $platinum_yesterday  = round($platinum_yesterday * $tola, 2);
    $palladium_yesterday = round($palladium_yesterday * $tola, 2);
    
    // Calculate changes
    $gold_change      = $gold_today - $gold_yesterday;
    $silver_change    = $silver_today - $silver_yesterday;
    $platinum_change  = $platinum_today - $platinum_yesterday;
    $palladium_change = $palladium_today - $palladium_yesterday;
    
    $unit = 'Tola';
    
    ob_start();
    ?>
    
    <style>
        .metal-price {
            display: inline-block;
            margin: 0 15px;
        }
        .metal-name {
            font-weight: bold;
        }
        .current-price {
            font-size: 1.2em;
        }
        .price-change {
            font-size: 0.9em;
            margin-left: 5px;
        }
        .positive {
            color: green;
        }
        .negative {
            color: red;
        }
        .marquee-container {
            overflow: hidden;
            white-space: nowrap;
            background: #f5f5f5;
            padding: 10px 0;
        }
        .marquee {
            display: inline-block;
            animation: marquee 30s linear infinite;
        }
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .metal-item {
            display: inline-block;
            margin: 0 20px;
        }
    </style>
    
    <div class="marquee-container">
        <div class="marquee">
            <div class="metal-item">
                <span class="metal-name">Gold:</span>
                <span class="current-price"><?php echo esc_html(number_format($gold_today, 2)); ?></span>
                <span class="price-change <?php echo $gold_change >= 0 ? 'positive' : 'negative'; ?>">
                    (<?php echo $gold_change >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($gold_change, 2)); ?>)
                </span>
            </div>
            
            <div class="metal-item">
                <span class="metal-name">Silver:</span>
                <span class="current-price"><?php echo esc_html(number_format($silver_today, 2)); ?></span>
                <span class="price-change <?php echo $silver_change >= 0 ? 'positive' : 'negative'; ?>">
                    (<?php echo $silver_change >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($silver_change, 2)); ?>)
                </span>
            </div>
            
            <div class="metal-item">
                <span class="metal-name">Platinum:</span>
                <span class="current-price"><?php echo esc_html(number_format($platinum_today, 2)); ?></span>
                <span class="price-change <?php echo $platinum_change >= 0 ? 'positive' : 'negative'; ?>">
                    (<?php echo $platinum_change >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($platinum_change, 2)); ?>)
                </span>
            </div>
            
            <div class="metal-item">
                <span class="metal-name">Palladium:</span>
                <span class="current-price"><?php echo esc_html(number_format($palladium_today, 2)); ?></span>
                <span class="price-change <?php echo $palladium_change >= 0 ? 'positive' : 'negative'; ?>">
                    (<?php echo $palladium_change >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($palladium_change, 2)); ?>)
                </span>
            </div>
            
            <!-- Duplicate for seamless marquee -->
            <div class="metal-item">
                <span class="metal-name">Gold:</span>
                <span class="current-price"><?php echo esc_html(number_format($gold_today, 2)); ?></span>
                <span class="price-change <?php echo $gold_change >= 0 ? 'positive' : 'negative'; ?>">
                    (<?php echo $gold_change >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($gold_change, 2)); ?>)
                </span>
            </div>
            
            <div class="metal-item">
                <span class="metal-name">Silver:</span>
                <span class="current-price"><?php echo esc_html(number_format($silver_today, 2)); ?></span>
                <span class="price-change <?php echo $silver_change >= 0 ? 'positive' : 'negative'; ?>">
                    (<?php echo $silver_change >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($silver_change, 2)); ?>)
                </span>
            </div>
            
            <div class="metal-item">
                <span class="metal-name">Platinum:</span>
                <span class="current-price"><?php echo esc_html(number_format($platinum_today, 2)); ?></span>
                <span class="price-change <?php echo $platinum_change >= 0 ? 'positive' : 'negative'; ?>">
                    (<?php echo $platinum_change >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($platinum_change, 2)); ?>)
                </span>
            </div>
            
            <div class="metal-item">
                <span class="metal-name">Palladium:</span>
                <span class="current-price"><?php echo esc_html(number_format($palladium_today, 2)); ?></span>
                <span class="price-change <?php echo $palladium_change >= 0 ? 'positive' : 'negative'; ?>">
                    (<?php echo $palladium_change >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($palladium_change, 2)); ?>)
                </span>
            </div>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

add_shortcode('star_gold_rate', 'pmx_gold_silver_marquee_shortcode');



function pmx_gold_silver_static_shortcode() {
    // Same code as above to fetch today's and yesterday's prices...
    
    ob_start();
    ?>
    
    <style>
        .gold-rates-table {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            max-width: 400px;
        }
        .rate-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .rate-row:last-child {
            border-bottom: none;
        }
        .metal-name {
            font-weight: bold;
            min-width: 80px;
        }
        .current-price {
            font-size: 1.1em;
            font-weight: bold;
            min-width: 100px;
            text-align: right;
        }
        .price-change {
            min-width: 80px;
            text-align: right;
        }
        .positive {
            color: green;
        }
        .negative {
            color: red;
        }
        .currency {
            font-size: 0.8em;
            color: #666;
        }
    </style>
    
    <div class="gold-rates-table">
        <div class="rate-row">
            <span class="metal-name">Gold:</span>
            <span class="current-price"><?php echo esc_html(number_format($gold_today, 2)); ?></span>
            <span class="price-change <?php echo $gold_change >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $gold_change >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($gold_change, 2)); ?>
            </span>
        </div>
        
        <div class="rate-row">
            <span class="metal-name">Silver:</span>
            <span class="current-price"><?php echo esc_html(number_format($silver_today, 2)); ?></span>
            <span class="price-change <?php echo $silver_change >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $silver_change >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($silver_change, 2)); ?>
            </span>
        </div>
        
        <div class="rate-row">
            <span class="metal-name">Platinum:</span>
            <span class="current-price"><?php echo esc_html(number_format($platinum_today, 2)); ?></span>
            <span class="price-change <?php echo $platinum_change >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $platinum_change >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($platinum_change, 2)); ?>
            </span>
        </div>
        
        <div class="rate-row">
            <span class="metal-name">Palladium:</span>
            <span class="current-price"><?php echo esc_html(number_format($palladium_today, 2)); ?></span>
            <span class="price-change <?php echo $palladium_change >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $palladium_change >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($palladium_change, 2)); ?>
            </span>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

add_shortcode('star_gold_rate_static', 'pmx_gold_silver_static_shortcode');


function pmx_get_latest_metal_rates() {
    $posts = get_posts(array(
        'post_type'      => 'gold_rate',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));

    if (empty($posts)) {
        return false;
    }

    $post_id = $posts[0]->ID;

    return array(
        'gold'      => (float) get_post_meta($post_id, 'gold_price', true),
        'silver'    => (float) get_post_meta($post_id, 'silver_price', true),
        'platinum'  => (float) get_post_meta($post_id, 'platinum_price', true),
        'palladium' => (float) get_post_meta($post_id, 'palladium_price', true),
    );
}


function pmx_get_dynamic_metal_product_price($product) {
    if (!$product || !is_a($product, 'WC_Product')) {
        return false;
    }

    $product_id = $product->get_id();
    $parent_id  = $product->get_parent_id();

    // First try current product/variation
    $metal_type   = get_post_meta($product_id, 'metal_type', true);
    $metal_weight = get_post_meta($product_id, 'metal_weight', true);

    // If variation meta is empty, try parent product
    if ((empty($metal_type) || empty($metal_weight)) && !empty($parent_id)) {
        $metal_type   = get_post_meta($parent_id, 'metal_type', true);
        $metal_weight = get_post_meta($parent_id, 'metal_weight', true);
    }

    $metal_type   = strtolower(trim((string) $metal_type));
    $metal_weight = (float) $metal_weight;

    if (empty($metal_type) || empty($metal_weight)) {
        return false;
    }

    $rates = pmx_get_latest_metal_rates();

    if (!$rates) {
        return false;
    }

    if (!isset($rates[$metal_type])) {
        return false;
    }

    $rate = (float) $rates[$metal_type];

    if ($rate <= 0) {
        return false;
    }

    $final_price = $rate * $metal_weight;

    return $final_price;
}

function pmx_dynamic_product_price($price, $product) {
    $dynamic_price = pmx_get_dynamic_metal_product_price($product);

    if ($dynamic_price === false) {
        return $price;
    }

    return $dynamic_price;
}
add_filter('woocommerce_product_get_price', 'pmx_dynamic_product_price', 9999, 2);
add_filter('woocommerce_product_get_regular_price', 'pmx_dynamic_product_price', 9999, 2);
add_filter('woocommerce_product_variation_get_price', 'pmx_dynamic_product_price', 9999, 2);
add_filter('woocommerce_product_variation_get_regular_price', 'pmx_dynamic_product_price', 9999, 2);

function pmx_dynamic_price_html($price_html, $product) {
    $dynamic_price = pmx_get_dynamic_metal_product_price($product);

    if ($dynamic_price === false) {
        return $price_html;
    }

    return wc_price($dynamic_price);
}
add_filter('woocommerce_get_price_html', 'pmx_dynamic_price_html', 9999, 2);

function pmx_set_cart_item_live_prices($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (did_action('woocommerce_before_calculate_totals') > 1) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item) {
        if (!empty($cart_item['data']) && is_a($cart_item['data'], 'WC_Product')) {
            $dynamic_price = pmx_get_dynamic_metal_product_price($cart_item['data']);

            if ($dynamic_price !== false) {
                $cart_item['data']->set_price($dynamic_price);
            }
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'pmx_set_cart_item_live_prices', 9999);










// Add custom 5-minute cron interval
add_filter('cron_schedules', function ($schedules) {
    $schedules['every_five_minutes'] = array(
        'interval' => 300,
        'display'  => __('Every 5 Minutes')
    );
    return $schedules;
});

// Schedule event if not already scheduled
add_action('init', function () {
    if (!wp_next_scheduled('pmx_fetch_gold_rates_every_5_minutes')) {
        wp_schedule_event(time(), 'every_five_minutes', 'pmx_fetch_gold_rates_every_5_minutes');
    }
});

// Hook your function
add_action('pmx_fetch_gold_rates_every_5_minutes', 'pmx_fetch_and_store_gold_rates');

function pmx_get_latest_gold_rate_message() {
    $posts = get_posts(array(
        'post_type'      => 'gold_rate',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));

    if (empty($posts)) {
        return false;
    }

    $post_id = $posts[0]->ID;
    $tola    = 11.6638038;

    $gold      = round((float) get_post_meta($post_id, 'gold_price', true) * $tola, 2);
    $silver    = round((float) get_post_meta($post_id, 'silver_price', true) * $tola, 2);
    $platinum  = round((float) get_post_meta($post_id, 'platinum_price', true) * $tola, 2);
    $palladium = round((float) get_post_meta($post_id, 'palladium_price', true) * $tola, 2);
    $currency  = get_post_meta($post_id, 'currency', true) ?: 'PKR';

    return "Gold Rates Update\n"
        . "Gold: {$gold} {$currency}/tola\n"
        . "Silver: {$silver} {$currency}/tola\n"
        . "Platinum: {$platinum} {$currency}/tola\n"
        . "Palladium: {$palladium} {$currency}/tola";
}