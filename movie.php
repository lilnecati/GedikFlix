<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config/database.php';
include 'config/profile_utils.php';

// Film ID'sini kontrol et
$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$movie_id) {
    header("Location: movies.php");
    exit();
}

// Film bilgilerini al
$movie = getMovieById($movie_id);

// Film bulunamadıysa ana sayfaya yönlendir
if (!$movie) {
    $_SESSION['error'] = "Film bulunamadı.";
    header("Location: movies.php");
    exit();
}

// Yorum gönderme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    if ($rating > 0 && $rating <= 5 && !empty($comment)) {
        if (addMovieReview($movie_id, $_SESSION['user_id'], $rating, $comment)) {
            $_SESSION['success'] = "Yorumunuz başarıyla eklendi.";
        } else {
            $_SESSION['error'] = "Yorum eklenirken bir hata oluştu.";
        }
    } else {
        $_SESSION['error'] = "Lütfen geçerli bir puan ve yorum girin.";
    }
    
    header("Location: movie.php?id=" . $movie_id);
    exit();
}

// Benzer filmleri al (aynı kategorideki diğer filmler)
$similar_movies = [];
$all_movies = getAllMovies();
$category_id = $movie['category']['id'];

foreach ($all_movies as $similar) {
    if (isset($similar['category']) && $similar['category']['id'] == $category_id && $similar['id'] != $movie_id) {
        $similar_movies[] = $similar;
        if (count($similar_movies) >= 4) break; // En fazla 4 benzer film
    }
}

// Film yorumlarını ve puanını al
$reviews = getMovieReviews($movie_id);
$movie_rating = getMovieAverageRating($movie_id);
$rating_count = count($reviews);

$page_title = $movie['title'];
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/base/movie-layout.css">
<link rel="stylesheet" href="css/components/buttons.css">
<link rel="stylesheet" href="css/components/notifications.css">
<link rel="stylesheet" href="css/components/comments.css">
<link rel="stylesheet" href="css/movie.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<main>
    <div class="movie-detail">
        <div class="movie-header" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('<?php echo htmlspecialchars($movie['poster_url']); ?>');">
            <div class="movie-info">
                <h1><?php echo htmlspecialchars($movie['title']); ?></h1>
                <div class="movie-meta">
                    <span><i class="fas fa-calendar"></i> <?php echo $movie['year']; ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo $movie['duration']; ?> dk</span>
                    <span><i class="fas fa-film"></i> <?php echo htmlspecialchars($movie['category']['name']); ?></span>
                </div>
                <div class="movie-rating">
                    <div class="stars">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $movie_rating ? 'filled' : ''; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-count"><?php echo $rating_count; ?> değerlendirme</span>
                </div>
                <p class="movie-description"><?php echo htmlspecialchars($movie['description']); ?></p>
                <div class="movie-actions">
                    <button class="watch-movie-btn" data-movie-id="<?php echo $movie['id']; ?>">
                        <i class="fas fa-play"></i> İzle
                    </button>
                    
                    <button class="add-to-favorites <?php echo isMovieFavorite($_SESSION['user_id'] ?? 0, $movie['id']) ? 'active' : ''; ?>" 
                            data-movie-id="<?php echo $movie['id']; ?>">
                        <i class="fas fa-heart"></i> 
                        <span><?php echo isMovieFavorite($_SESSION['user_id'] ?? 0, $movie['id']) ? 'Favorilerden Çıkar' : 'Favorilere Ekle'; ?></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="movie-content">
            <div class="content-grid">
                <div class="main-content">
            <div id="video-player" class="video-player">
                <video controls>
                            <source src="<?php echo htmlspecialchars($movie['video_url'] ?? ''); ?>" type="video/mp4">
                    Tarayıcınız video oynatmayı desteklemiyor.
                </video>
            </div>

                    <div class="movie-details">
                        <h2>Film Detayları</h2>
                        <div class="details-grid">
                            <div class="detail-item">
                                <i class="fas fa-calendar"></i>
                                <span>Yayın Tarihi:</span>
                                <strong><?php echo $movie['year']; ?></strong>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <span>Süre:</span>
                                <strong><?php echo $movie['duration']; ?> dakika</strong>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-film"></i>
                                <span>Kategori:</span>
                                <strong><?php echo htmlspecialchars($movie['category']['name']); ?></strong>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-star"></i>
                                <span>Puan:</span>
                                <strong><?php echo $movie_rating; ?>/5</strong>
                            </div>
                        </div>
                    </div>

                    <section class="comments-section">
                        <h3>Film Yorumları</h3>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="comment-form">
                            <div class="user-avatar">
                                <img src="images/profile-images/<?php echo htmlspecialchars($_SESSION['profile_image'] ?? 'default.png'); ?>" alt="User Avatar">
                            </div>
                            <form id="commentForm" method="post">
                                <div class="rating-container">
                                    <label>Puanınız:</label>
                                    <div class="stars">
                                        <span class="star" data-value="1"><i class="fas fa-star"></i></span>
                                        <span class="star" data-value="2"><i class="fas fa-star"></i></span>
                                        <span class="star" data-value="3"><i class="fas fa-star"></i></span>
                                        <span class="star" data-value="4"><i class="fas fa-star"></i></span>
                                        <span class="star" data-value="5"><i class="fas fa-star"></i></span>
                                    </div>
                                    <input type="hidden" name="rating" id="rating" value="0">
                                </div>
                                
                                <textarea name="comment" id="commentText" placeholder="Bu film hakkında ne düşünüyorsunuz?" required></textarea>
                                <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                                <button type="submit" class="submit-comment">Yorum Yap</button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="login-prompt">
                            <p>Yorum yapabilmek için <a href="login.php">giriş yapmalısınız</a>.</p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="comments-list">
                            <?php 
                            if (!empty($reviews)):
                                foreach ($reviews as $review):
                                    // Kullanıcının kendi yorumu mu kontrol et
                                    $is_user_review = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id'];
                            ?>
                            <div class="comment-item" id="review-<?php echo $review['id']; ?>">
                                <div class="user-avatar">
                                    <img src="images/profile-images/<?php echo htmlspecialchars($review['profile_image'] ?? 'default.png'); ?>" alt="User Avatar">
                                </div>
                                <div class="comment-content">
                                    <div class="comment-header">
                                        <div>
                                            <span class="user-name"><?php echo htmlspecialchars($review['username']); ?></span>
                                            <?php if ($is_user_review): ?>
                                                <span class="user-badge">(Siz)</span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="comment-date"><?php echo date('d.m.Y H:i', strtotime($review['created_at'])); ?></span>
                                    </div>
                                    <div class="user-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'active' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="comment-text"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                    
                                    <?php if ($is_user_review): ?>
                                    <div class="comment-actions">
                                        <button class="edit-review-btn" data-review-id="<?php echo $review['id']; ?>">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </button>
                                        <button class="delete-review-btn" data-review-id="<?php echo $review['id']; ?>">
                                            <i class="fas fa-trash"></i> Sil
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php 
                                endforeach;
                            else:
                            ?>
                            <div class="no-comments">
                                <p>Bu film için henüz yorum yapılmamış. İlk yorumu siz yapın!</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <div class="similar-movies-section">
                        <h2>Benzer Filmler</h2>
            <div class="similar-movies">
                            <?php if (empty($similar_movies)): ?>
                                <p class="no-similar">Bu kategoride başka film bulunamadı.</p>
                            <?php else: ?>
                    <?php foreach($similar_movies as $similar): ?>
                        <div class="movie-card">
                                        <div class="movie-poster">
                            <img src="<?php echo htmlspecialchars($similar['poster_url']); ?>" alt="<?php echo htmlspecialchars($similar['title']); ?>">
                                            <div class="movie-overlay">
                                                <a href="movie.php?id=<?php echo $similar['id']; ?>" class="watch-button"><i class="fas fa-play"></i> İzle</a>
                                            </div>
                                        </div>
                            <div class="movie-info">
                                <h3><?php echo htmlspecialchars($similar['title']); ?></h3>
                                <p><?php echo $similar['year']; ?> • <?php echo $similar['duration']; ?> dk</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="popup-overlay" class="popup-overlay">
  <div class="popup-content">
    <span class="popup-close">&times;</span>
    <div class="popup-icon">
      <i class="fas fa-exclamation-circle"></i>
    </div>
    <h3 class="popup-title">Oturum Açın</h3>
    <p class="popup-message">Favorilere eklemek için lütfen önce oturum açın.</p>
    <div class="popup-buttons">
      <a href="login.php" class="popup-button primary">Giriş Yap</a>
      <button class="popup-button secondary">Kapat</button>
    </div>
  </div>
</div>

<!-- Düzenleme Popup'ı -->
<div id="editReviewPopup" class="edit-popup">
    <div class="edit-popup-content">
        <div class="edit-popup-header">
            <h3>Yorumu Düzenle</h3>
            <span class="edit-popup-close">&times;</span>
        </div>
        <div class="edit-rating-container">
            <label>Puanınız:</label>
            <div class="stars">
                <span class="edit-star" data-value="1"><i class="fas fa-star"></i></span>
                <span class="edit-star" data-value="2"><i class="fas fa-star"></i></span>
                <span class="edit-star" data-value="3"><i class="fas fa-star"></i></span>
                <span class="edit-star" data-value="4"><i class="fas fa-star"></i></span>
                <span class="edit-star" data-value="5"><i class="fas fa-star"></i></span>
            </div>
            <input type="hidden" id="editRating" value="0">
        </div>
        <textarea id="editComment" placeholder="Bu film hakkında ne düşünüyorsunuz?"></textarea>
        <input type="hidden" id="editReviewId" value="0">
        <div class="edit-popup-buttons">
            <button class="cancel-edit-btn">İptal</button>
            <button class="save-edit-btn">Kaydet</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Popup işlevselliği
    const popupOverlay = document.getElementById('popup-overlay');
    const popupClose = document.querySelector('.popup-close');
    const closeButton = document.querySelector('.popup-button.secondary');
    
    function openPopup() {
        popupOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Sayfanın kaydırılmasını engelle
    }
    
    function closePopup() {
        popupOverlay.style.display = 'none';
        document.body.style.overflow = 'auto'; // Sayfanın kaydırılmasını tekrar etkinleştir
    }
    
    if (popupClose) {
        popupClose.addEventListener('click', closePopup);
    }
    
    if (closeButton) {
        closeButton.addEventListener('click', closePopup);
    }
    
    // Popup dışına tıklandığında kapat
    popupOverlay.addEventListener('click', function(e) {
        if (e.target === popupOverlay) {
            closePopup();
        }
    });
    
    // Favori butonu işlevselliği
    const favoriteBtn = document.querySelector('.add-to-favorites');
<?php if(isset($_SESSION['user_id'])): ?>
    // Favori durumunu kontrol et ve butonu güncelle
    <?php $is_favorite = isMovieFavorite($_SESSION['user_id'], $movie_id); ?>
    <?php if($is_favorite): ?>
        favoriteBtn.classList.add('active');
        favoriteBtn.querySelector('i').classList.remove('far');
        favoriteBtn.querySelector('i').classList.add('fas');
    <?php endif; ?>
<?php endif; ?>

favoriteBtn.addEventListener('click', function() {
    // Kullanıcı giriş yapmış mı kontrol et
    <?php if(isset($_SESSION['user_id'])): ?>
        // AJAX ile favori işlemini gerçekleştir
        fetch('add-favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                movie_id: <?php echo $movie_id; ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Buton durumunu güncelle
                this.classList.toggle('active');
                const icon = this.querySelector('i');
                const spanText = this.querySelector('span');
                
                // Buton durumunu güncelle
                if (data.action === 'added') {
                    this.classList.add('active');
                    spanText.textContent = 'Favorilerden Çıkar';
                    
                    // Bildirim göster
                    showNotification('Film favorilere eklendi', 'success');
                    
                    // Rozet kazanıldıysa bildirim göster
                    if (data.badge_earned) {
                        showBadgeNotification(data.badge_name);
                    }
                } else {
                    this.classList.remove('active');
                    spanText.textContent = 'Favorilere Ekle';
                    
                    // Bildirim göster
                    showNotification('Film favorilerden çıkarıldı', 'info');
                }
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Bir hata oluştu', 'error');
        });
    <?php else: ?>
        // Kullanıcı giriş yapmamışsa popup'ı göster
        openPopup();
    <?php endif; ?>
});

    // Video oynatıcı için tam ekran desteği
    const videoPlayer = document.querySelector('.video-player video');
    if (videoPlayer) {
        videoPlayer.addEventListener('dblclick', function() {
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                this.requestFullscreen();
            }
        });
    }

    // Yıldız derecelendirme sistemi
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('rating');
    
    stars.forEach(star => {
        star.addEventListener('mouseover', function() {
            const value = this.getAttribute('data-value');
            highlightStars(value);
        });
        
        star.addEventListener('mouseout', function() {
            const currentRating = ratingInput.value;
            highlightStars(currentRating);
        });
        
        star.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            ratingInput.value = value;
            highlightStars(value);
        });
    });
    
    function highlightStars(count) {
        stars.forEach(star => {
            const value = star.getAttribute('data-value');
            if (value <= count) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }

    // Yorum ekleme formunu AJAX ile gönder
    document.getElementById('commentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const ratingValue = document.getElementById('rating').value;
        const commentText = document.getElementById('commentText').value;
        const movieId = <?php echo $movie['id']; ?>;
        
        // Derecelendirme kontrolü
        if (ratingValue === '0') {
            alert('Lütfen bir derecelendirme seçin (1-5 yıldız)');
            return;
        }
        
        if (!commentText.trim()) {
            alert('Lütfen bir yorum yazın.');
            return;
        }
        
        // AJAX ile yorumu gönder
        fetch('add-review.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                movie_id: movieId,
                rating: ratingValue,
                comment: commentText
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Yorum başarıyla eklendiyse formu temizle
                document.getElementById('rating').value = '0';
                document.getElementById('commentText').value = '';
                highlightStars(0);
                
                // Yorumu listenin başına ekle
                const commentsList = document.querySelector('.comments-list');
                
                // "Yorum yok" mesajını kaldır
                const noComments = commentsList.querySelector('.no-comments');
                if (noComments) {
                    noComments.remove();
                }
                
                // Yeni yorumu oluştur
                const newComment = document.createElement('div');
                newComment.className = 'comment-item';
                newComment.id = `review-${data.review_id}`;
                
                newComment.innerHTML = `
                    <div class="user-avatar">
                        <img src="images/profile-images/${data.user_image}" alt="User Avatar">
                    </div>
                    <div class="comment-content">
                        <div class="comment-header">
                            <div>
                                <span class="user-name">${data.username}</span>
                                <span class="user-badge">(Siz)</span>
                            </div>
                            <span class="comment-date">${data.created_at}</span>
                        </div>
                        <div class="user-rating">
                            ${Array(5).fill().map((_, i) => 
                                `<i class="fas fa-star ${i < ratingValue ? 'active' : ''}"></i>`
                            ).join('')}
                        </div>
                        <p class="comment-text">${commentText.replace(/\n/g, '<br>')}</p>
                        <div class="comment-actions">
                            <button class="edit-review-btn" data-review-id="${data.review_id}">
                                <i class="fas fa-edit"></i> Düzenle
                            </button>
                            <button class="delete-review-btn" data-review-id="${data.review_id}">
                                <i class="fas fa-trash"></i> Sil
                            </button>
                        </div>
                    </div>
                `;
                
                // Yorumu listenin başına ekle
                commentsList.insertBefore(newComment, commentsList.firstChild);
                
                // Yeni eklenen yorumun görünmesi için sayfayı yorum bölümüne kaydır
                const commentsSection = document.querySelector('.comments-section');
                if (commentsSection) {
                    setTimeout(() => {
                        commentsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 300);
                }
                
                // Yeni eklenen yorumun düzenle ve sil butonlarına olay dinleyicileri ekle
                const editBtn = newComment.querySelector('.edit-review-btn');
                const deleteBtn = newComment.querySelector('.delete-review-btn');
                
                // Düzenleme butonu olayı
                editBtn.addEventListener('click', function() {
                    const reviewId = this.getAttribute('data-review-id');
                    
                    // Yorum bilgilerini getir
                    fetch(`edit-review.php?id=${reviewId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Popup içeriğini doldur
                                editRatingInput.value = data.review.rating;
                                editCommentTextarea.value = data.review.comment;
                                editReviewIdInput.value = data.review.id;
                                highlightEditStars(data.review.rating);
                                
                                // Popup'ı göster
                                editPopup.style.display = 'flex';
                                document.body.style.overflow = 'hidden';
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                        });
                });
                
                // Silme butonu olayı
                deleteBtn.addEventListener('click', function() {
                    if (confirm('Bu yorumu silmek istediğinize emin misiniz?')) {
                        const reviewId = this.getAttribute('data-review-id');
                        
                        fetch('delete-review.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ review_id: reviewId })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Yorumu sayfadan kaldır
                                const reviewElement = document.getElementById(`review-${reviewId}`);
                                if (reviewElement) {
                                    reviewElement.remove();
                                }
                                
                                alert(data.message);
                                
                                // Hiç yorum kalmadıysa "Yorum yok" mesajını göster
                                if (commentsList.children.length === 0) {
                                    const noComments = document.createElement('div');
                                    noComments.className = 'no-comments';
                                    noComments.innerHTML = '<p>Bu film için henüz yorum yapılmamış. İlk yorumu siz yapın!</p>';
                                    commentsList.appendChild(noComments);
                                }
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                        });
                    }
                });
                
                // Başarı mesajını göster
                showNotification(data.message, 'success');
                
                // Rozet kazanıldıysa bildirimi göster
                if (data.badge_earned) {
                    showBadgeNotification(data.badge_name);
                }
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    });

    // Düzenleme Popup'ı
    const editPopup = document.getElementById('editReviewPopup');
    const editPopupClose = document.querySelector('.edit-popup-close');
    const cancelEditBtn = document.querySelector('.cancel-edit-btn');
    const saveEditBtn = document.querySelector('.save-edit-btn');
    const editStars = document.querySelectorAll('.edit-star');
    const editRatingInput = document.getElementById('editRating');
    const editCommentTextarea = document.getElementById('editComment');
    const editReviewIdInput = document.getElementById('editReviewId');
    
    // Yorum düzenleme butonları
    const editButtons = document.querySelectorAll('.edit-review-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const reviewId = this.getAttribute('data-review-id');
            
            // Yorum bilgilerini getir
            fetch(`edit-review.php?id=${reviewId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Popup içeriğini doldur
                        editRatingInput.value = data.review.rating;
                        editCommentTextarea.value = data.review.comment;
                        editReviewIdInput.value = data.review.id;
                        highlightEditStars(data.review.rating);
                        
                        // Popup'ı göster
                        editPopup.style.display = 'flex';
                        document.body.style.overflow = 'hidden';
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                });
        });
    });
    
    // Yorum silme butonları
    const deleteButtons = document.querySelectorAll('.delete-review-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Bu yorumu silmek istediğinize emin misiniz?')) {
                const reviewId = this.getAttribute('data-review-id');
                
                fetch('delete-review.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ review_id: reviewId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Yorumu sayfadan kaldır
                        const reviewElement = document.getElementById(`review-${reviewId}`);
                        if (reviewElement) {
                            reviewElement.remove();
                        }
                        
                        alert(data.message);
                        
                        // Hiç yorum kalmadıysa "Yorum yok" mesajını göster
                        const commentsList = document.querySelector('.comments-list');
                        if (commentsList.children.length === 0) {
                            const noComments = document.createElement('div');
                            noComments.className = 'no-comments';
                            noComments.innerHTML = '<p>Bu film için henüz yorum yapılmamış. İlk yorumu siz yapın!</p>';
                            commentsList.appendChild(noComments);
                        }
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                });
            }
        });
    });
    
    // Düzenleme yıldızları için olaylar
    editStars.forEach(star => {
        star.addEventListener('mouseover', function() {
            const value = this.getAttribute('data-value');
            highlightEditStars(value);
        });
        
        star.addEventListener('mouseout', function() {
            const currentRating = editRatingInput.value;
            highlightEditStars(currentRating);
        });
        
        star.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            editRatingInput.value = value;
            highlightEditStars(value);
        });
    });
    
    function highlightEditStars(count) {
        editStars.forEach(star => {
            const value = star.getAttribute('data-value');
            if (value <= count) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }
    
    // Düzenleme popup'ını kapat
    function closeEditPopup() {
        editPopup.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    editPopupClose.addEventListener('click', closeEditPopup);
    cancelEditBtn.addEventListener('click', closeEditPopup);
    
    // Popup dışına tıklandığında kapat
    editPopup.addEventListener('click', function(e) {
        if (e.target === editPopup) {
            closeEditPopup();
        }
    });
    
    // Yorumu kaydet
    saveEditBtn.addEventListener('click', function() {
        const reviewId = editReviewIdInput.value;
        const rating = editRatingInput.value;
        const comment = editCommentTextarea.value;
        
        if (rating < 1 || rating > 5) {
            alert('Lütfen bir puan seçin (1-5).');
            return;
        }
        
        if (!comment.trim()) {
            alert('Lütfen bir yorum yazın.');
            return;
        }
        
        fetch('edit-review.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                review_id: reviewId,
                rating: rating,
                comment: comment
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Yorumu sayfada güncelle
                const reviewElement = document.getElementById(`review-${reviewId}`);
                if (reviewElement) {
                    // Yıldızları güncelle
                    const stars = reviewElement.querySelectorAll('.user-rating .fas');
                    stars.forEach((star, index) => {
                        if (index < rating) {
                            star.classList.add('active');
                        } else {
                            star.classList.remove('active');
                        }
                    });
                    
                    // Yorumu güncelle
                    const commentText = reviewElement.querySelector('.comment-text');
                    if (commentText) {
                        commentText.innerHTML = comment.replace(/\n/g, '<br>');
                    }
                }
                
                closeEditPopup();
                showNotification(data.message, 'success');
                
                // Rozet kazanıldıysa bildirimi göster
                if (data.badge_earned) {
                    showBadgeNotification(data.badge_name);
                }
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    });

    // Film izleme butonu
    const watchBtn = document.querySelector('.watch-movie-btn');
    if (watchBtn) {
        watchBtn.addEventListener('click', function() {
            <?php if(isset($_SESSION['user_id'])): ?>
                const movieId = this.dataset.movieId;
                
                // Video oynatıcısına git
                const videoPlayer = document.getElementById('video-player');
                if (videoPlayer) {
                    videoPlayer.scrollIntoView({ behavior: 'smooth' });
                    
                    // Videonun play butonunu tıkla
                    const video = videoPlayer.querySelector('video');
                    if (video) {
                        video.play().catch(e => console.error('Video oynatma hatası:', e));
                    }
                }
                
                // Filmi izlendi olarak kaydet ve rozet kontrol et
                fetch(`watch-movie.php?movie_id=${movieId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showNotification('Film izleme geçmişinize eklendi', 'success');
                        
                        // Rozet kazanıldıysa bildirim göster
                        if (data.badge_earned) {
                            showBadgeNotification(data.badge_name);
                        }
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showNotification('Bir hata oluştu', 'error');
                });
            <?php else: ?>
                // Kullanıcı giriş yapmamışsa popup'ı göster
                openPopup();
            <?php endif; ?>
        });
    }
});

// Bildirim gösterme fonksiyonu
function showNotification(message, type = 'info') {
    // Önceki bildirimleri kaldır (aynı anda sadece bir bildirim göster)
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => {
        notification.remove();
    });
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 
                          type === 'error' ? 'fa-exclamation-circle' : 
                          'fa-info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animasyon ekle
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // 3 saniye sonra kaldır
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Rozet bildirimi gösterme fonksiyonu
function showBadgeNotification(badgeName) {
    const badgeNotification = document.createElement('div');
    badgeNotification.className = 'badge-notification';
    
    // Rozet ikonunu türüne göre belirle
    let badgeIcon = 'fa-award';
    let badgeColor = '#8A2BE2';
    
    if (badgeName === 'İlk Favori' || badgeName === 'Favori Koleksiyoner' || badgeName === 'Süper Hayran') {
        badgeIcon = 'fa-heart';
        badgeColor = '#E91E63';
    } else if (badgeName === 'Yeni Başlayan' || badgeName === 'Film Gurusu' || badgeName === 'Film Tutkunu') {
        badgeIcon = 'fa-film';
        badgeColor = '#FF9800';
    } else if (badgeName === 'İlk Yorum' || badgeName === 'Eleştirmen') {
        badgeIcon = 'fa-comment';
        badgeColor = '#4CAF50';
    }
    
    badgeNotification.innerHTML = `
        <div class="badge-notification-content">
            <i class="fas ${badgeIcon}"></i>
            <div class="badge-info">
                <h4>Yeni Rozet Kazandınız!</h4>
                <p>${badgeName}</p>
            </div>
        </div>
        <div class="badge-actions">
            <button class="view-badge">Göster</button>
            <button class="close-badge">Kapat</button>
        </div>
    `;
    
    // Rozet rengini özelleştir
    badgeNotification.style.background = `linear-gradient(135deg, ${badgeColor}, ${adjustColor(badgeColor, 30)})`;
    
    document.body.appendChild(badgeNotification);
    
    // Ses efekti
    const audio = new Audio('sounds/achievement.mp3');
    audio.volume = 0.6;
    audio.play().catch(e => console.log('Ses çalınamadı:', e));
    
    // Animasyon ekle
    setTimeout(() => {
        badgeNotification.classList.add('show');
    }, 10);
    
    // Kapatma düğmesi
    badgeNotification.querySelector('.close-badge').addEventListener('click', () => {
        badgeNotification.classList.remove('show');
        setTimeout(() => {
            badgeNotification.remove();
        }, 300);
    });
    
    // Rozeti görüntüle butonu
    badgeNotification.querySelector('.view-badge').addEventListener('click', () => {
        window.location.href = 'profile.php?tab=badges-tab';
    });
    
    // 12 saniye sonra otomatik kapat
    setTimeout(() => {
        if (document.body.contains(badgeNotification)) {
            badgeNotification.classList.remove('show');
            setTimeout(() => {
                badgeNotification.remove();
            }, 300);
        }
    }, 12000);
    
    // Renk ayarlama yardımcı fonksiyonu
    function adjustColor(color, amount) {
        return '#' + color.replace(/^#/, '').replace(/../g, color => ('0'+Math.min(255, Math.max(0, parseInt(color, 16) + amount)).toString(16)).slice(-2));
    }
}
</script>

<!-- Bildirim stilleri -->
<style>
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 5px;
    background-color: #333;
    color: white;
    z-index: 1000;
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.notification.show {
    opacity: 1;
    transform: translateY(0);
}

.notification.success {
    background-color: #4CAF50;
}

.notification.error {
    background-color: #F44336;
}

.notification.info {
    background-color: #2196F3;
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.badge-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 20px;
    border-radius: 8px;
    background-color: #8A2BE2;
    color: white;
    z-index: 1000;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(138, 43, 226, 0.4);
    width: 300px;
}

.badge-notification.show {
    opacity: 1;
    transform: translateY(0);
}

.badge-notification-content {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.badge-notification-content i {
    font-size: 40px;
    color: #FFD700;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
}

.badge-info h4 {
    margin: 0 0 5px 0;
    font-size: 18px;
}

.badge-info p {
    margin: 0;
    font-size: 14px;
}

.badge-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.badge-actions button {
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
}

.view-badge {
    background-color: #FFD700;
    color: #333;
}

.close-badge {
    background-color: rgba(255,255,255,0.2);
    color: white;
}

.view-badge:hover {
    background-color: #FFC107;
    transform: translateY(-2px);
}

.close-badge:hover {
    background-color: rgba(255,255,255,0.3);
}
</style>

<?php include 'includes/footer.php'; ?> 