<?php
require_once __DIR__ . '/../includes/auth.php';

session_destroy();

header('Location: /IBT/pages/login.php');
exit;
