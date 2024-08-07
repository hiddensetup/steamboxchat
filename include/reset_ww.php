<?php
header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");

// Include the configuration file
require_once('../config.php');

$qrurl = WW_PORT_GO;
$authToken = WW_TOKEN;
$logoutUrl = WW_URL_GO . ':' . $qrurl . '/api/user/logout?auth=' . $authToken;

$ch = curl_init($logoutUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

print($response);

// Wait for 2 seconds
sleep(2);

// Construct the URL for the second task
$executeUrl = WW_URL_GO . ':' . $qrurl . '/api/user/execute?auth=' . $authToken;

$ch2 = curl_init($executeUrl);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
$response2 = curl_exec($ch2);
curl_close($ch2);

print($response2);
?>
