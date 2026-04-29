<?php
session_start();
require_once __DIR__ . '/app/Core/Database.php';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$notice = null;
$noticeType = 'success';

if (empty($_SESSION['feedback_csrf'])) {
    $_SESSION['feedback_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $topic = trim($_POST['topic'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!hash_equals($_SESSION['feedback_csrf'], $token)) {
        $notice = 'Security token expired. Please refresh and try again.';
        $noticeType = 'error';
    } elseif ($fullName === '' || $topic === '' || $message === '') {
        $notice = 'Please fill in your name, topic, and feedback message.';
        $noticeType = 'error';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $notice = 'Please enter a valid email address or leave it blank.';
        $noticeType = 'error';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $db->query("
            CREATE TABLE IF NOT EXISTS feedback_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(120) NOT NULL,
                email VARCHAR(160) DEFAULT NULL,
                topic VARCHAR(80) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $stmt = $db->prepare("
            INSERT INTO feedback_messages (full_name, email, topic, message)
            VALUES (?, ?, ?, ?)
        ");
        $emailValue = $email !== '' ? $email : null;
        $stmt->bind_param('ssss', $fullName, $emailValue, $topic, $message);
        $stmt->execute();
        $stmt->close();

        $_SESSION['feedback_csrf'] = bin2hex(random_bytes(32));
        $notice = 'Thank you. Your feedback has been submitted successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback | CivicConnect Bhubaneswar</title>
    <link rel="icon" href="assets/images/BRP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/public-pages.css">
</head>
<body>
    <header class="public-header">
        <a class="public-brand" href="index.html">
            <img src="assets/images/BRP.png" alt="CivicConnect logo">
            <span>CivicConnect Bhubaneswar</span>
        </a>
        <nav class="public-nav" aria-label="Public page navigation">
            <a href="index.html">Home</a>
            <a href="public_help.php">Help</a>
            <a href="contact_us.php">Contact</a>
        </nav>
    </header>

    <main class="public-page">
        <section class="public-hero">
            <p>Citizen Feedback</p>
            <h1>Share Your Feedback</h1>
            <span>Tell us what worked well, what felt confusing, or what should be improved in CivicConnect.</span>
        </section>

        <section class="feedback-card">
            <?php if ($notice): ?>
                <div class="notice <?php echo h($noticeType); ?>"><?php echo h($notice); ?></div>
            <?php endif; ?>

            <form class="feedback-form" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['feedback_csrf']); ?>">
                <div class="form-grid">
                    <div class="field">
                        <label for="full_name">Full Name</label>
                        <input id="full_name" name="full_name" value="<?php echo h($_POST['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="field">
                        <label for="email">Email Optional</label>
                        <input id="email" name="email" type="email" value="<?php echo h($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                <div class="field">
                    <label for="topic">Topic</label>
                    <select id="topic" name="topic" required>
                        <?php
                        $selectedTopic = $_POST['topic'] ?? '';
                        foreach (['Website Experience', 'Report Submission', 'Tracking Status', 'Accessibility', 'Other'] as $option):
                        ?>
                            <option value="<?php echo h($option); ?>" <?php echo $selectedTopic === $option ? 'selected' : ''; ?>><?php echo h($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="message">Feedback</label>
                    <textarea id="message" name="message" required><?php echo h($_POST['message'] ?? ''); ?></textarea>
                </div>
                <button class="submit-feedback" type="submit">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    Submit Feedback
                </button>
            </form>
        </section>
    </main>
</body>
</html>
