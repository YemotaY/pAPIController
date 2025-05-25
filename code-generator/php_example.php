<?php
function callAPI($data = []) {
    $url = 'http://example.com/items';
    
    $ch = curl_init();
    
    // Set request body
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($status >= 400 || $response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Request failed: " . ($error ?: $response));
    }
    
    curl_close($ch);
    return json_decode($response, true);
}