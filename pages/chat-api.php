<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Only logged-in users can use this
requireLogin();

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read the message from the request body
$input   = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');

if ($message === '') {
    echo json_encode(['error' => 'Empty message']);
    exit;
}

// Store conversation history in the session so Aram Bot remembers context
if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

$_SESSION['history'][] = ['role' => 'user', 'content' => $message];

// System prompt + full history sent to the AI on every request
$messages = array_merge(
    [['role' => 'system', 'content' => 'You are Aram Bot, a concise and helpful AI assistant. Keep your answers clear and to the point.']],
    $_SESSION['history']
);

// Call the Cerebras API
$ch = curl_init('https://api.cerebras.ai/v1/chat/completions');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . CEREBRAS_API_KEY,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'model'       => CEREBRAS_MODEL,
        'messages'    => $messages,
        'max_tokens'  => 8192,
        'temperature' => 0.7,
        'top_p'       => 0.8,
    ]),
    CURLOPT_TIMEOUT => 60,
]);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);

if ($curlErr) {
    echo json_encode(['error' => 'Could not reach Cerebras: ' . $curlErr]);
    exit;
}

$data  = json_decode($response, true);
$reply = $data['choices'][0]['message']['content'] ?? null;

if ($reply === null) {
    $apiError = $data['error']['message'] ?? 'Unknown API error';
    echo json_encode(['error' => $apiError]);
    exit;
}

// Save the reply so future messages have context
$_SESSION['history'][] = ['role' => 'assistant', 'content' => $reply];

echo json_encode(['reply' => $reply]);
