<?php
/** @var array $prestamo @var array $old */
// Valor a mostrar: lo que el usuario dejó a medias (si hubo error) o lo guardado.
$val = static fn(string $campo) => $old[$campo] ?? $prestamo[$campo] ?? '';
$freqActual = (string) $val('frecuencia');
?>

<a href="<?= e(url('/prestamo/' . (int) $prestamo['id'])) ?>" class="back-link">← Volver al préstamo</a>

<section class="detail">
    <div class="panel detail__hero">
        <div class="detail__headtext">
            <p class="eyebrow">Editar préstamo #<?= (int) $prestamo['id'] ?></p>
            <h1 class="detail__title">Editar “<?= e($prestamo['concepto']) ?>”</h1>
            <p class="detail__meta">
                <span class="chip chip--muted">Abonado hasta ahora: <?= e(money($prestamo['abonado'])) ?></span>
                <span class="chip chip--muted">Los abonos no se modifican</span>
            </p>
        </div>
    </div>

    <section class="panel panel--form" style="max-width: 640px;">
        <div class="panel__head">
            <h2 class="panel__title">Datos del préstamo</h2>
            <span class="panel__hint">Cambia lo que necesites</span>
        </div>

        <form class="form" method="post" action="<?= e(url('/prestamo/' . (int) $prestamo['id'] . '/editar')) ?>" id="form-prestamo" novalidate>
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <div class="field">
                <label class="field__label" for="concepto">Concepto</label>
                <input class="field__input" type="text" id="concepto" name="concepto"
                       maxlength="200" required value="<?= e($val('concepto')) ?>">
            </div>

            <div class="field">
                <label class="field__label" for="deudor">Deudor <span class="field__opt">(opcional)</span></label>
                <input class="field__input" type="text" id="deudor" name="deudor"
                       maxlength="120" value="<?= e($val('deudor')) ?>">
            </div>

            <div class="field">
                <label class="field__label" for="monto_prestado">Cantidad prestada</label>
                <div class="field__money">
                    <span class="field__prefix">$</span>
                    <input class="field__input field__input--money" type="number" step="0.01" min="0"
                           id="monto_prestado" name="monto_prestado" value="<?= e($val('monto_prestado')) ?>">
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label class="field__label" for="frecuencia">¿Cómo paga?</label>
                    <div class="field__select">
                        <select class="field__input" id="frecuencia" name="frecuencia" required>
                            <?php foreach (frecuencias() as $f): ?>
                                <option value="<?= e($f) ?>" <?= ($freqActual === $f) ? 'selected' : '' ?>>
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
                               id="num_periodos" name="num_periodos" value="<?= e($val('num_periodos')) ?>">
                        <span class="field__suffix" id="periodos-unidad">periodos</span>
                    </div>
                </div>
            </div>

            <div class="field">
                <label class="field__label" for="cuota">Cantidad que paga por periodo</label>
                <div class="field__money">
                    <span class="field__prefix">$</span>
                    <input class="field__input field__input--money" type="number" step="0.01" min="0"
                           id="cuota" name="cuota" value="<?= e($val('cuota')) ?>">
                </div>
            </div>

            <div class="total-preview" id="total-preview">
                <label class="total-preview__label" for="total_pagar">Cantidad total a pagar</label>
                <div class="total-preview__field">
                    <span class="total-preview__cur">$</span>
                    <input class="total-preview__input mono" type="number" step="0.01" min="0"
                           id="total_pagar" name="total_pagar" placeholder="0.00"
                           value="<?= e($val('total_pagar')) ?>">
                </div>
                <div class="total-preview__hint" id="preview-hint">Se calcula sola (cuota × tiempo), pero puedes escribir el monto exacto.</div>
            </div>

            <div class="edit-actions">
                <a href="<?= e(url('/prestamo/' . (int) $prestamo['id'])) ?>" class="btn-outline">Cancelar</a>
                <button type="submit" class="btn-glow">Guardar cambios</button>
            </div>
        </form>
    </section>
</section>
