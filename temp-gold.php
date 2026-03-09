<?php



/*Template Name: Gold Rate*/

get_header();




$url = "https://api.metals.dev/v1/latest?api_key=QWOIJUPFGXI8MSHKQLZW205HKQLZW&currency=PKR&unit=g";

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$headers = array("Accept: application/json");
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$resp = curl_exec($curl);
curl_close($curl);
var_dump($resp);



get_footer();