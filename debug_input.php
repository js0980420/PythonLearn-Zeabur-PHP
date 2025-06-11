<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$decoded = json_decode($rawInput, true);

echo json_encode([
    'raw_input' => $rawInput,
    'raw_length' => strlen($rawInput),
    'decoded' => $decoded,
    'json_error' => json_last_error_msg(),
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown',
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'unknown'
]);
