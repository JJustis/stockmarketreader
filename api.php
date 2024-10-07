<?php
// Enable CORS for all origins (allow requests from any domain)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Get the endpoint and parameters from the query string
$apiType = $_GET['type'];
$symbol = $_GET['symbol'] ?? '';
$query = $_GET['query'] ?? '';

// Determine which API to call based on the `type` parameter
if ($apiType === 'stock' && !empty($symbol)) {
    // Use Yahoo Finance API endpoint that does not require an API key
    $url = "https://query1.finance.yahoo.com/v8/finance/chart/$symbol";
    $headers = array(
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
    );
} elseif ($apiType === 'news' && !empty($query)) {
    // Use News API endpoint with the User-Agent header
    $newsApiKey = 'bd6e8e7ee080419d965cafeaf8ddc783';  // Replace with your News API key
    $url = "https://newsapi.org/v2/everything?q=$query&apiKey=$newsApiKey";
    $headers = array(
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
    );
} else {
    // Invalid request, return error
    echo json_encode(['error' => 'Invalid request. Please provide valid type and parameters.']);
    exit;
}

// Use cURL to send the request to the external API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // Skip SSL verification if needed
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Set headers
$response = curl_exec($ch);
curl_close($ch);

// Check if the response is valid
if ($response === false) {
    echo json_encode(['error' => 'Failed to fetch data from API.']);
} else {
    // Return the API response to the client
    echo $response;
}
?>
