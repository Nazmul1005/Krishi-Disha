<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(); // Any logged in user

$cid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$uid = $_SESSION['user_id'];

// Check if user is part of this consultation
$consult = $pdo->prepare("SELECT c.*, u1.name as client_name, u2.name as provider_name 
    FROM CONSULTATION c 
    JOIN USER u1 ON c.client_id=u1.id 
    JOIN USER u2 ON c.provider_id=u2.id 
    WHERE c.id=?");
$consult->execute([$cid]);
$c = $consult->fetch();

if (!$c || ($c['client_id'] !== $uid && $c['provider_id'] !== $uid)) {
    die("Unauthorized access or consultation not found.");
}

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $msg = trim($_POST['message']);
    if ($msg) {
        $stmt = $pdo->prepare("INSERT INTO CONSULTATION_MESSAGE (consultation_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$cid, $uid, $msg]);
        header("Location: consultation_chat.php?id=" . $cid);
        exit;
    }
}

// Fetch messages
$msgs = $pdo->prepare("SELECT m.*, u.name, u.role, u.profile_image FROM CONSULTATION_MESSAGE m JOIN USER u ON m.sender_id=u.id WHERE m.consultation_id=? ORDER BY m.created_at ASC");
$msgs->execute([$cid]);
$messages = $msgs->fetchAll();

$page_title = 'Consultation Chat';
$other_party_name = ($c['client_id'] === $uid) ? $c['provider_name'] : $c['client_name'];
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<style>
.chat-container { background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,0.05); overflow:hidden; display:flex; flex-direction:column; height:70vh; min-height:500px; }
.chat-header { background:linear-gradient(135deg,#4c1d95,#7c3aed); padding:20px; color:#fff; display:flex; align-items:center; justify-content:space-between; }
.chat-body { flex:1; padding:20px; overflow-y:auto; background:#f8fafc; display:flex; flex-direction:column; gap:16px; }
.chat-msg { max-width:70%; display:flex; flex-direction:column; gap:4px; }
.chat-msg.me { align-self:flex-end; }
.chat-msg.them { align-self:flex-start; }
.msg-bubble { padding:12px 16px; border-radius:16px; font-size:14px; line-height:1.5; }
.chat-msg.me .msg-bubble { background:#7c3aed; color:#fff; border-bottom-right-radius:4px; }
.chat-msg.them .msg-bubble { background:#fff; color:#334155; border:1px solid #e2e8f0; border-bottom-left-radius:4px; }
.msg-info { font-size:11px; color:#94a3b8; display:flex; align-items:center; gap:6px; }
.chat-msg.me .msg-info { justify-content:flex-end; }
.chat-input { padding:20px; background:#fff; border-top:1px solid #e2e8f0; display:flex; gap:12px; align-items:center; }
.chat-input textarea { flex:1; resize:none; border:1px solid #cbd5e1; border-radius:24px; padding:12px 20px; font-size:14px; outline:none; transition:0.2s; height:46px; }
.chat-input textarea:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,0.1); }
.btn-send { width:46px; height:46px; border-radius:50%; background:#7c3aed; color:#fff; border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:0.2s; font-size:16px; }
.btn-send:hover { background:#6d28d9; transform:scale(1.05); }
</style>

<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title"><i class="fa-solid fa-comments me-2" style="color:#7c3aed;"></i>Discussion</div>
        </div>
        <div>
            <?php 
                $backUrl = '/KrishiDisha/index.php';
                if ($_SESSION['role'] === 'expert') $backUrl = '/KrishiDisha/expert/sessions.php';
                else if ($_SESSION['role'] === 'guide') $backUrl = '/KrishiDisha/guide/sessions.php';
                else $backUrl = '/KrishiDisha/modules/book_consultation.php';
            ?>
            <a href="<?= $backUrl ?>" class="btn-kd btn-kd-outline"><i class="fa-solid fa-arrow-left"></i> Back to Sessions</a>
        </div>
    </div>
    
    <div class="page-body">
        <div class="chat-container">
            <div class="chat-header">
                <div>
                    <h5 style="margin:0;color:#fff;font-family:'Nunito',sans-serif;"><?= htmlspecialchars($other_party_name) ?></h5>
                    <div style="font-size:12px;color:rgba(255,255,255,0.8);">
                        Topic: <?= mb_substr(htmlspecialchars($c['topic']), 0, 40) ?><?= strlen($c['topic'])>40?'...':'' ?>
                    </div>
                </div>
                <div class="badge-kd badge-<?= $c['status']=='completed'?'success':($c['status']=='confirmed'?'info':'warning') ?>">
                    <?= ucfirst($c['status']) ?>
                </div>
            </div>
            
            <div class="chat-body" id="chatBody">
                <?php if (empty($messages)): ?>
                <div class="text-center" style="margin-top:auto; margin-bottom:auto; color:#94a3b8;">
                    <i class="fa-solid fa-messages" style="font-size:48px; margin-bottom:16px; opacity:0.5;"></i>
                    <p>No messages yet.<br>Start the discussion by sending a message below.</p>
                </div>
                <?php endif; ?>
                
                <?php foreach ($messages as $m): $isMe = ($m['sender_id'] === $uid); ?>
                <div class="chat-msg <?= $isMe ? 'me' : 'them' ?>">
                    <div class="msg-bubble"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
                    <div class="msg-info">
                        <?php if (!$isMe): ?><b><?= htmlspecialchars($m['name']) ?></b> • <?php endif; ?>
                        <?= date('M d, g:i a', strtotime($m['created_at'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <form method="POST" class="chat-input" style="margin:0;">
                <textarea name="message" placeholder="Type your message here..." required></textarea>
                <button type="submit" class="btn-send"><i class="fa-solid fa-paper-plane"></i></button>
            </form>
        </div>
    </div>
</div>
</div>

<script>
// Auto scroll to bottom
const cb = document.getElementById('chatBody');
if (cb) cb.scrollTop = cb.scrollHeight;

// Submit on enter
document.querySelector('.chat-input textarea').addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        this.closest('form').submit();
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
