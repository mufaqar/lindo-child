<?php


function send_gold_rate_to_whatsapp() {
    $args = array(
        'post_type'      => 'gold_rate',
        'posts_per_page' => 1,
        'post_status'    => 'publish'
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $query->the_post();
        $title = get_the_title();

        $api_url = "https://wasenderapi.com/api/send-message";

        // ❌ WRONG FORMAT
        // $phone_number = "03396006280";

        // ✅ FIX (Pakistan example → add country code, remove 0)
        $phone_number = "923396006280"; 

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer 12125113140da8c4341556756924f64a142b2db31b1cd62a83121ac84aa55b29',
                'Content-Type'  => 'application/json'
            ),
            'body' => json_encode(array(
                'to'      => $phone_number,
                'message' => $title
            ))
        ));

        wp_reset_postdata();

        // ✅ RETURN RESPONSE
        if (is_wp_error($response)) {
            return $response->get_error_message();
        } else {
            return array(
                'status_code' => wp_remote_retrieve_response_code($response),
                'body'        => wp_remote_retrieve_body($response)
            );
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
    $schedules['every_30_seconds'] = array(
        'interval' => 30,
        'display'  => 'Every 30 Seconds'
    );
    return $schedules;
});

add_action('init', function() {
    if (!wp_next_scheduled('send_gold_rate_event')) {
        wp_schedule_event(time(), 'every_30_seconds', 'send_gold_rate_event');
    }
});

add_action('send_gold_rate_event', 'send_gold_rate_to_whatsapp');