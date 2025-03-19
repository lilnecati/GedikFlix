<?php
session_start();
include 'config/database.php';

$page_title = "Kayıt Ol"; // Header'da kullanılacak sayfa başlığı

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Debug için
    error_log("POST verisi alındı:");
    error_log("Username: " . $username);
    error_log("Email: " . $email);
    
    // Python script'i çağır
    $command = sprintf('python3 py/register_verification.py "%s" "%s"',
        escapeshellarg($email),
        escapeshellarg($username)
    );
    
    exec($command, $output, $return_var);
    
    // Debug için
    error_log("Python çıktısı: " . print_r($output, true));
    
    $result = json_decode(implode('', $output), true);
    
    if ($result && isset($result['success']) && $result['success']) {
        $_SESSION['temp_user'] = [
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'verification_code' => $result['code']
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Doğrulama kodu e-posta adresinize gönderildi.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => isset($result['error']) ? $result['error'] : 'E-posta gönderimi başarısız oldu.'
        ]);
    }
    exit;
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/login.css">
<link rel="stylesheet" href="css/verify-code.css">

<div class="login-wrapper">
    <div class="login-container">
        <div class="login-header">
            <h2>Kayıt Ol</h2>
        </div>

        <div class="form-container">
            <form id="registerForm">
                <div class="input-group">
                    <input type="text" id="username" name="username" required>
                    <label for="username">Kullanıcı Adı</label>
                    <small>En az 3, en fazla 20 karakter</small>
                </div>

                <div class="input-group">
                    <input type="email" id="email" name="email" required>
                    <label for="email">E-posta</label>
                </div>

                <div class="input-group">
                    <input type="password" id="password" name="password" required>
                    <label for="password">Şifre</label>
                    <small>En az 6 karakter</small>
                </div>

                <div class="input-group">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <label for="confirm_password">Şifre Tekrar</label>
                </div>

                <button type="submit" class="login-button">Kayıt Ol</button>

                <div class="form-links">
                    Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Doğrulama Popup'ı -->
<div id="verificationPopup" class="popup" style="display: none;">
    <div class="popup-content">
        <h2>E-posta Doğrulama</h2>
        <p>E-posta adresinize gönderilen 6 haneli kodu giriniz.</p>
        <div class="verification-inputs">
            <input type="text" class="code-input" maxlength="1" pattern="[0-9]">
            <input type="text" class="code-input" maxlength="1" pattern="[0-9]">
            <input type="text" class="code-input" maxlength="1" pattern="[0-9]">
            <input type="text" class="code-input" maxlength="1" pattern="[0-9]">
            <input type="text" class="code-input" maxlength="1" pattern="[0-9]">
            <input type="text" class="code-input" maxlength="1" pattern="[0-9]">
        </div>
        <button type="button" id="verifyButton">Doğrula</button>
        <div class="timer">
            Kalan süre: <span id="countdown">10:00</span>
        </div>
        <div class="resend">
            <button type="button" id="resendCode" disabled>
                Yeni kod gönder (<span id="resendTimer">60</span>)
            </button>
        </div>
    </div>
</div>

<script>
// Timer fonksiyonları
function startMainTimer() {
    let timeLeft = 600; // 10 dakika
    const countdownEl = document.getElementById('countdown');
    
    const timer = setInterval(() => {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        if (timeLeft <= 0) {
            clearInterval(timer);
            document.getElementById('verificationPopup').style.display = 'none';
            alert('Doğrulama kodunun süresi doldu. Lütfen yeni kod talep edin.');
        }
        timeLeft--;
    }, 1000);
}

function startResendTimer() {
    let timeLeft = 60;
    const resendButton = document.getElementById('resendCode');
    const timerEl = document.getElementById('resendTimer');
    
    resendButton.disabled = true;
    
    const timer = setInterval(() => {
        timerEl.textContent = timeLeft;
        
        if (timeLeft <= 0) {
            clearInterval(timer);
            resendButton.disabled = false;
            resendButton.textContent = 'Tekrar Gönder';
        }
        timeLeft--;
    }, 1000);
}

// Form submit olayı
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Form verilerini konsola yazdır (debug için)
    console.log("Form verileri:");
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    fetch('register.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log("Response status:", response.status);
        return response.json();
    })
    .then(data => {
        console.log("Response data:", data);
        
        if (data.success) {
            // Başarılı ise doğrulama popup'ını göster
            document.getElementById('verificationPopup').style.display = 'block';
            startMainTimer();
            startResendTimer();
            
            // İlk input'a odaklan
            const inputs = document.querySelectorAll('.code-input');
            if (inputs.length > 0) {
                inputs[0].focus();
            }
            
            // Başarı mesajını göster
            alert(data.message);
        } else {
            // Başarısız ise hata mesajını göster
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    });
});

// Doğrulama kodu input olayları
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.code-input');
    
    inputs.forEach((input, index) => {
        input.addEventListener('focus', function() {
            this.select();
        });

        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            
            if (this.value.length === 1) {
                if (index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            }
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });

    // Doğrula butonuna tıklandığında
    document.getElementById('verifyButton').addEventListener('click', function() {
        const code = Array.from(inputs).map(input => input.value).join('');
        
        if (code.length === 6) {
            fetch('verify-register-code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ code: code })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Verification response:', data);
                
                if (data.success) {
                    alert(data.message);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    alert(data.message || 'Doğrulama başarısız oldu.');
                    inputs.forEach(input => input.value = '');
                    inputs[0].focus();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            });
        } else {
            alert('Lütfen 6 haneli kodu eksiksiz girin.');
        }
    });
});
</script>
</body>
</html> 