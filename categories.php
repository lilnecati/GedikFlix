<?php
session_start();
include 'config/database.php';

$page_title = "Kategoriler";

// Tüm kategorileri al
$categories = getAllCategories();

// Her kategori için film sayısını hesapla
$categories_with_count = [];
foreach ($categories as $category) {
    $category['movie_count'] = getMovieCountByCategory($category['id']);
    $categories_with_count[] = $category;
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="css/categories.css">

<main>
    <div class="categories-container">
        <h2>Film Kategorileri</h2>
        <div class="categories-grid">
            <?php foreach ($categories_with_count as $category): ?>
                <div class="category-card">
                    <h2><?php echo htmlspecialchars($category['name']); ?></h2>
                    <p><?php echo htmlspecialchars($category['description']); ?></p>
                    <span class="movie-count"><?php echo $category['movie_count']; ?> Film</span>
                    <br>
                    <a href="movies.php?category=<?php echo $category['id']; ?>">
                        Bu Kategorideki Filmleri İzle →
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?> 