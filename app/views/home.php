<?php
/** @var array $prestamos @var array $totales @var array $old */
$pct = (float) $totales['total'] > 0
    ? round((float) $totales['abonado'] / (float) $totales['total'] * 100, 1)
    : 0.0;
?>

<section class="hero">
    <div class="hero__copy">
        <p class="eyebrow">Consola de cobranza</p>
        <h1 class="hero__title">Controla cada <span class="grad-text">préstamo</span><br>y ve el saldo caer en tiempo real.</h1>
        <p class="hero__sub">Registra lo que prestaste, define cómo y por cuánto tiempo te pagarán, y cada abono descuenta del total al instante.</p>
        <a href="#nuevo" class="btn-glow">Registrar préstamo</a>
    </div>

    <div class="hero__panel">
        <div class="gauge" style="--pct: <?= $pct ?>">
            <svg viewBox="0 0 120 120" class="gauge__svg">
                <circle class="gauge__track" cx="60" cy="60" r="52"/>
                <circle class="gauge__fill" cx="60" cy="60" r="52"
                        data-pct="<?= $pct ?>"/>
            </svg>
            <div class="gauge__center">
                <span class="gauge__pct"><?= number_format($pct, 0) ?><small>%</small></span>
                <span class="gauge__label">recuperado</span>
            </div>
        </div>
        <div class="hero__stats">
            <div class="stat">
                <span class="stat__label">Total por cobrar</span>
                <span class="stat__value mono"><?= e(money($totales['total'])) ?></span>
            </div>
            <div class="stat">
                <span class="stat__label">Abonado</span>
                <span class="stat__value mono stat__value--ok"><?= e(money($totales['abonado'])) ?></span>
            </div>
            <div class="stat">
                <span class="stat__label">Saldo pendiente</span>
                <span class="stat__value mono stat__value--due"><?= e(money($totales['saldo'])) ?></span>
            </div>
            <div class="stat">
                <span class="stat__label">Préstamos activos</span>
                <span class="stat__value mono"><?= (int) $totales['num_prestamos'] ?></span>
            </div>
        </div>
    </div>
</section>

<div class="layout-split">
    <!-- ====================== FORMULARIO ====================== -->
    <section class="panel panel--form" id="nuevo">
        <div class="panel__head">
            <h2 class="panel__title">Nuevo préstamo</h2>
            <span class="panel__hint">Todos los cálculos son automáticos</span>
        </div>

        <form class="form" method="post" action="/prestamos/crear" id="form-prestamo" novalidate>
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <div class="field">
                <label class="field__label" for="concepto">Concepto</label>
                <input class="field__input" type="text" id="concepto" name="concepto"
                       maxlength="200" required placeholder="Ej. Préstamo para herramienta"
                       value="<?= e($old['concepto'] ?? '') ?>">
            </div>

            <div class="field">
                <label class="field__label" for="deudor">Deudor <span class="field__opt">(opcional)</span></label>
                <input class="field__input" type="text" id="deudor" name="deudor"
                       maxlength="120" placeholder="¿A quién le prestaste?"
                       value="<?= e($old['deudor'] ?? '') ?>">
            </div>

            <div class="field">
                <label class="field__label" for="monto_prestado">Cantidad prestada</label>
                <div class="field__money">
                    <span class="field__prefix">$</span>
                    <input class="field__input field__input--money" type="number" step="0.01" min="0"
                           id="monto_prestado" name="monto_prestado" placeholder="0.00"
                           value="<?= e($old['monto_prestado'] ?? '') ?>">
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label class="field__label" for="frecuencia">¿Cómo pagará?</label>
                    <div class="field__select">
                        <select class="field__input" id="frecuencia" name="frecuencia" required>
                            <option value="" disabled <?= empty($old['frecuencia']) ? 'selected' : '' ?>>Selecciona…</option>
                            <?php foreach (frecuencias() as $f): ?>
                                <option value="<?= e($f) ?>" <?= (($old['frecuencia'] ?? '') === $f) ? 'selected' : '' ?>>
                                    <?= e(frecuencia_label($f)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label class="field__label" for="num_periodos">¿Por cuánto tiempo?</label>
                    <div class="field__money">
                        <input class="field__input" type="number" step="1" min="1"
                               id="num_periodos" name="num_periodos" placeholder="0"
                               value="<?= e($old['num_periodos'] ?? '') ?>">
                        <span class="field__suffix" id="periodos-unidad">periodos</span>
                    </div>
                </div>
            </div>

            <div class="field">
                <label class="field__label" for="cuota">Cantidad que pagará por periodo</label>
                <div class="field__money">
                    <span class="field__prefix">$</span>
                    <input class="field__input field__input--money" type="number" step="0.01" min="0"
                           id="cuota" name="cuota" placeholder="0.00"
                           value="<?= e($old['cuota'] ?? '') ?>">
                </div>
            </div>

            <div class="total-preview" id="total-preview">
                <div class="total-preview__row">
                    <span>Cantidad total a pagar</span>
                    <span class="total-preview__value mono" id="preview-total">$0.00 MXN</span>
                </div>
                <div class="total-preview__hint" id="preview-hint">Captura la cuota y el tiempo para ver el total.</div>
            </div>

            <button type="submit" class="btn-glow btn-glow--full">Crear préstamo</button>
        </form>
    </section>

    <!-- ====================== LISTA ====================== -->
    <section class="panel panel--list">
        <div class="panel__head">
            <h2 class="panel__title">Préstamos</h2>
            <span class="panel__hint"><?= count($prestamos) ?> registro<?= count($prestamos) === 1 ? '' : 's' ?></span>
        </div>

        <?php if ($prestamos === []): ?>
            <div class="empty">
                <div class="empty__icon">◎</div>
                <p class="empty__title">Aún no hay préstamos</p>
                <p class="empty__text">Crea el primero con el formulario de la izquierda.</p>
            </div>
        <?php else: ?>
            <div class="loan-list">
                <?php foreach ($prestamos as $p):
                    $lpct = (float) $p['total_pagar'] > 0
                        ? min(100, round((float) $p['abonado'] / (float) $p['total_pagar'] * 100, 1))
                        : 0;
                    $liquidado = (float) $p['saldo'] <= 0.001;
                ?>
                    <a class="loan-card <?= $liquidado ? 'loan-card--done' : '' ?>" href="/prestamo/<?= (int) $p['id'] ?>">
                        <div class="loan-card__top">
                            <div>
                                <h3 class="loan-card__title"><?= e($p['concepto']) ?></h3>
                                <p class="loan-card__meta">
                                    <?php if (!empty($p['deudor'])): ?><?= e($p['deudor']) ?> · <?php endif; ?>
                                    <?= e(frecuencia_label($p['frecuencia'])) ?> ·
                                    <?= (int) $p['num_periodos'] ?> <?= e(periodo_sustantivo($p['frecuencia'], (int) $p['num_periodos'])) ?>
                                </p>
                            </div>
                            <?php if ($liquidado): ?>
                                <span class="badge badge--done">Liquidado</span>
                            <?php else: ?>
                                <span class="badge"><?= number_format($lpct, 0) ?>%</span>
                            <?php endif; ?>
                        </div>

                        <div class="progress-line"><span class="progress-line__fill" style="width: <?= $lpct ?>%"></span></div>

                        <div class="loan-card__figures">
                            <div><span class="fig__label">Saldo</span><span class="fig__value mono <?= $liquidado ? 'fig__value--ok' : 'fig__value--due' ?>"><?= e(money($p['saldo'])) ?></span></div>
                            <div class="ta-r"><span class="fig__label">Total</span><span class="fig__value mono"><?= e(money($p['total_pagar'])) ?></span></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
