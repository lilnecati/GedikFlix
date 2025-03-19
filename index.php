<?php
session_start();
include 'config/database.php';

// Site istatistiklerini al
$statistics = getSiteStatistics();
$movie_count = $statistics['movie_count'];
$category_count = $statistics['category_count'];
$user_count = $statistics['user_count'];

$page_title = "Ana Sayfa";

// Öne çıkan filmleri al
$featured_movies = getFeaturedMovies();
$categories = getAllCategories();

// Rastgele 4 film seçelim (trend olanlar için)
$all_movies = getAllMovies();
shuffle($all_movies);
$trending_movies = array_slice($all_movies, 0, 4);

// En son eklenen 6 filmi alalım
usort($all_movies, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$latest_movies = array_slice($all_movies, 0, 6);

// Yeni gelen popüler oyuncular
$popular_actors = [
    ['name' => 'Leonardo DiCaprio', 'image' => 'https://images.unsplash.com/photo-1580130379624-3a069adbffc2?q=80&w=1000&auto=format&fit=crop', 'movies' => '25'],
    ['name' => 'Scarlett Johansson', 'image' => 'https://images.unsplash.com/photo-1580130379624-3a069adbffc2?q=80&w=1000&auto=format&fit=crop', 'movies' => '22'],
    ['name' => 'Tom Hanks', 'image' => 'https://images.unsplash.com/photo-1580130379624-3a069adbffc2?q=80&w=1000&auto=format&fit=crop', 'movies' => '30'],
    ['name' => 'Emma Stone', 'image' => 'https://images.unsplash.com/photo-1580130379624-3a069adbffc2?q=80&w=1000&auto=format&fit=crop', 'movies' => '18']
];

// Rastgele yorumlar
$testimonials = [
    ['name' => 'Ahmet Y.', 'rating' => 5, 'comment' => 'GedikFlix sayesinde tüm film merakımı karşılayabiliyorum. Premium üyeliğe geçiş yaparak reklamlardan da kurtuldum!', 'date' => '2 gün önce'],
    ['name' => 'Ayşe K.', 'rating' => 4, 'comment' => 'Film kategorileri çok çeşitli, özellikle bilim kurgu seçkisi harika. Daha fazla animasyon filmi eklenebilir.', 'date' => '1 hafta önce'],
    ['name' => 'Mehmet S.', 'rating' => 5, 'comment' => 'Kullanıcı dostu arayüz ve yüksek kaliteli içerik. Arkadaşlarıma tavsiye ediyorum.', 'date' => '3 gün önce']
];

include 'includes/header.php';
?>

<link rel="stylesheet" href="css/index.css">

<main>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="animation-container">
                <h1 class="animate-title">GedikFlix'e <span class="highlight">Hoş Geldiniz</span></h1>
                <p class="animate-subtitle">En iyi filmler, diziler ve daha fazlası...</p>
                <div class="cta-buttons animate-buttons">
                    <a href="movies.php" class="cta-button primary">Filmleri Keşfet</a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="register.php" class="cta-button secondary">Üye Ol</a>
                    <?php endif; ?>
                </div>
                
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number" data-count="<?php echo is_numeric($movie_count) ? $movie_count : 0; ?>"><?php echo is_numeric($movie_count) ? number_format($movie_count) : 0; ?></span>
                        <span class="stat-text">Film</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" data-count="<?php echo is_numeric($category_count) ? $category_count : 0; ?>"><?php echo is_numeric($category_count) ? number_format($category_count) : 0; ?></span>
                        <span class="stat-text">Kategori</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" data-count="<?php echo is_numeric($user_count) ? $user_count : 0; ?>"><?php echo is_numeric($user_count) ? number_format($user_count) : 0; ?></span>
                        <span class="stat-text">Üye</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="scroll-down">
            <a href="#featured">
                <i class="fas fa-chevron-down"></i>
            </a>
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
                                    <span class="movie-category"><?php echo $movie['category']['name']; ?></span>
                                    <span class="movie-duration"><?php echo $movie['duration']; ?> dk</span>
                                </div>
                                <a href="movie.php?id=<?php echo $movie['id']; ?>" class="watch-button"><i class="fas fa-play"></i> İzle</a>
                            </div>
                        </div>
                        <div class="movie-info">
                            <h3><?php echo $movie['title']; ?></h3>
                            <div class="movie-meta">
                                <span class="year"><?php echo $movie['year']; ?></span>
                                <span class="rating"><i class="fas fa-star"></i> 8.5</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="slider-arrow next"><i class="fas fa-chevron-right"></i></button>
        </div>
    </section>
    
    <!-- Trend Filmler -->
    <section class="trending-section">
        <div class="section-header">
            <h2><i class="fas fa-fire"></i> Trend Filmler</h2>
            <a href="movies.php" class="view-all">Tümünü Gör <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="trending-container">
            <?php foreach ($trending_movies as $index => $movie): ?>
                <div class="trending-card" style="--delay: <?php echo $index * 0.1; ?>s">
                    <div class="trending-number"><?php echo $index + 1; ?></div>
                    <div class="trending-poster">
                        <img src="<?php echo $movie['poster_url']; ?>" alt="<?php echo $movie['title']; ?>">
                    </div>
                    <div class="trending-info">
                        <h3><?php echo $movie['title']; ?></h3>
                        <p class="trending-meta">
                            <span class="trending-year"><?php echo $movie['year']; ?></span>
                            <span class="trending-duration"><?php echo $movie['duration']; ?> dk</span>
                        </p>
                        <p class="trending-desc"><?php echo substr($movie['description'], 0, 100); ?>...</p>
                        <a href="movie.php?id=<?php echo $movie['id']; ?>" class="watch-now-btn">
                            <i class="fas fa-play-circle"></i> Hemen İzle
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Kategoriler -->
    <section class="categories">
        <div class="section-header">
            <h2><i class="fas fa-list"></i> Kategoriler</h2>
            <a href="categories.php" class="view-all">Tümünü Gör <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="category-grid">
            <?php foreach ($categories as $category): ?>
                <a href="movies.php?category=<?php echo $category['id']; ?>" class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-film"></i>
                    </div>
                    <div class="category-content">
                        <h3><?php echo $category['name']; ?></h3>
                        <p><?php echo $category['description']; ?></p>
                        <span class="movie-count"><?php echo getMovieCountByCategory($category['id']); ?> Film</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Son Eklenen Filmler -->
    <section class="latest-movies">
        <div class="section-header">
            <h2><i class="fas fa-clock"></i> Son Eklenen Filmler</h2>
            <a href="movies.php" class="view-all">Tümünü Gör <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="latest-grid">
            <?php foreach ($latest_movies as $movie): ?>
                <div class="latest-card">
                    <div class="latest-poster">
                        <img src="<?php echo $movie['poster_url']; ?>" alt="<?php echo $movie['title']; ?>">
                        <div class="latest-date">Yeni</div>
                    </div>
                    <div class="latest-info">
                        <h3><?php echo $movie['title']; ?></h3>
                        <p><?php echo $movie['year']; ?></p>
                        <a href="movie.php?id=<?php echo $movie['id']; ?>" class="watch-button">İzle</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Üyelik Planları -->
    <section class="membership-plans">
        <div class="section-header center">
            <h2><i class="fas fa-crown"></i> Üyelik Planları</h2>
            <p>Size en uygun planı seçin ve sınırsız film keyfine başlayın</p>
        </div>
        
        <div class="plans-container">
            <div class="plan-card basic">
                <div class="plan-header">
                    <h3>Temel</h3>
                    <div class="plan-price">
                        <span class="currency">₺</span>
                        <span class="amount">29</span>
                        <span class="period">/ay</span>
                    </div>
                </div>
                <div class="plan-features">
                    <div class="feature"><i class="fas fa-check"></i> HD içerik</div>
                    <div class="feature"><i class="fas fa-check"></i> 1 cihazda izleme</div>
                    <div class="feature"><i class="fas fa-check"></i> Film koleksiyonu</div>
                    <div class="feature disabled"><i class="fas fa-times"></i> Reklamsız deneyim</div>
                    <div class="feature disabled"><i class="fas fa-times"></i> 4K içerik</div>
                    <div class="feature disabled"><i class="fas fa-times"></i> Çevrimdışı izleme</div>
                </div>
                <a href="#" class="plan-button">Şimdi Başla</a>
            </div>
            
            <div class="plan-card standard recommended">
                <div class="recommended-badge">Önerilen</div>
                <div class="plan-header">
                    <h3>Standart</h3>
                    <div class="plan-price">
                        <span class="currency">₺</span>
                        <span class="amount">49</span>
                        <span class="period">/ay</span>
                    </div>
                </div>
                <div class="plan-features">
                    <div class="feature"><i class="fas fa-check"></i> HD içerik</div>
                    <div class="feature"><i class="fas fa-check"></i> 2 cihazda izleme</div>
                    <div class="feature"><i class="fas fa-check"></i> Film koleksiyonu</div>
                    <div class="feature"><i class="fas fa-check"></i> Reklamsız deneyim</div>
                    <div class="feature disabled"><i class="fas fa-times"></i> 4K içerik</div>
                    <div class="feature disabled"><i class="fas fa-times"></i> Çevrimdışı izleme</div>
                </div>
                <a href="#" class="plan-button">Şimdi Başla</a>
            </div>
            
            <div class="plan-card premium">
                <div class="plan-header">
                    <h3>Premium</h3>
                    <div class="plan-price">
                        <span class="currency">₺</span>
                        <span class="amount">79</span>
                        <span class="period">/ay</span>
                    </div>
                </div>
                <div class="plan-features">
                    <div class="feature"><i class="fas fa-check"></i> HD içerik</div>
                    <div class="feature"><i class="fas fa-check"></i> 4 cihazda izleme</div>
                    <div class="feature"><i class="fas fa-check"></i> Film koleksiyonu</div>
                    <div class="feature"><i class="fas fa-check"></i> Reklamsız deneyim</div>
                    <div class="feature"><i class="fas fa-check"></i> 4K içerik</div>
                    <div class="feature"><i class="fas fa-check"></i> Çevrimdışı izleme</div>
                </div>
                <a href="#" class="plan-button">Şimdi Başla</a>
            </div>
        </div>
    </section>
    
    <!-- Popüler Oyuncular -->
    <section class="popular-actors">
        <div class="section-header">
            <h2><i class="fas fa-users"></i> Popüler Oyuncular</h2>
            <a href="#" class="view-all">Tümünü Gör <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="actors-container">
            <?php foreach ($popular_actors as $actor): ?>
                <div class="actor-card">
                    <div class="actor-image">
                        <img src="<?php echo $actor['image']; ?>" alt="<?php echo $actor['name']; ?>">
                    </div>
                    <div class="actor-info">
                        <h3><?php echo $actor['name']; ?></h3>
                        <p><span class="movie-count"><?php echo $actor['movies']; ?> Film</span></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Kullanıcı Yorumları -->
    <section class="testimonials">
        <div class="section-header center">
            <h2><i class="fas fa-comment-alt"></i> Kullanıcı Yorumları</h2>
            <p>Üyelerimizin GedikFlix hakkında düşünceleri</p>
        </div>
        
        <div class="testimonials-container">
            <?php foreach ($testimonials as $testimonial): ?>
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $testimonial['rating'] ? 'filled' : ''; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="testimonial-content">
                        <p>"<?php echo $testimonial['comment']; ?>"</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-name"><?php echo $testimonial['name']; ?></div>
                        <div class="testimonial-date"><?php echo $testimonial['date']; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Mobil Uygulama -->
    <section class="app-download">
        <div class="app-content">
            <div class="app-text">
                <h2>GedikFlix Uygulaması ile Her Yerde Film Keyfi</h2>
                <p>Mobil uygulamamızı indirin, çevrimdışı izleme özelliği ile internet olmadan da film keyfinize devam edin.</p>
                <div class="app-buttons">
                    <a href="#" class="app-button">
                        <i class="fab fa-apple"></i>
                        <div class="app-button-text">
                            <span class="app-button-small">İndir</span>
                            <span class="app-button-big">App Store</span>
                        </div>
                    </a>
                    <a href="#" class="app-button">
                        <i class="fab fa-google-play"></i>
                        <div class="app-button-text">
                            <span class="app-button-small">İndir</span>
                            <span class="app-button-big">Google Play</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="app-image">
                <img src="images/app-mockup.png" alt="GedikFlix Mobil Uygulama">
            </div>
        </div>
    </section>
    
    <!-- Bülten Aboneliği -->
    <section class="newsletter">
        <div class="newsletter-container">
            <div class="newsletter-content">
                <h2>Yeni Filmlerden Haberdar Olun</h2>
                <p>Haftalık film önerileri ve yeni eklenen içeriklerden haberdar olmak için bültenimize abone olun.</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="E-posta adresiniz" required>
                    <button type="submit">Abone Ol</button>
                </form>
            </div>
            <div class="newsletter-decoration">
                <i class="fas fa-envelope"></i>
            </div>
        </div>
    </section>
</main>

<!-- Animasyon ve interaktif özellikler için JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hero bölümündeki animasyonlar
    setTimeout(() => {
        document.querySelector('.animate-title').classList.add('visible');
        setTimeout(() => {
            document.querySelector('.animate-subtitle').classList.add('visible');
            setTimeout(() => {
                document.querySelector('.animate-buttons').classList.add('visible');
                setTimeout(() => {
                    document.querySelector('.hero-stats').classList.add('visible');
                    // Sayaç animasyonu
                    startCounters();
                }, 400);
            }, 400);
        }, 400);
    }, 500);
    
    // İstatistik sayaçları
    function startCounters() {
        const counters = document.querySelectorAll('.stat-number');
        
        counters.forEach(counter => {
            // data-count özelliğini güvenli bir şekilde alıp sayıya dönüştürüyoruz
            const targetStr = counter.getAttribute('data-count') || '0';
            const target = parseInt(targetStr) || 0; // Geçersiz değer varsa 0 kullanıyoruz
            
            if (target <= 0) {
                counter.textContent = '0'; // Hedef 0 veya negatifse doğrudan 0 göster
                return;
            }
            
            const duration = 2000; // ms cinsinden
            const step = target / (duration / 16); // 60fps için
            
            let current = 0;
            const updateCounter = () => {
                current += step;
                if (current < target) {
                    counter.textContent = Math.floor(current).toLocaleString();
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target.toLocaleString();
                }
            };
            
            updateCounter();
        });
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
        '.section-header, .movie-card, .trending-card, .category-card, .latest-card, ' +
        '.plan-card, .actor-card, .testimonial-card, .app-content, .newsletter-container'
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
        
        /* Animasyon stillerini ekleyelim */
        .animate-title, .animate-subtitle, .animate-buttons, .hero-stats {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        
        .animate-title.visible, .animate-subtitle.visible, .animate-buttons.visible, .hero-stats.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Görünürlük animasyonları */
        .section-header, .movie-card, .trending-card, .category-card, .latest-card,
        .plan-card, .actor-card, .testimonial-card, .app-content, .newsletter-container {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        
        .section-header.in-view, .movie-card.in-view, .trending-card.in-view, .category-card.in-view, .latest-card.in-view,
        .plan-card.in-view, .actor-card.in-view, .testimonial-card.in-view, .app-content.in-view, .newsletter-container.in-view {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Gecikme animasyonları */
        .trending-card {
            transition-delay: var(--delay, 0s);
        }
    `;
    document.head.appendChild(style);
});
</script>

<?php include 'includes/footer.php'; ?> 