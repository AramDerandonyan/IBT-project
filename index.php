<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
$pageTitle = 'Home';
$chatOnlyNav = true;
require_once 'includes/header.php';
?>

<section class="simple-home">
    <h1><?= SITE_NAME ?></h1>
    <p class="meta"><?= STUDENT_NAME ?> | <?= STUDENT_SPECIALTY ?> | <?= STUDENT_FN ?></p>
    <p>This is my IBT project homepage. Use Chat to test the assistant feature.</p>
    <p><a class="chat-link" href="/IBT/chat.php">Open Chat</a></p>
</section>

<?php require_once 'includes/footer.php'; ?>
