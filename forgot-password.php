<?php
session_start();
include 'config/database.php';

$page_title = "Şifremi Unuttum";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    
    // E-posta doğrulama
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'E-posta adresi gerekli!']);
        exit;
    }
    
    // Kullanıcı kontrolü
    $user = getUserByEmailOrUsername($email);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Bu e-posta adresi ile kayıtlı kullanıcı bulunamadı!']);
        exit;
    }
    
    // Python script'i çağır
    $command = sprintf('python3 py/password_reset.py %s %s 2>&1',
        escapeshellarg(trim($email)),
        escapeshellarg($user['username'])
    );
    
    exec($command, $output, $return_var);
    
    // Debug için
    error_log("Command: " . $command);
    error_log("Python çıktısı: " . print_r($output, true));
    
    $result = json_decode(implode('', $output), true);
    
    if ($result && isset($result['success']) && $result['success']) {
        // Geçici kullanıcı bilgilerini session'da sakla
        $_SESSION['reset_user'] = [
            'user_id' => $user['id'],
            'email' => $email,
            'username' => $user['username'],
            'reset_code' => $result['code'],
            'expires' => time() + 600 // 10 dakika
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Şifre sıfırlama kodu e-posta adresinize gönderildi.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'E-posta gönderimi başarısız oldu: ' . ($result['error'] ?? 'Bilinmeyen bir hata oluştu')
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
            <h2>Şifremi Unuttum</h2>
        </div>

        <div class="form-container">
            <form id="forgotPasswordForm">
                <p class="info-text">E-posta adresinizi giriniz. Size şifre sıfırlama kodu göndereceğiz.</p>
                
                <div class="input-group">
                    <input type="email" id="email" name="email" required>
                    <label for="email">E-posta</label>
                </div>

                <button type="submit" class="login-button">Şifre Sıfırlama Kodu Gönder</button>

                <div class="form-links">
                    <a href="login.php">Giriş sayfasına dön</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Doğrulama Popup'ı -->
<div id="verificationPopup" class="popup" style="display: none;">
    <div class="popup-content">
        <h2>Şifre Sıfırlama Kodu</h2>
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
document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('forgot-password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log("Server response:", data);
        
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
        }
        
        // Mesajı göster
        alert(data.message);
    })
    .catch(error => {
        console.error('Error:', error);
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
            fetch('verify-reset-code.php', {
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

    // Yeni kod gönderme butonu
    document.getElementById('resendCode').addEventListener('click', function() {
        const emailInput = document.getElementById('email');
        const email = emailInput.value;
        
        if (!email) {
            alert('Lütfen e-posta adresinizi girin.');
            return;
        }
        
        // Yeni kod gönderme işlemi
        fetch('forgot-password.php', {
            method: 'POST',
            body: new FormData(document.getElementById('forgotPasswordForm'))
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Yeni doğrulama kodu gönderildi.');
                startResendTimer();
            } else {
                alert(data.message || 'Kod gönderimi başarısız oldu.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Kod gönderimi sırasında bir hata oluştu.');
        });
    });
});
</script>
</body>
</html>
