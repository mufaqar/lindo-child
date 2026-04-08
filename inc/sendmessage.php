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

        //Authorization: Bearer 12125113140da8c4341556756924f64a142b2db31b1cd62a83121ac84aa55b29

        // Wasender API details
        $api_url = "https://wasenderapi.com/api/send-message"; // change if needed
        $api_key = "12125113140da8c4341556756924f64a142b2db31b1cd62a83121ac84aa55b29";
        $group_id = "75729";

        $body = array(
            'api_key' => $api_key,
            'to'      => $group_id,
            'message' => $title
        );

        wp_remote_post($api_url, array(
            'body' => $body
        ));
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