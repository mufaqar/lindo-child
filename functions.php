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
<div class="verification-box">
    <h2>Verification</h2>
    <p class="verification-subtitle">item serial number</p>

    <form id="serial-verification-form">
        <input type="text" id="serial_number" name="serial_number" placeholder="Enter serial number" required />
        <button type="submit">VIEW CERTIFICATE</button>
    </form>

    <div id="serial-verification-result"></div>
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