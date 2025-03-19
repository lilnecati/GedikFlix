<?php
session_start();
require_once 'config/database.php';
require_once 'config/profile_utils.php';

// Kullanıcı giriş yapmış mı kontrol et
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Favorilere eklemek için giriş yapmalısınız']);
    exit;
}

// JSON verileri al
$data = json_decode(file_get_contents('php://input'), true);

$movie_id = isset($data['movie_id']) ? intval($data['movie_id']) : 0;

// Film ID geçerli mi kontrol et
if ($movie_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz film ID']);
    exit;
}

// Film bilgilerini getir
$movie = getMovieById($movie_id);
if (!$movie) {
    echo json_encode(['success' => false, 'message' => 'Film bulunamadı']);
    exit;
}

// Kullanıcının favori listesine filmi ekle/çıkar
$result = toggleFavoriteMovie($_SESSION['user_id'], $movie_id, $movie);

// Favori sayısını al
$favorite_count = count(getFavoriteMovies($_SESSION['user_id']));
$badge_earned = false;
$badge_name = '';

// Rozet kontrolleri
if ($result === 'added') {
    // İlk favori rozeti (id: 6)
    if ($favorite_count == 1) {
        awardBadgeToUser($_SESSION['user_id'], 6);
        $badge_earned = true;
        $badge_name = "İlk Favori";
        
        // Bildirim ekle
        addNotification(
            $_SESSION['user_id'],
            "İlk Favori rozetini kazandınız!",
            "İlk filmi favorilere ekleyerek 'İlk Favori' rozetini kazandınız. Tebrikler!",
            'badge'
        );
    }
    
    // 10 favori rozeti (id: 7)
    if ($favorite_count == 10) {
        awardBadgeToUser($_SESSION['user_id'], 7);
        $badge_earned = true;
        $badge_name = "Favori Koleksiyoner";
        
        // Bildirim ekle
        addNotification(
            $_SESSION['user_id'],
            "Favori Koleksiyoner rozetini kazandınız!",
            "10 filmi favorilere ekleyerek 'Favori Koleksiyoner' rozetini kazandınız. Harika bir koleksiyon oluşturuyorsunuz!",
            'badge'
        );
    }
    
    // 100 favori rozeti (id: 8)
    if ($favorite_count == 100) {
        awardBadgeToUser($_SESSION['user_id'], 8);
        $badge_earned = true;
        $badge_name = "Süper Hayran";
        
        // Bildirim ekle
        addNotification(
            $_SESSION['user_id'],
            "Süper Hayran rozetini kazandınız!",
            "100 filmi favorilere ekleyerek 'Süper Hayran' rozetini kazandınız. Siz gerçek bir film tutkunusunuz!",
            'badge'
        );
    }
    
    // Her durumda ilerleme güncellenir
    updateBadgeProgress($_SESSION['user_id'], 6, $favorite_count); // İlk Favori
    updateBadgeProgress($_SESSION['user_id'], 7, $favorite_count); // Favori Koleksiyoner
    updateBadgeProgress($_SESSION['user_id'], 8, $favorite_count); // Süper Hayran
}

// Yanıt döndür
echo json_encode([
    'success' => true, 
    'action' => $result,
    'favorite_count' => $favorite_count,
    'badge_earned' => $badge_earned,
    'badge_name' => $badge_name,
    'message' => $result == 'added' ? 'Film favorilere eklendi' : 'Film favorilerden çıkarıldı'
]);
?> 