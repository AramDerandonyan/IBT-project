<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: /IBT/chat.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $pdo = db();

        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $error = 'Username or email is already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
            $ins->execute([$username, $email, $hash]);

            $_SESSION['user_id']  = (int) $pdo->lastInsertId();
            $_SESSION['username'] = $username;

            header('Location: /IBT/chat.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/IBT/assets/css/style.css">
    <link rel="stylesheet" href="/IBT/assets/css/auth.css">
</head>
<body>
<div class="bg-blob blob-1"></div>
<div class="bg-blob blob-2"></div>
<div class="bg-blob blob-3"></div>
<div class="auth-page">
    <div class="auth-card">
        <h1>Create account</h1>

        <?php if ($error !== ''): ?>
            <p class="auth-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" novalidate>
            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   autocomplete="username" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   autocomplete="email" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   autocomplete="new-password" required>

            <button type="submit" class="auth-btn">Register</button>
        </form>

        <p class="auth-switch">Already have an account? <a href="/IBT/pages/login.php">Log in</a></p>
    </div>
</div>
</body>
</html>
