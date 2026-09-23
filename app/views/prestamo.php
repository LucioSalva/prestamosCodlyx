<?php
/** @var array $prestamo @var array $pagos */
$total     = (float) $prestamo['total_pagar'];
$abonado   = (float) $prestamo['abonado'];
$saldo     = (float) $prestamo['saldo'];
$pct       = $total > 0 ? min(100, round($abonado / $total * 100, 1)) : 0.0;
$liquidado = $saldo <= 0.001;
$periodos  = (int) $prestamo['num_periodos'];
?>

<a href="/" class="back-link">← Volver al panel</a>

<section class="detail">
    <!-- Encabezado + gauge -->
    <div class="panel detail__hero">
        <div class="detail__headtext">
            <p class="eyebrow">Préstamo #<?= (int) $prestamo['id'] ?></p>
            <h1 class="detail__title"><?= e($prestamo['concepto']) ?></h1>
            <p class="detail__meta">
                <?php if (!empty($prestamo['deudor'])): ?>
                    <span class="chip">👤 <?= e($prestamo['deudor']) ?></span>
                <?php endif; ?>
                <span class="chip"><?= e(frecuencia_label($prestamo['frecuencia'])) ?></span>
                <span class="chip"><?= e(money($prestamo['cuota'])) ?> × <?= $periodos ?> <?= e(periodo_sustantivo($prestamo['frecuencia'], $periodos)) ?></span>
                <span class="chip chip--muted">Prestado: <?= e(money($prestamo['monto_prestado'])) ?></span>
            </p>
        </div>

        <div class="gauge gauge--lg" data-gauge>
            <svg viewBox="0 0 120 120" class="gauge__svg">
                <circle class="gauge__track" cx="60" cy="60" r="52"/>
                <circle class="gauge__fill" cx="60" cy="60" r="52" data-pct="<?= $pct ?>"/>
            </svg>
            <div class="gauge__center">
                <span class="gauge__pct" data-gauge-pct><?= number_format($pct, 0) ?><small>%</small></span>
                <span class="gauge__label">pagado</span>
            </div>
        </div>
    </div>

    <!-- Métricas -->
    <div class="metric-grid">
        <div class="metric">
            <span class="metric__label">Cantidad total</span>
            <span class="metric__value mono"><?= e(money($total)) ?></span>
        </div>
        <div class="metric metric--ok">
            <span class="metric__label">Total abonado</span>
            <span class="metric__value mono" data-abonado><?= e(money($abonado)) ?></span>
        </div>
        <div class="metric metric--due">
            <span class="metric__label">Saldo restante</span>
            <span class="metric__value mono" data-saldo><?= e(money($saldo)) ?></span>
        </div>
        <div class="metric">
            <span class="metric__label">Abonos</span>
            <span class="metric__value mono" data-numpagos><?= (int) $prestamo['num_pagos'] ?></span>
        </div>
    </div>

    <div class="layout-split">
        <!-- Registrar abono -->
        <section class="panel panel--form">
            <div class="panel__head">
                <h2 class="panel__title">Registrar abono</h2>
                <span class="panel__hint">Se resta del saldo al instante</span>
            </div>

            <?php if ($liquidado): ?>
                <div class="liquidado-note" data-liquidado-note>
                    <span class="liquidado-note__icon">✓</span>
                    Este préstamo está totalmente liquidado. Puedes seguir registrando abonos si lo necesitas.
                </div>
            <?php endif; ?>

            <form class="form" id="form-pago"
                  method="post" action="/pagos/crear"
                  data-saldo-actual="<?= $saldo ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="prestamo_id" value="<?= (int) $prestamo['id'] ?>">

                <div class="field">
                    <label class="field__label" for="monto">Cantidad que estoy dando</label>
                    <div class="field__money">
                        <span class="field__prefix">$</span>
                        <input class="field__input field__input--money" type="number" step="0.01" min="0.01"
                               id="monto" name="monto" placeholder="0.00" required autofocus>
                    </div>
                    <div class="field__quick" id="quick-amounts">
                        <button type="button" class="chip-btn" data-fill="<?= e($prestamo['cuota']) ?>">1 cuota · <?= e(money($prestamo['cuota'])) ?></button>
                        <?php if (!$liquidado): ?>
                            <button type="button" class="chip-btn" data-fill="<?= $saldo ?>">Liquidar · <?= e(money($saldo)) ?></button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="field">
                    <label class="field__label" for="nota">Nota <span class="field__opt">(opcional)</span></label>
                    <input class="field__input" type="text" id="nota" name="nota" maxlength="200"
                           placeholder="Ej. Pago en efectivo">
                </div>

                <button type="submit" class="btn-glow btn-glow--full">Registrar abono</button>
                <p class="form__error" id="pago-error" hidden></p>
            </form>

            <form method="post" action="/prestamo/<?= (int) $prestamo['id'] ?>/eliminar"
                  class="danger-form" onsubmit="return confirm('¿Eliminar este préstamo y todos sus abonos? Esta acción no se puede deshacer.');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="btn-danger">Eliminar préstamo</button>
            </form>
        </section>

        <!-- Historial -->
        <section class="panel panel--list">
            <div class="panel__head">
                <h2 class="panel__title">Historial de abonos</h2>
                <span class="panel__hint" data-hist-count><?= count($pagos) ?> abono<?= count($pagos) === 1 ? '' : 's' ?></span>
            </div>

            <div class="pago-list" id="pago-list" data-empty-text="Todavía no hay abonos. Registra el primero.">
                <?php if ($pagos === []): ?>
                    <div class="empty empty--sm" data-empty>
                        <div class="empty__icon">＋</div>
                        <p class="empty__text">Todavía no hay abonos. Registra el primero.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pagos as $pago): ?>
                        <div class="pago">
                            <div class="pago__main">
                                <span class="pago__monto mono"><?= e(money($pago['monto'])) ?></span>
                                <?php if (!empty($pago['nota'])): ?>
                                    <span class="pago__nota"><?= e($pago['nota']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="pago__side">
                                <time class="pago__fecha"><?= e(date('d/m/Y H:i', strtotime((string) $pago['pagado_en']))) ?></time>
                                <form method="post" action="/pagos/<?= (int) $pago['id'] ?>/eliminar"
                                      onsubmit="return confirm('¿Eliminar este abono?');">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="prestamo_id" value="<?= (int) $prestamo['id'] ?>">
                                    <button type="submit" class="pago__del" title="Eliminar abono" aria-label="Eliminar abono">✕</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</section>
