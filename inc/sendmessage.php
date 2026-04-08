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

       // $group_id = "1203630XXXXXXX@g.us"; // ✅ FIX THIS

          $phone_number = "03396006280"; // ✅ PUT NUMBER HERE

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

        // Debug log
        if (is_wp_error($response)) {
            error_log('WhatsApp Error: ' . $response->get_error_message());
        } else {
            error_log('WhatsApp Response: ' . wp_remote_retrieve_body($response));
        }
    }

    wp_reset_postdata();
}

add_action('init', function() {
    if (isset($_GET['send_gold_rate'])) {
        send_gold_rate_to_whatsapp();
        echo "Sent!";
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