<?php
session_start();
include 'config/database.php';
include 'config/profile_utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['user_id']) || $_SESSION['user_id'] != $_GET['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

$user_id = intval($_GET['user_id']);
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 10;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

$stats = getUserStats($user_id);
$history = [];

if (!empty($stats['watch_history'])) {
    $history = array_slice($stats['watch_history'], $offset, $limit);
}

echo json_encode([
    'success' => true,
    'history' => $history,
    'has_more' => (count($stats['watch_history']) > ($offset + $limit))
]);
?> 