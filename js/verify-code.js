document.addEventListener('DOMContentLoaded', function() {
    const popup = document.getElementById('verificationPopup');
    const closeBtn = document.querySelector('.close-popup');
    const inputs = document.querySelectorAll('.code-input');
    const verifyButton = document.getElementById('verifyButton');
    const resendButton = document.getElementById('resendCode');
    const countdownEl = document.getElementById('countdown');
    const resendTimerEl = document.getElementById('resendTimer');

    let mainTimer;
    let resendTimer;

    // Doğrulama butonu olayı
    verifyButton.addEventListener('click', function() {
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
                console.log('Verification response:', data); // Debug için
                
                if (data.success) {
                    alert(data.message);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    alert(data.message || 'Doğrulama başarısız oldu.');
                    // Input alanlarını temizle
                    inputs.forEach(input => input.value = '');
                    inputs[0].focus();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Doğrulama sırasında bir hata oluştu. Lütfen tekrar deneyin.');
            });
        } else {
            alert('Lütfen 6 haneli kodu eksiksiz girin.');
        }
    });

    // Input olayları
    inputs.forEach((input, index) => {
        // Her input'a focuslanıldığında içeriği seç
        input.addEventListener('focus', function() {
            this.select();
        });

        // Input değeri değiştiğinde
        input.addEventListener('input', function(e) {
            // Sadece sayısal değer kabul et
            this.value = this.value.replace(/[^0-9]/g, '');
            
            if (this.value.length === 1) {
                // Sonraki input'a geç
                if (index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            }
        });

        // Backspace tuşu için önceki input'a geç
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });

    // Ana sayaç
    function startMainTimer() {
        let timeLeft = 600; // 10 dakika
        
        if (mainTimer) clearInterval(mainTimer);
        
        mainTimer = setInterval(() => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(mainTimer);
                popup.style.display = 'none';
                alert('Doğrulama kodunun süresi doldu. Lütfen yeni kod talep edin.');
            }
            timeLeft--;
        }, 1000);
    }

    // Tekrar gönderme sayacı
    function startResendTimer() {
        let timeLeft = 60;
        resendButton.disabled = true;
        
        if (resendTimer) clearInterval(resendTimer);
        
        resendTimer = setInterval(() => {
            resendTimerEl.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(resendTimer);
                resendButton.disabled = false;
                resendButton.textContent = 'Tekrar Gönder';
            }
            timeLeft--;
        }, 1000);
    }

    // Popup'ı göster fonksiyonunu güncelle
    function showVerificationPopup() {
        const popup = document.getElementById('verificationPopup');
        if (popup) {
            popup.style.display = 'block';
            const inputs = document.querySelectorAll('.code-input');
            if (inputs.length > 0) {
                inputs[0].focus();
            }
            startMainTimer();
            startResendTimer();
        }
    }

    // Global fonksiyon olarak tanımla
    window.showVerificationPopup = showVerificationPopup;

    // Yeni kod gönderme butonu
    resendButton.addEventListener('click', function() {
        // Yeni kod gönderme işlemi
        fetch('register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ resend: true })
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