<?php
require_once __DIR__ . '/includes/auth.php';
header('Location: ' . (is_logged_in() ? dashboard_url_for($_SESSION['role']) : '/login.php'));
exit;
