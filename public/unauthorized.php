<?php
session_start();

// Escapes output before rendering it in HTML.
function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$role = $_SESSION['user_type'] ?? '';
$isLoggedIn = !empty($_SESSION['logged_in']) || !empty($_SESSION['user_id']);

$dashboardUrl = '/town_issues/auth/login.php';
$dashboardLabel = 'Go to Login';

if ($isLoggedIn) {
    $dashboardLabel = 'Back to Dashboard';

    switch ($role) {
        case 'super_admin':
            $dashboardUrl = '/town_issues/admin/dashboard.php';
            break;
        case 'municipal_admin':
        case 'ward_admin':
            $dashboardUrl = '/town_issues/admin/municipal_dashboard.php';
            break;
        case 'volunteer':
            $dashboardUrl = '/town_issues/volunteers/dashboard.php';
            break;
        case 'citizen':
        default:
            $dashboardUrl = '/town_issues/user/dashboard.php';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted | Town Issues</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg: #eef4ff;
            --panel: #ffffff;
            --text: #172033;
            --muted: #667085;
            --line: #d9e2f2;
            --blue: #2563eb;
            --blue-dark: #1d4ed8;
            --warning: #f97316;
            --shadow: 0 24px 70px rgba(18, 38, 63, 0.16);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.18), transparent 34%),
                linear-gradient(135deg, #f8fbff 0%, var(--bg) 46%, #dfeaff 100%);
        }

        .access-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px 18px;
        }

        .access-card {
            width: min(100%, 860px);
            display: grid;
            grid-template-columns: 0.92fr 1.08fr;
            overflow: hidden;
            background: var(--panel);
            border: 1px solid rgba(217, 226, 242, 0.9);
            border-radius: 28px;
            box-shadow: var(--shadow);
        }

        .access-visual {
            position: relative;
            min-height: 420px;
            padding: 34px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #ffffff;
            background:
                linear-gradient(145deg, rgba(15, 23, 42, 0.95), rgba(30, 64, 175, 0.92)),
                url('/town_issues/assets/images/BRP.png') center 56% / 180px auto no-repeat;
        }

        .access-visual::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(transparent, rgba(15, 23, 42, 0.34)),
                repeating-linear-gradient(90deg, rgba(255,255,255,0.05) 0 1px, transparent 1px 58px);
            pointer-events: none;
        }

        .brand,
        .security-note {
            position: relative;
            z-index: 1;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            letter-spacing: 0;
        }

        .brand img {
            width: 44px;
            height: 44px;
            object-fit: contain;
            padding: 7px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.94);
        }

        .security-note {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: fit-content;
            padding: 12px 14px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            border-radius: 16px;
            background: rgba(15, 23, 42, 0.42);
            backdrop-filter: blur(10px);
            font-size: 0.94rem;
            line-height: 1.4;
        }

        .access-content {
            padding: 52px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .status-icon {
            width: 66px;
            height: 66px;
            display: grid;
            place-items: center;
            margin-bottom: 24px;
            border-radius: 22px;
            color: var(--warning);
            background: #fff3e8;
            box-shadow: inset 0 0 0 1px #fed7aa;
            font-size: 1.7rem;
        }

        .eyebrow {
            margin: 0 0 12px;
            color: var(--blue);
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        h1 {
            margin: 0;
            max-width: 520px;
            font-size: clamp(2rem, 5vw, 3.35rem);
            line-height: 1.02;
            letter-spacing: 0;
        }

        .message {
            margin: 18px 0 0;
            max-width: 560px;
            color: var(--muted);
            font-size: 1.02rem;
            line-height: 1.7;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 34px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 48px;
            padding: 0 20px;
            border-radius: 14px;
            font-weight: 800;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            color: #ffffff;
            background: var(--blue);
            box-shadow: 0 14px 30px rgba(37, 99, 235, 0.24);
        }

        .btn-primary:hover {
            background: var(--blue-dark);
        }

        .btn-secondary {
            color: var(--text);
            background: #f8fafc;
            border: 1px solid var(--line);
        }

        .hint {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid var(--line);
            color: #7a8599;
            font-size: 0.92rem;
            line-height: 1.55;
        }

        .hint i {
            color: var(--blue);
            margin-top: 3px;
        }

        @media (max-width: 760px) {
            .access-card {
                grid-template-columns: 1fr;
                border-radius: 22px;
            }

            .access-visual {
                min-height: 180px;
                padding: 24px;
                background-size: 118px auto;
            }

            .security-note {
                display: none;
            }

            .access-content {
                padding: 32px 24px;
            }

            .actions,
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="access-shell">
        <section class="access-card" aria-labelledby="access-title">
            <div class="access-visual">
                <div class="brand">
                    <img src="/town_issues/assets/images/BRP.png" alt="Town Issues logo">
                    <span>Town Issues</span>
                </div>
                <div class="security-note">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    <span>This area is protected for authorized users only.</span>
                </div>
            </div>

            <div class="access-content">
                <div class="status-icon" aria-hidden="true">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <p class="eyebrow">403 Access Restricted</p>
                <h1 id="access-title">You cannot open this page.</h1>
                <p class="message">
                    Your account does not have permission for this section. Return to your dashboard,
                    or sign in with an account that has the right access.
                </p>

                <div class="actions">
                    <a class="btn btn-primary" href="<?php echo h($dashboardUrl); ?>">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                        <?php echo h($dashboardLabel); ?>
                    </a>
                    <?php if ($isLoggedIn): ?>
                        <a class="btn btn-secondary" href="/town_issues/auth/logout.php">
                            <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                            Sign Out
                        </a>
                    <?php else: ?>
                        <a class="btn btn-secondary" href="/town_issues/index.php">
                            <i class="fa-solid fa-house" aria-hidden="true"></i>
                            Home
                        </a>
                    <?php endif; ?>
                </div>

                <div class="hint">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <span>If you believe this is a mistake, contact the municipal support team or your system administrator.</span>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
