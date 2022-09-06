<?php

require __DIR__ . '/App/Http/Request.php';
require __DIR__ . '/App/Http/Response.php';

use Exception as Exception;

use App\Http\Request;

if (empty($_POST['url']) || empty($_POST['method'])) {
    throw new Exception('Invalid request');
}

$requestMethod = $_POST['method'];

//list of available methods
$supportedMethods = [
    'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS',
];

if (!in_array($requestMethod, $supportedMethods)) {
    throw new Exception('Invalid request method');
}

$body = !empty($_POST['body']) ? json_decode($_POST['body'], true) : [];
$headers = !empty($_POST['headers']) ? json_decode($_POST['headers'], true) : [];

try {
    $request = Request::getInstance();

    $response = $request->makeRequest($requestMethod, $_POST['url'], $body, $headers);

    header('Content-type: application/json');

    echo json_encode([
        'headers' => $response->getResponseHeaders(),
        'response' => $response->getResponseBody(),
    ]);
} catch (Exception $e) {
    http_response_code($e->getCode());
    header('Content-type: application/json');
    echo json_encode($e->getMessage());
}
