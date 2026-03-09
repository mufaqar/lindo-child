<?php



/*Template Name: Gold Rate*/

get_header();


exit();


// $url = "https://api.metals.dev/v1/metal/spot?api_key=QWOIJUPFGXI8MSHKQLZW205HKQLZW&metal=gold&currency=PKR";

// $curl = curl_init($url);
// curl_setopt($curl, CURLOPT_URL, $url);
// curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

// $headers = array("Accept: application/json");
// curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

// $resp = curl_exec($curl);
// curl_close($curl);
// var_dump($resp);




// function star_gold_rate_shortcode() {

// 	// Check API key
// 	if (!defined('RAPIDAPI_GOLD_PK_KEY')) {
// 		return 'API key not configured.';
// 	}

// 	// Fetch from API
// 	$response = wp_remote_get(
// 		'https://gold-prices-pakistan.p.rapidapi.com/history',
// 		[
// 			'headers' => [
// 				'x-rapidapi-host' => 'gold-prices-pakistan.p.rapidapi.com',
// 				'x-rapidapi-key'  => RAPIDAPI_GOLD_PK_KEY,
// 			],
// 			'timeout' => 20,
// 		]
// 	);

// 	if (is_wp_error($response)) {
// 		return 'Gold rate unavailable.';
// 	}

// 	$body = wp_remote_retrieve_body($response);
// 	$data = json_decode($body, true);

// 	if (!is_array($data) || empty($data)) {
// 		return 'Invalid gold data.';
// 	}

// 	// Build last 5 days message
// 	$parts = [];
// 	$count = 0;

// 	foreach ($data as $date => $price) {
// 		$parts[] = $date . ': Rs ' . number_format((float)$price);
// 		$count++;
// 		if ($count >= 5) break; // stop after 5 days
// 	}

// 	$message = 'Gold (24K Tola) • ' . implode('  |  ', $parts);

// 	// Marquee HTML
// 	ob_start(); ?>
	
// 	<div class="gold-marquee">
// 		<div class="gold-marquee-track">
//             <span><?php echo esc_html($message); ?></span>
//             <span><?php echo esc_html($message); ?></span>
//          </div>
// 	</div>

// 	<?php
// 	return ob_get_clean();
// }

// add_shortcode('star_gold_rate', 'star_gold_rate_shortcode');


get_footer();