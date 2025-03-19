<?php
include 'includes/header.php';
?>

<main>
    <section class="contact-section">
        <div class="contact-container">
            <h1 class="gedikflix-title">İletişim</h1>
            
            <div class="contact-content">
                <div class="contact-info">
                    <h2>Bize Ulaşın</h2>
                    <p>GedikFlix ekibi olarak görüş, öneri ve sorularınız için her zaman buradayız. Aşağıdaki iletişim bilgilerinden bize ulaşabilir veya iletişim formunu doldurarak mesajınızı iletebilirsiniz.</p>
                    
                    <div class="contact-details">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h3>Adres</h3>
                                <p>Gedik Üniversitesi, Cumhuriyet Mah. İlkbahar Sok. No:1, 34876 Yakacık, Kartal/İstanbul</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h3>E-posta</h3>
                                <p>info@gedikflix.com</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-phone-alt"></i>
                            <div>
                                <h3>Telefon</h3>
                                <p>+90 (216) 452 45 85</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h3>Çalışma Saatleri</h3>
                                <p>Pazartesi - Cuma: 09:00 - 18:00</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="social-contact">
                        <h3>Sosyal Medya</h3>
                        <div class="social-links">
                            <a href="#" target="_blank"><i class="fab fa-facebook"></i></a>
                            <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
                            <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
                            <a href="#" target="_blank"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="contact-form-container">
                    <h2>İletişim Formu</h2>
                    <form class="contact-form" action="#" method="post">
                        <div class="form-group">
                            <label for="name">Adınız Soyadınız</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">E-posta Adresiniz</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Konu</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Mesajınız</label>
                            <textarea id="message" name="message" rows="5" required></textarea>
                        </div>
                        
                        <button type="submit" class="submit-button">Gönder</button>
                    </form>
                </div>
            </div>
            
            <div class="map-container">
                <h2>Konum</h2>
                <div class="map">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3016.3080866288473!2d29.196456715411782!3d40.88423537931175!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14cad90fbcac4529%3A0x8bfc7ae2bc7a390c!2sGedik%20University!5e0!3m2!1sen!2str!4v1647788876021!5m2!1sen!2str" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
include 'includes/footer.php';
?> 