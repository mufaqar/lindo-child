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
    $posts = get_posts(array(
        'post_type'      => 'gold_rate',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));

    if (empty($posts)) {
        return 'No rates found.';
    }

    $post_id = $posts[0]->ID;

    $gold      = get_post_meta($post_id, 'gold_price', true);
    $silver    = get_post_meta($post_id, 'silver_price', true);
    $platinum  = get_post_meta($post_id, 'platinum_price', true);
    $palladium = get_post_meta($post_id, 'palladium_price', true);
    $unit      = get_post_meta($post_id, 'unit', true);
    $currency  = get_post_meta($post_id, 'currency', true);
 ob_start();
    ?>

<div class="marquee">
    <div class="marquee-track">
        <div class="marquee-group">
            Gold: <?php echo esc_html($gold); ?> <?php echo esc_html($currency); ?>/<?php echo esc_html($unit); ?>
            &nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;
            Silver: <?php echo esc_html($silver); ?> <?php echo esc_html($currency); ?>/<?php echo esc_html($unit); ?>
            &nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;
            Platinum: <?php echo esc_html($platinum); ?> <?php echo esc_html($currency); ?>/<?php echo esc_html($unit); ?>
            &nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;
            Palladium: <?php echo esc_html($palladium); ?> <?php echo esc_html($currency); ?>/<?php echo esc_html($unit); ?>
            &nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;
        </div>

        <div class="marquee-group">
            Gold: <?php echo esc_html($gold); ?> <?php echo esc_html($currency); ?>/<?php echo esc_html($unit); ?>
            &nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;
            Silver: <?php echo esc_html($silver); ?> <?php echo esc_html($currency); ?>/<?php echo esc_html($unit); ?>
            &nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;
            Platinum: <?php echo esc_html($platinum); ?> <?php echo esc_html($currency); ?>/<?php echo esc_html($unit); ?>
            &nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;
            Palladium: <?php echo esc_html($palladium); ?> <?php echo esc_html($currency); ?>/<?php echo esc_html($unit); ?>
            &nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;
        </div>
    </div>
</div>

<?php
    return ob_get_clean();
}

add_shortcode('star_gold_rate', 'pmx_gold_silver_marquee_shortcode');



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