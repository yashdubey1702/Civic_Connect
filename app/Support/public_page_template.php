<?php
if (!function_exists('public_page_h')) {
    // Escapes public page output before rendering it in HTML.
    function public_page_h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$pageTitle = $pageTitle ?? 'CivicConnect Bhubaneswar';
$pageKicker = $pageKicker ?? 'Citizen Services';
$pageIntro = $pageIntro ?? '';
$sections = $sections ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo public_page_h($pageTitle); ?> | CivicConnect Bhubaneswar</title>
    <link rel="icon" href="/assets/images/BRP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/public-pages.css">
</head>
<body>
    <header class="public-header">
        <a class="public-brand" href="/town_issues/public/index.html">
            <img src="/assets/images/BRP.png" alt="CivicConnect logo">
            <span>CivicConnect Bhubaneswar</span>
        </a>
        <nav class="public-nav" aria-label="Public page navigation">
            <a href="/town_issues/public/index.html">Home</a>
            <a href="/town_issues/public/feedback.php">Feedback</a>
            <a href="/town_issues/public/contact_us.php">Contact</a>
        </nav>
    </header>

    <main class="public-page">
        <section class="public-hero">
            <p><?php echo public_page_h($pageKicker); ?></p>
            <h1><?php echo public_page_h($pageTitle); ?></h1>
            <?php if ($pageIntro): ?>
                <span><?php echo public_page_h($pageIntro); ?></span>
            <?php endif; ?>
        </section>

        <section class="content-card">
            <?php foreach ($sections as $section): ?>
                <article class="content-section">
                    <h2><?php echo public_page_h($section['title'] ?? ''); ?></h2>
                    <?php foreach (($section['body'] ?? []) as $paragraph): ?>
                        <p><?php echo public_page_h($paragraph); ?></p>
                    <?php endforeach; ?>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
