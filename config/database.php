<?php
// JSON dosya yolları
define('DATABASE_FILE', __DIR__ . '/../data/database.json');
define('USERS_FILE', __DIR__ . '/../data/users.json');

// Ana veritabanını oku (filmler)
function readDatabase() {
    if (!file_exists(DATABASE_FILE)) {
        // Eğer dosya yoksa, temel yapıyı oluştur
        $data = [
            'movies' => []
        ];
        writeDatabase($data);
        return $data;
    }
    return json_decode(file_get_contents(DATABASE_FILE), true);
}

// Ana veritabanına yaz
function writeDatabase($data) {
    return file_put_contents(DATABASE_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

// Kullanıcı veritabanını oku
function readUsers() {
    if (!file_exists(USERS_FILE)) {
        // Eğer dosya yoksa, temel yapıyı oluştur
        $data = ['users' => []];
        writeUsers($data);
        return $data;
    }
    return json_decode(file_get_contents(USERS_FILE), true);
}

// Kullanıcı veritabanına yaz
function writeUsers($data) {
    return file_put_contents(USERS_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

// Kullanıcı işlemleri
/**
 * Email veya kullanıcı adına göre kullanıcı bilgisini getirir
 */
function getUserByEmailOrUsername($login) {
    // JSON dosyasından kullanıcıları oku
    $jsonData = file_get_contents('data/users.json');
    $data = json_decode($jsonData, true);
    
    if (isset($data['users'])) {
        foreach ($data['users'] as $user) {
            // Email veya kullanıcı adını kontrol et
            if ($user['email'] == $login || $user['username'] == $login) {
                return $user;
            }
        }
    }
    
    return null;
}

// Kullanıcı ID'sine göre kullanıcı getirme
function getUserById($id) {
    $data = readUsers();
    if (!isset($data['users'])) return null;
    
    foreach ($data['users'] as $user) {
        if ($user['id'] == $id) {
            return $user;
        }
    }
    return null;
}

function createUser($username, $email, $password) {
    // JSON dosyası kullanılıyorsa
    $data = readUsers();
    if (!isset($data['users'])) {
        $data['users'] = [];
    }
    
    // Yeni kullanıcı ID'si oluştur
    $newId = count($data['users']) + 1;
    
    // Yeni kullanıcıyı ekle
    $data['users'][] = [
        'id' => $newId,
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    return writeUsers($data);
}

function updateUserProfile($userId, $profileImage) {
    $data = readUsers();
    if (!isset($data['users'])) return false;
    
    foreach ($data['users'] as &$user) {
        if ($user['id'] == $userId) {
            $user['profile_image'] = $profileImage;
            return writeUsers($data);
        }
    }
    return false;
}

// Kullanıcı şifresini güncelleme
function updateUserPassword($userId, $newPassword) {
    $data = readUsers();
    if (!isset($data['users'])) return false;
    
    foreach ($data['users'] as &$user) {
        if ($user['id'] == $userId) {
            $user['password'] = $newPassword;
            return writeUsers($data);
        }
    }
    return false;
}

// Kullanıcıyı silme
function deleteUser($userId) {
    $data = readUsers();
    if (!isset($data['users'])) return false;
    
    foreach ($data['users'] as $key => $user) {
        if ($user['id'] == $userId) {
            // Kullanıcıyı diziden çıkar
            unset($data['users'][$key]);
            // Diziyi yeniden indeksle
            $data['users'] = array_values($data['users']);
            return writeUsers($data);
        }
    }
    return false;
}

// Film işlemleri
function getAllMovies() {
    // JSON kullan
    $data = readDatabase();
    return isset($data['movies']) ? $data['movies'] : [];
}

function getMovieById($id) {
    $movies = getAllMovies();
    foreach ($movies as $movie) {
        if ($movie['id'] == $id) {
            return $movie;
        }
    }
    return null;
}

function getFeaturedMovies() {
    // JSON kullan
    $movies = getAllMovies();
    // Featured özelliği olan filmleri filtrele
    return array_filter($movies, function($movie) {
        return isset($movie['featured']) && $movie['featured'];
    });
}

// Kategori işlemleri
function getAllCategories() {
    // JSON kullan
    $movies = getAllMovies();
    $categories = [];
    $category_ids = [];
    
    foreach ($movies as $movie) {
        if (isset($movie['category']) && isset($movie['category']['id'])) {
            $category_id = $movie['category']['id'];
            
            if (!in_array($category_id, $category_ids)) {
                $category_ids[] = $category_id;
                $categories[] = $movie['category'];
            }
        }
    }
    
    // Kategori ID'lerine göre sırala
    usort($categories, function($a, $b) {
        return $a['id'] - $b['id'];
    });
    
    return $categories;
}

function getCategoryById($id) {
    $categories = getAllCategories();
    foreach ($categories as $category) {
        if ($category['id'] == $id) {
            return $category;
        }
    }
    return null;
}

// Film sayısını hesaplama
function getMovieCountByCategory($category_id) {
    // JSON kullan
    $movies = getAllMovies();
    $count = 0;
    foreach ($movies as $movie) {
        if (isset($movie['category']) && $movie['category']['id'] == $category_id) {
            $count++;
        }
    }
    return $count;
}

// Kategoriye göre film getirme
function getMoviesByCategory($category_id) {
    $movies = getAllMovies();
    return array_filter($movies, function($movie) use ($category_id) {
        return isset($movie['category']) && $movie['category']['id'] == $category_id;
    });
}

// Yardımcı fonksiyonlar
function real_escape_string($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function num_rows($result) {
    return is_array($result) ? count($result) : 0;
}

function fetch_assoc($result) {
    if (!is_array($result)) return null;
    return array_shift($result);
}

// Yorum işlemleri
function getMovieReviews($movieId) {
    // JSON dosyasından yorumları oku
    $data = readDatabase();
    if (!isset($data['reviews'])) {
        return [];
    }
    
    $reviews = array_filter($data['reviews'], function($review) use ($movieId) {
        return $review['movie_id'] == $movieId;
    });
    
    // Kullanıcı bilgilerini ekle
    $users = readUsers();
    foreach ($reviews as &$review) {
        $user = getUserById($review['user_id']);
        if ($user) {
            $review['username'] = $user['username'];
            $review['profile_image'] = $user['profile_image'] ?? null;
        }
    }
    
    return array_values($reviews);
}

function addMovieReview($movieId, $userId, $rating, $comment) {
    // JSON dosyasına yorum ekle
    $data = readDatabase();
    if (!isset($data['reviews'])) {
        $data['reviews'] = [];
    }
    
    $newReview = [
        'id' => count($data['reviews']) + 1,
        'movie_id' => $movieId,
        'user_id' => $userId,
        'rating' => $rating,
        'comment' => $comment,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $data['reviews'][] = $newReview;
    return writeDatabase($data);
}

function getMovieAverageRating($movieId) {
    $reviews = getMovieReviews($movieId);
    if (empty($reviews)) {
        return 0;
    }
    
    $totalRating = 0;
    foreach ($reviews as $review) {
        $totalRating += $review['rating'];
    }
    
    return round($totalRating / count($reviews), 1);
}

/**
 * Site istatistiklerini getirir
 * 
 * @return array Site istatistikleri (film sayısı, kategori sayısı, üye sayısı)
 */
function getSiteStatistics() {
    $stats = array(
        'movie_count' => 0,
        'category_count' => 0,
        'user_count' => 0
    );
    
    try {
        // JSON dosyalarını kullan
        $movies = getAllMovies();
        $stats['movie_count'] = count($movies);
        
        // Kategorileri topla
        $categories = [];
        foreach ($movies as $movie) {
            if (isset($movie['category']['id']) && !in_array($movie['category']['id'], $categories)) {
                $categories[] = $movie['category']['id'];
            }
        }
        $stats['category_count'] = count($categories);
        
        // Kullanıcı sayısını al
        $userData = readUsers();
        $stats['user_count'] = isset($userData['users']) ? count($userData['users']) : 0;
    } catch (Exception $e) {
        // Herhangi bir hata durumunda varsayılan değerleri kullan
        error_log("İstatistik hesaplama hatası: " . $e->getMessage());
    }
    
    // Son kontrol - sayıların gerçekten sayı olduğundan emin olalım
    foreach ($stats as $key => $value) {
        if (!is_numeric($value)) {
            $stats[$key] = 0;
        }
    }
    
    return $stats;
}

/**
 * Şifre sıfırlama tokeni oluşturur ve kaydeder
 */
function savePasswordResetToken($userId, $token, $expires) {
    // JSON dosyası kullanılıyorsa
    $data = readDatabase();
    if (!isset($data['password_resets'])) {
        $data['password_resets'] = [];
    }
    
    $data['password_resets'][] = [
        'user_id' => $userId,
        'token' => $token,
        'expires_at' => $expires
    ];
    
    return writeDatabase($data);
}

/**
 * Şifre sıfırlama tokenini kontrol eder
 */
function getPasswordResetToken($token) {
    // JSON dosyası kullanılıyorsa
    $data = readDatabase();
    if (!isset($data['password_resets'])) {
        return null;
    }
    
    $now = date('Y-m-d H:i:s');
    foreach ($data['password_resets'] as $reset) {
        if ($reset['token'] === $token && $reset['expires_at'] > $now) {
            return $reset;
        }
    }
    
    return null;
}

/**
 * Şifre sıfırlama tokenini siler
 */
function removePasswordResetToken($token) {
    // JSON dosyası kullanılıyorsa
    $data = readDatabase();
    if (!isset($data['password_resets'])) {
        return true;
    }
    
    foreach ($data['password_resets'] as $key => $reset) {
        if ($reset['token'] === $token) {
            unset($data['password_resets'][$key]);
            $data['password_resets'] = array_values($data['password_resets']);
            return writeDatabase($data);
        }
    }
    
    return true;
}

/**
 * Belirli bir kategorideki film sayısını hesaplar
 * @param int $category_id Kategori ID'si
 * @return int Film sayısı
 */
function getCategoryMovieCount($category_id) {
    // JSON kullanarak film sayısını hesapla
    $movies = getAllMovies();
    $count = 0;
    
    foreach ($movies as $movie) {
        if (isset($movie['category']) && isset($movie['category']['id']) && $movie['category']['id'] == $category_id) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Tüm kategorileri film sayılarıyla birlikte getirir
 */
function getAllCategoriesWithMovieCount() {
    // JSON kullanarak kategorileri ve film sayılarını getir
    $categories = getAllCategories();
    $result = [];
    
    foreach ($categories as $category) {
        $category['movie_count'] = getCategoryMovieCount($category['id']);
        $result[] = $category;
    }
    
    return $result;
}
?> 