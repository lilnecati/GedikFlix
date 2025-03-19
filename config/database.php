<?php
// PDO veritabanı bağlantısı
try {
    $host = 'localhost';
    $dbname = 'gedikflix';
    $username = 'root';
    $password = '';
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Veritabanına bağlanılamazsa JSON kullanmaya geri dön
    $db = null;
    error_log("Veritabanı bağlantı hatası: " . $e->getMessage());
}

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
    try {
        global $db;
        if ($db) {
            // MySQL kullanılıyorsa
            $query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            return $stmt->execute([$username, $email, $password]);
        } else {
            // JSON dosyası kullanılıyorsa
            $data = readDatabase();
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
            
            return writeDatabase($data);
        }
        return true;
    } catch (Exception $e) {
        error_log("Kullanıcı oluşturma hatası: " . $e->getMessage());
        return false;
    }
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
    global $db;
    
    if ($db) {
        // MySQL veritabanı varsa
        $query = "SELECT m.*, c.name as category_name, c.id as category_id 
                FROM movies m 
                JOIN categories c ON m.category_id = c.id 
                ORDER BY m.title";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $movies = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $movies[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'year' => $row['year'],
                'duration' => $row['duration'],
                'poster_url' => $row['poster_url'],
                'created_at' => $row['created_at'],
                'category' => [
                    'id' => $row['category_id'],
                    'name' => $row['category_name']
                ]
            ];
        }
        
        return $movies;
    } else {
        // MySQL veritabanı yoksa JSON kullan
        $data = readDatabase();
        return isset($data['movies']) ? $data['movies'] : [];
    }
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
    global $db;
    
    if ($db) {
        // MySQL veritabanı varsa
        // Burada öne çıkan filmleri seçeceğimiz mantık
        // Şu an için rastgele 6 film seçeceğiz
        $query = "SELECT m.*, c.name as category_name, c.id as category_id 
                FROM movies m 
                JOIN categories c ON m.category_id = c.id 
                ORDER BY RAND() 
                LIMIT 6";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $movies = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $movies[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'year' => $row['year'],
                'duration' => $row['duration'],
                'poster_url' => $row['poster_url'],
                'created_at' => $row['created_at'],
                'category' => [
                    'id' => $row['category_id'],
                    'name' => $row['category_name']
                ]
            ];
        }
        
        return $movies;
    } else {
        // MySQL veritabanı yoksa JSON kullan
        $movies = getAllMovies();
        // Featured özelliği olan filmleri filtrele
        return array_filter($movies, function($movie) {
            return isset($movie['featured']) && $movie['featured'];
        });
    }
}

// Kategori işlemleri
function getAllCategories() {
    global $db;
    
    if ($db) {
        // MySQL veritabanı varsa
        $query = "SELECT * FROM categories ORDER BY name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // MySQL veritabanı yoksa JSON kullan
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
    global $db;
    
    if ($db) {
        // MySQL veritabanı varsa
        $query = "SELECT COUNT(*) as count FROM movies WHERE category_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$category_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'];
    } else {
        // MySQL veritabanı yoksa JSON kullan
        $movies = getAllMovies();
        $count = 0;
        foreach ($movies as $movie) {
            if (isset($movie['category']) && $movie['category']['id'] == $category_id) {
                $count++;
            }
        }
        return $count;
    }
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
    global $db;
    
    if ($db) {
        // MySQL veritabanı varsa
        $query = "SELECT r.*, u.username, u.profile_image 
                FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.movie_id = ? 
                ORDER BY r.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$movieId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
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
}

function addMovieReview($movieId, $userId, $rating, $comment) {
    global $db;
    
    if ($db) {
        // MySQL veritabanı varsa
        $query = "INSERT INTO reviews (movie_id, user_id, rating, comment, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($query);
        return $stmt->execute([$movieId, $userId, $rating, $comment]);
    } else {
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
    global $db;
    
    $stats = array(
        'movie_count' => 0,
        'category_count' => 0,
        'user_count' => 0
    );
    
    try {
        if ($db) {
            // MySQL veritabanı varsa
            
            // Film sayısını al
            $query = "SELECT COUNT(*) as count FROM movies";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && isset($result['count'])) {
                $stats['movie_count'] = intval($result['count']);
            }
            
            // Kategori sayısını al
            $query = "SELECT COUNT(*) as count FROM categories";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && isset($result['count'])) {
                $stats['category_count'] = intval($result['count']);
            }
            
            // Üye sayısını al
            $query = "SELECT COUNT(*) as count FROM users";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && isset($result['count'])) {
                $stats['user_count'] = intval($result['count']);
            }
        } else {
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
        }
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
    global $db;
    
    if ($db) {
        // MySQL kullanılıyorsa
        $query = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        return $stmt->execute([$userId, $token, $expires]);
    } else {
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
}

/**
 * Şifre sıfırlama tokenini kontrol eder
 */
function getPasswordResetToken($token) {
    global $db;
    
    if ($db) {
        // MySQL kullanılıyorsa
        $query = "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$token]);
        return $stmt->fetch();
    } else {
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
}

/**
 * Şifre sıfırlama tokenini siler
 */
function removePasswordResetToken($token) {
    global $db;
    
    if ($db) {
        // MySQL kullanılıyorsa
        $query = "DELETE FROM password_resets WHERE token = ?";
        $stmt = $db->prepare($query);
        return $stmt->execute([$token]);
    } else {
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
}
?> 