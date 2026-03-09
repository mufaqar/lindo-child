<?php



/*Template Name: Gold Rate*/

get_header();







function pmx_get_latest_gold_rate() {
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
        'post_id'         => $post_id,
        'gold_price'      => get_post_meta($post_id, 'gold_price', true),
        'silver_price'    => get_post_meta($post_id, 'silver_price', true),
        'platinum_price'  => get_post_meta($post_id, 'platinum_price', true),
        'palladium_price' => get_post_meta($post_id, 'palladium_price', true),
        'currency'        => get_post_meta($post_id, 'currency', true),
        'unit'            => get_post_meta($post_id, 'unit', true),
        'metal_timestamp' => get_post_meta($post_id, 'metal_timestamp', true),
    );
}



$latest = pmx_get_latest_gold_rate();

echo '<pre>';
print_r($latest);
echo '</pre>';


get_footer();