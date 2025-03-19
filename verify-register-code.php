<?php
session_start();
include 'config/database.php';
include 'config/profile_utils.php'; // Profil fonksiyonlarını ekle

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $submitted_code = $data['code'] ?? '';
    
    // Debug için
    error_log("Gönderilen kod: " . $submitted_code);
    error_log("Session temp_user: " . print_r($_SESSION['temp_user'] ?? [], true));
    
    if (isset($_SESSION['temp_user']) && 
        isset($_SESSION['temp_user']['verification_code']) && 
        $submitted_code === $_SESSION['temp_user']['verification_code']) {
        
        try {
            // Kullanıcı bilgilerini al
            $user = $_SESSION['temp_user'];
            
            // JSON dosyasından mevcut kullanıcıları oku
            $users = readUsers();
            
            // Yeni kullanıcı için ID oluştur
            $newId = count($users['users']) + 1;
            
            // Rastgele bir profil resmi seç
            $profileImage = getRandomProfileImage();
            
            // Yeni kullanıcıyı ekle
            $users['users'][] = [
                'id' => $newId,
                'username' => $user['username'],
                'email' => $user['email'],
                'password' => $user['password'],
                'profile_image' => $profileImage, // Profil resmini ekle
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // JSON dosyasını güncelle
            if (writeUsers($users)) {
                unset($_SESSION['temp_user']);
                
                error_log("Kullanıcı başarıyla oluşturuldu. ID: " . $newId . ", Profil Resmi: " . $profileImage);
                
                $response = [
                    'success' => true,
                    'message' => 'Kayıt başarıyla tamamlandı! Giriş yapabilirsiniz.',
                    'redirect' => 'login.php'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Kullanıcı kaydı sırasında bir hata oluştu.'
                ];
            }
        } catch (Exception $e) {
            error_log("JSON işleme hatası: " . $e->getMessage());
            $response = [
                'success' => false,
                'message' => 'Bir hata oluştu: ' . $e->getMessage()
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Geçersiz doğrulama kodu!'
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?> 