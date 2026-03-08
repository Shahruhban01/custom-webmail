<?php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['user_id'])) {
    redirect(APP_URL . '/dashboard/index.php');
} else {
    redirect(APP_URL . '/auth/login.php');
}
