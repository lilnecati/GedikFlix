<?php
session_start();
include 'config/database.php';
// functions.php dosyasını dahil etmeye gerek yok, tüm fonksiyonlar database.php'de

// Site istatistiklerini al
$statistics = getSiteStatistics();
$movie_count = $statistics['movie_count'];
$category_count = $statistics['category_count'];
$user_count = $statistics['user_count'];

$page_title = "GedikFlix - Ana Sayfa";

// Öne çıkan filmleri al
$featured_movies = getFeaturedMovies();
$categories = getAllCategories();

include 'includes/header.php';
?>

<link rel="stylesheet" href="css/index.css">

<main>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-overlay"></div>
        
        <div class="hero-content">
            <h1>GedikFlix'e <span class="highlight">Hoş Geldiniz</span></h1>
            <p>En yeni ve popüler filmleri keşfedin. Sınırsız eğlence için hemen üye olun!</p>
            <div class="cta-buttons">
                <a href="register.php" class="cta-button primary">Ücretsiz Üye Ol</a>
                <a href="#featured" class="cta-button secondary">Filmleri Keşfet</a>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $movie_count; ?>+</span>
                    <span class="stat-text">Film</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $category_count; ?>+</span>
                    <span class="stat-text">Kategori</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $user_count; ?>+</span>
                    <span class="stat-text">Kullanıcı</span>
                </div>
            </div>
        </div>

        <div class="hero-posters">
            <?php 
            // database.json'dan filmleri al
            $json_data = file_get_contents('data/database.json');
            $data = json_decode($json_data, true);
            $random_movies = $data['movies'];
            
            // Rastgele karıştırma
            shuffle($random_movies);
            
            // Hero için film gösterimi
            $poster_movies = array_slice($random_movies, 0, 20);
            
            // İkinci sıra için farklı filmler
            shuffle($random_movies);
            $poster_movies_2 = array_slice($random_movies, 0, 20);
            ?>
            <!-- Sola kayan üst sıra -->
            <div class="poster-row">
                <?php foreach ($poster_movies as $movie): ?>
                <div class="hero-poster">
                    <a href="movie.php?id=<?php echo $movie['id']; ?>">
                        <img src="<?php echo $movie['poster_url']; ?>" alt="<?php echo $movie['title']; ?>">
                        <div class="poster-overlay">
                            <div class="poster-info">
                                <h3><?php echo $movie['title']; ?></h3>
                                <div class="poster-meta">
                                    <span class="year"><?php echo $movie['year']; ?></span> 
                                    <span class="duration"><?php echo $movie['duration']; ?> dk</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
                <?php foreach ($poster_movies as $movie): ?>
                <div class="hero-poster">
                    <a href="movie.php?id=<?php echo $movie['id']; ?>">
                        <img src="<?php echo $movie['poster_url']; ?>" alt="<?php echo $movie['title']; ?>">
                        <div class="poster-overlay">
                            <div class="poster-info">
                                <h3><?php echo $movie['title']; ?></h3>
                                <div class="poster-meta">
                                    <span class="year"><?php echo $movie['year']; ?></span> 
                                    <span class="duration"><?php echo $movie['duration']; ?> dk</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Sağa kayan alt sıra -->
            <div class="poster-row poster-row-reverse">
                <?php foreach ($poster_movies_2 as $movie): ?>
                <div class="hero-poster">
                    <a href="movie.php?id=<?php echo $movie['id']; ?>">
                        <img src="<?php echo $movie['poster_url']; ?>" alt="<?php echo $movie['title']; ?>">
                        <div class="poster-overlay">
                            <div class="poster-info">
                                <h3><?php echo $movie['title']; ?></h3>
                                <div class="poster-meta">
                                    <span class="year"><?php echo $movie['year']; ?></span> 
                                    <span class="duration"><?php echo $movie['duration']; ?> dk</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
                <?php foreach ($poster_movies_2 as $movie): ?>
                <div class="hero-poster">
                    <a href="movie.php?id=<?php echo $movie['id']; ?>">
                        <img src="<?php echo $movie['poster_url']; ?>" alt="<?php echo $movie['title']; ?>">
                        <div class="poster-overlay">
                            <div class="poster-info">
                                <h3><?php echo $movie['title']; ?></h3>
                                <div class="poster-meta">
                                    <span class="year"><?php echo $movie['year']; ?></span> 
                                    <span class="duration"><?php echo $movie['duration']; ?> dk</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="scroll-down">
            <a href="#featured"><i class="fas fa-chevron-down"></i></a>
        </div>
    </section>

    <!-- Öne Çıkan Filmler -->
    <section id="featured" class="featured-movies">
        <div class="section-header">
            <h2><i class="fas fa-star"></i> Öne Çıkan Filmler</h2>
            <a href="movies.php" class="view-all">Tümünü Gör <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="movie-slider">
            <button class="slider-arrow prev"><i class="fas fa-chevron-left"></i></button>
            <div class="movie-grid">
                <?php foreach ($featured_movies as $movie): ?>
                <div class="movie-card">
                    <div class="movie-poster">
                        <img src="<?php echo $movie['poster_url']; ?>" alt="<?php echo $movie['title']; ?>">
                        <div class="movie-overlay">
                            <div class="movie-details">
                                <span class="movie-category">
                                    <?php 
                                    // Kategori bir dizi ise, dizi üzerinden name değerine erişelim
                                    // Değilse doğrudan değeri kullanacağız
                                    echo is_array($movie['category']) ? $movie['category']['name'] : $movie['category']; 
                                    ?>
                                </span>
                                <span class="movie-duration"><?php echo $movie['duration']; ?> dk</span>
                            </div>
                            <a href="movie.php?id=<?php echo $movie['id']; ?>" class="watch-button">
                                <i class="fas fa-play"></i> İzle
                            </a>
                        </div>
                    </div>
                    <div class="movie-info">
                        <h3><?php echo $movie['title']; ?></h3>
                        <div class="movie-meta">
                            <span class="year"><?php echo $movie['year']; ?></span>
                            <span class="rating">
                                <i class="fas fa-star"></i>
                                <?php 
                                // Rating değerinin tanımlı olup olmadığını kontrol edelim
                                echo isset($movie['rating']) ? number_format($movie['rating'], 1) : '8.5'; 
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="slider-arrow next"><i class="fas fa-chevron-right"></i></button>
        </div>
    </section>

    <!-- Kategoriler -->
    <section id="categories" class="categories section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-list"></i> Kategoriler</h2>
                <p class="section-subtitle">En sevdiğiniz türdeki filmleri keşfedin</p>
            </div>
            <div class="category-grid">
                <?php foreach ($categories as $category): 
                    // Her kategori için film sayısını hesapla - artık database.php'deki getCategoryMovieCount fonksiyonunu kullanabiliriz
                    $category_movies_count = getCategoryMovieCount($category['id']);
                ?>
                <a href="category.php?id=<?php echo $category['id']; ?>" class="category-card">
                    <div class="category-icon">
                        <?php
                        // Kategori adına göre ikon belirleme
                        $icon = 'film';
                        switch($category['name']) {
                            case 'Bilim Kurgu': $icon = 'rocket'; break;
                            case 'Aksiyon': $icon = 'fire'; break;
                            case 'Suç': $icon = 'video'; break;
                            case 'Dram': $icon = 'video'; break;
                            case 'Fantastik': $icon = 'video'; break;
                            case 'Gerilim': $icon = 'video'; break;
                            case 'Komedi': $icon = 'smile'; break;
                            case 'Western': $icon = 'video'; break;
                            default: $icon = 'video';
                        }
                        ?>
                        <i class="fas fa-<?php echo $icon; ?>"></i>
                    </div>
                    <div class="category-content">
                        <h3><?php echo $category['name']; ?></h3>
                        <span class="movie-count">
                            <i class="fas fa-film"></i>
                            <?php echo $category_movies_count; ?> Film
                        </span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Üyelik Planları -->
    <section class="membership-plans">
        <div class="section-header center">
            <h2><i class="fas fa-crown"></i> Üyelik Planları</h2>
            <p>Size en uygun planı seçin ve sınırsız eğlenceye başlayın</p>
        </div>
        <div class="plans-container">
            <!-- Basic Plan -->
            <div class="plan-card">
                <div class="plan-header">
                    <h3>Basic</h3>
                    <div class="plan-price">
                        <span class="currency">₺</span>
                        <span class="amount">29</span>
                        <span class="period">/ay</span>
                    </div>
                </div>
                <div class="plan-features">
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>HD İçerik</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>1 Cihazda İzleme</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-times"></i>
                        <span>Reklamsız İzleme</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-times"></i>
                        <span>Çevrimdışı İzleme</span>
                    </div>
                </div>
                <a href="register.php" class="plan-button">Hemen Başla</a>
            </div>

            <!-- Standard Plan -->
            <div class="plan-card recommended">
                <div class="recommended-badge">En Popüler</div>
                <div class="plan-header">
                    <h3>Standard</h3>
                    <div class="plan-price">
                        <span class="currency">₺</span>
                        <span class="amount">49</span>
                        <span class="period">/ay</span>
                    </div>
                </div>
                <div class="plan-features">
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>Full HD İçerik</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>2 Cihazda İzleme</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>Reklamsız İzleme</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-times"></i>
                        <span>Çevrimdışı İzleme</span>
                    </div>
                </div>
                <a href="register.php" class="plan-button">Hemen Başla</a>
            </div>

            <!-- Premium Plan -->
            <div class="plan-card">
                <div class="plan-header">
                    <h3>Premium</h3>
                    <div class="plan-price">
                        <span class="currency">₺</span>
                        <span class="amount">79</span>
                        <span class="period">/ay</span>
                    </div>
                </div>
                <div class="plan-features">
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>4K İçerik</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>4 Cihazda İzleme</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>Reklamsız İzleme</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>Çevrimdışı İzleme</span>
                    </div>
                </div>
                <a href="register.php" class="plan-button">Hemen Başla</a>
            </div>
        </div>
    </section>
    
    <!-- Mobil Uygulama -->
    <section class="app-download">
        <div class="app-content">
            <div class="app-text">
                <h2>Mobil Uygulamamızı İndirin</h2>
                <p>GedikFlix'i her yerde izleyin. iOS ve Android cihazlarınız için özel uygulamamızı hemen indirin.</p>
                <div class="app-buttons">
                    <a href="#" class="app-button">
                        <i class="fab fa-apple"></i>
                        <div class="app-button-text">
                            <span class="app-button-small">Download on the</span>
                            <span class="app-button-big">App Store</span>
                        </div>
                    </a>
                    <a href="#" class="app-button">
                        <i class="fab fa-google-play"></i>
                        <div class="app-button-text">
                            <span class="app-button-small">GET IT ON</span>
                            <span class="app-button-big">Google Play</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="app-image">
                <img src="images/app-preview.png" alt="GedikFlix Mobile App">
            </div>
        </div>
    </section>
    
    <!-- Bülten Aboneliği -->
    <section class="newsletter">
        <div class="newsletter-container">
            <div class="newsletter-content">
                <h2>Bültenimize Abone Olun</h2>
                <p>En yeni filmler ve özel içeriklerden ilk siz haberdar olun!</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="E-posta adresiniz" required>
                    <button type="submit">Abone Ol</button>
                </form>
            </div>
            <div class="newsletter-decoration">
                <i class="fas fa-envelope-open-text"></i>
            </div>
        </div>
    </section>
</main>

<!-- Animasyon ve interaktif özellikler için JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hero bölümündeki animasyonları kaldırdık, çünkü ilgili sınıflar artık yok
    // Doğrudan sayaç animasyonunu başlatıyoruz
    startCounters();
    
    // İstatistik sayaçları
    function startCounters() {
        const counters = document.querySelectorAll('.stat-number');
        
        counters.forEach(counter => {
            // Doğrudan mevcut metin içeriğini kullanıyoruz
            const textContent = counter.textContent;
            // Sayısal bir değer varsa animasyon yapabiliriz
            if (textContent.includes('+')) {
                const baseValue = parseInt(textContent.replace(/\D/g, ''));
                if (!isNaN(baseValue)) {
                    // Sayı değerini animasyon ile göster
                    animateValue(counter, 0, baseValue, 2000);
                }
            }
        });
    }
    
    // Sayı animasyonu fonksiyonu
    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            element.textContent = value + '+';
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }
    
    // Film Slider'ı için oklar
    const prevButton = document.querySelector('.slider-arrow.prev');
    const nextButton = document.querySelector('.slider-arrow.next');
    const movieGrid = document.querySelector('.movie-grid');
    
    if (prevButton && nextButton && movieGrid) {
        let scrollAmount = 0;
        const scrollStep = 300;
        
        prevButton.addEventListener('click', () => {
            scrollAmount = Math.max(scrollAmount - scrollStep, 0);
            movieGrid.scrollTo({
                left: scrollAmount,
                behavior: 'smooth'
            });
        });
        
        nextButton.addEventListener('click', () => {
            scrollAmount = Math.min(scrollAmount + scrollStep, movieGrid.scrollWidth - movieGrid.clientWidth);
            movieGrid.scrollTo({
                left: scrollAmount,
                behavior: 'smooth'
            });
        });
    }
    
    // Scroll Down butonu
    const scrollDownBtn = document.querySelector('.scroll-down a');
    if (scrollDownBtn) {
        scrollDownBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = scrollDownBtn.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    }
    
    // Görünürlük animasyonları için Intersection Observer
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };
    
    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in-view');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Animasyon uygulanacak elementler
    const animatedElements = document.querySelectorAll(
        '.section-header, .movie-card, .category-card, .plan-card, ' +
        '.app-content, .newsletter-container'
    );
    
    animatedElements.forEach(element => {
        observer.observe(element);
    });
    
    // Footer'ı gizle
    const footer = document.querySelector('footer');
    if (footer) {
        footer.style.display = 'none';
    }
    
    // Sayfa CSS stillerini güncelleyelim
    const style = document.createElement('style');
    style.textContent = `
        /* Footer gizleme */
        footer {
            display: none !important;
        }
        
        /* Hero animasyonları */
        .hero-content {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 1s ease-out forwards;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Görünürlük animasyonları */
        .section-header, .movie-card, .category-card,
        .plan-card, .app-content, .newsletter-container {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        
        .section-header.in-view, .movie-card.in-view, .category-card.in-view,
        .plan-card.in-view, .app-content.in-view, .newsletter-container.in-view {
            opacity: 1;
            transform: translateY(0);
        }
    `;
    document.head.appendChild(style);
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html> 