<?php /** @var string $title @var string $content */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title><?= e($title) ?> · Núcleo de Pagos</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="app">
    <div class="aurora" aria-hidden="true"></div>
    <div class="grid-overlay" aria-hidden="true"></div>

    <header class="topbar">
        <div class="topbar__inner">
            <a class="brand" href="/">
                <span class="brand__mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" width="26" height="26" fill="none">
                        <path d="M16 2 4 9v14l12 7 12-7V9L16 2Z" stroke="url(#g)" stroke-width="1.6"/>
                        <path d="M16 10v12M11 13.5l5-3 5 3M11 18.5l5 3 5-3" stroke="url(#g)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <defs><linearGradient id="g" x1="0" y1="0" x2="32" y2="32">
                            <stop stop-color="#35e0d0"/><stop offset="1" stop-color="#7c5cff"/>
                        </linearGradient></defs>
                    </svg>
                </span>
                <span class="brand__text">Núcleo<span class="brand__accent">Pagos</span></span>
            </a>
            <nav class="topbar__nav">
                <a href="/" class="topbar__link">Panel</a>
                <a href="/#nuevo" class="btn-glow btn-glow--sm">+ Nuevo préstamo</a>
            </nav>
        </div>
    </header>

    <main class="container-xxl main">
        <?php if (!empty($_SESSION['flash_ok'])): ?>
            <div class="flash flash--ok" role="status"><?= e($_SESSION['flash_ok']) ?></div>
            <?php unset($_SESSION['flash_ok']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="flash flash--error" role="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <footer class="footer">
        <span>Núcleo de Pagos · gestión de préstamos</span>
        <span class="footer__dim">PHP · PostgreSQL 17 · Docker</span>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/app.js" defer></script>
</body>
</html>
