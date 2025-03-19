<?php
session_start();
include 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $submitted_code = $data['code'] ?? '';
    
    // Debug için
    error_log("Gönderilen kod: " . $submitted_code);
    error_log("Session reset_user: " . print_r($_SESSION['reset_user'] ?? [], true));
    
    if (isset($_SESSION['reset_user']) && 
        isset($_SESSION['reset_user']['reset_code']) && 
        $submitted_code === $_SESSION['reset_user']['reset_code']) {
        
        // Süre kontrolü
        if ($_SESSION['reset_user']['expires'] < time()) {
            $response = [
                'success' => false,
                'message' => 'Doğrulama kodunun süresi dolmuş. Lütfen yeni kod talep edin.'
            ];
        } else {
            // Token oluştur
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 saat
            
            // Token'ı kaydet
            if (savePasswordResetToken($_SESSION['reset_user']['user_id'], $token, $expires)) {
                // Session'ı temizle
                unset($_SESSION['reset_user']);
                
                $response = [
                    'success' => true,
                    'message' => 'Doğrulama başarılı! Şimdi yeni şifrenizi belirleyebilirsiniz.',
                    'redirect' => 'reset-password.php?token=' . $token
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Token kaydedilirken bir hata oluştu.'
                ];
            }
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