<?php /** @var string $title @var string $content */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#0A0E14">
    <title><?= e($title) ?> · CODLYX</title>

    <link rel="icon" type="image/x-icon" href="<?= e(url('/assets/img/favicon.ico')) ?>">
    <link rel="shortcut icon" href="<?= e(url('/assets/img/favicon.ico')) ?>">
    <link rel="apple-touch-icon" href="<?= e(url('/assets/img/logo.webp')) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('/assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="app">
    <div class="aurora" aria-hidden="true"></div>
    <div class="grid-overlay" aria-hidden="true"></div>

    <?php $__u = \App\Auth::user(); ?>
    <header class="topbar">
        <div class="topbar__inner">
            <a class="brand" href="<?= e(url($__u ? '/' : '/login')) ?>" aria-label="CODLYX">
                <img class="brand__logo" src="<?= e(url('/assets/img/logo.webp')) ?>" alt="Logo CODLYX" width="40" height="40">
                <img class="brand__letras" src="<?= e(url('/assets/img/letras.webp')) ?>" alt="CODLYX" height="20">
                <span class="brand__tag">Pagos</span>
            </a>
            <?php if ($__u): ?>
                <nav class="topbar__nav">
                    <a href="<?= e(url('/')) ?>" class="topbar__link">Panel</a>
                    <?php if (\App\Auth::isDios()): ?>
                        <a href="<?= e(url('/usuarios')) ?>" class="topbar__link">Usuarios</a>
                    <?php endif; ?>
                    <span class="user-chip" title="<?= e($__u['rol'] === 'dios' ? 'Rol: Dios' : 'Rol: Normal') ?>">
                        <span class="user-chip__dot <?= $__u['rol'] === 'dios' ? 'user-chip__dot--dios' : '' ?>"></span>
                        <?= e($__u['usuario']) ?>
                        <?php if ($__u['rol'] === 'dios'): ?><small class="user-chip__rol">Dios</small><?php endif; ?>
                    </span>
                    <form method="post" action="<?= e(url('/logout')) ?>" class="logout-form">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="topbar__link topbar__logout">Salir</button>
                    </form>
                </nav>
            <?php endif; ?>
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

    <footer class="site-footer" role="contentinfo">
        <div class="site-footer__inner">
            <div class="site-footer__grid">
                <!-- Marca -->
                <div class="site-footer__brand">
                    <div class="site-footer__brandrow">
                        <img src="<?= e(url('/assets/img/logo.webp')) ?>" alt="CODLYX" width="34" height="34">
                        <span class="site-footer__brandtext">CODLYX <span>Systems</span></span>
                    </div>
                    <p>Soluciones tecnológicas profesionales orientadas a resultados. Desarrollo, infraestructura y automatización para empresas que necesitan crecer con tecnología real.</p>
                </div>

                <!-- Navegación -->
                <div class="site-footer__col">
                    <h5 class="site-footer__heading">Navegación</h5>
                    <ul class="site-footer__links">
                        <li><a href="https://web.codlyx.com.mx/index.php">Inicio</a></li>
                        <li><a href="https://web.codlyx.com.mx/servicios.php">Servicios</a></li>
                        <li><a href="https://web.codlyx.com.mx/proyectos.php">Proyectos</a></li>
                        <li><a href="https://web.codlyx.com.mx/nosotros.php">Sobre mí</a></li>
                        <li><a href="https://web.codlyx.com.mx/proceso.php">Proceso</a></li>
                        <li><a href="https://web.codlyx.com.mx/tecnologias.php">Stack</a></li>
                        <li><a href="https://web.codlyx.com.mx/recursos.php">Recursos</a></li>
                        <li><a href="https://web.codlyx.com.mx/contacto">Contacto</a></li>
                    </ul>
                </div>

                <!-- Servicios -->
                <div class="site-footer__col">
                    <h5 class="site-footer__heading">Servicios</h5>
                    <ul class="site-footer__links">
                        <li><a href="https://web.codlyx.com.mx/servicios.php">Desarrollo a medida</a></li>
                        <li><a href="https://web.codlyx.com.mx/servicios.php">Datos y bases de datos</a></li>
                        <li><a href="https://web.codlyx.com.mx/servicios.php">Automatización e integración</a></li>
                        <li><a href="https://web.codlyx.com.mx/servicios.php">Infraestructura y soporte</a></li>
                        <li><a href="https://web.codlyx.com.mx/servicios.php">Consultoría tecnológica</a></li>
                        <li><a href="https://web.codlyx.com.mx/recursos.php">Recursos / Blog</a></li>
                    </ul>
                </div>

                <!-- Contacto -->
                <div class="site-footer__col">
                    <h5 class="site-footer__heading">Contacto</h5>
                    <div class="site-footer__contact">
                        <a href="mailto:lucio.s.isc@gmail.com">✉ lucio.s.isc@gmail.com</a>
                        <a href="tel:+525621111752">✆ +52 56 2111 1752</a>
                        <a href="https://www.linkedin.com/in/luciosalck/" target="_blank" rel="noopener noreferrer">in · LinkedIn</a>
                        <a href="https://github.com/LucioSalva" target="_blank" rel="noopener noreferrer">◆ GitHub</a>
                    </div>
                </div>
            </div>

            <div class="site-footer__bottom">
                <p class="site-footer__copy">&copy; 2026 CODLYX Systems. Todos los derechos reservados.</p>
                <ul class="site-footer__legal">
                    <li><a href="https://web.codlyx.com.mx/aviso-privacidad.php">Aviso de privacidad</a></li>
                </ul>
                <div class="site-footer__social" aria-label="Redes sociales">
                    <a href="https://www.linkedin.com/in/luciosalck/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn de CODLYX Systems">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.94v5.67H9.36V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.38-1.85 3.61 0 4.27 2.38 4.27 5.47v6.27zM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12zM7.12 20.45H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/></svg>
                    </a>
                    <a href="https://github.com/LucioSalva" target="_blank" rel="noopener noreferrer" aria-label="GitHub de CODLYX Systems">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 .5C5.37.5 0 5.87 0 12.5c0 5.3 3.44 9.8 8.2 11.39.6.11.82-.26.82-.58 0-.28-.01-1.04-.02-2.05-3.34.73-4.04-1.61-4.04-1.61-.55-1.39-1.34-1.76-1.34-1.76-1.09-.75.08-.73.08-.73 1.21.09 1.85 1.24 1.85 1.24 1.07 1.84 2.81 1.31 3.5 1 .11-.78.42-1.31.76-1.61-2.67-.3-5.47-1.33-5.47-5.93 0-1.31.47-2.38 1.24-3.22-.13-.3-.54-1.52.11-3.18 0 0 1.01-.32 3.3 1.23.96-.27 1.98-.4 3-.4s2.04.13 3 .4c2.29-1.55 3.3-1.23 3.3-1.23.66 1.66.25 2.88.12 3.18.77.84 1.23 1.91 1.23 3.22 0 4.61-2.8 5.63-5.48 5.92.43.37.82 1.1.82 2.22 0 1.6-.02 2.89-.02 3.29 0 .32.22.7.83.58C20.57 22.29 24 17.8 24 12.5 24 5.87 18.63.5 12 .5z"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(url('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
