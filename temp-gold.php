
<?php
/*
Template Name: Live Gold Rates (Gram)
*/

get_header();

// ✅ Check API key
if (!defined('GOLD_API_KEY') || empty(GOLD_API_KEY)) {
    echo "<p>API Key missing</p>";
    get_footer();
    return;
}

// ✅ API URL (already in grams)
$url = add_query_arg(array(
    'api_key'  => GOLD_API_KEY,
    'currency' => 'PKR',
    'unit'     => 'g', // IMPORTANT → gram
), 'https://api.metals.dev/v1/latest');

// ✅ API call
$response = wp_remote_get($url, array(
    'timeout' => 20,
));

if (is_wp_error($response)) {
    echo "<p>API Error: " . esc_html($response->get_error_message()) . "</p>";
    get_footer();
    return;
}

$data = json_decode(wp_remote_retrieve_body($response), true);

if (empty($data) || $data['status'] !== 'success') {
    echo "<p>Invalid API response</p>";
    get_footer();
    return;
}

// ✅ Direct values (NO conversion)
$gold      = (float) ($data['metals']['gold'] ?? 0);
$silver    = (float) ($data['metals']['silver'] ?? 0);
$platinum  = (float) ($data['metals']['platinum'] ?? 0);
$palladium = (float) ($data['metals']['palladium'] ?? 0);

$currency = esc_html($data['currency'] ?? 'PKR');
?>

<div style="max-width:800px;margin:50px auto;font-family:sans-serif;">

    <h2>📊 Live Metal Rates (Per Gram)</h2>
    <p><strong>Updated:</strong> <?php echo current_time('Y-m-d H:i:s'); ?></p>

    <table style="width:100%;border-collapse:collapse;margin-top:20px;">
        <thead>
            <tr style="background:#222;color:#fff;">
                <th style="padding:10px;">Metal</th>
                <th style="padding:10px;">Price (<?php echo $currency; ?> / gram)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding:10px;">🥇 Gold</td>
                <td style="padding:10px;"><?php echo number_format($gold, 2); ?></td>
            </tr>
            <tr>
                <td style="padding:10px;">🥈 Silver</td>
                <td style="padding:10px;"><?php echo number_format($silver, 2); ?></td>
            </tr>
            <tr>
                <td style="padding:10px;">⚪ Platinum</td>
                <td style="padding:10px;"><?php echo number_format($platinum, 2); ?></td>
            </tr>
            <tr>
                <td style="padding:10px;">🔘 Palladium</td>
                <td style="padding:10px;"><?php echo number_format($palladium, 2); ?></td>
            </tr>
        </tbody>
    </table>

</div>

<?php get_footer(); ?>





