'use strict';

/* ============================================================
   Núcleo de Pagos — interacciones
   ============================================================ */

const CIRC = 2 * Math.PI * 52; // circunferencia del gauge (r = 52)

const fmtMoney = (n) =>
    '$' + Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MXN';

const UNIDADES = {
    diario:    ['día', 'días'],
    semanal:   ['semana', 'semanas'],
    quincenal: ['quincena', 'quincenas'],
    mensual:   ['mes', 'meses'],
    anual:     ['año', 'años'],
};

document.addEventListener('DOMContentLoaded', () => {
    animarGauges();
    calculadoraTotal();
    formularioPago();
});

/* ---------- Anima todos los gauges radiales ---------- */
function animarGauges() {
    document.querySelectorAll('.gauge__fill').forEach((el) => {
        const pct = Math.max(0, Math.min(100, parseFloat(el.dataset.pct) || 0));
        requestAnimationFrame(() => {
            el.style.strokeDashoffset = String(CIRC - (CIRC * pct) / 100);
        });
    });
}

function setGauge(gaugeEl, pct) {
    if (!gaugeEl) return;
    pct = Math.max(0, Math.min(100, pct));
    const fill = gaugeEl.querySelector('.gauge__fill');
    const txt  = gaugeEl.querySelector('[data-gauge-pct]');
    if (fill) fill.style.strokeDashoffset = String(CIRC - (CIRC * pct) / 100);
    if (txt)  txt.innerHTML = Math.round(pct) + '<small>%</small>';
}

/* ---------- Calculadora del total en el formulario de préstamo ---------- */
function calculadoraTotal() {
    const form = document.getElementById('form-prestamo');
    if (!form) return;

    const cuota    = form.querySelector('#cuota');
    const periodos = form.querySelector('#num_periodos');
    const freq     = form.querySelector('#frecuencia');
    const unidad   = document.getElementById('periodos-unidad');
    const box      = document.getElementById('total-preview');
    const total    = document.getElementById('total_pagar');
    const hint     = document.getElementById('preview-hint');

    // Si el préstamo trae un total previo (al reabrir el form), respétalo.
    let totalManual = total.value.trim() !== '';

    const recalc = () => {
        const c = parseFloat(cuota.value) || 0;
        const n = parseInt(periodos.value, 10) || 0;
        const auto = Math.round(c * n * 100) / 100;

        // Etiqueta de la unidad de tiempo según la frecuencia.
        const par = UNIDADES[freq.value];
        const uni = par ? (n === 1 ? par[0] : par[1]) : 'periodos';
        if (unidad) unidad.textContent = uni;

        // Autocompleta el total SOLO si el usuario no lo ha escrito a mano.
        if (!totalManual && auto > 0) {
            total.value = auto.toFixed(2);
        }

        const val = parseFloat(total.value) || 0;
        box.classList.toggle('is-active', val > 0);

        if (auto > 0 && totalManual && Math.abs(val - auto) > 0.005) {
            hint.textContent = `Cuota × tiempo = ${fmtMoney(auto)}. Estás usando un monto exacto distinto.`;
        } else if (auto > 0) {
            hint.textContent = `${fmtMoney(c)} × ${n} ${uni} = ${fmtMoney(auto)}. Puedes escribir el monto exacto.`;
        } else {
            hint.textContent = 'Se calcula sola (cuota × tiempo), pero puedes escribir el monto exacto.';
        }
    };

    // Al tocar el total, se vuelve manual; si lo vacían, vuelve a autocalcular.
    total.addEventListener('input', () => {
        totalManual = total.value.trim() !== '';
        recalc();
    });

    [cuota, periodos, freq].forEach((el) => {
        el.addEventListener('input', recalc);
        el.addEventListener('change', recalc);
    });
    recalc();
}

/* ---------- Registro de abonos por AJAX ---------- */
function formularioPago() {
    const form = document.getElementById('form-pago');
    if (!form) return;

    // Botones de relleno rápido (1 cuota / liquidar).
    document.querySelectorAll('#quick-amounts .chip-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = form.querySelector('#monto');
            input.value = parseFloat(btn.dataset.fill).toFixed(2);
            input.focus();
        });
    });

    const errorBox = document.getElementById('pago-error');

    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        errorBox.hidden = true;

        const btn = form.querySelector('button[type="submit"]');
        const montoInput = form.querySelector('#monto');
        const monto = parseFloat(montoInput.value);

        if (!(monto > 0)) {
            mostrarError('Ingresa una cantidad mayor que cero.');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Registrando…';

        try {
            const resp = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: new FormData(form),
            });
            const data = await resp.json();

            if (!resp.ok || !data.ok) {
                mostrarError(data.error || 'No se pudo registrar el abono.');
                return;
            }

            aplicarPago(data, form);
            // reset() conserva los ocultos (csrf, prestamo_id) porque tienen value por defecto.
            form.reset();
        } catch (e) {
            mostrarError('Error de conexión. Intenta de nuevo.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Registrar abono';
        }
    });

    function mostrarError(msg) {
        errorBox.textContent = msg;
        errorBox.hidden = false;
    }
}

/* ---------- Aplica el resultado del abono a la interfaz ---------- */
function aplicarPago(data, form) {
    // Métricas
    const saldoEl   = document.querySelector('[data-saldo]');
    const abonadoEl = document.querySelector('[data-abonado]');
    const numEl     = document.querySelector('[data-numpagos]');
    if (saldoEl)   saldoEl.textContent   = data.saldo_fmt;
    if (abonadoEl) abonadoEl.textContent = data.abonado_fmt;
    if (numEl)     numEl.textContent     = String(data.num_pagos);

    // Gauge
    setGauge(document.querySelector('[data-gauge]'), data.pct);

    // Saldo de referencia y botón "liquidar"
    form.dataset.saldoActual = String(data.saldo);
    const liquidarBtn = document.querySelector('#quick-amounts .chip-btn:last-child');
    if (liquidarBtn && liquidarBtn.textContent.includes('Liquidar')) {
        if (data.saldo > 0.001) {
            liquidarBtn.dataset.fill = String(data.saldo);
            liquidarBtn.textContent = 'Liquidar · ' + data.saldo_fmt;
        } else {
            liquidarBtn.remove();
        }
    }

    // Nuevo abono al inicio del historial
    const lista = document.getElementById('pago-list');
    const vacio = lista.querySelector('[data-empty]');
    if (vacio) vacio.remove();

    const row = document.createElement('div');
    row.className = 'pago';
    row.innerHTML = `
        <div class="pago__main">
            <span class="pago__monto mono">${data.pago.monto_fmt}</span>
            ${data.pago.nota ? `<span class="pago__nota">${escapeHtml(data.pago.nota)}</span>` : ''}
        </div>
        <div class="pago__side">
            <time class="pago__fecha">${data.pago.fecha_fmt}</time>
        </div>`;
    lista.prepend(row);

    // Contador del historial
    const count = document.querySelector('[data-hist-count]');
    if (count) count.textContent = data.num_pagos + (data.num_pagos === 1 ? ' abono' : ' abonos');

    // Nota de liquidado
    if (data.saldo <= 0.001) mostrarLiquidado();
}

function mostrarLiquidado() {
    if (document.querySelector('[data-liquidado-note]')) return;
    const form = document.getElementById('form-pago');
    const note = document.createElement('div');
    note.className = 'liquidado-note';
    note.setAttribute('data-liquidado-note', '');
    note.innerHTML = '<span class="liquidado-note__icon">✓</span> ¡Préstamo liquidado! El saldo llegó a cero.';
    form.parentElement.insertBefore(note, form);
}

function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
