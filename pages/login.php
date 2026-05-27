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
    $identifier = trim($_POST['identifier'] ?? '');
    $password   =      $_POST['password']   ?? '';

    if ($identifier === '' || $password === '') {
        $error = 'All fields are required.';
    } else {
        $pdo  = db();
        $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Invalid credentials.';
        } else {
            $_SESSION['user_id']  = (int) $user['id'];
            $_SESSION['username'] = $user['username'];

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
    <title>Login — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/IBT/assets/css/style.css">
    <link rel="stylesheet" href="/IBT/assets/css/auth.css">
</head>
<body>
<div class="bg-blob blob-1"></div>
<div class="bg-blob blob-2"></div>
<div class="bg-blob blob-3"></div>
<div class="auth-page">
    <div class="auth-card">
        <h1>Welcome back</h1>

        <?php if ($error !== ''): ?>
            <p class="auth-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" novalidate>
            <label for="identifier">Username or email</label>
            <input type="text" id="identifier" name="identifier"
                   value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
                   autocomplete="username" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   autocomplete="current-password" required>

            <button type="submit" class="auth-btn">Log in</button>
        </form>

        <p class="auth-switch">No account yet? <a href="/IBT/pages/register.php">Register</a></p>
    </div>
</div>
</body>
</html>
