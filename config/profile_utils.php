<?php
/**
 * Rastgele bir profil resmi döndürür
 * 
 * @return string Profil resminin dosya adı
 */
function getRandomProfileImage() {
    $images = [
        'ironman.png',
        'spiderman.jpg',
        'hulk.png',
        'yüzbaşı.png'
    ];
    
    $randomImage = $images[array_rand($images)];
    
    // Resim klasörde yoksa, random-images klasöründen kopyala
    $sourceDir = __DIR__ . '/../images/profile-images/random-images/';
    $targetDir = __DIR__ . '/../images/profile-images/';
    
    if (!file_exists($targetDir . $randomImage) && file_exists($sourceDir . $randomImage)) {
        copy($sourceDir . $randomImage, $targetDir . $randomImage);
        error_log("Resim kopyalandı: " . $sourceDir . $randomImage . " -> " . $targetDir . $randomImage);
    }
    
    return $randomImage;
}

/**
 * Kullanıcı kaydı sırasında rastgele bir profil resmi atar
 * 
 * @param int $userId Kullanıcı ID'si
 * @return bool İşlem başarılı mı?
 */
function assignRandomProfileImage($userId) {
    $randomImage = getRandomProfileImage();
    
    // Debug için
    error_log("Kullanıcı ID: " . $userId . " için rastgele resim atanıyor: " . $randomImage);
    
    // updateUserProfile fonksiyonunu çağır
    $result = updateUserProfile($userId, $randomImage);
    
    // Debug için
    error_log("Profil resmi atama sonucu: " . ($result ? 'Başarılı' : 'Başarısız'));
    
    return $result;
}

/**
 * Kullanıcı istatistiklerini getirir
 * 
 * @param int $user_id Kullanıcı ID
 * @return array Kullanıcı istatistikleri
 */
function getUserStats($user_id) {
    $stats_file = __DIR__ . '/../data/user_stats.json';
    $user_stats = [];
    
    if (file_exists($stats_file)) {
        $stats_data = json_decode(file_get_contents($stats_file), true);
        
        if (isset($stats_data['users'][$user_id])) {
            $user_stats = $stats_data['users'][$user_id];
        }
    }
    
    // Eğer kullanıcı verisi yoksa boş şablon döndür
    if (empty($user_stats)) {
        $user_stats = [
            'total_watched' => 0,
            'total_hours' => 0,
            'genres' => [],
            'favorite_movies' => [],
            'watch_history' => [],
            'activity_calendar' => [],
            'last_login' => date('Y-m-d\TH:i:s')
        ];
    }
    
    return $user_stats;
}

/**
 * Kullanıcının izlediği bir filmi kaydeder
 * 
 * @param int $user_id Kullanıcı ID
 * @param int $movie_id Film ID
 * @param string $movie_title Film adı
 * @param int $duration Film süresi (dakika)
 * @param string $genre Film türü
 * @param string $director Yönetmen adı
 * @param int $year Film yılı
 * @return bool İşlem başarılıysa true, değilse false
 */
function recordMovieWatch($user_id, $movie_id, $movie_title, $duration, $genre, $director = "Bilinmiyor", $year = null) {
    $stats_file = __DIR__ . '/../data/user_stats.json';
    $date = date('Y-m-d');
    $stats_data = [];
    
    if ($year === null) {
        $year = date('Y');
    }
    
    // JSON dosyasını oku
    if (file_exists($stats_file)) {
        $stats_data = json_decode(file_get_contents($stats_file), true);
    }
    
    // Kullanıcı için veri yapısını oluştur
    if (!isset($stats_data['users'][$user_id])) {
        $stats_data['users'][$user_id] = [
            'total_watched' => 0,
            'total_hours' => 0,
            'genres' => [],
            'favorite_movies' => [],
            'watch_history' => [],
            'activity_calendar' => []
        ];
    }
    
    // Toplam izlenen film sayısını artır
    $stats_data['users'][$user_id]['total_watched']++;
    
    // Toplam izlenen saat sayısını artır
    $stats_data['users'][$user_id]['total_hours'] += round($duration / 60, 1);
    
    // Tür istatistiklerini güncelle
    if (!isset($stats_data['users'][$user_id]['genres'][$genre])) {
        $stats_data['users'][$user_id]['genres'][$genre] = 0;
    }
    $stats_data['users'][$user_id]['genres'][$genre]++;
    
    // İzleme geçmişine ekle
    $watch_id = count($stats_data['users'][$user_id]['watch_history']) + 1;
    $stats_data['users'][$user_id]['watch_history'][] = [
        'id' => $watch_id,
        'date' => $date,
        'movie_id' => $movie_id,
        'movie_title' => $movie_title,
        'duration' => $duration
    ];
    
    // Aktivite takvimine ekle
    if (!isset($stats_data['users'][$user_id]['activity_calendar'][$date])) {
        $stats_data['users'][$user_id]['activity_calendar'][$date] = 0;
    }
    $stats_data['users'][$user_id]['activity_calendar'][$date]++;
    
    // Favori filmler listesini güncelle
    // En çok izlenen 5 filmi tutacağız
    $found = false;
    foreach ($stats_data['users'][$user_id]['favorite_movies'] as $key => $movie) {
        if ($movie['id'] == $movie_id) {
            $found = true;
            // Filmi listenin başına taşı
            unset($stats_data['users'][$user_id]['favorite_movies'][$key]);
            array_unshift($stats_data['users'][$user_id]['favorite_movies'], [
                'id' => $movie_id,
                'title' => $movie_title,
                'year' => $year,
                'director' => $director,
                'watched_date' => $date
            ]);
            break;
        }
    }
    
    if (!$found) {
        // Filmi favori listesine ekle
        array_unshift($stats_data['users'][$user_id]['favorite_movies'], [
            'id' => $movie_id,
            'title' => $movie_title,
            'year' => $year,
            'director' => $director,
            'watched_date' => $date
        ]);
        
        // Liste 5 filmden fazlaysa en eskiyi çıkar
        if (count($stats_data['users'][$user_id]['favorite_movies']) > 5) {
            array_pop($stats_data['users'][$user_id]['favorite_movies']);
        }
    }
    
    // Son oturum açma zamanını güncelle
    $stats_data['users'][$user_id]['last_login'] = date('Y-m-d\TH:i:s');
    
    // JSON dosyasına yaz
    $result = file_put_contents($stats_file, json_encode($stats_data, JSON_PRETTY_PRINT));
    
    // Rozet ilerlemelerini kontrol et ve güncelle
    if ($result) {
        checkAndUpdateBadges($user_id);
    }
    
    return $result !== false;
}

/**
 * Kullanıcı bildirimlerini getirir
 * 
 * @param int $user_id Kullanıcı ID
 * @return array Bildirimler ve ayarlar
 */
function getUserNotifications($user_id) {
    $notifications_file = __DIR__ . '/../data/notifications.json';
    $notifications_data = [];
    
    if (file_exists($notifications_file)) {
        $notifications_data = json_decode(file_get_contents($notifications_file), true);
    }
    
    $result = [
        'notifications' => [],
        'settings' => [
            'email_notifications' => true,
            'browser_notifications' => true,
            'sms_notifications' => false
        ]
    ];
    
    if (isset($notifications_data['users'][$user_id])) {
        $result = $notifications_data['users'][$user_id];
    }
    
    return $result;
}

/**
 * Yeni bir bildirim ekler
 * 
 * @param int $user_id Kullanıcı ID
 * @param string $title Bildirim başlığı
 * @param string $message Bildirim mesajı
 * @param string $type Bildirim türü
 * @return bool İşlem başarılıysa true, değilse false
 */
function addNotification($user_id, $title, $message, $type = 'general') {
    $notifications_file = __DIR__ . '/../data/notifications.json';
    $notifications_data = [];
    
    if (file_exists($notifications_file)) {
        $notifications_data = json_decode(file_get_contents($notifications_file), true);
    }
    
    // Kullanıcı için veri yapısını oluştur
    if (!isset($notifications_data['users'][$user_id])) {
        $notifications_data['users'][$user_id] = [
            'notifications' => [],
            'settings' => [
                'email_notifications' => true,
                'browser_notifications' => true,
                'sms_notifications' => false
            ]
        ];
    }
    
    // Yeni bildirim ID'sini belirle
    $notification_id = 1;
    if (!empty($notifications_data['users'][$user_id]['notifications'])) {
        $last_notification = $notifications_data['users'][$user_id]['notifications'][0];
        $notification_id = $last_notification['id'] + 1;
    }
    
    // Bildirim tipi simgesini belirle
    $icon = 'fa-bell';
    $color = '#3498db';
    
    switch ($type) {
        case 'badge':
            $icon = 'fa-award';
            $color = '#f1c40f';
            break;
        case 'favorite':
            $icon = 'fa-heart';
            $color = '#e74c3c';
            break;
        case 'watch':
            $icon = 'fa-film';
            $color = '#2ecc71';
            break;
        case 'comment':
            $icon = 'fa-comment';
            $color = '#9b59b6';
            break;
        case 'security':
            $icon = 'fa-shield-alt';
            $color = '#34495e';
            break;
    }
    
    // Yeni bildirimi ekle
    array_unshift($notifications_data['users'][$user_id]['notifications'], [
        'id' => $notification_id,
        'title' => $title,
        'message' => $message,
        'date' => date('Y-m-d H:i:s'),
        'read' => false,
        'type' => $type,
        'icon' => $icon,
        'color' => $color
    ]);
    
    // Bildirim listesi çok uzunsa eski bildirimleri sil (son 20 bildirim tutulur)
    if (count($notifications_data['users'][$user_id]['notifications']) > 20) {
        $notifications_data['users'][$user_id]['notifications'] = array_slice(
            $notifications_data['users'][$user_id]['notifications'], 
            0, 
            20
        );
    }
    
    // JSON dosyasına yaz
    return file_put_contents($notifications_file, json_encode($notifications_data, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Bildirimi okundu olarak işaretler
 * 
 * @param int $user_id Kullanıcı ID
 * @param int $notification_id Bildirim ID
 * @return bool İşlem başarılıysa true, değilse false
 */
function markNotificationAsRead($user_id, $notification_id) {
    $notifications_file = __DIR__ . '/../data/notifications.json';
    
    if (!file_exists($notifications_file)) {
        return false;
    }
    
    $notifications_data = json_decode(file_get_contents($notifications_file), true);
    
    if (!isset($notifications_data['users'][$user_id])) {
        return false;
    }
    
    // Bildirimi bul ve okundu olarak işaretle
    foreach ($notifications_data['users'][$user_id]['notifications'] as $key => $notification) {
        if ($notification['id'] == $notification_id) {
            $notifications_data['users'][$user_id]['notifications'][$key]['read'] = true;
            break;
        }
    }
    
    // JSON dosyasına yaz
    return file_put_contents($notifications_file, json_encode($notifications_data, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Tüm bildirimleri okundu olarak işaretler
 * 
 * @param int $user_id Kullanıcı ID
 * @return bool İşlem başarılıysa true, değilse false
 */
function markAllNotificationsAsRead($user_id) {
    $notifications_file = __DIR__ . '/../data/notifications.json';
    
    if (!file_exists($notifications_file)) {
        return false;
    }
    
    $notifications_data = json_decode(file_get_contents($notifications_file), true);
    
    if (!isset($notifications_data['users'][$user_id])) {
        return false;
    }
    
    // Tüm bildirimleri okundu olarak işaretle
    foreach ($notifications_data['users'][$user_id]['notifications'] as $key => $notification) {
        $notifications_data['users'][$user_id]['notifications'][$key]['read'] = true;
    }
    
    // JSON dosyasına yaz
    return file_put_contents($notifications_file, json_encode($notifications_data, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Bildirim ayarlarını günceller
 * 
 * @param int $user_id Kullanıcı ID
 * @param array $settings Bildirim ayarları
 * @return bool İşlem başarılıysa true, değilse false
 */
function updateNotificationSettings($user_id, $settings) {
    $notifications_file = __DIR__ . '/../data/notifications.json';
    $notifications_data = [];
    
    if (file_exists($notifications_file)) {
        $notifications_data = json_decode(file_get_contents($notifications_file), true);
    }
    
    // Kullanıcı için veri yapısını oluştur
    if (!isset($notifications_data['users'][$user_id])) {
        $notifications_data['users'][$user_id] = [
            'notifications' => [],
            'settings' => []
        ];
    }
    
    // Ayarları güncelle
    $notifications_data['users'][$user_id]['settings'] = $settings;
    
    // JSON dosyasına yaz
    return file_put_contents($notifications_file, json_encode($notifications_data, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Kullanıcının rozetlerini getirir
 * 
 * @param int $user_id Kullanıcı ID
 * @return array Kullanıcının kazandığı ve ilerlemekte olduğu rozetler
 */
function getUserBadges($user_id) {
    $badges_file = __DIR__ . '/../data/badges.json';
    $badges_data = [];
    
    if (file_exists($badges_file)) {
        $badges_data = json_decode(file_get_contents($badges_file), true);
    }
    
    $earned_badges = [];
    $upcoming_badges = [];
    
    if (empty($badges_data)) {
        return [
            'earned' => $earned_badges,
            'upcoming' => $upcoming_badges
        ];
    }
    
    // Kullanıcının kazandığı rozetleri al
    $user_badge_ids = [];
    if (isset($badges_data['users'][$user_id]['earned_badges'])) {
        $user_badge_ids = $badges_data['users'][$user_id]['earned_badges'];
    }
    
    // Yeni kazanılan rozetleri kontrol et
    $new_badges = [];
    if (isset($badges_data['users'][$user_id]['new_badges'])) {
        $new_badges = $badges_data['users'][$user_id]['new_badges'];
    }
    
    // Kullanıcının ilerleme durumunu al
    $user_progress = [];
    if (isset($badges_data['users'][$user_id]['progress'])) {
        $user_progress = $badges_data['users'][$user_id]['progress'];
    }
    
    // Tüm rozetleri döngüyle kontrol et
    foreach ($badges_data['badges'] as $badge) {
        // Kazanılmış rozetler
        if (in_array($badge['id'], $user_badge_ids)) {
            // Yeni kazanılan rozetleri işaretle
            if (in_array($badge['id'], $new_badges)) {
                $badge['new'] = true;
            } else {
                $badge['new'] = false;
            }
            $earned_badges[] = $badge;
        } 
        // İlerlemekte olan rozetler
        else if (isset($user_progress[$badge['id']])) {
            $progress = $user_progress[$badge['id']];
            $badge['progress'] = [
                'current' => $progress['current'],
                'target' => $progress['target'],
                'percent' => min(100, ($progress['current'] / $progress['target']) * 100)
            ];
            $upcoming_badges[] = $badge;
        }
        // Henüz başlanmamış rozetler
        else {
            $badge['progress'] = [
                'current' => 0,
                'target' => $badge['requirement']['value'] ?? 1,
                'percent' => 0
            ];
            $upcoming_badges[] = $badge;
        }
    }
    
    // Yeni rozetleri görüldü olarak işaretle
    if (!empty($new_badges)) {
        foreach ($new_badges as $badge_id) {
            markBadgeAsNew($user_id, $badge_id, false);
        }
    }
    
    // İlerlemeye yakın olan rozetleri başa getir (sıralama)
    usort($upcoming_badges, function($a, $b) {
        return $b['progress']['percent'] - $a['progress']['percent'];
    });
    
    return [
        'earned' => $earned_badges,
        'upcoming' => $upcoming_badges
    ];
}

/**
 * Kullanıcıya rozet ekler
 * 
 * @param int $user_id Kullanıcı ID
 * @param int $badge_id Rozet ID
 * @return bool İşlem başarılıysa true, değilse false
 */
function awardBadgeToUser($user_id, $badge_id) {
    $badges_file = __DIR__ . '/../data/badges.json';
    
    if (!file_exists($badges_file)) {
        return false;
    }
    
    $badges_data = json_decode(file_get_contents($badges_file), true);
    
    // Kullanıcı için veri yapısını oluştur
    if (!isset($badges_data['users'][$user_id])) {
        $badges_data['users'][$user_id] = [
            'earned_badges' => [],
            'progress' => [],
            'new_badges' => []
        ];
    }
    
    // Rozeti daha önce kazanmamışsa ekle
    if (!in_array($badge_id, $badges_data['users'][$user_id]['earned_badges'])) {
        $badges_data['users'][$user_id]['earned_badges'][] = $badge_id;
        
        // Rozeti yeni olarak işaretle
        if (!isset($badges_data['users'][$user_id]['new_badges'])) {
            $badges_data['users'][$user_id]['new_badges'] = [];
        }
        $badges_data['users'][$user_id]['new_badges'][] = $badge_id;
        
        // Rozet bildirimini ekle
        $badge_info = null;
        foreach ($badges_data['badges'] as $badge) {
            if ($badge['id'] == $badge_id) {
                $badge_info = $badge;
                break;
            }
        }
        
        if ($badge_info) {
            addNotification(
                $user_id,
                "{$badge_info['name']} rozetini kazandınız!",
                "{$badge_info['description']}. Tebrikler!",
                'badge'
            );
        }
    }
    
    // JSON dosyasına yaz
    return file_put_contents($badges_file, json_encode($badges_data, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Rozet ilerlemesini günceller
 * 
 * @param int $user_id Kullanıcı ID
 * @param int $badge_id Rozet ID
 * @param int $progress İlerleme miktarı
 * @return bool İşlem başarılıysa true, değilse false
 */
function updateBadgeProgress($user_id, $badge_id, $progress) {
    $badges_file = __DIR__ . '/../data/badges.json';
    
    if (!file_exists($badges_file)) {
        return false;
    }
    
    $badges_data = json_decode(file_get_contents($badges_file), true);
    
    // Kullanıcı için veri yapısını oluştur
    if (!isset($badges_data['users'][$user_id])) {
        $badges_data['users'][$user_id] = [
            'earned_badges' => [],
            'progress' => []
        ];
    }
    
    // Rozet bilgisini al
    $badge_info = null;
    foreach ($badges_data['badges'] as $badge) {
        if ($badge['id'] == $badge_id) {
            $badge_info = $badge;
            break;
        }
    }
    
    if (!$badge_info) {
        return false;
    }
    
    // Rozet zaten kazanılmışsa güncelleme yapma
    if (in_array($badge_id, $badges_data['users'][$user_id]['earned_badges'])) {
        return true;
    }
    
    // İlerlemeyi güncelle
    $target = $badge_info['requirement']['value'] ?? 1;
    $badges_data['users'][$user_id]['progress'][$badge_id] = [
        'current' => $progress,
        'target' => $target
    ];
    
    // Hedef tamamlandıysa rozeti ver
    if ($progress >= $target) {
        awardBadgeToUser($user_id, $badge_id);
    }
    
    // JSON dosyasına yaz
    return file_put_contents($badges_file, json_encode($badges_data, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Film izleme sonrasında tüm rozet ilerlemelerini kontrol eder ve günceller
 * 
 * @param int $user_id Kullanıcı ID
 * @return void
 */
function checkAndUpdateBadges($user_id) {
    $stats = getUserStats($user_id);
    $badges_file = __DIR__ . '/../data/badges.json';
    
    if (!file_exists($badges_file)) {
        return;
    }
    
    $badges_data = json_decode(file_get_contents($badges_file), true);
    
    if (empty($badges_data) || empty($badges_data['badges'])) {
        return;
    }
    
    // Film sayısı rozetlerini kontrol et
    $movie_count = $stats['total_watched'];
    foreach ($badges_data['badges'] as $badge) {
        if (isset($badge['requirement']['type']) && $badge['requirement']['type'] == 'movie_count') {
            updateBadgeProgress($user_id, $badge['id'], $movie_count);
        }
    }
    
    // Tür bazlı rozetleri kontrol et
    foreach ($stats['genres'] as $genre => $count) {
        foreach ($badges_data['badges'] as $badge) {
            if (isset($badge['requirement']['type']) && $badge['requirement']['type'] == 'genre_count' && 
                $badge['requirement']['genre'] == $genre) {
                updateBadgeProgress($user_id, $badge['id'], $count);
            }
        }
    }
    
    // Gece izleme rozeti için kontrol et
    $night_watches = 0;
    foreach ($stats['watch_history'] as $watch) {
        $watch_hour = date('H', strtotime($watch['date']));
        if ($watch_hour >= 0 && $watch_hour < 4) {
            $night_watches++;
        }
    }
    
    foreach ($badges_data['badges'] as $badge) {
        if (isset($badge['requirement']['type']) && $badge['requirement']['type'] == 'night_watch') {
            updateBadgeProgress($user_id, $badge['id'], $night_watches);
        }
    }
    
    // Aynı gün içinde çoklu izleme rozeti için kontrol et
    $day_watches = [];
    foreach ($stats['watch_history'] as $watch) {
        $date = $watch['date'];
        if (!isset($day_watches[$date])) {
            $day_watches[$date] = 0;
        }
        $day_watches[$date]++;
    }
    
    $max_same_day = 0;
    foreach ($day_watches as $count) {
        if ($count > $max_same_day) {
            $max_same_day = $count;
        }
    }
    
    foreach ($badges_data['badges'] as $badge) {
        if (isset($badge['requirement']['type']) && $badge['requirement']['type'] == 'same_day_watch') {
            updateBadgeProgress($user_id, $badge['id'], $max_same_day);
        }
    }
    
    // Üyelik süresi rozetini kontrol et
    $now = new DateTime();
    $user = getUserById($user_id);
    
    if ($user && isset($user['created_at'])) {
        $created = new DateTime($user['created_at']);
        $days_diff = $now->diff($created)->days;
        
        foreach ($badges_data['badges'] as $badge) {
            if (isset($badge['requirement']['type']) && $badge['requirement']['type'] == 'membership_duration') {
                updateBadgeProgress($user_id, $badge['id'], $days_diff);
            }
        }
    }
    
    // Favori sayısını kontrol et
    $favorites = getFavoriteMovies($user_id);
    $favorite_count = count($favorites);
    
    foreach ($badges_data['badges'] as $badge) {
        if (isset($badge['requirement']['type']) && $badge['requirement']['type'] == 'favorite_count') {
            updateBadgeProgress($user_id, $badge['id'], $favorite_count);
        }
    }
}

/**
 * Yorum/inceleme sayısına bağlı rozet ilerlemesini günceller
 * 
 * @param int $user_id Kullanıcı ID
 * @return void
 */
function updateReviewBadge($user_id) {
    // Kullanıcının yorum sayısını al
    $review_count = getReviewCountByUserId($user_id);
    
    // Rozet ilerlemesini güncelle
    $badges_file = __DIR__ . '/../data/badges.json';
    
    if (!file_exists($badges_file)) {
        return;
    }
    
    $badges_data = json_decode(file_get_contents($badges_file), true);
    
    if (empty($badges_data) || empty($badges_data['badges'])) {
        return;
    }
    
    foreach ($badges_data['badges'] as $badge) {
        if ($badge['requirement']['type'] == 'review_count') {
            updateBadgeProgress($user_id, $badge['id'], $review_count);
        }
    }
}

/**
 * Kullanıcının toplam yorum sayısını döndürür
 * 
 * @param int $user_id Kullanıcı ID
 * @return int Yorum sayısı
 */
function getReviewCountByUserId($user_id) {
    // Bu fonksiyonu database.php içerisinde tanımlamalısınız
    // Şimdilik örnek olarak sabit bir değer döndürelim
    $reviews_file = __DIR__ . '/../data/reviews.json';
    $review_count = 0;
    
    if (file_exists($reviews_file)) {
        $reviews_data = json_decode(file_get_contents($reviews_file), true);
        
        if (isset($reviews_data['reviews'])) {
            foreach ($reviews_data['reviews'] as $review) {
                if ($review['user_id'] == $user_id) {
                    $review_count++;
                }
            }
        }
    }
    
    return $review_count;
}

/**
 * @return array Profil verileri
 */
function getProfileData($user_id) {
    $user = getUserById($user_id);
    
    if (!$user) {
        return [
            'user' => null,
            'stats' => [],
            'notifications' => [],
            'notification_settings' => [],
            'earned_badges' => [],
            'upcoming_badges' => [],
            'favorite_movies' => []
        ];
    }
    
    $stats = getUserStats($user_id);
    $notification_data = getUserNotifications($user_id);
    $badges = getUserBadges($user_id);
    $favorite_movies = getFavoriteMovies($user_id);
    
    return [
        'user' => $user,
        'stats' => $stats,
        'notifications' => $notification_data['notifications'],
        'notification_settings' => $notification_data['settings'],
        'earned_badges' => $badges['earned'],
        'upcoming_badges' => $badges['upcoming'],
        'favorite_movies' => $favorite_movies
    ];
}

/**
 * Yeni bir kullanıcı izleme etkinliği ekler ve rozetleri/istatistikleri günceller
 * 
 * @param int $user_id Kullanıcı ID
 * @param int $movie_id Film ID
 * @param array $movie_data Film verileri (başlık, süre, tür, yönetmen, yıl)
 * @return bool İşlem başarılıysa true, değilse false
 */
function addMovieWatchActivity($user_id, $movie_id, $movie_data) {
    $result = recordMovieWatch(
        $user_id,
        $movie_id,
        $movie_data['title'],
        $movie_data['duration'],
        $movie_data['genre'],
        $movie_data['director'] ?? "Bilinmiyor",
        $movie_data['year'] ?? date('Y')
    );
    
    if ($result) {
        // İzleme bildirimi ekle
        addNotification(
            $user_id,
            "Film izlediniz: {$movie_data['title']}",
            "{$movie_data['title']} filmini izlediniz. İyi seyirler!",
            'watch'
        );
    }
    
    return $result;
}

/**
 * Favori filmleri getirir
 * 
 * @param int $user_id Kullanıcı ID
 * @return array Favori filmler listesi
 */
function getFavoriteMovies($user_id) {
    $stats_file = __DIR__ . '/../data/user_stats.json';
    $user_stats = [];
    
    if (file_exists($stats_file)) {
        $stats_data = json_decode(file_get_contents($stats_file), true);
        
        if (isset($stats_data['users'][$user_id]['favorites'])) {
            return $stats_data['users'][$user_id]['favorites'];
        }
    }
    
    return [];
}

/**
 * Film favorilere ekler veya çıkarır
 * 
 * @param int $user_id Kullanıcı ID
 * @param int $movie_id Film ID
 * @param array $movie_data Film verileri
 * @return string 'added' veya 'removed'
 */
function toggleFavoriteMovie($user_id, $movie_id, $movie_data) {
    $stats_file = __DIR__ . '/../data/user_stats.json';
    $stats_data = [];
    
    if (file_exists($stats_file)) {
        $stats_data = json_decode(file_get_contents($stats_file), true);
    }
    
    // Kullanıcı için veri yapısını oluştur
    if (!isset($stats_data['users'][$user_id])) {
        $stats_data['users'][$user_id] = [
            'total_watched' => 0,
            'total_hours' => 0,
            'genres' => [],
            'favorite_movies' => [],
            'watch_history' => [],
            'activity_calendar' => [],
            'favorites' => []
        ];
    }
    
    // Kullanıcının favorileri yoksa dizisini oluştur
    if (!isset($stats_data['users'][$user_id]['favorites'])) {
        $stats_data['users'][$user_id]['favorites'] = [];
    }
    
    // Film zaten favorilerde mi kontrol et
    $favorites = &$stats_data['users'][$user_id]['favorites'];
    $found = false;
    foreach ($favorites as $key => $favorite) {
        if ($favorite['id'] == $movie_id) {
            // Filmini favorilerden çıkar
            unset($favorites[$key]);
            $favorites = array_values($favorites); // Diziyi yeniden indeksle
            $found = true;
            $action = 'removed';
            
            // Favori çıkarma bildirimi ekle
            addNotification(
                $user_id,
                "Film favorilerden çıkarıldı",
                "\"{$movie_data['title']}\" filmi favorilerinizden çıkarıldı.",
                'favorite'
            );
            
            break;
        }
    }
    
    if (!$found) {
        // Filmi favorilere ekle
        $favorites[] = [
            'id' => $movie_id,
            'title' => $movie_data['title'],
            'year' => $movie_data['year'],
            'director' => isset($movie_data['director']) ? $movie_data['director'] : 'Bilinmiyor',
            'poster_url' => $movie_data['poster_url'],
            'category' => $movie_data['category']['name'],
            'added_at' => date('Y-m-d H:i:s')
        ];
        
        $action = 'added';
        
        // Favori ekleme bildirimi ekle
        addNotification(
            $user_id,
            "Film favorilere eklendi",
            "\"{$movie_data['title']}\" filmi favorilerinize eklendi.",
            'favorite'
        );
    }
    
    // JSON dosyasına yaz
    file_put_contents($stats_file, json_encode($stats_data, JSON_PRETTY_PRINT));
    
    // Favori sayısına bağlı rozet ilerlemelerini kontrol et
    checkAndUpdateFavoriteBadges($user_id);
    
    return $action;
}

/**
 * Filmin kullanıcının favorilerinde olup olmadığını kontrol eder
 * 
 * @param int $user_id Kullanıcı ID
 * @param int $movie_id Film ID
 * @return bool Film favorilerdeyse true, değilse false
 */
function isMovieFavorite($user_id, $movie_id) {
    $favorites = getFavoriteMovies($user_id);
    
    foreach ($favorites as $favorite) {
        if ($favorite['id'] == $movie_id) {
            return true;
        }
    }
    
    return false;
}

/**
 * Favori filmlere ekleme/çıkarma sonrasında rozet ilerlemelerini kontrol eder
 * 
 * @param int $user_id Kullanıcı ID
 * @return void
 */
function checkAndUpdateFavoriteBadges($user_id) {
    $favorites = getFavoriteMovies($user_id);
    $favorite_count = count($favorites);
    $badges_file = __DIR__ . '/../data/badges.json';
    
    if (!file_exists($badges_file)) {
        return;
    }
    
    $badges_data = json_decode(file_get_contents($badges_file), true);
    
    if (empty($badges_data) || empty($badges_data['badges'])) {
        return;
    }
    
    // Favori sayısına bağlı rozetleri kontrol et
    foreach ($badges_data['badges'] as $badge) {
        if (isset($badge['requirement']['type']) && $badge['requirement']['type'] == 'favorite_count') {
            updateBadgeProgress($user_id, $badge['id'], $favorite_count);
        }
    }
}

/**
 * Yeni kazanılan rozeti işaretler
 * 
 * @param int $user_id Kullanıcı ID
 * @param int $badge_id Rozet ID
 * @param bool $new Yeni mi
 * @return bool İşlem başarılıysa true, değilse false
 */
function markBadgeAsNew($user_id, $badge_id, $new = true) {
    $badges_file = __DIR__ . '/../data/badges.json';
    
    if (!file_exists($badges_file)) {
        return false;
    }
    
    $badges_data = json_decode(file_get_contents($badges_file), true);
    
    if (!isset($badges_data['users'][$user_id]['new_badges'])) {
        $badges_data['users'][$user_id]['new_badges'] = [];
    }
    
    if ($new) {
        // Yeni rozeti ekle
        if (!in_array($badge_id, $badges_data['users'][$user_id]['new_badges'])) {
            $badges_data['users'][$user_id]['new_badges'][] = $badge_id;
        }
    } else {
        // Yeni işaretini kaldır
        $key = array_search($badge_id, $badges_data['users'][$user_id]['new_badges']);
        if ($key !== false) {
            unset($badges_data['users'][$user_id]['new_badges'][$key]);
            $badges_data['users'][$user_id]['new_badges'] = array_values($badges_data['users'][$user_id]['new_badges']);
        }
    }
    
    // JSON dosyasına yaz
    return file_put_contents($badges_file, json_encode($badges_data, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Kullanıcının toplam yorum sayısını döndürür
 * 
 * @param int $user_id Kullanıcı ID
 * @return int Toplam yorum sayısı
 */
function countUserReviews($user_id) {
    // Burada veritabanınıza göre kullanıcının yorum sayısını alabilirsiniz
    // Örnek olarak movie_reviews.json dosyasından okuyacağız
    $reviews_file = __DIR__ . '/../data/movie_reviews.json';
    $review_count = 0;
    
    if (file_exists($reviews_file)) {
        $reviews_data = json_decode(file_get_contents($reviews_file), true);
        
        if (isset($reviews_data['reviews'])) {
            foreach ($reviews_data['reviews'] as $review) {
                if ($review['user_id'] == $user_id) {
                    $review_count++;
                }
            }
        }
    }
    
    return $review_count;
}

/**
 * Kullanıcının izleme geçmişine film ekler
 * 
 * @param int $user_id Kullanıcı ID
 * @param int $movie_id Film ID
 * @param array $movie_data Film verileri
 * @return bool İşlem başarılıysa true, değilse false
 */
function addToWatchHistory($user_id, $movie_id, $movie_data) {
    $stats_file = __DIR__ . '/../data/user_stats.json';
    $stats_data = [];
    
    if (file_exists($stats_file)) {
        $stats_data = json_decode(file_get_contents($stats_file), true);
    }
    
    // Kullanıcı için veri yapısını oluştur
    if (!isset($stats_data['users'][$user_id])) {
        $stats_data['users'][$user_id] = [
            'total_watched' => 0,
            'total_hours' => 0,
            'genres' => [],
            'favorite_movies' => [],
            'watch_history' => [],
            'activity_calendar' => [],
            'favorites' => []
        ];
    }
    
    // Film zaten izlendi mi kontrol et
    $already_watched = false;
    foreach ($stats_data['users'][$user_id]['watch_history'] as $watch) {
        if ($watch['movie_id'] == $movie_id) {
            $already_watched = true;
            break;
        }
    }
    
    // Eğer film daha önce izlenmemişse istatistikleri güncelle
    if (!$already_watched) {
        // Film süresini dakika cinsinden al (varsa)
        $duration = isset($movie_data['duration']) ? intval($movie_data['duration']) : 120; // Varsayılan 2 saat
        
        // Toplam izlenen film sayısını ve süresini güncelle
        $stats_data['users'][$user_id]['total_watched']++;
        $stats_data['users'][$user_id]['total_hours'] += $duration / 60; // Saate çevir
        
        // Kategori istatistiklerini güncelle
        $category = $movie_data['category']['name'] ?? 'Diğer';
        if (!isset($stats_data['users'][$user_id]['genres'][$category])) {
            $stats_data['users'][$user_id]['genres'][$category] = 0;
        }
        $stats_data['users'][$user_id]['genres'][$category]++;
        
        // İzleme geçmişine ekle
        $stats_data['users'][$user_id]['watch_history'][] = [
            'id' => count($stats_data['users'][$user_id]['watch_history']) + 1,
            'date' => date('Y-m-d H:i:s'),
            'movie_id' => $movie_id,
            'movie_title' => $movie_data['title'],
            'duration' => $duration
        ];
        
        // Aktivite takvimine ekle
        $today = date('Y-m-d');
        if (!isset($stats_data['users'][$user_id]['activity_calendar'][$today])) {
            $stats_data['users'][$user_id]['activity_calendar'][$today] = 0;
        }
        $stats_data['users'][$user_id]['activity_calendar'][$today]++;
        
        // Bildirim ekle
        addNotification(
            $user_id,
            "Film İzlendi",
            "\"{$movie_data['title']}\" filmini izlediniz.",
            'watch'
        );
    }
    
    // JSON dosyasına yaz
    return file_put_contents($stats_file, json_encode($stats_data, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Geliştirilmiş Rozet Kontrol sistemi
 * Kullanıcı aktivitelerini daha kapsamlı takip eder ve rozet kazanımlarını optimize eder
 * 
 * @param int $user_id Kullanıcı ID
 * @param string $action İşlem türü (view, favorite, review, login, streak)
 * @param array $data İşlemle ilgili ek veriler
 * @return array Kazanılan yeni rozetler
 */
function checkBadgeAchievements($user_id, $action = '', $data = []) {
    $badges_file = __DIR__ . '/../data/badges.json';
    $stats = getUserStats($user_id);
    $newlyEarnedBadges = [];
    
    if (!file_exists($badges_file)) {
        return $newlyEarnedBadges;
    }
    
    $badges_data = json_decode(file_get_contents($badges_file), true);
    
    if (empty($badges_data) || empty($badges_data['badges'])) {
        return $newlyEarnedBadges;
    }
    
    // Kullanıcı için veri yapısını oluştur
    if (!isset($badges_data['users'][$user_id])) {
        $badges_data['users'][$user_id] = [
            'earned_badges' => [],
            'progress' => [],
            'new_badges' => [],
            'last_check' => [
                'date' => date('Y-m-d'),
                'stats' => $stats
            ]
        ];
    }
    
    // Kullanıcının kazandığı rozetleri al
    $earned_badges = isset($badges_data['users'][$user_id]['earned_badges']) 
        ? $badges_data['users'][$user_id]['earned_badges'] : [];
    
    // Kategoriye göre rozet kontrolü
    foreach ($badges_data['badges'] as $badge) {
        // Zaten kazanılmış rozeti kontrol etme
        if (in_array($badge['id'], $earned_badges)) {
            continue;
        }
        
        $requirement = $badge['requirement'] ?? [];
        $type = $requirement['type'] ?? '';
        $achieved = false;
        
        // İşlem türüne göre kontrol et
        switch ($action) {
            case 'view':
                // Film izleme rozetleri
                if ($type == 'movie_count') {
                    $target = $requirement['value'] ?? 1;
                    $current = $stats['total_watched'];
                    $achieved = $current >= $target;
                    updateBadgeProgress($user_id, $badge['id'], $current);
                }
                elseif ($type == 'genre_count' && isset($data['genre'])) {
                    $target = $requirement['value'] ?? 1;
                    $genre = $data['genre'];
                    $current = $stats['genres'][$genre] ?? 0;
                    $achieved = $current >= $target && $requirement['genre'] == $genre;
                    updateBadgeProgress($user_id, $badge['id'], $current);
                }
                elseif ($type == 'watch_streak') {
                    $target = $requirement['value'] ?? 1;
                    // Son 7 günü kontrol et
                    $streak = calculateWatchStreak($stats);
                    $achieved = $streak >= $target;
                    updateBadgeProgress($user_id, $badge['id'], $streak);
                }
                elseif ($type == 'night_watch') {
                    $target = $requirement['value'] ?? 1;
                    $hour = date('H');
                    $is_night = ($hour >= 0 && $hour < 4);
                    
                    if ($is_night) {
                        $current = countNightWatches($user_id);
                        $achieved = $current >= $target;
                        updateBadgeProgress($user_id, $badge['id'], $current);
                    }
                }
                elseif ($type == 'watch_duration') {
                    $target = $requirement['value'] ?? 1; // Saat cinsinden
                    $current = $stats['total_hours'];
                    $achieved = $current >= $target;
                    updateBadgeProgress($user_id, $badge['id'], $current);
                }
                elseif ($type == 'same_day_watch') {
                    $target = $requirement['value'] ?? 2;
                    $today = date('Y-m-d');
                    $today_watches = $stats['activity_calendar'][$today] ?? 0;
                    
                    if ($today_watches >= $target) {
                        $achieved = true;
                    }
                    updateBadgeProgress($user_id, $badge['id'], $today_watches);
                }
                break;
                
            case 'favorite':
                // Favori rozetleri
                if ($type == 'favorite_count') {
                    $favorites = getFavoriteMovies($user_id);
                    $target = $requirement['value'] ?? 1;
                    $current = count($favorites);
                    $achieved = $current >= $target;
                    updateBadgeProgress($user_id, $badge['id'], $current);
                }
                elseif ($type == 'first_favorite' && isset($data['is_first']) && $data['is_first']) {
                    $achieved = true;
                    updateBadgeProgress($user_id, $badge['id'], 1);
                }
                break;
                
            case 'review':
                // Yorum rozetleri
                if ($type == 'review_count') {
                    $target = $requirement['value'] ?? 1;
                    $current = countUserReviews($user_id);
                    $achieved = $current >= $target;
                    updateBadgeProgress($user_id, $badge['id'], $current);
                }
                elseif ($type == 'first_review' && isset($data['is_first']) && $data['is_first']) {
                    $achieved = true;
                    updateBadgeProgress($user_id, $badge['id'], 1);
                }
                elseif ($type == 'high_rating' && isset($data['rating']) && $data['rating'] >= ($requirement['rating'] ?? 5)) {
                    $current = countHighRatingReviews($user_id, $requirement['rating'] ?? 5);
                    $target = $requirement['value'] ?? 1;
                    $achieved = $current >= $target;
                    updateBadgeProgress($user_id, $badge['id'], $current);
                }
                break;
                
            case 'login':
                // Oturum açma rozetleri
                if ($type == 'login_streak') {
                    $target = $requirement['value'] ?? 1;
                    $current = calculateLoginStreak($user_id);
                    $achieved = $current >= $target;
                    updateBadgeProgress($user_id, $badge['id'], $current);
                }
                elseif ($type == 'membership_duration') {
                    $user = getUserById($user_id);
                    if ($user && isset($user['created_at'])) {
                        $created = new DateTime($user['created_at']);
                        $now = new DateTime();
                        $days_diff = $now->diff($created)->days;
                        $target = $requirement['value'] ?? 30; // Gün cinsinden
                        $achieved = $days_diff >= $target;
                        updateBadgeProgress($user_id, $badge['id'], $days_diff);
                    }
                }
                break;
                
            case 'streak':
                // Günlük giriş yapma streak'i
                if ($type == 'login_streak') {
                    $target = $requirement['value'] ?? 1;
                    $current = calculateLoginStreak($user_id);
                    $achieved = $current >= $target;
                    updateBadgeProgress($user_id, $badge['id'], $current);
                }
                break;
                
            default:
                // Genel kontroller (herhangi bir aktivite olmadan da çalışır)
                if ($type == 'movie_count') {
                    $target = $requirement['value'] ?? 1;
                    $current = $stats['total_watched'];
                    $achieved = $current >= $target;
                    updateBadgeProgress($user_id, $badge['id'], $current);
                }
                elseif ($type == 'favorite_count') {
                    $favorites = getFavoriteMovies($user_id);
                    $target = $requirement['value'] ?? 1;
                    $current = count($favorites);
                    $achieved = $current >= $target;
                    updateBadgeProgress($user_id, $badge['id'], $current);
                }
                elseif ($type == 'review_count') {
                    $target = $requirement['value'] ?? 1;
                    $current = countUserReviews($user_id);
                    $achieved = $current >= $target;
                    updateBadgeProgress($user_id, $badge['id'], $current);
                }
                break;
        }
        
        // Rozet kazanıldı mı?
        if ($achieved) {
            // Kullanıcıya rozeti ver
            if (awardBadgeToUser($user_id, $badge['id'])) {
                $newlyEarnedBadges[] = $badge;
            }
        }
    }
    
    // Son kontrol zamanını güncelle
    $badges_data['users'][$user_id]['last_check'] = [
        'date' => date('Y-m-d'),
        'stats' => $stats
    ];
    
    // JSON dosyasını güncelle
    file_put_contents($badges_file, json_encode($badges_data, JSON_PRETTY_PRINT));
    
    return $newlyEarnedBadges;
}

/**
 * Kullanıcının izleme streak'ini hesaplar (ardışık günlerde film izleme)
 * 
 * @param array $stats Kullanıcı istatistikleri
 * @return int Streak gün sayısı
 */
function calculateWatchStreak($stats) {
    if (empty($stats['activity_calendar'])) {
        return 0;
    }
    
    $calendar = $stats['activity_calendar'];
    krsort($calendar); // Tarihleri son tarihten başlayarak sırala
    
    $streak = 0;
    $today = date('Y-m-d');
    $check_date = new DateTime($today);
    
    // Bugün film izlenmişse streak'i başlat
    if (isset($calendar[$today]) && $calendar[$today] > 0) {
        $streak = 1;
    } else {
        return 0; // Bugün izleme yoksa streak 0
    }
    
    // Önceki günleri kontrol et
    for ($i = 1; $i <= 30; $i++) { // Son 30 günü kontrol et
        $check_date->modify('-1 day');
        $date = $check_date->format('Y-m-d');
        
        if (isset($calendar[$date]) && $calendar[$date] > 0) {
            $streak++;
        } else {
            break; // Streak kesildi
        }
    }
    
    return $streak;
}

/**
 * Gece izlenen filmlerin sayısını hesaplar (00:00-04:00 arası)
 * 
 * @param int $user_id Kullanıcı ID
 * @return int Gece izlenen film sayısı
 */
function countNightWatches($user_id) {
    $stats = getUserStats($user_id);
    $night_watches = 0;
    
    if (empty($stats['watch_history'])) {
        return 0;
    }
    
    foreach ($stats['watch_history'] as $watch) {
        if (isset($watch['date'])) {
            $hour = date('H', strtotime($watch['date']));
            if ($hour >= 0 && $hour < 4) {
                $night_watches++;
            }
        }
    }
    
    return $night_watches;
}

/**
 * Kullanıcının yüksek puanlı yorumlarının sayısını hesaplar
 * 
 * @param int $user_id Kullanıcı ID
 * @param int $min_rating Minimum rating (5 üzerinden)
 * @return int Yüksek puanlı yorum sayısı
 */
function countHighRatingReviews($user_id, $min_rating = 4) {
    $reviews_file = __DIR__ . '/../data/movie_reviews.json';
    $high_ratings = 0;
    
    if (!file_exists($reviews_file)) {
        return 0;
    }
    
    $reviews_data = json_decode(file_get_contents($reviews_file), true);
    
    if (empty($reviews_data) || !isset($reviews_data['reviews'])) {
        return 0;
    }
    
    foreach ($reviews_data['reviews'] as $review) {
        if ($review['user_id'] == $user_id && $review['rating'] >= $min_rating) {
            $high_ratings++;
        }
    }
    
    return $high_ratings;
}

/**
 * Kullanıcının oturum açma streak'ini hesaplar
 * 
 * @param int $user_id Kullanıcı ID
 * @return int Streak gün sayısı
 */
function calculateLoginStreak($user_id) {
    $login_file = __DIR__ . '/../data/user_logins.json';
    
    if (!file_exists($login_file)) {
        return 0;
    }
    
    $login_data = json_decode(file_get_contents($login_file), true);
    
    if (!isset($login_data['users'][$user_id]) || empty($login_data['users'][$user_id]['logins'])) {
        return 0;
    }
    
    $logins = $login_data['users'][$user_id]['logins'];
    rsort($logins); // Tarihleri son tarihten başlayarak sırala
    
    $streak = 1; // Bugün giriş yapmışsa 1 ile başla
    $last_date = new DateTime($logins[0]);
    $today = new DateTime(date('Y-m-d'));
    
    // Bugün giriş yapmış mı kontrol et
    if ($last_date->format('Y-m-d') != $today->format('Y-m-d')) {
        return 0; // Bugün giriş yapmamışsa streak 0
    }
    
    // Önceki günleri kontrol et
    for ($i = 1; $i < count($logins); $i++) {
        $current_date = new DateTime($logins[$i]);
        $diff = $last_date->diff($current_date);
        
        if ($diff->days == 1) {
            $streak++;
            $last_date = $current_date;
        } else if ($diff->days > 1) {
            break; // Streak kesildi
        }
    }
    
    return $streak;
}

/**
 * Kullanıcının ilk kez yorum yapıp yapmadığını kontrol eder
 * 
 * @param int $user_id Kullanıcı ID
 * @return bool İlk yorum ise true, değilse false
 */
function isFirstReview($user_id) {
    return countUserReviews($user_id) <= 1; // Yeni eklenen yorum da dahil
}

/**
 * Kullanıcının ilk kez favorilere ekleme yapıp yapmadığını kontrol eder
 * 
 * @param int $user_id Kullanıcı ID
 * @return bool İlk favori ise true, değilse false
 */
function isFirstFavorite($user_id) {
    $favorites = getFavoriteMovies($user_id);
    return count($favorites) <= 1; // Yeni eklenen favori de dahil
}

/**
 * Film izleme aktivitesini kaydet ve rozetleri kontrol et
 * 
 * @param int $user_id Kullanıcı ID
 * @param int $movie_id Film ID
 * @param array $movie_data Film verileri
 * @return array Sonuç ve kazanılan rozetler
 */
function recordMovieWatchWithBadges($user_id, $movie_id, $movie_data) {
    // Filmi izleme geçmişine ekle
    $result = addToWatchHistory($user_id, $movie_id, $movie_data);
    
    // Rozet kazanımlarını kontrol et
    $earnedBadges = checkBadgeAchievements($user_id, 'view', [
        'genre' => $movie_data['category']['name'] ?? 'Diğer',
        'duration' => $movie_data['duration'] ?? 120
    ]);
    
    return [
        'success' => $result,
        'earned_badges' => $earnedBadges
    ];
}

/**
 * Film favorilere ekle/çıkar ve rozetleri kontrol et
 * 
 * @param int $user_id Kullanıcı ID
 * @param int $movie_id Film ID
 * @param array $movie_data Film verileri
 * @return array Sonuç ve kazanılan rozetler
 */
function toggleFavoriteWithBadges($user_id, $movie_id, $movie_data) {
    $is_first = isFirstFavorite($user_id);
    
    // Filmi favorilere ekle/çıkar
    $action = toggleFavoriteMovie($user_id, $movie_id, $movie_data);
    
    // Eğer favorilere eklendiyse rozet kontrolü yap
    $earnedBadges = [];
    if ($action == 'added') {
        $earnedBadges = checkBadgeAchievements($user_id, 'favorite', [
            'is_first' => $is_first
        ]);
    }
    
    return [
        'action' => $action,
        'earned_badges' => $earnedBadges
    ];
}

/**
 * Yorum ekle ve rozetleri kontrol et
 * 
 * @param int $user_id Kullanıcı ID
 * @param int $movie_id Film ID
 * @param string $comment Yorum metni
 * @param int $rating Puanlama (1-5)
 * @return array Sonuç ve kazanılan rozetler
 */
function addReviewWithBadges($user_id, $movie_id, $comment, $rating) {
    $is_first = isFirstReview($user_id);
    
    // Yorumu ekle (bu fonksiyonun database.php içinde tanımlanmış olduğunu varsayıyoruz)
    // Bu fonksiyonun tanımlı olduğu varsayılıyor, eğer değilse eklemeniz gerekecek
    $result = addMovieReview($user_id, $movie_id, $comment, $rating);
    
    // Rozet kontrolü yap
    $earnedBadges = checkBadgeAchievements($user_id, 'review', [
        'is_first' => $is_first,
        'rating' => $rating
    ]);
    
    return [
        'success' => $result,
        'earned_badges' => $earnedBadges
    ];
}

/**
 * Kullanıcı giriş yaptığında rozet kontrolü yapar
 * 
 * @param int $user_id Kullanıcı ID
 * @return array Kazanılan rozetler
 */
function checkLoginBadges($user_id) {
    // Giriş kaydını güncelle
    updateLoginRecord($user_id);
    
    // Rozet kontrolü yap
    return checkBadgeAchievements($user_id, 'login', []);
}

/**
 * Kullanıcının giriş kayıtlarını günceller
 * 
 * @param int $user_id Kullanıcı ID
 * @return bool İşlem başarılıysa true, değilse false
 */
function updateLoginRecord($user_id) {
    $login_file = __DIR__ . '/../data/user_logins.json';
    $login_data = [];
    
    if (file_exists($login_file)) {
        $login_data = json_decode(file_get_contents($login_file), true);
    }
    
    // Kullanıcı için veri yapısını oluştur
    if (!isset($login_data['users'][$user_id])) {
        $login_data['users'][$user_id] = [
            'logins' => []
        ];
    }
    
    // Bugünün tarihini ekle
    $today = date('Y-m-d');
    
    // Bugün zaten giriş yapmış mı kontrol et
    if (!in_array($today, $login_data['users'][$user_id]['logins'])) {
        $login_data['users'][$user_id]['logins'][] = $today;
    }
    
    // Giriş listesini son 60 gün ile sınırla
    if (count($login_data['users'][$user_id]['logins']) > 60) {
        $login_data['users'][$user_id]['logins'] = array_slice(
            $login_data['users'][$user_id]['logins'],
            -60
        );
    }
    
    // JSON dosyasına yaz
    return file_put_contents($login_file, json_encode($login_data, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Kullanıcıya rozet bildirimi göster (JavaScript için)
 *
 * @param array $badges Kazanılan rozetler
 * @return string JavaScript kodu
 */
function generateBadgeNotificationJS($badges) {
    if (empty($badges)) {
        return '';
    }
    
    $js = '<script>';
    $js .= 'document.addEventListener("DOMContentLoaded", function() {';
    
    foreach ($badges as $badge) {
        $js .= 'setTimeout(function() {';
        $js .= 'showBadgeNotification("' . htmlspecialchars($badge['name']) . '", "' . 
               htmlspecialchars($badge['description']) . '", "' . 
               htmlspecialchars($badge['icon']) . '", "' . 
               htmlspecialchars($badge['color']) . '");';
        $js .= '}, ' . (rand(500, 3000)) . ');'; // Rasgele bir gecikme ile göster
    }
    
    $js .= '});';
    $js .= '</script>';
    
    return $js;
}

/**
 * Tüm badge ilerlemelerini kontrol eder (bir zamanlayıcı veya kontrol noktasından çağrılabilir)
 * 
 * @param int $user_id Kullanıcı ID
 * @return array Kazanılan rozetler
 */
function checkAllBadgeProgression($user_id) {
    return checkBadgeAchievements($user_id, '', []);
}

/**
 * Kullanıcının en son rozet kontrolünün yapıldığı tarihi kontrol eder
 * ve eğer bugünden farklıysa tüm rozet ilerlemelerini yeniden kontrol eder
 * 
 * @param int $user_id Kullanıcı ID
 * @return array Kazanılan rozetler veya boş dizi
 */
function checkDailyBadgeProgression($user_id) {
    $badges_file = __DIR__ . '/../data/badges.json';
    
    if (!file_exists($badges_file)) {
        return [];
    }
    
    $badges_data = json_decode(file_get_contents($badges_file), true);
    
    if (!isset($badges_data['users'][$user_id]['last_check'])) {
        return checkAllBadgeProgression($user_id);
    }
    
    $last_check = $badges_data['users'][$user_id]['last_check']['date'];
    $today = date('Y-m-d');
    
    if ($last_check != $today) {
        return checkAllBadgeProgression($user_id);
    }
    
    return [];
}

/**
 * Sabah saatlerinde izlenen film sayısını hesaplar (06:00-09:00)
 * 
 * @param int $user_id Kullanıcı ID
 * @return int Sabah saatlerinde izlenen film sayısı
 */
function countMorningWatches($user_id) {
    $stats = getUserStats($user_id);
    $morning_watches = 0;
    
    if (empty($stats['watch_history'])) {
        return 0;
    }
    
    foreach ($stats['watch_history'] as $watch) {
        if (isset($watch['date'])) {
            $hour = date('H', strtotime($watch['date']));
            if ($hour >= 6 && $hour < 9) {
                $morning_watches++;
            }
        }
    }
    
    return $morning_watches;
}

/**
 * Kullanıcının gece izleme alışkanlıklarını analiz eder
 * 
 * @param int $user_id Kullanıcı ID
 * @return array Gece izleme analizi
 */
function analyzeNightWatchingHabits($user_id) {
    $stats = getUserStats($user_id);
    $result = [
        'total_night_watches' => 0,
        'consecutive_nights' => 0,
        'preferred_hour' => null
    ];
    
    if (empty($stats['watch_history'])) {
        return $result;
    }
    
    $hours = [];
    $dates = [];
    
    foreach ($stats['watch_history'] as $watch) {
        if (isset($watch['date'])) {
            $hour = (int)date('H', strtotime($watch['date']));
            if ($hour >= 22 || $hour < 6) {
                $result['total_night_watches']++;
                $date = date('Y-m-d', strtotime($watch['date']));
                $dates[$date] = true;
                
                if (!isset($hours[$hour])) {
                    $hours[$hour] = 0;
                }
                $hours[$hour]++;
            }
        }
    }
    
    // En çok tercih edilen saat
    if (!empty($hours)) {
        $result['preferred_hour'] = array_search(max($hours), $hours);
    }
    
    // Ardışık gece izlemeleri
    $dates = array_keys($dates);
    sort($dates);
    $consecutive = 1;
    $max_consecutive = 1;
    
    for ($i = 1; $i < count($dates); $i++) {
        $prev = new DateTime($dates[$i-1]);
        $curr = new DateTime($dates[$i]);
        $diff = $prev->diff($curr);
        
        if ($diff->days == 1) {
            $consecutive++;
            $max_consecutive = max($max_consecutive, $consecutive);
        } else {
            $consecutive = 1;
        }
    }
    
    $result['consecutive_nights'] = $max_consecutive;
    
    return $result;
}

/**
 * Kullanıcının bir günde farklı film etkileşimi sayısını hesaplar
 * (izleme, favori ekleme, yorum yapma)
 * 
 * @param int $user_id Kullanıcı ID
 * @return int Günlük farklı etkileşim sayısı
 */
function countDailyInteractions($user_id) {
    $stats = getUserStats($user_id);
    $today = date('Y-m-d');
    $interactions = 0;
    
    // İzleme kontrolü
    if (isset($stats['activity_calendar'][$today]) && $stats['activity_calendar'][$today] > 0) {
        $interactions++;
    }
    
    // Favori ekleme kontrolü
    $favorites_file = __DIR__ . '/../data/user_favorites.json';
    if (file_exists($favorites_file)) {
        $favorites_data = json_decode(file_get_contents($favorites_file), true);
        if (isset($favorites_data['users'][$user_id]['activity'])) {
            foreach ($favorites_data['users'][$user_id]['activity'] as $activity) {
                if (isset($activity['date']) && substr($activity['date'], 0, 10) === $today) {
                    $interactions++;
                    break;
                }
            }
        }
    }
    
    // Yorum yapma kontrolü
    $reviews_file = __DIR__ . '/../data/movie_reviews.json';
    if (file_exists($reviews_file)) {
        $reviews_data = json_decode(file_get_contents($reviews_file), true);
        if (isset($reviews_data['reviews'])) {
            foreach ($reviews_data['reviews'] as $review) {
                if ($review['user_id'] == $user_id && isset($review['date']) && substr($review['date'], 0, 10) === $today) {
                    $interactions++;
                    break;
                }
            }
        }
    }
    
    return $interactions;
}

/**
 * Kullanıcının premium filmleri izleme sayısını hesaplar
 * 
 * @param int $user_id Kullanıcı ID
 * @return int Premium film izleme sayısı
 */
function countPremiumWatches($user_id) {
    $stats = getUserStats($user_id);
    $premium_watches = 0;
    
    if (empty($stats['watch_history'])) {
        return 0;
    }
    
    foreach ($stats['watch_history'] as $watch) {
        if (isset($watch['premium']) && $watch['premium'] === true) {
            $premium_watches++;
        }
    }
    
    return $premium_watches;
}

/**
 * Kullanıcının koleksiyon filmlerini izleme sayısını hesaplar
 * 
 * @param int $user_id Kullanıcı ID
 * @return int Koleksiyon filmi izleme sayısı
 */
function countCollectionWatches($user_id) {
    $stats = getUserStats($user_id);
    $collection_watches = 0;
    
    if (empty($stats['watch_history'])) {
        return 0;
    }
    
    foreach ($stats['watch_history'] as $watch) {
        if (isset($watch['collection']) && !empty($watch['collection'])) {
            $collection_watches++;
        }
    }
    
    return $collection_watches;
}

/**
 * Kullanıcının klasik filmleri izleme sayısını hesaplar (1980 öncesi)
 * 
 * @param int $user_id Kullanıcı ID
 * @return int Klasik film izleme sayısı
 */
function countClassicWatches($user_id) {
    $stats = getUserStats($user_id);
    $classic_watches = 0;
    
    if (empty($stats['watch_history'])) {
        return 0;
    }
    
    foreach ($stats['watch_history'] as $watch) {
        if (isset($watch['year']) && $watch['year'] < 1980) {
            $classic_watches++;
        }
    }
    
    return $classic_watches;
}

/**
 * Kullanıcının festival filmlerini izleme sayısını hesaplar
 * 
 * @param int $user_id Kullanıcı ID
 * @return int Festival filmi izleme sayısı
 */
function countFestivalWatches($user_id) {
    $stats = getUserStats($user_id);
    $festival_watches = 0;
    
    if (empty($stats['watch_history'])) {
        return 0;
    }
    
    foreach ($stats['watch_history'] as $watch) {
        if (isset($watch['tags']) && is_array($watch['tags']) && in_array('festival', $watch['tags'])) {
            $festival_watches++;
        }
    }
    
    return $festival_watches;
}

/**
 * Kullanıcının yeni çıkan filmleri izleme sayısını hesaplar (24 saat içinde)
 * 
 * @param int $user_id Kullanıcı ID
 * @return int Yeni çıkan film izleme sayısı
 */
function countNewReleaseWatches($user_id) {
    $stats = getUserStats($user_id);
    $new_release_watches = 0;
    
    if (empty($stats['watch_history'])) {
        return 0;
    }
    
    foreach ($stats['watch_history'] as $watch) {
        if (isset($watch['release_date']) && isset($watch['date'])) {
            $release_date = new DateTime($watch['release_date']);
            $watch_date = new DateTime($watch['date']);
            $hours_diff = ($watch_date->getTimestamp() - $release_date->getTimestamp()) / 3600;
            
            if ($hours_diff >= 0 && $hours_diff <= 24) {
                $new_release_watches++;
            }
        }
    }
    
    return $new_release_watches;
}

/**
 * Kullanıcının popüler filmleri izleme sayısını hesaplar
 * 
 * @param int $user_id Kullanıcı ID
 * @return int Popüler film izleme sayısı
 */
function countTrendingWatches($user_id) {
    $stats = getUserStats($user_id);
    $trending_watches = 0;
    
    if (empty($stats['watch_history'])) {
        return 0;
    }
    
    foreach ($stats['watch_history'] as $watch) {
        if (isset($watch['trending']) && $watch['trending'] === true) {
            $trending_watches++;
        }
    }
    
    return $trending_watches;
}

/**
 * Kullanıcının aynı filmi tekrar izleme sayısını hesaplar
 * 
 * @param int $user_id Kullanıcı ID
 * @return int Tekrar izlenen film sayısı
 */
function countRewatchedMovies($user_id) {
    $stats = getUserStats($user_id);
    $movie_counts = [];
    
    if (empty($stats['watch_history'])) {
        return 0;
    }
    
    // Filmleri ve izlenme sayılarını say
    foreach ($stats['watch_history'] as $watch) {
        if (isset($watch['movie_id'])) {
            $movie_id = $watch['movie_id'];
            if (!isset($movie_counts[$movie_id])) {
                $movie_counts[$movie_id] = 0;
            }
            $movie_counts[$movie_id]++;
        }
    }
    
    // 3 veya daha fazla izlenen filmleri say
    $rewatched_count = 0;
    foreach ($movie_counts as $count) {
        if ($count >= 3) {
            $rewatched_count++;
        }
    }
    
    return $rewatched_count;
}

/**
 * Kullanıcının yüksek puanlı filmleri izleme sayısını hesaplar
 * 
 * @param int $user_id Kullanıcı ID
 * @param float $min_rating Minimum puan (ör: 8.5)
 * @return int Yüksek puanlı film izleme sayısı
 */
function countHighRatedWatches($user_id, $min_rating = 8.5) {
    $stats = getUserStats($user_id);
    $high_rated_watches = 0;
    
    if (empty($stats['watch_history'])) {
        return 0;
    }
    
    foreach ($stats['watch_history'] as $watch) {
        if (isset($watch['imdb_rating']) && $watch['imdb_rating'] >= $min_rating) {
            $high_rated_watches++;
        }
    }
    
    return $high_rated_watches;
}

/**
 * Farklı türlerden izlenen film sayısını kontrol eder
 * 
 * @param int $user_id Kullanıcı ID
 * @return int İzlenen farklı tür sayısı
 */
function countDiverseGenres($user_id) {
    $stats = getUserStats($user_id);
    
    if (empty($stats['genres'])) {
        return 0;
    }
    
    return count(array_keys($stats['genres']));
}

/**
 * Hafta sonu izlenen film sayısını kontrol eder
 * 
 * @param int $user_id Kullanıcı ID
 * @return int Hafta sonu izlenen film sayısı
 */
function countWeekendWatches($user_id) {
    $stats = getUserStats($user_id);
    $weekend_watches = 0;
    
    if (empty($stats['watch_history'])) {
        return 0;
    }
    
    foreach ($stats['watch_history'] as $watch) {
        if (isset($watch['date'])) {
            $day_of_week = date('N', strtotime($watch['date']));
            if ($day_of_week >= 6) { // 6 = Cumartesi, 7 = Pazar
                $weekend_watches++;
            }
        }
    }
    
    return $weekend_watches;
}

/**
 * Kullanıcının giriş bilgilerini alır
 * 
 * @param int $user_id Kullanıcı ID
 * @return array Giriş bilgileri
 */
function getUserLoginData($user_id) {
    $login_file = __DIR__ . '/../data/user_logins.json';
    $login_data = [];
    
    if (file_exists($login_file)) {
        $login_data = json_decode(file_get_contents($login_file), true);
    }
    
    if (!isset($login_data['users'][$user_id])) {
        return ['logins' => []];
    }
    
    return $login_data['users'][$user_id];
}

/**
 * Film verilerine premium ve trending gibi özel etiketler ekler
 * 
 * @param array $movie_data Film verileri
 * @return array Güncellenmiş film verileri
 */
function enrichMovieData($movie_data) {
    // Eğer film verisi yoksa boş dizi döndür
    if (empty($movie_data)) {
        return [];
    }
    
    // Premium film kontrolü (örnek kriter: puan > 8.5 veya özel etiket)
    if ((isset($movie_data['imdb_rating']) && $movie_data['imdb_rating'] > 8.5) || 
        (isset($movie_data['tags']) && in_array('premium', $movie_data['tags']))) {
        $movie_data['premium'] = true;
    }
    
    // Trend film kontrolü (son eklenenler veya çok izlenenler)
    $release_date = $movie_data['release_date'] ?? null;
    if ($release_date) {
        $release = new DateTime($release_date);
        $now = new DateTime();
        $days_diff = $now->diff($release)->days;
        
        if ($days_diff <= 30 || (isset($movie_data['view_count']) && $movie_data['view_count'] > 1000)) {
            $movie_data['trending'] = true;
        }
    }
    
    return $movie_data;
}

/**
 * Rozet bildirimi oluşturur ve kullanıcıya gösterir
 * 
 * @param int $user_id Kullanıcı ID
 * @param array $badge Rozet bilgileri
 * @return string Bildirim HTML kodu
 */
function createBadgeNotification($user_id, $badge) {
    // Kullanıcıya bildirim ekle
    addNotification(
        $user_id,
        "Yeni Rozet: {$badge['name']}",
        "Tebrikler! {$badge['description']}",
        'badge'
    );
    
    // JavaScript bildirimi için HTML kodu döndür
    $html = '<div class="toast-container" id="badgeToast_' . $badge['id'] . '">
        <div class="toast badge-toast">
            <div class="badge-icon" style="background-color:' . $badge['color'] . '">
                <i class="fas ' . $badge['icon'] . '"></i>
            </div>
            <div class="badge-content">
                <h4>Yeni Rozet Kazandın!</h4>
                <h3>' . $badge['name'] . '</h3>
                <p>' . $badge['description'] . '</p>
            </div>
        </div>
    </div>';
    
    return $html;
}

/**
 * Film izleme esnasında gerekli veri zenginleştirmelerini yapar ve
 * rozet kontrolünü çağırır
 * 
 * @param int $user_id Kullanıcı ID
 * @param int $movie_id Film ID
 * @param array $movie_data Film verileri
 * @return array Sonuç ve kazanılan rozetler
 */
function recordEnhancedMovieWatch($user_id, $movie_id, $movie_data) {
    // Film verilerini zenginleştir
    $enriched_data = enrichMovieData($movie_data);
    
    // Filmi izleme geçmişine ekle
    $result = addToWatchHistory($user_id, $movie_id, $enriched_data);
    
    // Rozet kazanımlarını kontrol et
    $earnedBadges = checkBadgeAchievements($user_id, 'view', [
        'genre' => $enriched_data['category']['name'] ?? $enriched_data['genre'] ?? 'Diğer',
        'duration' => $enriched_data['duration'] ?? 120,
        'premium' => $enriched_data['premium'] ?? false,
        'trending' => $enriched_data['trending'] ?? false
    ]);
    
    // Günlük etkileşim rozetlerini de kontrol et
    $interactionBadges = checkBadgeAchievements($user_id, 'interaction', []);
    
    // Yeni kazanılan rozetleri birleştir
    $allEarnedBadges = array_merge($earnedBadges, $interactionBadges);
    
    return [
        'success' => $result,
        'earned_badges' => $allEarnedBadges
    ];
}

?> 