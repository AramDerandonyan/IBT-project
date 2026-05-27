/* ── DOM references ── */
const chatInput     = document.getElementById('chatInput');
const sendBtn       = document.getElementById('sendBtn');
const sendIconWrap  = document.getElementById('sendIconWrap');
const loaderWrap    = document.getElementById('loaderWrap');
const typingBar     = document.getElementById('typingBar');
const webSearch     = document.getElementById('webSearchToggle');
const mouseGradient = document.getElementById('mouseGradient');
const messages      = document.getElementById('messages');
const pageHeader    = document.getElementById('pageHeader');
const pageWrapper   = document.querySelector('.page-wrapper');

let isTyping    = false;
let chatStarted = false;

/* ── Textarea auto-resize ── */
function adjustHeight() {
    chatInput.style.height = '60px';
    chatInput.style.height = Math.min(chatInput.scrollHeight, 200) + 'px';
}

/* ── Send button ready/idle state ── */
function refreshSendBtn() {
    const ready = chatInput.value.trim().length > 0 && !isTyping;
    sendBtn.className = 'send-btn ' + (ready ? 'ready' : 'idle');
    sendBtn.disabled  = !ready;
}

/* ──────────────────────────────────────────────
   HTML escape — keeps user text safe from XSS
────────────────────────────────────────────── */
function esc(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

/* ──────────────────────────────────────────────
   Inline markdown: bold, italic, inline code
   Runs AFTER HTML escaping so & < > are safe
────────────────────────────────────────────── */
function inline(text) {
    return text
        .replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>')
        .replace(/\*\*(.+?)\*\*/g,     '<strong>$1</strong>')
        .replace(/\*([^*\n]+?)\*/g,    '<em>$1</em>')
        .replace(/`([^`]+)`/g,         '<code>$1</code>');
}

/* ──────────────────────────────────────────────
   Full markdown renderer
   Handles: fenced code blocks, headings,
   lists, bold/italic/inline-code, hr, paragraphs
────────────────────────────────────────────── */
function renderMarkdown(raw) {
    let text = raw.replace(/\r\n/g, '\n').replace(/\n{3,}/g, '\n\n');

    // Step 1 — extract fenced code blocks (protect their contents)
    const blocks = [];
    text = text.replace(/```(\w*)\n?([\s\S]*?)```/g, function(_, lang, code) {
        blocks.push({ lang: lang, code: esc(code.trimEnd()) });
        return '\u0001B' + (blocks.length - 1) + '\u0001';
    });

    // Step 2 — HTML-escape everything outside code blocks
    text = esc(text);

    // Step 3 — process line by line
    const out = [];
    let inUl = false, inOl = false;

    function closeList() {
        if (inUl) { out.push('</ul>'); inUl = false; }
        if (inOl) { out.push('</ol>'); inOl = false; }
    }

    var lines = text.split('\n');
    for (var i = 0; i < lines.length; i++) {
        var line = lines[i];
        var t = line.trim();

        if (/^\u0001B\d+\u0001$/.test(t)) {
            closeList();
            out.push(t);
        } else if (t.indexOf('### ') === 0) {
            closeList(); out.push('<h3>' + inline(t.slice(4)) + '</h3>');
        } else if (t.indexOf('## ') === 0) {
            closeList(); out.push('<h2>' + inline(t.slice(3)) + '</h2>');
        } else if (t.indexOf('# ') === 0) {
            closeList(); out.push('<h1>' + inline(t.slice(2)) + '</h1>');
        } else if (/^-{3,}$/.test(t) || /^\*{3,}$/.test(t)) {
            closeList(); out.push('<hr>');
        } else if (/^[-*]\s/.test(t)) {
            if (inOl) { out.push('</ol>'); inOl = false; }
            if (!inUl) { out.push('<ul>'); inUl = true; }
            out.push('<li>' + inline(t.replace(/^[-*]\s+/, '')) + '</li>');
        } else if (/^\d+\.\s/.test(t)) {
            if (inUl) { out.push('</ul>'); inUl = false; }
            if (!inOl) { out.push('<ol>'); inOl = true; }
            out.push('<li>' + inline(t.replace(/^\d+\.\s+/, '')) + '</li>');
        } else if (t === '') {
            closeList(); out.push('<br>');
        } else {
            closeList(); out.push('<p>' + inline(t) + '</p>');
        }
    }
    closeList();

    // Step 4 — restore code blocks
    return out.join('').replace(/\u0001B(\d+)\u0001/g, function(_, idx) {
        var b = blocks[+idx];
        return '<pre><code>' + b.code + '</code></pre>';
    });
}

/* ──────────────────────────────────────────────
   Add a message bubble to the conversation
────────────────────────────────────────────── */
function addMessage(role, text) {
    // First ever message: collapse header, enter full-screen chat layout
    if (!chatStarted) {
        chatStarted = true;
        pageHeader.classList.add('hidden');
        pageWrapper.classList.add('chatting');
        messages.classList.add('has-messages');
    }

    var row = document.createElement('div');
    row.className = 'msg ' + role + ' msg-enter';

    if (role === 'assistant') {
        row.innerHTML =
            '<div class="msg-avatar">Z</div>' +
            '<div class="msg-bubble">' + renderMarkdown(text) + '</div>';
    } else {
        row.innerHTML =
            '<div class="msg-bubble">' + esc(text).replace(/\n/g, '<br>') + '</div>';
    }

    messages.appendChild(row);

    // Force reflow so browser sees opacity:0, then transition to 1
    row.getBoundingClientRect();
    requestAnimationFrame(function() { row.classList.remove('msg-enter'); });

    messages.scrollTop = messages.scrollHeight;
}

/* ──────────────────────────────────────────────
   Send a message to the AI
────────────────────────────────────────────── */
async function sendMessage() {
    var text = chatInput.value.trim();
    if (!text || isTyping) return;

    addMessage('user', text);
    chatInput.value = '';
    adjustHeight();

    isTyping = true;
    refreshSendBtn();
    sendIconWrap.style.display = 'none';
    loaderWrap.style.display   = 'inline-flex';
    typingBar.classList.add('show');

    try {
        var res  = await fetch('/IBT/pages/chat-api.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ message: text, webSearch: webSearch.checked }),
        });
        var data = await res.json();
        addMessage('assistant', data.error ? '⚠️ ' + data.error : data.reply);
    } catch (err) {
        var msg = err instanceof SyntaxError
            ? '⚠️ Server returned invalid response. Check PHP errors.'
            : '⚠️ Connection error: ' + err.message;
        addMessage('assistant', msg);
    }

    isTyping = false;
    refreshSendBtn();
    sendIconWrap.style.display = '';
    loaderWrap.style.display   = 'none';
    typingBar.classList.remove('show');
    chatInput.focus();
}

/* ── Event listeners ── */
chatInput.addEventListener('input', function() { adjustHeight(); refreshSendBtn(); });

chatInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

sendBtn.addEventListener('click', sendMessage);

chatInput.addEventListener('focus', function() { mouseGradient.classList.add('active'); });
chatInput.addEventListener('blur',  function() { mouseGradient.classList.remove('active'); });

document.addEventListener('mousemove', function(e) {
    mouseGradient.style.left = e.clientX + 'px';
    mouseGradient.style.top  = e.clientY + 'px';
});

/* ── Initialize button state on load ── */
refreshSendBtn();
