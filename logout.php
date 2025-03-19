<?php
session_start();

// Tüm session değişkenlerini temizle
$_SESSION = array();

// Session çerezini yok et
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Session'ı sonlandır
session_destroy();

// Kullanıcıyı ana sayfaya yönlendir
header("Location: index.php");
exit();