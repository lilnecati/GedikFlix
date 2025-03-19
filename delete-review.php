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
    
    if (!$review_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Yorum ID gerekli.']);
        exit;
    }
    
    // JSON veritabanını okuma
    $database = readDatabase();
    
    // Yorumu bul ve sil
    $found = false;
    foreach ($database['reviews'] as $key => $review) {
        if ($review['id'] == $review_id && $review['user_id'] == $_SESSION['user_id']) {
            // Yorumu sil
            unset($database['reviews'][$key]);
            $database['reviews'] = array_values($database['reviews']); // Diziyi yeniden indeksle
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Yorum bulunamadı veya silme yetkiniz yok.']);
        exit;
    }
    
    // Veritabanını güncelle
    if (writeDatabase($database)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Yorumunuz başarıyla silindi.']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Yorum silinirken bir hata oluştu.']);
    }
    exit;
}
?> 