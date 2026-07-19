<?php
require_once __DIR__ . '/includes/auth_middleware.php';
$user = require_web_auth();
$activePage = 'messages';
$initialWith = (int) ($_GET['with'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages — TutorFinder</title>
<link rel="stylesheet" href="/assets/app.css">
<style>
    .messages-layout { display: grid; grid-template-columns: 280px 1fr; gap: 20px; }
    @media (max-width: 700px) { .messages-layout { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<main class="app-main">
    <h1 class="page-title">Messages</h1>
    <div id="errorMsg" class="error-msg"></div>

    <div class="messages-layout">
        <div>
            <ul id="conversationList" class="conversation-list"></ul>
        </div>
        <div>
            <div id="chatEmpty" class="card empty-state">Select a conversation to start chatting.</div>
            <div id="chatWindow" class="chat-window" style="display:none;">
                <div class="chat-messages" id="chatMessages"></div>
                <form class="chat-input-row" id="chatForm">
                    <input type="text" id="chatInput" placeholder="Type a message..." autocomplete="off" required>
                    <button type="submit">Send</button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
const currentUserId = <?= json_encode($user['id']) ?>;
let activeOtherId = <?= json_encode($initialWith ?: null) ?>;

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function formatTime(iso) {
    const d = new Date(iso.replace(' ', 'T'));
    return d.toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short' });
}

async function loadConversations() {
    const list = document.getElementById('conversationList');
    try {
        const res = await fetch('/api/messages/conversations.php');
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Failed to load conversations');

        let conversations = data.conversations;

        // If opened with a ?with= param for a user not yet in the list
        // (e.g. first message to a new tutor), add a placeholder entry.
        if (activeOtherId && !conversations.some(c => c.other_user_id === activeOtherId)) {
            conversations = [{ other_user_id: activeOtherId, other_user_name: null, last_message: null, unread_count: 0 }, ...conversations];
        }

        if (conversations.length === 0) {
            list.innerHTML = '<li class="empty-state" style="border:none;">No conversations yet.</li>';
            return;
        }

        list.innerHTML = conversations.map(c => `
            <li class="${c.unread_count > 0 ? 'unread' : ''}" data-user-id="${c.other_user_id}"
                onclick="openConversation(${c.other_user_id})">
                <div>${escapeHtml(c.other_user_name || ('User #' + c.other_user_id))}</div>
                <div style="font-weight:400; color:var(--muted); font-size:0.85rem;">
                    ${c.last_message ? escapeHtml(c.last_message.slice(0, 40)) : 'No messages yet'}
                </div>
            </li>
        `).join('');

        if (activeOtherId) {
            openConversation(activeOtherId);
        }
    } catch (err) {
        document.getElementById('errorMsg').textContent = err.message;
        document.getElementById('errorMsg').style.display = 'block';
    }
}

async function openConversation(otherId) {
    activeOtherId = otherId;
    document.getElementById('chatEmpty').style.display = 'none';
    document.getElementById('chatWindow').style.display = 'flex';

    document.querySelectorAll('.conversation-list li').forEach(li => {
        li.style.borderColor = li.dataset.userId == otherId ? 'var(--primary)' : '';
    });

    try {
        const res = await fetch('/api/messages/thread.php?with=' + otherId);
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Failed to load messages');

        const chatMessages = document.getElementById('chatMessages');
        chatMessages.innerHTML = data.messages.map(m => `
            <div class="chat-bubble ${m.sender_id == currentUserId ? 'mine' : 'theirs'}">
                ${escapeHtml(m.body)}
                <div style="font-size:0.7rem; opacity:0.7; margin-top:4px;">${formatTime(m.created_at)}</div>
            </div>
        `).join('') || '<div class="empty-state">No messages yet — say hello!</div>';

        chatMessages.scrollTop = chatMessages.scrollHeight;
    } catch (err) {
        document.getElementById('errorMsg').textContent = err.message;
        document.getElementById('errorMsg').style.display = 'block';
    }
}

document.getElementById('chatForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!activeOtherId) return;

    const input = document.getElementById('chatInput');
    const body = input.value.trim();
    if (!body) return;

    try {
        const res = await fetch('/api/messages/send.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ recipient_id: activeOtherId, body }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Failed to send message');

        input.value = '';
        openConversation(activeOtherId);
        loadConversations();
    } catch (err) {
        alert(err.message);
    }
});

loadConversations();
</script>

</body>
</html>
