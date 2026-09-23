<?php /** @var string $mensaje */ ?>
<section class="error-state">
    <div class="panel error-state__panel">
        <div class="error-state__glyph">⚠</div>
        <h1 class="error-state__title"><?= e($title ?? 'Error') ?></h1>
        <p class="error-state__text"><?= e($mensaje ?? 'Algo salió mal.') ?></p>
        <a href="<?= e(url('/')) ?>" class="btn-glow">Volver al panel</a>
    </div>
</section>
