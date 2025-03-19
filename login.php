<?php
session_start();
include 'config/database.php';

$page_title = "Giriş Yap";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tüm POST verilerini logla
    error_log("Tüm POST verileri: " . print_r($_POST, true));
    error_log("Ham POST verisi: " . file_get_contents("php://input"));
    
    // Form verilerini al
    $login = isset($_POST['login']) ? trim($_POST['login']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    // Boş değer kontrolü
    if (empty($login) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı adı/email ve şifre boş bırakılamaz!']);
        exit;
    }
    
    // Kullanıcı bilgilerini al
    $user = getUserByEmailOrUsername($login);
    
    // Debug için kullanıcı bilgilerini logla
    error_log("Bulunan kullanıcı için login: " . $login);
    error_log("Bulunan kullanıcı: " . print_r($user, true));
    
    if ($user && password_verify($password, $user['password'])) {
        // Giriş başarılı
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['profile_image'] = $user['profile_image'] ?? 'default.png';
        
        // Debug için
        error_log("Kullanıcı giriş yaptı: " . $user['username'] . ", Profil resmi: " . $_SESSION['profile_image']);
        
        echo json_encode(['success' => true, 'redirect' => 'index.php']);
    } else {
        // Giriş başarısız
        echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı adı/email veya şifre!']);
    }
    exit;
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/login.css">

<div class="login-wrapper">
    <div class="login-container">
        <div class="login-header">
            <h2>Giriş Yap</h2>
        </div>

        <div class="form-container">
            <form id="loginForm">
                <div class="input-group">
                    <input type="text" id="login" name="login" required>
                    <label for="login">E-posta veya Kullanıcı Adı</label>
                </div>

                <div class="input-group">
                    <input type="password" id="password" name="password" required>
                    <label for="password">Şifre</label>
                </div>

                <button type="submit" class="login-button">Giriş Yap</button>

                <div class="form-links">
                    Hesabınız yok mu? <a href="register.php">Kayıt Ol</a>
                    <span class="separator">•</span>
                    <a href="forgot-password.php">Şifremi Unuttum</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Debug için
    console.log('Form verileri:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    fetch('login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data);
        
        if (data.success) {
            // Başarılı giriş
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            // Hata mesajı
            alert(data.message);
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
