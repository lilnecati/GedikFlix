import smtplib
import random
import string
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from datetime import datetime
import sys
import json
import logging

# Logging ayarları
logging.basicConfig(filename='email_error.log', level=logging.DEBUG)

class EmailVerification:
    def __init__(self):
        # SMTP sunucu ayarları
        self.smtp_server = "smtp.gmail.com"
        self.smtp_port = 587
        self.sender_email = "snakecollections4@gmail.com"  # Bu e-posta adresinin doğru olduğundan emin olun
        self.sender_password = "epza hubt quuq lxao"  # Bu uygulama şifresinin doğru olduğundan emin olun
        
    def send_verification_email(self, to_email, username):
        """Doğrulama kodunu e-posta ile gönderir"""
        try:
            # E-posta adresini temizle
            to_email = to_email.strip().replace("'", "").replace('"', '')
            logging.info(f"Temizlenmiş e-posta: {to_email}")
            
            verification_code = ''.join(random.choices(string.digits, k=6))
            current_year = datetime.now().year
            
            html_content = f"""
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
            </head>
            <body style="margin: 0; padding: 0; font-family: Arial, sans-serif;">
                <div style="background-color: #1a1a1a; padding: 20px; text-align: center;">
                    <h1 style="color: #901a58; margin: 0; font-size: 32px;">GedikFlix</h1>
                </div>
                
                <div style="background: linear-gradient(45deg, #901a58, #c7267e); padding: 40px 20px; text-align: center;">
                    <h2 style="color: #fff; margin-bottom: 20px; font-size: 32px;">E-POSTA DOĞRULAMA</h2>
                    <p style="color: #fff; font-size: 20px; margin: 0;">
                        Merhaba <strong>{username}</strong>
                    </p>
                    <p style="color: rgba(255,255,255,0.9); font-size: 16px;">
                        GedikFlix'e hoş geldiniz! Hesabınızı aktifleştirmek için aşağıdaki doğrulama kodunu kullanın:
                    </p>
                </div>
                
                <div style="padding: 40px 20px; text-align: center;">
                    <div style="display: inline-block; background-color: #f8f9fa; border: 2px dashed #901a58; padding: 20px 40px; border-radius: 10px;">
                        <h2 style="color: #901a58; letter-spacing: 8px; margin: 0; font-size: 36px;">{verification_code}</h2>
                        <p style="color: #999; margin-top: 10px; font-size: 14px;">Bu kod 10 dakika içinde geçerliliğini yitirecektir</p>
                    </div>
                    
                    <div style="margin-top: 40px; display: flex; justify-content: center; gap: 20px;">
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; width: 200px;">
                            <div style="font-size: 32px;">🎬</div>
                            <h3 style="color: #333; margin: 10px 0;">En İyi Filmler</h3>
                            <p style="color: #666; font-size: 14px;">Özenle seçilmiş koleksiyon</p>
                        </div>
                        
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; width: 200px;">
                            <div style="font-size: 32px;">📺</div>
                            <h3 style="color: #333; margin: 10px 0;">HD Kalite</h3>
                            <p style="color: #666; font-size: 14px;">Yüksek çözünürlük</p>
                        </div>
                        
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; width: 200px;">
                            <div style="font-size: 32px;">🌟</div>
                            <h3 style="color: #333; margin: 10px 0;">Özel İçerikler</h3>
                            <p style="color: #666; font-size: 14px;">Size özel seçkiler</p>
                        </div>
                    </div>
                </div>
                
                <div style="background-color: #fff8f6; border-left: 4px solid #901a58; padding: 15px; margin: 25px auto; max-width: 500px;">
                    <h4 style="color: #333; margin: 0 0 10px 0;">🔒 Güvenlik Bildirimi</h4>
                    <p style="color: #666; margin: 0; font-size: 14px;">
                        Bu e-postayı siz talep etmediyseniz, lütfen dikkate almayın.
                    </p>
                </div>
                
                <div style="background-color: #f8f9fa; padding: 20px; text-align: center;">
                    <p style="color: #999; font-size: 14px; margin-bottom: 10px;">
                        Bu e-posta <strong>{to_email}</strong> adresine gönderilmiştir.
                    </p>
                    <p style="color: #999; font-size: 12px; margin: 0;">
                        © {current_year} GedikFlix. Tüm hakları saklıdır.
                    </p>
                </div>
            </body>
            </html>
            """
            
            # E-posta oluşturma
            msg = MIMEMultipart('alternative')
            msg['Subject'] = "GedikFlix - E-posta Doğrulama Kodunuz"
            msg['From'] = self.sender_email
            msg['To'] = to_email
            
            msg.attach(MIMEText(html_content, 'html'))
            
            logging.info("SMTP sunucusuna bağlanılıyor...")
            # SMTP sunucusuna bağlanma ve e-posta gönderme
            with smtplib.SMTP(self.smtp_server, self.smtp_port) as server:
                logging.info("SMTP bağlantısı başlatıldı")
                server.starttls()
                logging.info("TLS başlatıldı")
                
                try:
                    server.login(self.sender_email, self.sender_password)
                    logging.info("SMTP login başarılı")
                except Exception as login_error:
                    logging.error(f"SMTP login hatası: {str(login_error)}")
                    return {'success': False, 'error': f"SMTP login hatası: {str(login_error)}"}
                
                try:
                    server.send_message(msg)
                    logging.info("E-posta başarıyla gönderildi")
                except Exception as send_error:
                    logging.error(f"E-posta gönderim hatası: {str(send_error)}")
                    return {'success': False, 'error': f"E-posta gönderim hatası: {str(send_error)}"}
            
            return {'success': True, 'code': verification_code}
            
        except Exception as e:
            logging.error(f"Genel hata: {str(e)}")
            return {'success': False, 'error': str(e)}

# Script doğrudan çalıştırıldığında
if __name__ == "__main__":
    try:
        logging.info("Script başlatıldı")
        logging.info(f"Argümanlar: {sys.argv}")
        
        if len(sys.argv) != 3:
            error_msg = 'E-posta ve kullanıcı adı gerekli'
            logging.error(error_msg)
            print(json.dumps({'success': False, 'error': error_msg}))
            sys.exit(1)
            
        email = sys.argv[1]
        username = sys.argv[2]
        
        logging.info(f"E-posta: {email}, Kullanıcı adı: {username}")
        
        verifier = EmailVerification()
        result = verifier.send_verification_email(email, username)
        
        logging.info(f"İşlem sonucu: {result}")
        print(json.dumps(result))
        
    except Exception as e:
        logging.error(f"Ana script hatası: {str(e)}")
        print(json.dumps({'success': False, 'error': str(e)}))