<?php
/** @var array $usuarios */
$yo = \App\Auth::user();
?>

<a href="<?= e(url('/')) ?>" class="back-link">← Volver al panel</a>

<section class="detail">
    <div class="panel detail__hero">
        <div class="detail__headtext">
            <p class="eyebrow">Administración</p>
            <h1 class="detail__title">Usuarios</h1>
            <p class="detail__meta">
                <span class="chip">Rol Dios: control total</span>
                <span class="chip chip--muted">Las contraseñas se guardan cifradas (bcrypt)</span>
            </p>
        </div>
    </div>

    <div class="layout-split">
        <!-- Crear usuario -->
        <section class="panel panel--form">
            <div class="panel__head">
                <h2 class="panel__title">Nuevo usuario</h2>
                <span class="panel__hint">Solo el rol Dios puede crearlos</span>
            </div>

            <form class="form" method="post" action="<?= e(url('/usuarios/crear')) ?>" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                <div class="field">
                    <label class="field__label" for="usuario">Usuario</label>
                    <input class="field__input" type="text" id="usuario" name="usuario"
                           minlength="3" maxlength="60" required placeholder="Ej. maria">
                </div>

                <div class="field">
                    <label class="field__label" for="password">Contraseña</label>
                    <input class="field__input" type="text" id="password" name="password"
                           minlength="8" required placeholder="Mínimo 8 caracteres">
                    <span class="field__hint">Se cifra al guardar; nadie podrá verla después.</span>
                </div>

                <div class="field">
                    <label class="field__label" for="rol">Rol</label>
                    <div class="field__select">
                        <select class="field__input" id="rol" name="rol">
                            <option value="normal" selected>Normal (usa la app)</option>
                            <option value="dios">Dios (además crea usuarios)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-glow btn-glow--full">Crear usuario</button>
            </form>
        </section>

        <!-- Lista de usuarios -->
        <section class="panel panel--list">
            <div class="panel__head">
                <h2 class="panel__title">Usuarios registrados</h2>
                <span class="panel__hint"><?= count($usuarios) ?></span>
            </div>

            <div class="user-list">
                <?php foreach ($usuarios as $u):
                    $esDios = $u['rol'] === 'dios';
                    $soyYo  = (int) $u['id'] === (int) $yo['id'];
                ?>
                    <div class="user-row">
                        <div class="user-row__main">
                            <span class="user-chip__dot <?= $esDios ? 'user-chip__dot--dios' : '' ?>"></span>
                            <span class="user-row__name"><?= e($u['usuario']) ?><?= $soyYo ? ' (tú)' : '' ?></span>
                            <span class="badge <?= $esDios ? 'badge--dios' : '' ?>"><?= $esDios ? 'Dios' : 'Normal' ?></span>
                        </div>
                        <div class="user-row__side">
                            <time class="pago__fecha"><?= e(date('d/m/Y', strtotime((string) $u['creado_en']))) ?></time>
                            <?php if (!$soyYo): ?>
                                <form method="post" action="<?= e(url('/usuarios/' . (int) $u['id'] . '/eliminar')) ?>"
                                      onsubmit="return confirm('¿Eliminar al usuario <?= e($u['usuario']) ?>?');">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <button type="submit" class="pago__del" title="Eliminar usuario" aria-label="Eliminar usuario">✕</button>
                                </form>
                            <?php else: ?>
                                <span class="user-row__lock" title="No puedes eliminar tu propio usuario">🔒</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>
