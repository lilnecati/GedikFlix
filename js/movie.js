document.addEventListener('DOMContentLoaded', function() {
    const watchBtn = document.querySelector('.btn-watch');
    const favoriteBtn = document.querySelector('.btn-favorite');
    const commentForm = document.getElementById('comment-form');
    const notification = document.getElementById('notification');

    // İzleme durumunu güncelle
    if (watchBtn) {
        watchBtn.addEventListener('click', function() {
            const movieId = this.dataset.movieId;
            fetch('watch-movie.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ movie_id: movieId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('watched');
                    this.innerHTML = this.classList.contains('watched') ? 
                        '<i class="fas fa-check-circle"></i> İzlendi' : 
                        '<i class="fas fa-play-circle"></i> İzle';
                    showNotification(data.message);
                }
            });
        });
    }

    // Favori durumunu güncelle
    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', function() {
            const movieId = this.dataset.movieId;
            fetch('add-favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ movie_id: movieId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('favorited');
                    this.innerHTML = this.classList.contains('favorited') ? 
                        '<i class="fas fa-heart"></i> Favorilerden Çıkar' : 
                        '<i class="fas fa-heart"></i> Favorilere Ekle';
                    showNotification(data.message);
                }
            });
        });
    }

    // Yorum gönderme
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const movieId = document.querySelector('[data-movie-id]').dataset.movieId;
            
            fetch('add-review.php', {
                method: 'POST',
                body: JSON.stringify({
                    movie_id: movieId,
                    rating: formData.get('rating'),
                    comment: formData.get('comment')
                }),
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Yorumunuz başarıyla eklendi!');
                    this.reset();
                    loadComments();
                }
            });
        });
    }

    // Bildirimleri göster
    function showNotification(message) {
        notification.textContent = message;
        notification.classList.add('show');
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }

    // Yorumları yükle
    function loadComments() {
        const movieId = document.querySelector('[data-movie-id]').dataset.movieId;
        const commentsList = document.getElementById('comments-list');

        fetch(`get-comments.php?movie_id=${movieId}`)
            .then(response => response.json())
            .then(data => {
                commentsList.innerHTML = data.comments.map(comment => `
                    <div class="comment">
                        <div class="comment-header">
                            <span class="user">${comment.username}</span>
                            <span class="rating">${'★'.repeat(comment.rating)}</span>
                            <span class="date">${comment.created_at}</span>
                        </div>
                        <p class="comment-text">${comment.comment}</p>
                    </div>
                `).join('');
            });
    }

    // Sayfa yüklendiğinde yorumları göster
    loadComments();
}); 