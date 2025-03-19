<?php
session_start();
include 'config/database.php';

// Kullanıcı giriş yapmış mı kontrol et
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

// POST isteği mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $review_id = $data['review_id'] ?? 0;
    $rating = $data['rating'] ?? 0;
    $comment = $data['comment'] ?? '';
    
    // Giriş validasyonu
    if (!$review_id || $rating < 1 || $rating > 5 || empty($comment)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Geçersiz yorum bilgileri.']);
        exit;
    }
    
    // JSON veritabanını okuma
    $database = readDatabase();
    
    // Yorum var mı ve kullanıcının yetkisi var mı kontrol et
    $found = false;
    foreach ($database['reviews'] as &$review) {
        if ($review['id'] == $review_id && $review['user_id'] == $_SESSION['user_id']) {
            // Yorumu güncelle
            $review['rating'] = $rating;
            $review['comment'] = $comment;
            $review['updated_at'] = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Yorum bulunamadı veya düzenleme yetkiniz yok.']);
        exit;
    }
    
    // Veritabanını güncelle
    if (writeDatabase($database)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Yorumunuz başarıyla güncellendi.']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Yorum güncellenirken bir hata oluştu.']);
    }
    exit;
}

// GET isteği için yorum bilgilerini getir
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $review_id = $_GET['id'] ?? 0;
    
    if (!$review_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Yorum ID gerekli.']);
        exit;
    }
    
    // Yorumu bul
    $database = readDatabase();
    $found_review = null;
    
    foreach ($database['reviews'] as $review) {
        if ($review['id'] == $review_id && $review['user_id'] == $_SESSION['user_id']) {
            $found_review = $review;
            break;
        }
    }
    
    if (!$found_review) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Yorum bulunamadı veya düzenleme yetkiniz yok.']);
        exit;
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'review' => [
            'id' => $found_review['id'],
            'rating' => $found_review['rating'],
            'comment' => $found_review['comment']
        ]
    ]);
    exit;
}
?> 