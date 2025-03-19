<?php
session_start();
require_once 'config/database.php';
require_once 'config/profile_utils.php'; // Rozet fonksiyonları için

// Kullanıcı giriş yapmış mı kontrol et
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmalısınız']);
    exit;
}

// JSON verileri al
$data = json_decode(file_get_contents('php://input'), true);

$movie_id = isset($data['movie_id']) ? intval($data['movie_id']) : 0;
$rating = isset($data['rating']) ? intval($data['rating']) : 0;
$comment = isset($data['comment']) ? trim($data['comment']) : '';

if ($movie_id <= 0 || $rating <= 0 || $rating > 5 || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veri']);
    exit;
}

// Film datasını al
$movie = getMovieById($movie_id);
if (!$movie) {
    if (isset($data)) {
        echo json_encode(['status' => 'error', 'message' => 'Film bulunamadı']);
        exit();
    } else {
        $_SESSION['error'] = 'Film bulunamadı';
        echo json_encode(['status' => 'error', 'message' => 'Film bulunamadı']);
        exit();
    }
}

// Yorumu ekle
$review_id = addMovieReview($movie_id, $_SESSION['user_id'], $rating, $comment);

if ($review_id) {
    // Başarılı
    $badge_earned = false;
    $badge_name = '';
    
    // Kullanıcının yorum sayısını kontrol et
    $user_id = $_SESSION['user_id'];
    $review_count = countUserReviews($user_id);
    
    // Rozet kontrolü
    // İlk yorum rozeti
    if ($review_count == 1) {
        awardBadgeToUser($user_id, 13); // İlk Yorum rozeti (ID 13)
        $badge_earned = true;
        $badge_name = "İlk Yorum";
        
        // Bildirim ekle
        addNotification(
            $user_id,
            "İlk Yorum rozetini kazandınız!",
            "İlk film yorumunuzu yaparak 'İlk Yorum' rozetini kazandınız. Tebrikler!",
            'badge'
        );
    }
    
    // 10 yorum eleştirmen rozeti
    if ($review_count == 10) {
        awardBadgeToUser($user_id, 5); // Eleştirmen rozeti (ID 5)
        $badge_earned = true;
        $badge_name = "Eleştirmen";
        
        // Bildirim ekle
        addNotification(
            $user_id,
            "Eleştirmen rozetini kazandınız!",
            "10 film yorumu yaparak 'Eleştirmen' rozetini kazandınız. Müthiş eleştirileriniz için teşekkürler!",
            'badge'
        );
    }
    
    // Rozet ilerlemelerini güncelle
    updateBadgeProgress($user_id, 13, 1); // İlk Yorum rozeti
    updateBadgeProgress($user_id, 5, $review_count); // Eleştirmen rozeti
    
    // Kullanıcı bilgilerini al
    $user = getUserById($_SESSION['user_id']);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Yorumunuz başarıyla eklendi', 
        'review_id' => $review_id,
        'username' => $_SESSION['username'],
        'user_image' => $_SESSION['profile_image'] ?? 'default.png',
        'created_at' => date('d.m.Y H:i'),
        'badge_earned' => $badge_earned,
        'badge_name' => $badge_name
    ]);
    
} else {
    // Hata
    echo json_encode(['success' => false, 'message' => 'Yorum eklenirken bir hata oluştu']);
}
?> 