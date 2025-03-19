<?php
session_start();
include 'config/database.php';
include 'config/profile_utils.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Profil Ayarları";
$success_message = '';
$error_message = '';
$user = getUserById($_SESSION['user_id']);

// Profil verileri
$profile_data = getProfileData($_SESSION['user_id']);

// Kullanıcı istatistikleri
$user_stats = $profile_data['stats'];
$total_watched = $user_stats['total_watched'];
$total_hours = $user_stats['total_hours'];
$favorite_movies = $user_stats['favorite_movies'];
$activity_calendar = $user_stats['activity_calendar'];

// Bildirimler
$notifications = $profile_data['notifications'];
$notification_settings = $profile_data['notification_settings'];

// Rozetler
$earned_badges = $profile_data['earned_badges'];
$upcoming_badges = $profile_data['upcoming_badges'];

// Kategori dağılımını hesapla
$genre_percentages = [];
$total_genre_count = array_sum($user_stats['genres']);
if ($total_genre_count > 0) {
    foreach ($user_stats['genres'] as $genre => $count) {
        $genre_percentages[$genre] = round(($count / $total_genre_count) * 100);
    }
    // Yüzdelere göre sırala (büyükten küçüğe)
    arsort($genre_percentages);
}

// Kullanıcı favorileri
$favorite_movies = getFavoriteMovies($_SESSION['user_id']);

// Profil resmi güncelleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile_image'])) {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $file = $_FILES['profile_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (in_array($file['type'], $allowed_types)) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $_SESSION['user_id'] . '_' . time() . '.' . $extension;
            $target = 'images/profile-images/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $target)) {
                if (updateUserProfile($_SESSION['user_id'], $filename)) {
                    $_SESSION['profile_image'] = $filename;
                    $success_message = "Profil resmi başarıyla güncellendi!";
                } else {
                    $error_message = "Veritabanı güncellenirken bir hata oluştu.";
                }
            } else {
                $error_message = "Dosya yüklenirken bir hata oluştu.";
            }
        } else {
            $error_message = "Sadece JPG, PNG ve GIF formatları kabul edilir!";
        }
    } else {
        $error_message = "Lütfen bir resim seçin.";
    }
}

// Şifre değiştirme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Mevcut şifre kontrolü
    if (password_verify($current_password, $user['password'])) {
        // Şifre karmaşıklık kontrolü
        if (strlen($new_password) < 6) {
            $error_message = "Yeni şifre en az 6 karakter olmalıdır!";
        } elseif (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
            $error_message = "Yeni şifre en az bir büyük harf, bir küçük harf ve bir rakam içermelidir!";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "Yeni şifreler eşleşmiyor!";
        } else {
            // Şifreyi hashle ve güncelle
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            if (updateUserPassword($_SESSION['user_id'], $hashed_password)) {
                $success_message = "Şifreniz başarıyla güncellendi!";
                
                // Şifre değişikliği bildirimi ekle
                addNotification(
                    $_SESSION['user_id'],
                    "Şifreniz değiştirildi",
                    "Hesap şifreniz başarıyla güncellenmiştir.",
                    'security'
                );
            } else {
                $error_message = "Şifre güncellenirken bir hata oluştu.";
            }
        }
    } else {
        $error_message = "Mevcut şifreniz hatalı!";
    }
}

// Bildirim ayarlarını güncelleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_notification_settings'])) {
    $email_notifications = isset($_POST['email_notifications']) ? true : false;
    $browser_notifications = isset($_POST['browser_notifications']) ? true : false;
    $sms_notifications = isset($_POST['sms_notifications']) ? true : false;
    
    $new_settings = [
        'email_notifications' => $email_notifications,
        'browser_notifications' => $browser_notifications,
        'sms_notifications' => $sms_notifications
    ];
    
    if (updateNotificationSettings($_SESSION['user_id'], $new_settings)) {
        $success_message = "Bildirim ayarları başarıyla güncellendi!";
        
        // Bildirim ayarları değişikliği için bildirim ekle
        addNotification(
            $_SESSION['user_id'],
            "Bildirim ayarları güncellendi",
            "Bildirim tercihleriniz başarıyla güncellendi.",
            'settings'
        );
        
        // Bildirim ayarlarını yenile
        $notification_settings = $new_settings;
    } else {
        $error_message = "Bildirim ayarları güncellenirken bir hata oluştu.";
    }
}

// Tüm bildirimleri okundu olarak işaretle
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_all_read'])) {
    if (markAllNotificationsAsRead($_SESSION['user_id'])) {
        $success_message = "Tüm bildirimler okundu olarak işaretlendi!";
        // Bildirimleri yenile
        $profile_data = getProfileData($_SESSION['user_id']);
        $notifications = $profile_data['notifications'];
    } else {
        $error_message = "Bildirimler işaretlenirken bir hata oluştu.";
    }
}

// Hesap silme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    $confirm_delete = $_POST['confirm_delete'];
    
    if ($confirm_delete === $_SESSION['username']) {
        if (deleteUser($_SESSION['user_id'])) {
            // Session'ı temizle
            session_unset();
            session_destroy();
            
            // Kullanıcıyı ana sayfaya yönlendir
            header("Location: index.php?deleted=1");
            exit();
        } else {
            $error_message = "Hesap silinirken bir hata oluştu.";
        }
    } else {
        $error_message = "Hesap silme işlemi için kullanıcı adınızı doğru girmelisiniz.";
    }
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="css/profile.css">

<main>
    <div class="profile-container">
        <h2>Profil Ayarları</h2>
        
        <?php if ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <div class="profile-tabs">
            <div class="tab active" data-tab="profile-tab">Profil</div>
            <div class="tab" data-tab="password-tab">Şifre Değiştir</div>
            <div class="tab" data-tab="stats-tab">İstatistikler</div>
            <div class="tab" data-tab="notifications-tab">Bildirimler</div>
            <div class="tab" data-tab="badges-tab">Rozetler</div>
            <div class="tab" data-tab="delete-tab">Hesabı Sil</div>
        </div>
        
        <!-- Profil Bilgileri Tab -->
        <div class="tab-content active" id="profile-tab">
            <div class="profile-info">
                <div class="profile-header">
                    <div class="current-profile">
                        <img src="images/profile-images/<?php echo $_SESSION['profile_image'] ?? 'default.png'; ?>" alt="Profil">
                    </div>
                    <div class="user-details">
                        <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="member-since">Üyelik: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
                        <div class="user-status">
                            <span class="premium-badge">
                                <i class="fas fa-crown"></i> Premium Üye
                            </span>
                        </div>
                    </div>
                </div>
                
                <form class="profile-form" method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="profile_image">Profil Resmi Değiştir</label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                        <small>JPG, PNG veya GIF. Max 2MB.</small>
                    </div>
                    <button type="submit" name="update_profile_image">Profil Resmini Güncelle</button>
                </form>
                
                <div class="profile-customization">
                    <h3><i class="fas fa-palette"></i> Tema Ayarları</h3>
                    <div class="theme-selector">
                        <div class="theme-option active" data-theme="dark">
                            <div class="theme-preview dark-theme"></div>
                            <span>Karanlık</span>
                        </div>
                        <div class="theme-option" data-theme="light">
                            <div class="theme-preview light-theme"></div>
                            <span>Aydınlık</span>
                        </div>
                        <div class="theme-option" data-theme="purple">
                            <div class="theme-preview purple-theme"></div>
                            <span>Mor</span>
                        </div>
                        <div class="theme-option" data-theme="blue">
                            <div class="theme-preview blue-theme"></div>
                            <span>Mavi</span>
                        </div>
                    </div>
                </div>
                
                <div class="profile-preferences">
                    <h3><i class="fas fa-sliders-h"></i> İzleme Tercihleri</h3>
                    <div class="preference-options">
                        <div class="preference-option">
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider round"></span>
                            </label>
                            <span>Otomatik oynatma</span>
                        </div>
                        <div class="preference-option">
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider round"></span>
                            </label>
                            <span>İzleme geçmişini kaydet</span>
                        </div>
                        <div class="preference-option">
                            <label class="switch">
                                <input type="checkbox">
                                <span class="slider round"></span>
                            </label>
                            <span>Film önerileri bildirimlerini göster</span>
                        </div>
                        <div class="preference-option">
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider round"></span>
                            </label>
                            <span>4K içerik (mümkün olduğunda)</span>
                        </div>
                    </div>
                </div>
                
                <div class="language-preferences">
                    <h3><i class="fas fa-globe"></i> Dil Tercihleri</h3>
                    <div class="lang-selector">
                        <div class="form-group">
                            <label for="interface_lang">Arayüz Dili</label>
                            <select id="interface_lang" class="custom-select">
                                <option value="tr" selected>Türkçe</option>
                                <option value="en">İngilizce</option>
                                <option value="de">Almanca</option>
                                <option value="fr">Fransızca</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="subtitle_lang">Altyazı Dili</label>
                            <select id="subtitle_lang" class="custom-select">
                                <option value="tr" selected>Türkçe</option>
                                <option value="en">İngilizce</option>
                                <option value="de">Almanca</option>
                                <option value="fr">Fransızca</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Şifre Değiştirme Tab -->
        <div class="tab-content" id="password-tab">
            <form class="password-form" method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Mevcut Şifre</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Yeni Şifre</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <small>En az 6 karakter, bir büyük harf, bir küçük harf ve bir rakam içermelidir.</small>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Yeni Şifre (Tekrar)</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="password-strength">
                    <div class="strength-meter">
                        <div class="meter-bar"></div>
                    </div>
                    <div class="strength-text">Şifre zorluk derecesi: <span>Güçsüz</span></div>
                </div>
                <div class="security-tips">
                    <h4><i class="fas fa-shield-alt"></i> Güvenlik İpuçları</h4>
                    <ul>
                        <li>Şifrenizde kişisel bilgilerinizi (doğum tarihi gibi) kullanmayın.</li>
                        <li>Şifrenizi düzenli olarak değiştirin.</li>
                        <li>Farklı platformlarda aynı şifreyi kullanmayın.</li>
                    </ul>
                </div>
                <button type="submit" name="change_password">Şifreyi Değiştir</button>
            </form>
        </div>
        
        <!-- İstatistikler Tab -->
        <div class="tab-content" id="stats-tab">
            <div class="stats-container">
                <div class="stats-header">
                    <div class="stats-highlight">
                        <div class="stat-box">
                            <i class="fas fa-film"></i>
                            <h3><?php echo $total_watched; ?></h3>
                            <p>İzlenen Film</p>
                        </div>
                        <div class="stat-box">
                            <i class="fas fa-heart"></i>
                            <h3><?php echo count($favorite_movies); ?></h3>
                            <p>Favori Film</p>
                        </div>
                        <div class="stat-box">
                            <i class="fas fa-clock"></i>
                            <h3><?php echo $total_hours; ?></h3>
                            <p>Toplam Saat</p>
                        </div>
                        <div class="stat-box">
                            <i class="fas fa-award"></i>
                            <h3><?php echo count($earned_badges); ?></h3>
                            <p>Kazanılan Rozet</p>
                        </div>
                    </div>
                </div>
                
                <div class="genres-chart">
                    <h3><i class="fas fa-chart-pie"></i> Kategori Dağılımı</h3>
                    <div class="genres-distribution">
                        <?php if (empty($genre_percentages)): ?>
                            <p class="no-data">Henüz izlediğiniz film yok.</p>
                        <?php else: ?>
                            <?php foreach ($genre_percentages as $genre => $percent): ?>
                            <div class="genre-bar" style="width: <?php echo $percent; ?>%;">
                                <span class="genre-name"><?php echo $genre; ?></span>
                                <span class="genre-percent"><?php echo $percent; ?>%</span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="favorite-movies-section">
                    <h3><i class="fas fa-heart"></i> Favori Filmleriniz</h3>
                    <div class="movie-list favorite-list">
                        <?php if (empty($favorite_movies)): ?>
                            <p class="no-data">Henüz favorilere eklediğiniz bir film bulunmuyor.</p>
                        <?php else: ?>
                            <?php foreach ($favorite_movies as $favorite): ?>
                                <div class="favorite-movie-card">
                                    <div class="movie-poster">
                                        <img src="<?php echo htmlspecialchars($favorite['poster_url']); ?>" alt="<?php echo htmlspecialchars($favorite['title']); ?>">
                                        <div class="movie-overlay">
                                            <a href="movie.php?id=<?php echo $favorite['id']; ?>" class="watch-button"><i class="fas fa-play"></i> İzle</a>
                                            <button class="remove-favorite" data-movie-id="<?php echo $favorite['id']; ?>"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                    <div class="movie-info">
                                        <h4><?php echo htmlspecialchars($favorite['title']); ?> (<?php echo $favorite['year']; ?>)</h4>
                                        <p><?php echo htmlspecialchars($favorite['category']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="activity-calendar">
                    <h3><i class="fas fa-calendar-alt"></i> İzleme Aktivitesi (Son 30 Gün)</h3>
                    <div class="calendar-grid">
                        <?php 
                        // Son 30 günün calendar'ını oluştur
                        for ($i = 30; $i >= 1; $i--) {
                            $date = date('Y-m-d', strtotime("-$i day"));
                            $formatted_date = date('d M', strtotime("-$i day"));
                            $activity = isset($activity_calendar[$date]) ? $activity_calendar[$date] : 0;
                            $opacity = $activity > 0 ? min($activity / 3, 1) : 0;
                            echo "<div class='calendar-day' style='opacity: $opacity;' title='$formatted_date: $activity film'>$formatted_date</div>";
                        }
                        ?>
                    </div>
                </div>
                
                <div class="watch-history">
                    <h3><i class="fas fa-history"></i> İzleme Geçmişi</h3>
                    <div class="history-list">
                        <?php if (empty($user_stats['watch_history'])): ?>
                            <p class="no-data">Henüz film izleme geçmişiniz bulunmuyor.</p>
                        <?php else: ?>
                            <?php foreach (array_slice($user_stats['watch_history'], 0, 10) as $watch): ?>
                                <div class="history-item">
                                    <div class="history-date">
                                        <?php echo date('d.m.Y', strtotime($watch['date'])); ?>
                                    </div>
                                    <div class="history-title">
                                        <?php echo $watch['movie_title']; ?>
                                    </div>
                                    <div class="history-duration">
                                        <?php echo $watch['duration']; ?> dk
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($user_stats['watch_history']) > 10): ?>
                                <div class="show-more">
                                    <button>Daha Fazla Göster</button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bildirimler Tab -->
        <div class="tab-content" id="notifications-tab">
            <div class="notifications-header">
                <h3><i class="fas fa-bell"></i> Bildirimleriniz</h3>
                <form method="POST" action="" style="display: inline;">
                    <button class="mark-all-read" name="mark_all_read" type="submit">Tümünü Okundu İşaretle</button>
                </form>
            </div>
            
            <div class="notifications-list">
                <?php if (empty($notifications)): ?>
                    <p class="no-data">Hiç bildiriminiz yok.</p>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['read'] ? '' : 'unread'; ?>" data-id="<?php echo $notification['id']; ?>">
                        <div class="notification-icon">
                            <?php 
                            $icon_class = 'fa-bell';
                            switch ($notification['type']) {
                                case 'badge':
                                    $icon_class = 'fa-award';
                                    break;
                                case 'reminder':
                                    $icon_class = 'fa-clock';
                                    break;
                                case 'watch':
                                    $icon_class = 'fa-film';
                                    break;
                                case 'new_movie':
                                    $icon_class = 'fa-plus-circle';
                                    break;
                                case 'new_category':
                                    $icon_class = 'fa-folder-plus';
                                    break;
                                case 'security':
                                    $icon_class = 'fa-shield-alt';
                                    break;
                                case 'settings':
                                    $icon_class = 'fa-cog';
                                    break;
                            }
                            ?>
                            <i class="fas <?php echo $icon_class; ?>"></i>
                        </div>
                        <div class="notification-content">
                            <h4><?php echo $notification['title']; ?></h4>
                            <p><?php echo $notification['message']; ?></p>
                            <div class="notification-meta">
                                <span class="notification-date"><?php echo date('d F Y', strtotime($notification['date'])); ?></span>
                                <?php if (!$notification['read']): ?>
                                    <span class="notification-badge">Yeni</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="notification-settings">
                <h3><i class="fas fa-cog"></i> Bildirim Ayarları</h3>
                <form method="POST" action="">
                    <div class="setting-options">
                        <div class="setting-option">
                            <label class="switch">
                                <input type="checkbox" name="email_notifications" <?php echo $notification_settings['email_notifications'] ? 'checked' : ''; ?>>
                                <span class="slider round"></span>
                            </label>
                            <div class="setting-description">
                                <h4>E-posta Bildirimleri</h4>
                                <p>Yeni filmler ve özel teklifler için e-posta bildirimleri alın</p>
                            </div>
                        </div>
                        <div class="setting-option">
                            <label class="switch">
                                <input type="checkbox" name="browser_notifications" <?php echo $notification_settings['browser_notifications'] ? 'checked' : ''; ?>>
                                <span class="slider round"></span>
                            </label>
                            <div class="setting-description">
                                <h4>Tarayıcı Bildirimleri</h4>
                                <p>Tarayıcı üzerinden anlık bildirimler alın</p>
                            </div>
                        </div>
                        <div class="setting-option">
                            <label class="switch">
                                <input type="checkbox" name="sms_notifications" <?php echo $notification_settings['sms_notifications'] ? 'checked' : ''; ?>>
                                <span class="slider round"></span>
                            </label>
                            <div class="setting-description">
                                <h4>SMS Bildirimleri</h4>
                                <p>Telefonunuza SMS bildirimleri alın</p>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="update_notification_settings">Bildirim Ayarlarını Kaydet</button>
                </form>
            </div>
        </div>
        
        <!-- Rozetler Tab -->
        <div class="tab-content" id="badges-tab">
            <div class="badges-container">
                <h3><i class="fas fa-award"></i> Kazandığınız Rozetler</h3>
                <div class="badges-grid">
                    <?php if (empty($earned_badges)): ?>
                        <p class="no-data">Henüz rozet kazanmadınız. Film izleyerek ve platformda aktif olarak rozetler kazanabilirsiniz.</p>
                    <?php else: ?>
                        <?php foreach ($earned_badges as $badge): ?>
                        <div class="badge-item" data-new="<?php echo isset($badge['new']) && $badge['new'] ? 'true' : 'false'; ?>">
                            <div class="badge-icon" style="background-color: <?php echo $badge['color']; ?>">
                                <i class="fas <?php echo $badge['icon']; ?>"></i>
                            </div>
                            <div class="badge-info">
                                <h4><?php echo $badge['name']; ?></h4>
                                <p><?php echo $badge['description']; ?></p>
                            </div>
                            <?php if (isset($badge['new']) && $badge['new']): ?>
                            <div class="new-badge">Yeni!</div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="upcoming-badges">
                    <h3><i class="fas fa-unlock"></i> Yakında Kazanabileceğiniz Rozetler</h3>
                    <div class="badges-grid">
                        <?php if (empty($upcoming_badges)): ?>
                            <p class="no-data">Tebrikler! Tüm rozetleri kazandınız.</p>
                        <?php else: ?>
                            <?php foreach ($upcoming_badges as $badge): ?>
                            <div class="badge-item locked">
                                <div class="badge-icon" style="background-color: <?php echo $badge['color']; ?>; opacity: 0.5;">
                                    <i class="fas <?php echo $badge['icon']; ?>"></i>
                                </div>
                                <div class="badge-info">
                                    <h4><?php echo $badge['name']; ?></h4>
                                    <p><?php echo $badge['description']; ?> (Şu an: <?php echo $badge['progress']['current']; ?>/<?php echo $badge['progress']['target']; ?>)</p>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo $badge['progress']['percent']; ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Rozet Dashboard -->
            <div class="badges-dashboard">
                <h3><i class="fas fa-chart-line"></i> Rozet İlerleme Durumu</h3>
                <div class="dashboard-stats">
                    <div class="stat-item">
                        <div class="stat-circle">
                            <div class="circle-fill" style="width: <?php echo (count($earned_badges) / (count($earned_badges) + count($upcoming_badges))) * 100; ?>%"></div>
                            <span class="stat-value"><?php echo count($earned_badges); ?> / <?php echo count($earned_badges) + count($upcoming_badges); ?></span>
                        </div>
                        <div class="stat-label">Toplam Rozet</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-bar">
                            <div class="bar-fill" style="width: <?php echo min(100, (count($favorite_movies) / 10) * 100); ?>%"></div>
                            <span class="stat-value"><?php echo count($favorite_movies); ?> / 10</span>
                        </div>
                        <div class="stat-label">Favori Film</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-bar">
                            <div class="bar-fill" style="width: <?php echo min(100, ($total_watched / 50) * 100); ?>%"></div>
                            <span class="stat-value"><?php echo $total_watched; ?> / 50</span>
                        </div>
                        <div class="stat-label">İzlenen Film</div>
                    </div>
                </div>
                
                <div class="badges-map">
                    <h4>Rozet Yolculuğunuz</h4>
                    <div class="map-path">
                        <div class="map-node <?php echo count($earned_badges) >= 1 ? 'completed' : ''; ?>" data-tippy-content="İlk rozeti kazandınız!">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="map-line <?php echo count($earned_badges) >= 3 ? 'completed' : ''; ?>"></div>
                        <div class="map-node <?php echo count($earned_badges) >= 3 ? 'completed' : ''; ?>" data-tippy-content="3 rozet kazandınız">
                            <i class="fas fa-medal"></i>
                        </div>
                        <div class="map-line <?php echo count($earned_badges) >= 5 ? 'completed' : ''; ?>"></div>
                        <div class="map-node <?php echo count($earned_badges) >= 5 ? 'completed' : ''; ?>" data-tippy-content="5 rozet kazandınız">
                            <i class="fas fa-award"></i>
                        </div>
                        <div class="map-line <?php echo count($earned_badges) >= 8 ? 'completed' : ''; ?>"></div>
                        <div class="map-node <?php echo count($earned_badges) >= 8 ? 'completed' : ''; ?>" data-tippy-content="8 rozet kazandınız">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="map-line <?php echo count($earned_badges) >= 10 ? 'completed' : ''; ?>"></div>
                        <div class="map-node <?php echo count($earned_badges) >= 10 ? 'completed' : ''; ?>" data-tippy-content="10+ rozet kazandınız! Uzman oldunuz!">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                
                <div class="badges-collection">
                    <div class="collection-header">
                        <h4>Rozet Koleksiyonunuz</h4>
                        <div class="view-toggle">
                            <button class="view-btn active" data-view="grid">
                                <i class="fas fa-th"></i>
                            </button>
                            <button class="view-btn" data-view="list">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                    <div class="collection-grid">
                        <?php
                        // Tüm rozetleri (kazanılmış ve kazanılmamış) göster
                        $all_badges = array_merge($earned_badges, $upcoming_badges);
                        foreach ($all_badges as $index => $badge):
                            $is_locked = !in_array($badge, $earned_badges);
                            $progress = $is_locked ? ($badge['progress']['percent'] ?? 0) : 100;
                        ?>
                        <div class="collection-item badge-animation fadeInUp" style="animation-delay: <?php echo $index * 0.1; ?>s" data-badge-id="<?php echo $badge['id']; ?>">
                            <div class="item-icon">
                                <i class="fas <?php echo $badge['icon']; ?>" style="color: <?php echo $badge['color']; ?>; opacity: <?php echo $is_locked ? '0.5' : '1'; ?>"></i>
                            </div>
                            <?php if ($is_locked): ?>
                            <div class="locked-badge">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div class="badge-progress">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hesap Silme Tab -->
        <div class="tab-content" id="delete-tab">
            <div class="delete-account-warning">
                <h3>Hesabınızı Silmek İstediğinize Emin Misiniz?</h3>
                <p>Bu işlem geri alınamaz. Tüm verileriniz kalıcı olarak silinecektir.</p>
                <div class="consequences">
                    <h4><i class="fas fa-exclamation-triangle"></i> Bu işlemden sonra:</h4>
                    <ul>
                        <li>Tüm izleme geçmişiniz silinecek</li>
                        <li>Favori filmleriniz ve listeleriniz kaybolacak</li>
                        <li>Premium aboneliğiniz (eğer varsa) sonlandırılacak</li>
                        <li>Hesap bilgileriniz tamamen silinecek</li>
                    </ul>
                </div>
                <form class="delete-form" method="POST" action="">
                    <div class="form-group">
                        <label for="confirm_delete">Onay için kullanıcı adınızı yazın: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></label>
                        <input type="text" id="confirm_delete" name="confirm_delete" required>
                    </div>
                    <div class="delete-reason">
                        <label for="delete_reason">Ayrılma nedeniniz (opsiyonel):</label>
                        <select id="delete_reason" name="delete_reason" class="custom-select">
                            <option value="">Bir neden seçin...</option>
                            <option value="pricing">Fiyatlandırma çok yüksek</option>
                            <option value="content">İçerik yeterli değil</option>
                            <option value="ux">Kullanım deneyimi sorunları</option>
                            <option value="alternative">Başka bir platform kullanıyorum</option>
                            <option value="other">Diğer</option>
                        </select>
                    </div>
                    <button type="submit" name="delete_account" class="delete-button">Hesabımı Kalıcı Olarak Sil</button>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab işlemleri
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetId = this.getAttribute('data-tab');
            
            // Aktif tab'ı güncelle
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Aktif içeriği göster
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Hedef içeriği bul ve göster
            const targetContent = document.getElementById(targetId);
            if (targetContent) {
                targetContent.classList.add('active');
                
                // Rozet sekmesi için animasyonları yeniden başlat
                if (targetId === 'badges-tab') {
                    setTimeout(animateStats, 300);
                }
            }
            
            // URL'yi güncelle
            history.pushState(null, null, `?tab=${targetId}`);
        });
    });
    
    // Rozet animasyonları
    const badgeItemsList = document.querySelectorAll('.badge-item:not(.locked)');
    
    // Rozet görünüm animasyonu
    badgeItemsList.forEach((badge, index) => {
        setTimeout(() => {
            badge.classList.add('animate');
            
            // Yeni kazanılmış rozetlere özel efekt
            if (badge.dataset.new === "true" && !badge.querySelector('.new-badge')) {
                const newBadge = document.createElement('div');
                newBadge.className = 'new-badge';
                newBadge.textContent = 'Yeni!';
                badge.appendChild(newBadge);
                
                // Tebrik sesi çal
                const audio = new Audio('sounds/achievement.mp3');
                audio.volume = 0.5;
                audio.play().catch(e => console.log('Ses çalınamadı:', e));
            }
        }, 200 * index);
    });
    
    // Rozet detay görüntüleme
    badgeItemsList.forEach(badge => {
        badge.addEventListener('click', function() {
            this.classList.toggle('expanded');
            
            if (this.classList.contains('expanded')) {
                // Diğer genişletilmiş rozetleri kapat
                badgeItemsList.forEach(otherBadge => {
                    if (otherBadge !== this && otherBadge.classList.contains('expanded')) {
                        otherBadge.classList.remove('expanded');
                    }
                });
                
                // Rozet ikon animasyonu
                const icon = this.querySelector('.badge-icon i');
                icon.style.transform = 'rotate(360deg) scale(1.2)';
                icon.style.transition = 'all 0.5s ease';
                
                setTimeout(() => {
                    icon.style.transform = 'rotate(0deg) scale(1)';
                }, 500);
            }
        });
    });
    
    // Yıldız patlaması efekti
    function createStars(container, count, color) {
        for(let i = 0; i < count; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            const size = Math.random() * 6 + 3;
            const angle = Math.random() * 360;
            const spread = Math.random() * 100 + 30;
            const delay = Math.random() * 0.3;
            
            star.style.width = `${size}px`;
            star.style.height = `${size}px`;
            star.style.position = 'absolute';
            star.style.backgroundColor = color || getRandomColor();
            star.style.borderRadius = '50%';
            star.style.top = '50%';
            star.style.left = '50%';
            star.style.transform = `translate(-50%, -50%)`;
            star.style.opacity = '0';
            
            container.appendChild(star);
            
            setTimeout(() => {
                star.style.transform = `translate(${Math.cos(angle * Math.PI / 180) * spread}px, ${Math.sin(angle * Math.PI / 180) * spread}px)`;
                star.style.opacity = '1';
                star.style.transition = `all 0.6s cubic-bezier(0.165, 0.84, 0.32, 1.28)`;
                
                setTimeout(() => {
                    star.style.opacity = '0';
                    setTimeout(() => {
                        star.remove();
                    }, 600);
                }, delay * 1000);
            }, delay * 1000);
        }
    }
    
    function getRandomColor() {
        const colors = ['#FFD700', '#FF6347', '#32CD32', '#4169E1', '#FF1493', '#9B30FF', '#00CED1'];
        return colors[Math.floor(Math.random() * colors.length)];
    }
    
    // Yeni rozet efektleri
    const newBadges = document.querySelectorAll('.badge-item[data-new="true"]');
    newBadges.forEach(badge => {
        setTimeout(() => {
            // Yıldız patlaması efekti
            const starContainer = document.createElement('div');
            starContainer.className = 'star-container';
            starContainer.style.position = 'absolute';
            starContainer.style.width = '100%';
            starContainer.style.height = '100%';
            starContainer.style.top = '0';
            starContainer.style.left = '0';
            starContainer.style.overflow = 'hidden';
            starContainer.style.pointerEvents = 'none';
            starContainer.style.zIndex = '1';
            
            badge.style.position = 'relative';
            badge.appendChild(starContainer);
            
            // Rozet rengine göre yıldız rengi belirle
            const badgeIcon = badge.querySelector('.badge-icon');
            const badgeColor = window.getComputedStyle(badgeIcon).backgroundColor;
            
            createStars(starContainer, 30, badgeColor);
            
            // Rozeti hafifçe hareket ettir
            badge.style.transform = 'translateY(-10px) scale(1.05)';
            badge.style.transition = 'transform 0.8s cubic-bezier(0.18, 0.89, 0.32, 1.28)';
            
            setTimeout(() => {
                badge.style.transform = 'translateY(0) scale(1)';
            }, 800);
            
            // Işıltı efekti
            const glow = document.createElement('div');
            glow.className = 'badge-glow';
            glow.style.position = 'absolute';
            glow.style.top = '0';
            glow.style.left = '0';
            glow.style.width = '100%';
            glow.style.height = '100%';
            glow.style.borderRadius = '12px';
            glow.style.boxShadow = `0 0 20px ${badgeColor}`;
            glow.style.opacity = '0';
            glow.style.zIndex = '0';
            glow.style.pointerEvents = 'none';
            
            badge.insertBefore(glow, badge.firstChild);
            
            setTimeout(() => {
                glow.style.opacity = '0.7';
                glow.style.transition = 'opacity 0.5s ease';
                
                setTimeout(() => {
                    glow.style.opacity = '0';
                    
                    setTimeout(() => {
                        glow.remove();
                    }, 500);
                }, 1500);
            }, 300);
        }, 1000);
    });
    
    // Kilidi açılmamış rozetlere hover efekti
    document.querySelectorAll('.badge-item.locked').forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            const progressBar = this.querySelector('.progress');
            if (progressBar) {
                progressBar.style.opacity = '1';
                progressBar.style.filter = 'saturate(1.5)';
            }
        });
        
        badge.addEventListener('mouseleave', function() {
            const progressBar = this.querySelector('.progress');
            if (progressBar) {
                progressBar.style.opacity = '0.8';
                progressBar.style.filter = 'saturate(1)';
            }
        });
    });
    
    // Rozet Dashboard için JavaScript
    tippy('[data-tippy-content]', {
        theme: 'light',
        animation: 'scale',
        duration: [300, 200]
    });
    
    // Rozet koleksiyonu görünümü değiştirme
    const viewButtons = document.querySelectorAll('.view-btn');
    const collectionContainer = document.querySelector('.collection-grid');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const view = this.dataset.view;
            
            // Aktif butonu güncelle
            viewButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Görünümü değiştir
            if (view === 'grid') {
                collectionContainer.classList.remove('collection-list');
                collectionContainer.classList.add('collection-grid');
            } else if (view === 'list') {
                collectionContainer.classList.remove('collection-grid');
                collectionContainer.classList.add('collection-list');
            }
        });
    });
    
    // Rozet detaylarını göster (tıklandığında)
    const collectionItems = document.querySelectorAll('.collection-item');
    
    collectionItems.forEach(item => {
        item.addEventListener('click', function() {
            const badgeId = this.dataset.badgeId;
            const matchingBadge = document.querySelector(`.badge-item[data-badge-id="${badgeId}"]`);
            
            if (matchingBadge) {
                // İlgili rozet kartına git ve vurgula
                matchingBadge.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Vurgulama animasyonu
                matchingBadge.classList.add('highlight-badge');
                setTimeout(() => {
                    matchingBadge.classList.remove('highlight-badge');
                }, 2000);
            }
        });
    });
    
    // Rozet ilerleme animasyonu
    const stats = document.querySelectorAll('.stat-circle, .stat-bar');
    
    function animateStats() {
        stats.forEach(stat => {
            const fill = stat.querySelector('.circle-fill, .bar-fill');
            const currentWidth = fill.style.width;
            fill.style.width = '0%';
            
            setTimeout(() => {
                fill.style.width = currentWidth;
            }, 300);
        });
    }
    
    // Animasyon ekle
    function addAnimationClasses() {
        const badgeAnimation = document.querySelectorAll('.badge-animation');
        badgeAnimation.forEach((element, index) => {
            setTimeout(() => {
                element.classList.add('animated');
            }, index * 100);
        });
    }
    
    // Map node animasyon
    const mapNodes = document.querySelectorAll('.map-node.completed');
    mapNodes.forEach((node, index) => {
        setTimeout(() => {
            node.classList.add('bounce');
            setTimeout(() => {
                node.classList.remove('bounce');
            }, 1000);
        }, 1000 + (index * 300));
    });
    
    // Sayfa ilk yüklendiğinde animasyonları çalıştır
    addAnimationClasses();

    // Rozet Modal Kontrolü
    const badgeModal = document.getElementById('badge-modal');
    const closeModalBtn = document.querySelector('.close-modal');
    const allBadgeItems = document.querySelectorAll('.badge-item, .collection-item');

    // Modalı kapat
    closeModalBtn.addEventListener('click', function() {
        badgeModal.style.display = 'none';
    });

    // Modal dışına tıklayınca kapat
    window.addEventListener('click', function(event) {
        if (event.target === badgeModal) {
            badgeModal.style.display = 'none';
        }
    });

    // Tüm rozetler için tıklama işlemi
    allBadgeItems.forEach(badge => {
        badge.addEventListener('click', function() {
            // Rozet ID'sinden rozet bilgilerini al
            const badgeId = this.dataset.badgeId || (this.classList.contains('collection-item') ? this.dataset.badgeId : 0);
            
            if (!badgeId) return;
            
            // Rozet bilgilerini bul
            const isLocked = this.classList.contains('locked');
            const badgeIcon = this.querySelector('.badge-icon i, .item-icon i').className;
            const badgeColor = this.querySelector('.badge-icon')?.style.backgroundColor || 
                              this.querySelector('.item-icon i')?.style.color || 
                              '#8A2BE2';
            const badgeName = this.querySelector('.badge-info h4')?.textContent || 'Rozet';
            const badgeDesc = this.querySelector('.badge-info p')?.textContent || 'Bu rozeti kazanmak için platformda aktif olun.';
            
            // Modal içeriğini doldur
            document.querySelector('.big-badge-icon i').className = badgeIcon;
            document.querySelector('.big-badge-icon').style.backgroundColor = badgeColor;
            document.querySelector('.badge-title h2').textContent = badgeName;
            document.querySelector('.badge-description').textContent = badgeDesc.split('(')[0]; // İlerleme bilgisini kaldır
            
            // İlerleme bilgisi
            const progressText = document.querySelector('.progress-text');
            const progressFill = document.querySelector('.progress-fill-detail');
            
            if (isLocked) {
                // Kilidi açılmamış rozet için ilerleme göster
                const progressMatch = badgeDesc.match(/\(Şu an: (\d+)\/(\d+)\)/);
                if (progressMatch) {
                    const current = parseInt(progressMatch[1]);
                    const target = parseInt(progressMatch[2]);
                    const percent = (current / target) * 100;
                    
                    progressText.textContent = `${current}/${target} Tamamlandı`;
                    progressFill.style.width = `${percent}%`;
                    
                    document.querySelector('.stat-row:first-child .stat-value').textContent = 'Henüz kazanılmadı';
                }
            } else {
                // Kazanılmış rozet için tam ilerleme göster
                progressText.textContent = 'Tamamlandı';
                progressFill.style.width = '100%';
                
                // Kazanılma tarihi - örnek olarak bugünün tarihini kullanıyoruz
                const today = new Date();
                const formattedDate = today.toLocaleDateString('tr-TR', {
                    day: 'numeric', 
                    month: 'long', 
                    year: 'numeric'
                });
                document.querySelector('.stat-row:first-child .stat-value').textContent = formattedDate;
            }
            
            // Açıklama metni
            const achievementText = document.querySelector('.achievement-text');
            achievementText.textContent = getAchievementText(badgeName, isLocked);
            
            // İlgili rozetleri göster
            const relatedBadgesGrid = document.querySelector('.related-badges-grid');
            relatedBadgesGrid.innerHTML = '';
            
            // Rastgele 4 ilgili rozet ekle (örnek)
            const otherBadges = Array.from(allBadgeItems).filter(item => item !== this).slice(0, 4);
            otherBadges.forEach(otherBadge => {
                const icon = otherBadge.querySelector('.badge-icon i, .item-icon i').className;
                const color = otherBadge.querySelector('.badge-icon')?.style.backgroundColor || 
                             otherBadge.querySelector('.item-icon i')?.style.color || 
                             '#8A2BE2';
                const name = otherBadge.querySelector('.badge-info h4')?.textContent || 'Rozet';
                
                const badgeElement = document.createElement('div');
                badgeElement.className = 'related-badge';
                badgeElement.innerHTML = `
                    <div class="mini-badge-icon" style="background-color: ${color}">
                        <i class="${icon}"></i>
                    </div>
                    <div class="mini-badge-name">${name}</div>
                `;
                relatedBadgesGrid.appendChild(badgeElement);
            });
            
            // Modalı göster
            badgeModal.style.display = 'flex';
        });
    });
    
    // Rozet açıklamı oluştur
    function getAchievementText(badgeName, isLocked) {
        if (isLocked) {
            return `Bu rozeti henüz kazanmadınız. ${badgeName} rozetini kazanmak için platformda daha fazla aktif olun ve gerekli kriterleri tamamlayın.`;
        }
        
        // Rozet adına göre özel açıklama
        switch(badgeName) {
            case 'Film Gurusu':
                return 'Bu rozeti kazanmak için 50+ film izlediniz. Film kültürünüz ve platformdaki aktiviteniz için tebrikler!';
            case 'Bilim Kurgu Hayranı':
                return 'Bu rozeti kazanmak için 10+ bilim kurgu filmi izlediniz. Uzay, zaman ve gelecek temalı filmlere olan ilginiz için tebrikler!';
            case 'GedikFlix Veteran':
                return 'Bu rozeti kazanmak için platformda 1+ ay aktif kaldınız. Uzun süreli üyeliğiniz için teşekkür ederiz!';
            case 'İlk Favori':
                return 'Bu rozeti ilk filminizi favorilere ekleyerek kazandınız. Film zevkinizi keşfetmenin ilk adımını attınız!';
            case 'Favori Koleksiyoner':
                return 'Bu rozeti 10 filmi favorilere ekleyerek kazandınız. Seçkin film zevkinizi gösterdiniz!';
            case 'Süper Hayran':
                return 'Bu rozeti 100 filmi favorilere ekleyerek kazandınız. Gerçek bir film tutkunusunuz!';
            default:
                return 'Bu rozeti platformdaki aktif katılımınız sonucunda kazandınız. Tebrikler!';
        }
    }
    
    // Rozet paylaşım butonu
    document.querySelector('.share-badge').addEventListener('click', function() {
        const badgeName = document.querySelector('.badge-title h2').textContent;
        
        alert(`"${badgeName}" rozetini sosyal medyada paylaşabilirsiniz!`);
        
        // Sosyal medya paylaşım kodu buraya eklenebilir
    });
});
</script>

<?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
<div class="admin-actions" style="margin-top: 30px; background: #f8f9fa; padding: 15px; border-radius: 8px;">
    <h3><i class="fas fa-tools"></i> Geliştirici Araçları</h3>
    <div style="display: flex; gap: 10px; margin-top: 10px;">
        <button id="test-badge" class="action-button" data-badge-id="6" style="background: #4CAF50;">
            İlk Favori Rozeti Kazan
        </button>
        <button id="test-badge" class="action-button" data-badge-id="7" style="background: #2196F3;">
            Favori Koleksiyoner Rozeti Kazan
        </button>
        <button id="reset-progress" class="action-button" style="background: #F44336;">
            Rozet İlerlemelerini Sıfırla
        </button>
    </div>
</div>

<script>
document.querySelectorAll('#test-badge').forEach(button => {
    button.addEventListener('click', function() {
        const badgeId = this.dataset.badgeId;
        
        fetch('badge-test.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'award',
                badge_id: badgeId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Rozet başarıyla verildi! Sayfayı yenileyin.');
                window.location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Hata:', error);
        });
    });
});

document.querySelector('#reset-progress').addEventListener('click', function() {
    if (confirm('Tüm rozet ilerlemelerini sıfırlamak istediğinize emin misiniz?')) {
        fetch('badge-test.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reset'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Rozet ilerlemeleri sıfırlandı! Sayfayı yenileyin.');
                window.location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Hata:', error);
        });
    }
});
</script>
<?php endif; ?>

<!-- Rozet Detay Modal -->
<div id="badge-modal" class="badge-modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <div class="badge-detail">
            <div class="badge-header">
                <div class="big-badge-icon">
                    <i class="fas fa-award"></i>
                </div>
                <div class="badge-title">
                    <h2>Rozet Adı</h2>
                    <p class="badge-description">Rozet açıklaması burada yer alacak.</p>
                </div>
            </div>
            <div class="badge-stats">
                <div class="stat-row">
                    <span class="stat-label">Kazanılma Tarihi:</span>
                    <span class="stat-value">12 Mart 2023</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">İlerleme:</span>
                    <div class="progress-container">
                        <div class="progress-bar-detail">
                            <div class="progress-fill-detail"></div>
                        </div>
                        <span class="progress-text">10/10 Tamamlandı</span>
                    </div>
                </div>
            </div>
            <div class="badge-info-section">
                <h3>Bu Rozeti Nasıl Kazandınız?</h3>
                <p class="achievement-text">
                    Bu rozeti kazanmak için 10 film favorilere eklediniz. 
                    Favori filmleri ekleyerek film zevkinizi kaydetmiş ve platformdaki deneyiminizi kişiselleştirmiş oldunuz.
                </p>
            </div>
            <div class="related-badges">
                <h3>İlgili Rozetler</h3>
                <div class="related-badges-grid">
                    <!-- İlgili rozetler buraya gelecek -->
                </div>
            </div>
            <div class="badge-actions">
                <button class="share-badge">
                    <i class="fas fa-share-alt"></i> Rozeti Paylaş
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</rewritten_file>

