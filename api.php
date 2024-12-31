<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

$apiType = $_GET['type'] ?? '';
$symbol = $_GET['symbol'] ?? '';
$query = $_GET['query'] ?? '';

function makeRequest($url, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
    ], $headers));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'response' => $response,
        'httpCode' => $httpCode,
        'error' => $error
    ];
}

function validateSymbol($symbol) {
    return preg_match('/^[A-Za-z]{1,5}$/', $symbol);
}

function getFinanceNews($query) {
    // Use Yahoo Finance RSS feed for financial news
    $url = "https://finance.yahoo.com/rss/headline?s=" . urlencode($query);
    
    $apiResult = makeRequest($url);
    
    if ($apiResult['httpCode'] !== 200) {
        // Fallback to dummy data if RSS feed fails
        return [
            'articles' => [
                [
                    'title' => 'Market Update for ' . $query,
                    'description' => 'Latest market movements and analysis for ' . $query,
                    'url' => 'https://finance.yahoo.com/quote/' . $query,
                    'publishedAt' => date('r'),
                    'source' => ['name' => 'Market News']
                ],
                [
                    'title' => 'Trading Analysis: ' . $query,
                    'description' => 'Technical and fundamental analysis for ' . $query,
                    'url' => 'https://finance.yahoo.com/quote/' . $query . '/analysis',
                    'publishedAt' => date('r'),
                    'source' => ['name' => 'Trading Analysis']
                ]
            ]
        ];
    }

    $articles = [];
    
    // Load the XML string
    $xml = @simplexml_load_string($apiResult['response']);
    
    if ($xml === false) {
        // If XML parsing fails, return dummy data
        return [
            'articles' => [
                [
                    'title' => 'Latest Updates for ' . $query,
                    'description' => 'Recent market activity and news for ' . $query,
                    'url' => 'https://finance.yahoo.com/quote/' . $query,
                    'publishedAt' => date('r'),
                    'source' => ['name' => 'Financial News']
                ]
            ]
        ];
    }

    // Parse the XML feed
    foreach ($xml->channel->item as $item) {
        $articles[] = [
            'title' => (string)$item->title,
            'description' => strip_tags((string)$item->description),
            'url' => (string)$item->link,
            'publishedAt' => (string)$item->pubDate,
            'source' => ['name' => (string)$xml->channel->title]
        ];
    }

    return ['articles' => array_slice($articles, 0, 10)];
}

try {
    if ($apiType === 'stock') {
        if (empty($symbol)) {
            throw new Exception("Stock symbol is required");
        }
        if (!validateSymbol($symbol)) {
            throw new Exception("Invalid stock symbol format");
        }

        $url = "https://query1.finance.yahoo.com/v8/finance/chart/" . urlencode($symbol) . "?interval=1m";
        $apiResult = makeRequest($url);
        
        if ($apiResult['httpCode'] === 200) {
            $result = json_decode($apiResult['response'], true);
            if (isset($result['chart']['error'])) {
                throw new Exception("Invalid stock symbol or no data available");
            }
            echo json_encode($result);
        } else {
            throw new Exception("Failed to fetch stock data. Status: " . $apiResult['httpCode']);
        }
    } 
    elseif ($apiType === 'news') {
        if (empty($query)) {
            throw new Exception("Search query is required");
        }

        $result = getFinanceNews($query);
        echo json_encode($result);
    } 
    else {
        throw new Exception("Invalid request type");
    }
} 
catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'status' => 'error'
    ]);
}