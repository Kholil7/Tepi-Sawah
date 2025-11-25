<?php

define('SESSION_TIMEOUT', 7200);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    session_set_cookie_params(SESSION_TIMEOUT);
    session_start();
}

function getBasePath() {
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $pathParts = explode('/', trim($scriptPath, '/'));
    
    if (count($pathParts) >= 3) {
        return '/' . $pathParts[0];
    }
    return '';
}

function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        
        if ($elapsed > SESSION_TIMEOUT) {
            $role = $_SESSION['role'] ?? null;
            $basePath = getBasePath();
            
            session_unset();
            session_destroy();
            
            if ($role === 'kasir') {
                header("Location: {$basePath}/kasir/auth/register.php?timeout=1");
            } elseif ($role === 'owner') {
                header("Location: {$basePath}/owner/auth/login.php?timeout=1");
            } else {
                header("Location: {$basePath}/");
            }
            exit();
        }
    }
    
    $_SESSION['last_activity'] = time();
}

function getSessionTimeRemaining() {
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        $remaining = SESSION_TIMEOUT - $elapsed;
        return max(0, $remaining);
    }
    return 0;
}

function getSessionTimeRemainingFormatted() {
    $seconds = getSessionTimeRemaining();
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . " jam " . $minutes . " menit";
    } else {
        return $minutes . " menit";
    }
}

function isLoggedIn() {
    checkSessionTimeout();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && isset($_SESSION['role']);
}

function checkLogin() {
    if (!isLoggedIn()) {
        session_unset();
        session_destroy();
        
        $currentUrl = $_SERVER['REQUEST_URI'];
        $basePath = getBasePath();
        
        if (strpos($currentUrl, '/kasir/') !== false) {
            header("Location: {$basePath}/kasir/auth/register.php");
        } elseif (strpos($currentUrl, '/owner/') !== false) {
            header("Location: {$basePath}/owner/auth/login.php");
        } else {
            header("Location: {$basePath}/");
        }
        exit();
    }
}

function checkRole($allowedRole) {
    checkLogin();
    
    $userRole = $_SESSION['role'] ?? null;
    $basePath = getBasePath();
    
    if ($userRole !== $allowedRole) {
        if ($userRole === 'kasir') {
            header("Location: {$basePath}/kasir/inside/dashboard_kasir.php");
        } elseif ($userRole === 'owner') {
            header("Location: {$basePath}/owner/inside/dashboard.php");
        }
        exit();
    }
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        $role = getUserRole();
        $basePath = getBasePath();
        
        if ($role === 'kasir') {
            header("Location: {$basePath}/kasir/inside/dashboard_kasir.php");
        } elseif ($role === 'owner') {
            header("Location: {$basePath}/owner/inside/dashboard.php");
        }
        exit();
    }
}

function setUserSession($userData) {
    $_SESSION['user_id'] = $userData['id_pengguna'] ?? $userData['id'];
    $_SESSION['username'] = $userData['nama'];
    $_SESSION['email'] = $userData['email'];
    $_SESSION['role'] = $userData['role'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
}

function getUserSession() {
    if (isLoggedIn()) {
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'login_time' => $_SESSION['login_time'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null
        ];
    }
    return null;
}

function getUserId() {
    checkSessionTimeout();
    return $_SESSION['user_id'] ?? null;
}

function getUsername() {
    checkSessionTimeout();
    return $_SESSION['username'] ?? null;
}

function getUserEmail() {
    checkSessionTimeout();
    return $_SESSION['email'] ?? null;
}

function getUserRole() {
    checkSessionTimeout();
    return $_SESSION['role'] ?? null;
}

function isKasir() {
    return getUserRole() === 'kasir';
}

function isOwner() {
    return getUserRole() === 'owner';
}

function doLogout() {
    $role = $_SESSION['role'] ?? null;
    $basePath = getBasePath();
    
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
    
    if ($role === 'kasir') {
        header("Location: {$basePath}/kasir/auth/register.php");
    } elseif ($role === 'owner') {
        header("Location: {$basePath}/owner/auth/login.php");
    } else {
        header("Location: {$basePath}/");
    }
    exit();
}

?>