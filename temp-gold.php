<?php



/*Template Name: Gold Rate*/

get_header();



$result = pmx_fetch_and_store_gold_rates();

echo '<h2>Gold API Result</h2>';
echo '<pre>';
print_r($result);
echo '</pre>';

get_footer();



