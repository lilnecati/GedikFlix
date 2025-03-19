<?php
session_start();
include 'config/database.php';

$page_title = "Filmler";

// Kategori filtresi
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search_query = isset($_GET['search']) ? real_escape_string($_GET['search']) : '';

// Filtre uygula
if ($category_filter) {
    $filtered_movies = getMoviesByCategory($category_filter);
} else {
    $filtered_movies = getAllMovies();
}

// Arama sorgusu varsa filtrele
if ($search_query) {
    $filtered_movies = array_filter($filtered_movies, function($movie) use ($search_query) {
        return stripos($movie['title'], $search_query) !== false || 
               stripos($movie['description'], $search_query) !== false;
    });
}

// Kategorileri al
$categories = getAllCategories();

include 'includes/header.php';
?>

<link rel="stylesheet" href="css/movies.css">

<main>
    <div class="movies-container">
        <div class="page-header">
            <h2>Tüm Filmler</h2>
            <p class="subtitle">En iyi filmleri keşfedin</p>
        </div>
        
        <div class="filters-section">
            <form method="GET" class="search-box">
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" placeholder="Film ara..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <?php if ($category_filter): ?>
                    <input type="hidden" name="category" value="<?php echo $category_filter; ?>">
                <?php endif; ?>
            </form>
            
            <form method="GET" class="category-filter-form">
                <?php if ($search_query): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                <?php endif; ?>
                
                <div class="select-wrapper">
                    <i class="fas fa-film category-icon"></i>
                    <select name="category" class="category-filter" onchange="this.form.submit()">
                        <option value="">Tüm Kategoriler</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if (!empty($filtered_movies)): ?>
            <div class="movies-grid">
                <?php foreach ($filtered_movies as $movie): ?>
                    <div class="movie-card">
                        <div class="movie-poster">
                            <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                            <div class="movie-overlay">
                                <div class="movie-actions">
                                    <a href="movie.php?id=<?php echo $movie['id']; ?>" class="watch-button">
                                        <i class="fas fa-play"></i>
                                        <span>İzle</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="movie-info">
                            <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <div class="movie-meta">
                                <span class="year"><?php echo $movie['year']; ?></span>
                                <span class="duration"><?php echo $movie['duration']; ?> dk</span>
                            </div>
                            <span class="category-tag"><?php echo htmlspecialchars($movie['category']['name']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-film no-results-icon"></i>
                <p>Film bulunamadı.</p>
                <?php if ($search_query || $category_filter): ?>
                    <a href="movies.php" class="reset-filters">Filtreleri Temizle</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?> 