<?php

function send_gold_rate_to_whatsapp() {

    // 🔹 Get latest post
    $args = array(
        'post_type'      => 'gold_rate',
        'posts_per_page' => 1,
        'post_status'    => 'publish'
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();

        // 🔴 جلوگیری از ارسال تکراری
        $last_sent_id = get_option('last_sent_gold_rate_id');

        if ($last_sent_id == $post_id) {
            return "Already sent";
        }

        // 🔹 Get meta fields
        $gold      = get_post_meta($post_id, 'gold_price', true);
        $silver    = get_post_meta($post_id, 'silver_price', true);
        $platinum  = get_post_meta($post_id, 'platinum_price', true);
        $palladium = get_post_meta($post_id, 'palladium_price', true);

        // 🔹 Convert to tola
        $gold_tola      = number_format(round($gold * 11.6638));
        $silver_tola    = number_format(round($silver * 11.6638));
        $platinum_tola  = number_format(round($platinum * 11.6638));
        $palladium_tola = number_format(round($palladium * 11.6638));

        // 🔹 Message
        $message = "📊 Gold & Metals Update\n\n";
        $message .= "🥇 Gold: {$gold_tola} PKR / tola\n";
        $message .= "🥈 Silver: {$silver_tola} PKR / tola\n";
        $message .= "⚪ Platinum: {$platinum_tola} PKR / tola\n";
        $message .= "🔘 Palladium: {$palladium_tola} PKR / tola\n\n";
        $message .= "🕒 " . current_time('Y-m-d H:i:s');

        // 🔹 Send API
        $response = wp_remote_post("https://wasenderapi.com/api/send-message", array(
            'headers' => array(
                'Authorization' => 'Bearer ' . WASENDER_API_KEY,
                'Content-Type'  => 'application/json'
            ),
            'body' => json_encode(array(
                'to'   => '120363405110564479@g.us',
                'text' => $message
            ))
        ));

        // 🔹 Save last sent ID
        update_option('last_sent_gold_rate_id', $post_id);

        wp_reset_postdata();

        // 🔹 Debug
        if (is_wp_error($response)) {
            return $response->get_error_message();
        } else {
            return wp_remote_retrieve_body($response);
        }
    }

    return "No post found";
}

add_action('init', function() {
    if (isset($_GET['send_gold_rate'])) {
        $response = send_gold_rate_to_whatsapp();

        echo "Gold rate sent to WhatsApp. Check logs for details.";

        echo "<pre>";
        print_r($response);
        echo "</pre>";
        exit;
    }
});

add_filter('cron_schedules', function($schedules) {
    $schedules['every_5_minutes'] = array(
         'interval' => 300,
        'display'  => 'Every 5 Minutes'
    );
    return $schedules;
});

add_action('init', function() {
    if (!wp_next_scheduled('send_gold_rate_event')) {
        wp_schedule_event(time(), 'every_5_minutes', 'send_gold_rate_event');
    }
});

add_action('send_gold_rate_event', 'send_gold_rate_to_whatsapp');
