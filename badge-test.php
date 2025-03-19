<?php
session_start();
require_once 'config/database.php';
require_once 'config/profile_utils.php';

// Sadece admin kullanıcıları erişebilir
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit();
}

// JSON verilerini al
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];

if (!isset($data['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek, action belirtilmemiş']);
    exit();
}

header('Content-Type: application/json');

// Rozet verme işlemi
if ($data['action'] === 'award') {
    if (!isset($data['badge_id'])) {
        echo json_encode(['success' => false, 'message' => 'Rozet ID belirtilmemiş']);
        exit();
    }
    
    $badge_id = intval($data['badge_id']);
    
    // Rozetin var olup olmadığını kontrol et
    $badges_file = __DIR__ . '/data/badges.json';
    if (!file_exists($badges_file)) {
        echo json_encode(['success' => false, 'message' => 'Rozet dosyası bulunamadı']);
        exit();
    }
    
    $badges_data = json_decode(file_get_contents($badges_file), true);
    $badge_exists = false;
    $badge_info = null;
    
    foreach ($badges_data['badges'] as $badge) {
        if ($badge['id'] == $badge_id) {
            $badge_exists = true;
            $badge_info = $badge;
            break;
        }
    }
    
    if (!$badge_exists) {
        echo json_encode(['success' => false, 'message' => 'Belirtilen rozet bulunamadı']);
        exit();
    }
    
    // Rozeti kullanıcıya ver
    if (awardBadgeToUser($user_id, $badge_id)) {
        echo json_encode([
            'success' => true, 
            'message' => "Rozet başarıyla verildi: {$badge_info['name']}",
            'badge' => $badge_info
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Rozet verme işlemi başarısız oldu']);
    }
}
// Rozet ilerlemelerini sıfırlama işlemi
else if ($data['action'] === 'reset') {
    $badges_file = __DIR__ . '/data/badges.json';
    
    if (!file_exists($badges_file)) {
        echo json_encode(['success' => false, 'message' => 'Rozet dosyası bulunamadı']);
        exit();
    }
    
    $badges_data = json_decode(file_get_contents($badges_file), true);
    
    // Kullanıcının rozet verilerini sıfırla
    if (isset($badges_data['users'][$user_id])) {
        $badges_data['users'][$user_id] = [
            'earned_badges' => [],
            'progress' => [],
            'new_badges' => []
        ];
        
        if (file_put_contents($badges_file, json_encode($badges_data, JSON_PRETTY_PRINT))) {
            echo json_encode(['success' => true, 'message' => 'Tüm rozet ilerlemeleri sıfırlandı']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Rozet verilerini kaydetme işlemi başarısız oldu']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Kullanıcı için rozet verisi bulunamadı, zaten sıfır durumunda']);
    }
}
// Elle rozet ilerlemesi güncelleme
else if ($data['action'] === 'update_progress') {
    if (!isset($data['badge_id']) || !isset($data['progress'])) {
        echo json_encode(['success' => false, 'message' => 'Rozet ID veya ilerleme değeri belirtilmemiş']);
        exit();
    }
    
    $badge_id = intval($data['badge_id']);
    $progress = intval($data['progress']);
    
    if (updateBadgeProgress($user_id, $badge_id, $progress)) {
        echo json_encode(['success' => true, 'message' => "Rozet ilerlemesi güncellendi: {$progress}"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'İlerleme güncelleme işlemi başarısız oldu']);
    }
}
// Tüm kullanıcı rozetleri için ototest
else if ($data['action'] === 'autotest') {
    // Tüm rozet türlerini test et
    
    // 1. Film sayacı rozetleri için
    updateBadgeProgress($user_id, 1, 50);  // Film Gurusu (50+ film)
    updateBadgeProgress($user_id, 4, 100); // Film Tutkunu (100+ film)
    updateBadgeProgress($user_id, 11, 1);  // Yeni Başlayan (İlk film)
    
    // 2. Favori filmleri rozeti için
    updateBadgeProgress($user_id, 6, 1);   // İlk Favori
    updateBadgeProgress($user_id, 7, 10);  // Favori Koleksiyoner (10 film)
    updateBadgeProgress($user_id, 8, 20);  // Süper Hayran (hedefin yolunda)
    
    // 3. Diğer rozetler
    updateBadgeProgress($user_id, 5, 10);  // Eleştirmen (10+ yorum)
    updateBadgeProgress($user_id, 13, 1);  // İlk Yorum
    
    echo json_encode(['success' => true, 'message' => 'Otomatik rozet testleri tamamlandı, rozet ilerlemeleri güncellendi']);
}
else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz işlem: ' . $data['action']]);
}
?> 