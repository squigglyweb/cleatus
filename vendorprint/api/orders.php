<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Your Google Apps Script URL
$gasUrl = 'https://script.google.com/macros/s/AKfycbyFonnyEXEUX3sBG68ZrV4plidnbnYRyGGYnQyinTSaaBSbC6fMaG0TPKf7vOp5rLdt/exec';

$action = $_GET['action'] ?? 'orders.list';
$url = $gasUrl . '?action=' . urlencode($action) . '&callback=cb&t=' . time();

$response = file_get_contents($url);
if ($response) {
    // Strip the callback wrapper
    $response = preg_replace('/^cb\(|\);?$/', '', $response);
    echo $response;
} else {
    echo json_encode(['ok' => false, 'error' => 'Failed to fetch']);
}