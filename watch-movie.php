<?php
session_start();
require_once 'config/database.php';
require_once 'config/profile_utils.php';

// Kullanıcı giriş yapmış mı kontrol et
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Film izlemek için giriş yapmalısınız']);
    exit();
}

// Film ID'sini al
$movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;

// Film ID geçerli mi kontrol et
if ($movie_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz film ID']);
    exit();
}

// Film bilgilerini getir
$movie = getMovieById($movie_id);
if (!$movie) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Film bulunamadı']);
    exit();
}

// Filmi izlendi olarak kaydet
$watched = addToWatchHistory($_SESSION['user_id'], $movie_id, $movie);
$badge_earned = false;
$badge_name = '';

if ($watched) {
    // İzleme başarılı olduysa, rozet kontrolü yap
    $user_stats = getUserStats($_SESSION['user_id']);
    $total_watched = $user_stats['total_watched'];
    
    // İlerleme güncelleme
    updateBadgeProgress($_SESSION['user_id'], 11, 1); // İlk film rozeti
    updateBadgeProgress($_SESSION['user_id'], 1, $total_watched); // Film Gurusu (50+ film)
    updateBadgeProgress($_SESSION['user_id'], 4, $total_watched); // Film Tutkunu (100+ film)
    
    // İlk film rozeti
    if ($total_watched == 1) {
        awardBadgeToUser($_SESSION['user_id'], 11);
        $badge_earned = true;
        $badge_name = "Yeni Başlayan";
        
        // Bildirim ekle
        addNotification(
            $_SESSION['user_id'],
            "Yeni Başlayan rozetini kazandınız!",
            "İlk filminizi izleyerek 'Yeni Başlayan' rozetini kazandınız. Film yolculuğunuza hoş geldiniz!",
            'badge'
        );
    }
    
    // 50 film rozeti
    if ($total_watched == 50) {
        awardBadgeToUser($_SESSION['user_id'], 1);
        $badge_earned = true;
        $badge_name = "Film Gurusu";
        
        // Bildirim ekle
        addNotification(
            $_SESSION['user_id'],
            "Film Gurusu rozetini kazandınız!",
            "50 film izleyerek 'Film Gurusu' rozetini kazandınız. Muhteşem bir başarı!",
            'badge'
        );
    }
    
    // 100 film rozeti
    if ($total_watched == 100) {
        awardBadgeToUser($_SESSION['user_id'], 4);
        $badge_earned = true;
        $badge_name = "Film Tutkunu";
        
        // Bildirim ekle
        addNotification(
            $_SESSION['user_id'],
            "Film Tutkunu rozetini kazandınız!",
            "100 film izleyerek 'Film Tutkunu' rozetini kazandınız. Siz gerçek bir sinema tutkunusunuz!",
            'badge'
        );
    }
    
    // Diğer rozet kontrolleri
    checkAndUpdateBadges($_SESSION['user_id']);
    
    // Başarılı yanıt döndür
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Film izleme geçmişinize eklendi',
        'total_watched' => $total_watched,
        'badge_earned' => $badge_earned,
        'badge_name' => $badge_name
    ]);
} else {
    // Hata yanıtı döndür
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Film izleme geçmişi güncellenirken bir hata oluştu']);
} 