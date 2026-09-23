<?php
$old_login = $_SESSION['old_login'] ?? '';
unset($_SESSION['old_login']);
?>
<section class="auth">
    <div class="panel auth__card">
        <img class="auth__logo" src="<?= e(url('/assets/img/logo.webp')) ?>" alt="CODLYX" width="84" height="84">
        <img class="auth__letras" src="<?= e(url('/assets/img/letras.webp')) ?>" alt="CODLYX" height="26">
        <p class="eyebrow">Acceso</p>
        <h1 class="auth__title">Iniciar sesión</h1>
        <p class="auth__sub">Entra con tu usuario para gestionar los préstamos.</p>

        <form class="form" method="post" action="<?= e(url('/login')) ?>" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <div class="field">
                <label class="field__label" for="usuario">Usuario</label>
                <input class="field__input" type="text" id="usuario" name="usuario"
                       maxlength="60" required autofocus placeholder="Tu usuario"
                       value="<?= e($old_login) ?>">
            </div>

            <div class="field">
                <label class="field__label" for="password">Contraseña</label>
                <input class="field__input" type="password" id="password" name="password"
                       required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-glow btn-glow--full">Entrar</button>
        </form>
    </div>
</section>
