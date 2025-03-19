# GedikFlix - Film İzleme Platformu

GedikFlix, modern ve kullanıcı dostu bir film izleme platformudur. PHP, MySQL ve HTML/CSS kullanılarak geliştirilmiştir.

## Özellikler

- Kullanıcı kaydı ve girişi
- Film kategorileri
- Film arama
- Film detay sayfaları
- Video oynatıcı
- Responsive tasarım
- Modern ve kullanıcı dostu arayüz

## Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Web sunucusu (Apache/Nginx)
- mod_rewrite etkin (Apache için)

## Kurulum

1. Projeyi klonlayın:
```bash
git clone https://github.com/kullaniciadi/gedikflix.git
```

2. Veritabanını oluşturun:
- MySQL'de yeni bir veritabanı oluşturun
- `database.sql` dosyasını içe aktarın

3. Veritabanı bağlantı ayarlarını yapın:
- `config/database.php` dosyasını düzenleyin
- Veritabanı bilgilerinizi girin:
  ```php
  $servername = "localhost";
  $username = "kullanici_adi";
  $password = "sifre";
  $dbname = "gedikflix";
  ```

4. Dosya izinlerini ayarlayın:
```bash
chmod 755 -R /path/to/gedikflix
chmod 777 -R /path/to/gedikflix/uploads
```

5. Web sunucunuzu yapılandırın:
- Apache için `.htaccess` dosyasını etkinleştirin
- Nginx için uygun yapılandırmayı ekleyin

## Kullanım

1. Web tarayıcınızda `http://localhost/gedikflix` adresine gidin
2. Yeni bir hesap oluşturun veya mevcut hesabınızla giriş yapın
3. Filmleri keşfedin ve izlemeye başlayın

## Güvenlik

- Tüm kullanıcı girişleri doğrulanır ve temizlenir
- Şifreler güvenli bir şekilde hashlenir
- SQL injection koruması
- XSS koruması

## Katkıda Bulunma

1. Bu depoyu fork edin
2. Yeni bir branch oluşturun (`git checkout -b feature/yeniOzellik`)
3. Değişikliklerinizi commit edin (`git commit -am 'Yeni özellik eklendi'`)
4. Branch'inizi push edin (`git push origin feature/yeniOzellik`)
5. Pull Request oluşturun

## Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için `LICENSE` dosyasına bakın.

## İletişim

- Website: [www.gedikflix.com](http://www.gedikflix.com)
- Email: info@gedikflix.com
- Twitter: [@gedikflix](https://twitter.com/gedikflix)
- Facebook: [GedikFlix](https://facebook.com/gedikflix) 