<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>GedikFlix</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="loading-screen">
        <img src="images/logo.png" alt="GedikFlix Logo">
        <div class="loader"></div>
    </div>
    
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="index.php" class="logo-container">
                    <img src="images/logo.png" alt="GedikFlix Logo">
                    <h1>GedikFlix</h1>
                </a>
            </div>
            <div class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="profile-dropdown">
                        <div class="profile-image">
                            <img src="images/profile-images/<?php echo htmlspecialchars($_SESSION['profile_image'] ?? 'default.png'); ?>" alt="Profil Resmi">
                        </div>
                        <div class="profile-dropdown-content">
                            <div class="profile-info">
                                <p class="profile-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Kullanıcı'); ?></p>
                                <p class="profile-email"><?php echo htmlspecialchars($_SESSION['email'] ?? 'email@example.com'); ?></p>
                            </div>
                            
                            <div class="menu-section">
                                <p class="menu-title">HESAP</p>
                                <ul>
                                    <li><a href="profile.php"><i class="fas fa-user"></i> Profilim</a></li>
                                    <li><a href="profile.php?tab=favorites-tab"><i class="fas fa-heart"></i> Favorilerim</a></li>
                                    <li><a href="profile.php?tab=history-tab"><i class="fas fa-history"></i> İzleme Geçmişim</a></li>
                                </ul>
                            </div>
                            
                            <div class="menu-section">
                                <p class="menu-title">AYARLAR</p>
                                <ul>
                                    <li><a href="profile.php?tab=settings-tab"><i class="fas fa-cog"></i> Hesap Ayarları</a></li>
                                </ul>
                            </div>
                            
                            <div class="logout-button">
                                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" title="Giriş Yap"><i class="fas fa-sign-in-alt"></i></a>
                <?php endif; ?>
                
                <a href="movies.php" title="Filmler"><i class="fas fa-film"></i></a>
                <a href="categories.php" title="Kategoriler"><i class="fas fa-list"></i></a>
            </div>
        </nav>
    </header>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Yükleme ekranı
            const loadingScreen = document.querySelector('.loading-screen');
            if (loadingScreen) {
                setTimeout(function() {
                    loadingScreen.classList.add('fade-out');
                }, 500);
            }
            
            // Profil menüsü
            const profileCircle = document.querySelector('.profile-circle');
            if (profileCircle) {
                profileCircle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const menu = document.querySelector('.profile-menu');
                    menu.classList.toggle('show');
                    
                    // Animasyon efekti
                    if (menu.classList.contains('show')) {
                        // Menüyü açarken hafif sarsma animasyonu
                        menu.animate([
                            { transform: 'translateY(0) scale(1)', offset: 0 },
                            { transform: 'translateY(0) scale(1.02)', offset: 0.5 },
                            { transform: 'translateY(0) scale(1)', offset: 1 }
                        ], {
                            duration: 300,
                            easing: 'ease-out'
                        });
                    }
                });
                
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.profile-dropdown')) {
                        const menu = document.querySelector('.profile-menu');
                        if (menu && menu.classList.contains('show')) {
                            menu.classList.remove('show');
                        }
                    }
                });
            }
        });
    </script>

    <!-- Profil menüsü için JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileImage = document.querySelector('.profile-image');
        const profileDropdown = document.querySelector('.profile-dropdown-content');
        
        if (profileImage && profileDropdown) {
            // Profil fotoğrafına tıklandığında menüyü aç/kapat
            profileImage.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('show');
            });
            
            // Dışarı tıklandığında menüyü kapat
            document.addEventListener('click', function(e) {
                if (!profileDropdown.contains(e.target) && !profileImage.contains(e.target)) {
                    profileDropdown.classList.remove('show');
                }
            });
        }
    });
    </script>

    <style>
    /* Profil dropdown için ekstra stil */
    .profile-dropdown-content {
        display: none;
    }

    .profile-dropdown-content.show {
        display: block;
        animation: fadeIn 0.3s ease;
    }
    </style>
</body>
</html>
 