<?php


/*
|--------------------------------------------------------------------------
| FETCH API AND SAVE TO CUSTOM POST TYPE
|--------------------------------------------------------------------------
*/
function pmx_fetch_and_store_gold_rates() {

    if (!defined('GOLD_API_KEY') || empty(GOLD_API_KEY)) {
        error_log('GOLD API KEY MISSING');
        return false;
    }

    $url = add_query_arg(array(
        'api_key'  => GOLD_API_KEY,
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

    $gold      = $data['metals']['gold'] ?? '';
    $silver    = $data['metals']['silver'] ?? '';
    $platinum  = $data['metals']['platinum'] ?? '';
    $palladium = $data['metals']['palladium'] ?? '';

    // ✅ Prevent duplicate posts
    $last_post = get_posts(array(
        'post_type'      => 'gold_rate',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC'
    ));

    if (!empty($last_post)) {
        $last_id   = $last_post[0]->ID;
        $last_gold = get_post_meta($last_id, 'gold_price', true);

        if ($last_gold == $gold) {
            return "No change in price";
        }
    }

    // ✅ Insert new post
    $post_id = wp_insert_post(array(
        'post_type'   => 'gold_rate',
        'post_status' => 'publish',
        'post_title'  => 'Gold Rate - ' . current_time('Y-m-d H:i:s'),
        'post_content'=> wp_json_encode($data, JSON_PRETTY_PRINT),
    ));

    if (is_wp_error($post_id) || !$post_id) {
        error_log('Gold Rate Post Insert Failed');
        return false;
    }

    // ✅ Save meta
    update_post_meta($post_id, 'gold_price', $gold);
    update_post_meta($post_id, 'silver_price', $silver);
    update_post_meta($post_id, 'platinum_price', $platinum);
    update_post_meta($post_id, 'palladium_price', $palladium);

    update_post_meta($post_id, 'currency', $data['currency'] ?? 'PKR');
    update_post_meta($post_id, 'unit', $data['unit'] ?? 'g');

    update_post_meta($post_id, 'metal_timestamp', $data['timestamps']['metal'] ?? '');
    update_post_meta($post_id, 'currency_timestamp', $data['timestamps']['currency'] ?? '');

    return $post_id;
}



// Add 5-minute interval
add_filter('cron_schedules', function ($schedules) {
    $schedules['every_five_minutes'] = array(
        'interval' => 300,
        'display'  => __('Every 5 Minutes')
    );
    return $schedules;
});

// Add 5-minute interval
add_filter('cron_schedules', function ($schedules) {
    $schedules['every_five_minutes'] = array(
        'interval' => 300,
        'display'  => __('Every 5 Minutes')
    );
    return $schedules;
});

// Schedule cron
add_action('init', function () {

    if (!wp_next_scheduled('pmx_fetch_gold_rates_every_5_minutes')) {

        wp_schedule_event(time(), 'every_five_minutes', 'pmx_fetch_gold_rates_every_5_minutes');
    }
});

// Hook event to function
add_action('pmx_fetch_gold_rates_every_5_minutes', 'pmx_fetch_and_store_gold_rates');


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

        /* Convert Gram → Tola */
        $tola = 11.6638038;

        $gold      = round($gold * $tola, 2);
        $silver    = round($silver * $tola, 2);
        $platinum  = round($platinum * $tola, 2);
        $palladium = round($palladium * $tola, 2);
        $unit = 'Tola';


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