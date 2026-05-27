<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$commands = [
    ['icon' => 'image',    'label' => 'Clone UI',     'prefix' => '/clone'],
    ['icon' => 'figma',    'label' => 'Import Figma', 'prefix' => '/figma'],
    ['icon' => 'monitor',  'label' => 'Create Page',  'prefix' => '/page'],
    ['icon' => 'sparkles', 'label' => 'Improve',      'prefix' => '/improve'],
];

function svgIcon(string $name, string $extraClass = ''): string {
    $cls = 'icon' . ($extraClass ? " $extraClass" : '');
    $icons = [
        'image'     => '<polyline points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>',
        'figma'     => '<path d="M5 5.5A3.5 3.5 0 0 1 8.5 2H12v7H8.5A3.5 3.5 0 0 1 5 5.5z"/><path d="M12 2h3.5a3.5 3.5 0 1 1 0 7H12V2z"/><path d="M12 12.5a3.5 3.5 0 1 1 7 0 3.5 3.5 0 1 1-7 0z"/><path d="M5 19.5A3.5 3.5 0 0 1 8.5 16H12v3.5a3.5 3.5 0 1 1-7 0z"/><path d="M5 12.5A3.5 3.5 0 0 1 8.5 9H12v7H8.5A3.5 3.5 0 0 1 5 12.5z"/>',
        'monitor'   => '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>',
        'sparkles'  => '<path d="M12 3l1.88 5.76L20 10l-6.12 1.24L12 17l-1.88-5.76L4 10l6.12-1.24z"/><path d="M5 3v4M3 5h4M19 17v4M17 19h4"/>',
        'paperclip' => '<path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>',
        'command'   => '<path d="M18 3a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3H6a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3V6a3 3 0 0 0-3-3 3 3 0 0 0-3 3 3 3 0 0 0 3 3h12a3 3 0 0 0 3-3 3 3 0 0 0-3-3z"/>',
        'send'      => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
        'loader'    => '<line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/>',
    ];
    $path = $icons[$name] ?? '';
    return '<svg class="' . $cls . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aram Bot — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/IBT/assets/css/chat.css?v=3">
</head>
<body>

<div class="bg-blob blob-1"></div>
<div class="bg-blob blob-2"></div>
<div class="bg-blob blob-3"></div>
<div class="mouse-gradient" id="mouseGradient"></div>

<div class="page-wrapper">

    <a href="/IBT/pages/logout.php" class="logout-btn">
        <span class="logout-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['username'], 0, 1))) ?></span>
        <span class="logout-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
        <svg class="logout-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
    </a>

    <div class="main-wrapper">

        <div class="header" id="pageHeader">
            <div style="width:100%;">
                <h1>How can I help today?</h1>
                <div class="header-line"></div>
            </div>
            <p>Type a command or ask a question</p>
        </div>

        <div class="chat-box" id="chatBox">

            <div class="messages" id="messages"></div>

            <div class="textarea-wrap">
                <textarea id="chatInput" placeholder="Ask Aram Bot a question..." rows="1"></textarea>
            </div>

            <div class="bottom-bar">
                <label class="web-search-toggle" title="Toggle web search">
                    <input type="checkbox" id="webSearchToggle" style="display:none;position:absolute;">
                    <span class="toggle-track">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <span>Web search</span>
                    </span>
                </label>
                <button class="send-btn idle" id="sendBtn" disabled>
                    <span id="sendIconWrap"><?= svgIcon('send') ?></span>
                    <span id="loaderWrap" style="display:none;"><?= svgIcon('loader', 'spin') ?></span>
                    <span>Send</span>
                </button>
            </div>
        </div>

    </div>
</div>

<div class="typing-bar" id="typingBar">
    <div class="typing-inner">
        <div class="aram-bot-avatar"><span>Aram Bot</span></div>
        <div class="typing-text">
            <span>Thinking</span>
            <div class="dots">
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script src="/IBT/assets/js/chat.js?v=3"></script>
</body>
</html>
