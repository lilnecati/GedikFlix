<?php
session_start();
include 'config/database.php';

$page_title = "Şifre Sıfırla";
$token = $_GET['token'] ?? '';

// Token kontrolü
if (empty($token)) {
    header('Location: forgot-password.php');
    exit;
}

// Token geçerli mi?
$reset_data = getPasswordResetToken($token);
if (!$reset_data) {
    $_SESSION['error'] = 'Geçersiz veya süresi dolmuş şifre sıfırlama bağlantısı!';
    header('Location: forgot-password.php');
    exit;
}

// Kullanıcı bilgilerini al
$user = getUserById($reset_data['user_id']);
if (!$user) {
    $_SESSION['error'] = 'Kullanıcı bulunamadı!';
    header('Location: forgot-password.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'Tüm alanları doldurunuz!']);
        exit;
    }
    
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Şifreler eşleşmiyor!']);
        exit;
    }
    
    // Şifreyi güncelle
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    if (updateUserPassword($user['id'], $hashed_password)) {
        // Token'ı sil
        removePasswordResetToken($token);
        
        echo json_encode([
            'success' => true,
            'message' => 'Şifreniz başarıyla güncellendi!',
            'redirect' => 'login.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Şifre güncellenirken bir hata oluştu!']);
    }
    exit;
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/login.css">

<div class="login-wrapper">
    <div class="login-container">
        <div class="login-header">
            <h2>Şifre Sıfırla</h2>
        </div>

        <div class="form-container">
            <form id="resetPasswordForm">
                <p class="info-text">Merhaba, <strong><?php echo htmlspecialchars($user['username']); ?></strong>. Lütfen yeni şifrenizi belirleyin.</p>
                
                <div class="input-group">
                    <input type="password" id="password" name="password" required>
                    <label for="password">Yeni Şifre</label>
                    <small>En az 6 karakter</small>
                </div>

                <div class="input-group">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <label for="confirm_password">Şifreyi Tekrar Girin</label>
                </div>

                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <button type="submit" class="login-button">Şifreyi Güncelle</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('reset-password.php?token=<?php echo htmlspecialchars($token); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        
        if (data.success && data.redirect) {
            window.location.href = data.redirect;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    });
});
</script>
</body>
</html> 