<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../register.php');
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kasir') {
    session_destroy();
    header('Location: ../register.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    session_destroy();
    header('Location: ../register.php');
    exit;
}

$timeout_duration = 1800;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: ../register.php?message=session_expired');
    exit;
}

$_SESSION['last_activity'] = time();
?>