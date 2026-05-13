<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}
include "config/db.php";

$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_message') {
        $receiver_id = $_POST['receiver_id'];
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        $internship_id = $_POST['internship_id'] ?? null;
        $message_type = $_POST['message_type'] ?? 'general';

        if (!empty($subject) && !empty($message)) {
            $stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, internship_id, subject, message, message_type) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('iiisss', $user_id, $receiver_id, $internship_id, $subject, $message, $message_type);
            $stmt->execute();
            $stmt->close();
            $success = 'Message sent successfully!';
        }
    } elseif ($_POST['action'] === 'mark_read') {
        $message_id = $_POST['message_id'];
        $stmt = $conn->prepare('UPDATE messages SET is_read = TRUE WHERE id = ? AND receiver_id = ?');
        $stmt->bind_param('ii', $message_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Get conversations
$conversations = [];
$stmt = $conn->prepare('
    SELECT 
        m.*, 
        sender.name as sender_name, sender.role as sender_role,
        receiver.name as receiver_name, receiver.role as receiver_role,
        i.title as internship_title
    FROM messages m
    JOIN users sender ON m.sender_id = sender.id
    JOIN users receiver ON m.receiver_id = receiver.id
    LEFT JOIN internships i ON m.internship_id = i.id
    WHERE (m.sender_id = ? OR m.receiver_id = ?)
    ORDER BY m.created_at DESC
');
$stmt->bind_param('ii', $user_id, $user_id);
$stmt->execute();
$all_messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group messages by conversation
$conversation_partners = [];
foreach ($all_messages as $msg) {
    $partner_id = ($msg['sender_id'] == $user_id) ? $msg['receiver_id'] : $msg['sender_id'];
    $partner_name = ($msg['sender_id'] == $user_id) ? $msg['receiver_name'] : $msg['sender_name'];
    $partner_role = ($msg['sender_id'] == $user_id) ? $msg['receiver_role'] : $msg['sender_role'];
    
    if (!isset($conversation_partners[$partner_id])) {
        $conversation_partners[$partner_id] = [
            'name' => $partner_name,
            'role' => $partner_role,
            'last_message' => $msg,
            'unread_count' => 0
        ];
    }
    
    if ($msg['receiver_id'] == $user_id && !$msg['is_read']) {
        $conversation_partners[$partner_id]['unread_count']++;
    }
    
    if (strtotime($msg['created_at']) > strtotime($conversation_partners[$partner_id]['last_message']['created_at'])) {
        $conversation_partners[$partner_id]['last_message'] = $msg;
    }
}

// Get specific conversation if requested
$selected_conversation = null;
$conversation_messages = [];
if (isset($_GET['conversation'])) {
    $partner_id = $_GET['conversation'];
    
    $stmt = $conn->prepare('
        SELECT m.*, sender.name as sender_name, receiver.name as receiver_name, i.title as internship_title
        FROM messages m
        JOIN users sender ON m.sender_id = sender.id
        JOIN users receiver ON m.receiver_id = receiver.id
        LEFT JOIN internships i ON m.internship_id = i.id
        WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
        ORDER BY m.created_at ASC
    ');
    $stmt->bind_param('iiii', $user_id, $partner_id, $partner_id, $user_id);
    $stmt->execute();
    $conversation_messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Mark messages as read
    $stmt = $conn->prepare('UPDATE messages SET is_read = TRUE WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE');
    $stmt->bind_param('ii', $partner_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    $selected_conversation = $conversation_partners[$partner_id] ?? null;
}

// Get potential message recipients based on user role
$potential_recipients = [];
if ($user_role === 'student') {
    // Students can message companies they've applied to
    $stmt = $conn->prepare('
        SELECT DISTINCT u.id, u.name, i.title as internship_title
        FROM users u
        JOIN internships i ON u.id = i.company_id
        JOIN applications a ON a.internship_id = i.id
        WHERE a.student_id = ? AND u.role = "company"
    ');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $potential_recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} elseif ($user_role === 'company') {
    // Companies can message students who applied to their internships
    $stmt = $conn->prepare('
        SELECT DISTINCT u.id, u.name, i.title as internship_title
        FROM users u
        JOIN applications a ON a.student_id = u.id
        JOIN internships i ON a.internship_id = i.id
        WHERE i.company_id = ? AND u.role = "student"
    ');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $potential_recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - Communication Center</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-bg: #f9fafb;
            --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --card-hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            margin: 20px auto;
            max-width: 1400px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            height: calc(100vh - 40px);
        }
        
        .header-section {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 20px 20px 0 0;
            position: relative;
            overflow: hidden;
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .header-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .nav-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .btn-custom {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary-custom {
            background: white;
            color: var(--primary-color);
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
            color: var(--primary-dark);
        }
        
        .btn-secondary-custom {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn-secondary-custom:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .content-section {
            display: flex;
            height: calc(100% - 120px);
        }
        
        .conversations-list {
            width: 350px;
            background: white;
            border-right: 1px solid #e5e7eb;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .conversation-item:hover {
            background: #f9fafb;
        }
        
        .conversation-item.active {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            border-left: 4px solid var(--primary-color);
        }
        
        .conversation-item.unread {
            background: #fef3c7;
        }
        
        .unread-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f9fafb;
        }
        
        .chat-header {
            background: white;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }
        
        .message {
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
        }
        
        .message.sent {
            justify-content: flex-end;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 16px;
            position: relative;
        }
        
        .message.received .message-bubble {
            background: white;
            color: var(--dark-color);
            border: 1px solid #e5e7eb;
        }
        
        .message.sent .message-bubble {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }
        
        .message-meta {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }
        
        .chat-input {
            background: white;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6b7280;
        }
        
        .compose-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .compose-modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .btn-send {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">Messages 💬</h1>
            <p class="mb-0">Communicate with companies and students</p>
            
            <div class="nav-buttons">
                <a href="<?php echo $user_role; ?>/dashboard.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <button class="btn-custom btn-secondary-custom" onclick="showComposeModal()">
                    <i class="fas fa-plus"></i> New Message
                </button>
                <a href="auth/logout.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="content-section">
        <!-- Conversations List -->
        <div class="conversations-list">
            <div class="p-3 border-bottom">
                <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Conversations</h6>
            </div>
            
            <?php if (empty($conversation_partners)): ?>
                <div class="text-center p-4">
                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                    <p class="text-muted">No conversations yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversation_partners as $partner_id => $partner): ?>
                    <a href="messages.php?conversation=<?php echo $partner_id; ?>" class="text-decoration-none">
                        <div class="conversation-item <?php echo ($selected_conversation && $selected_conversation['name'] === $partner['name']) ? 'active' : ''; ?> <?php echo $partner['unread_count'] > 0 ? 'unread' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-<?php echo $partner['role'] === 'company' ? 'building' : 'user'; ?>"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h6 class="mb-1 text-dark"><?php echo htmlspecialchars($partner['name']); ?></h6>
                                        <small class="text-muted"><?php echo date('M j', strtotime($partner['last_message']['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-0 text-muted small"><?php echo htmlspecialchars(substr($partner['last_message']['subject'], 0, 30)); ?></p>
                                </div>
                                <?php if ($partner['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?php echo $partner['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <?php if ($selected_conversation): ?>
                <!-- Chat Header -->
                <div class="chat-header">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-<?php echo $selected_conversation['role'] === 'company' ? 'building' : 'user'; ?>"></i>
                        </div>
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($selected_conversation['name']); ?></h6>
                            <small class="text-muted"><?php echo ucfirst($selected_conversation['role']); ?></small>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="showComposeModal()">
                        <i class="fas fa-edit"></i> New Message
                    </button>
                </div>

                <!-- Messages -->
                <div class="chat-messages">
                    <?php foreach ($conversation_messages as $msg): ?>
                        <div class="message <?php echo ($msg['sender_id'] == $user_id) ? 'sent' : 'received'; ?>">
                            <?php if ($msg['sender_id'] != $user_id): ?>
                                <div class="me-3">
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="fas fa-<?php echo $selected_conversation['role'] === 'company' ? 'building' : 'user'; ?> fa-sm"></i>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="message-bubble">
                                <div class="fw-bold mb-1"><?php echo htmlspecialchars($msg['subject']); ?></div>
                                <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                <div class="message-meta">
                                    <?php echo date('M j, Y g:i A', strtotime($msg['created_at'])); ?>
                                    <?php if ($msg['internship_title']): ?>
                                        • Re: <?php echo htmlspecialchars($msg['internship_title']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Quick Reply -->
                <div class="chat-input">
                    <form method="post" action="messages.php?conversation=<?php echo $_GET['conversation']; ?>">
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="receiver_id" value="<?php echo $_GET['conversation']; ?>">
                        <input type="hidden" name="subject" value="Re: <?php echo htmlspecialchars($selected_conversation['last_message']['subject']); ?>">
                        <input type="hidden" name="message_type" value="general">
                        
                        <div class="input-group">
                            <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                            <button type="submit" class="btn-send">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-comments fa-3x mb-3"></i>
                    <h5>Select a conversation</h5>
                    <p>Choose a conversation from the list to start messaging</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Compose Modal -->
<div class="compose-modal" id="composeModal">
    <div class="modal-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0"><i class="fas fa-plus me-2"></i>New Message</h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="hideComposeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success border-0 mb-3" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="messages.php">
            <input type="hidden" name="action" value="send_message">
            
            <div class="mb-3">
                <label class="form-label fw-semibold">To:</label>
                <select name="receiver_id" class="form-select" required>
                    <option value="">Select recipient</option>
                    <?php foreach ($potential_recipients as $recipient): ?>
                        <option value="<?php echo $recipient['id']; ?>">
                            <?php echo htmlspecialchars($recipient['name']); ?>
                            <?php if ($recipient['internship_title']): ?>
                                - <?php echo htmlspecialchars($recipient['internship_title']); ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Subject:</label>
                <input type="text" name="subject" class="form-control" placeholder="Message subject" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Message Type:</label>
                <select name="message_type" class="form-select">
                    <option value="general">General Question</option>
                    <option value="application">Application Related</option>
                    <option value="interview">Interview</option>
                    <option value="offer">Offer</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Message:</label>
                <textarea name="message" class="form-control" rows="5" placeholder="Type your message here..." required></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn-send flex-grow-1">
                    <i class="fas fa-paper-plane me-2"></i>Send Message
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="hideComposeModal()">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showComposeModal() {
    document.getElementById('composeModal').classList.add('show');
}

function hideComposeModal() {
    document.getElementById('composeModal').classList.remove('show');
}

// Auto-scroll to bottom of messages
window.addEventListener('load', function() {
    const chatMessages = document.querySelector('.chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});

// Close modal when clicking outside
document.getElementById('composeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideComposeModal();
    }
});
</script>
</body>
</html>
