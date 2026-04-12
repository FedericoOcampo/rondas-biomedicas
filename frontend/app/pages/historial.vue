<template>
  <div class="pagina">

    <!-- Header -->
    <div class="header">
      <div>
        <p class="header-sub">Ingeniería Biomédica — Sumimedical</p>
        <h1>Panel de control — Rondas biomédicas</h1>
      </div>
      <NuxtLink to="/" class="btn-volver">← Volver</NuxtLink>
    </div>

    <!-- Filtro -->
    <div class="filtros">
      <div class="filtro-campo">
        <label>Desde</label>
        <input v-model="desde" type="date" />
      </div>
      <div class="filtro-campo">
        <label>Hasta</label>
        <input v-model="hasta" type="date" />
      </div>
      <button class="btn-filtrar" @click="cargarDatos" :disabled="cargando">
        {{ cargando ? 'Cargando...' : 'Aplicar filtro' }}
      </button>
    </div>

    <div v-if="datos" class="contenido">

      <!-- Resumen global -->
      <div class="resumen-grid">
        <div class="resumen-card">
          <p class="resumen-num">{{ datos.resumen.totalEquipos }}</p>
          <p class="resumen-label">Total equipos</p>
        </div>
        <div class="resumen-card rojo">
          <p class="resumen-num">{{ datos.resumen.fueraServicio }}</p>
          <p class="resumen-label">Fuera de servicio</p>
        </div>
        <div class="resumen-card amarillo">
          <p class="resumen-num">{{ datos.resumen.conFallas }}</p>
          <p class="resumen-label">Con fallas</p>
        </div>
        <div class="resumen-card naranja">
          <p class="resumen-num">{{ datos.resumen.repuestoPendiente }}</p>
          <p class="resumen-label">Repuesto pendiente</p>
        </div>
        <div class="resumen-card verde">
          <p class="resumen-num">{{ datos.resumen.accesoriosReemplazados }}</p>
          <p class="resumen-label">Accesorios reemplazados</p>
        </div>
        <div class="resumen-card azul">
          <p class="resumen-num">{{ datos.resumen.funcionamiento }}</p>
          <p class="resumen-label">En funcionamiento</p>
        </div>
      </div>

      <!-- Fila superior: torta + fugas -->
      <div class="fila-graficas">

        <!-- Estado equipos torta -->
        <div class="grafica-card pequeña">
          <p class="grafica-titulo">Estado de equipos médicos</p>
          <div class="canvas-wrapper">
            <canvas ref="canvasTorta"></canvas>
          </div>
        </div>

        <!-- Histórico fugas -->
        <div class="grafica-card grande">
          <p class="grafica-titulo">Histórico fugas máquina de anestesia (ml/min)</p>
          <div class="canvas-wrapper">
            <canvas ref="canvasFugas"></canvas>
          </div>
        </div>

      </div>

      <!-- Cambio de accesorios -->
      <div class="grafica-card full">
        <p class="grafica-titulo">Cambio de accesorios monitores quirófanos</p>
        <div class="canvas-wrapper" style="max-height: 500px;">
          <canvas ref="canvasAccesorios"></canvas>
        </div>
      </div>

      <!-- Fila inferior: equipos en riesgo + movimientos -->
      <div class="fila-inferior">

        <!-- Equipos en riesgo -->
        <div v-for="eq in datos.equiposEnRiesgo" :key="eq.placa" class="riesgo-item" :class="eq.nivel">
          <div class="riesgo-info">
            <p class="riesgo-nombre">{{ eq.nombre }} <span class="riesgo-placa">{{ eq.placa }}</span></p>
            <p class="riesgo-desc">
              {{ eq.totalFallas }} falla(s) | Último fallo: hace {{ eq.diasDesdeUltimaFalla }} día(s)
              <span v-if="eq.intervaloPromedio"> | Falla cada ~{{ eq.intervaloPromedio }} día(s)</span>
            </p>
            <div class="riesgo-barra-wrapper">
              <div class="riesgo-barra" :style="{ width: Math.min(100, (eq.totalFallas / 8) * 100) + '%' }"></div>
            </div>
          </div>
          <div class="riesgo-dias">
            <p class="riesgo-num">{{ eq.totalFallas }}</p>
            <p class="riesgo-label">fallas registradas</p>
          </div>
        </div>

        <!-- Últimos movimientos -->
        <div class="card-seccion pequeña">
          <p class="seccion-titulo">Últimos movimientos de equipos</p>
          <div v-if="datos.ultimosMovimientos.length === 0" class="sin-datos">
            No hay movimientos en este período.
          </div>
          <div v-for="mov in datos.ultimosMovimientos" :key="mov.fecha + mov.placa + mov.areaActual" class="movimiento-item">
            <div>
              <p class="mov-equipo">{{ mov.equipo }}</p>
              <p class="mov-placa">{{ mov.placa }}</p>
              <p class="mov-fecha">{{ formatearFecha(mov.fecha) }}</p>
            </div>
            <div class="mov-ruta">
              <span class="mov-area-anterior">{{ mov.areaAnterior }}</span>
              <span class="mov-flecha">→</span>
              <span class="mov-area-actual" :class="{ perdido: mov.tipo === 'perdido' }">{{ mov.areaActual }}</span>
            </div>
          </div>
        </div>

      </div>

    </div>

    <div v-else-if="!cargando" class="sin-datos-global">
      Selecciona un rango de fechas y aplica el filtro para ver el historial.
    </div>

  </div>
</template>

<script setup>
const desde = ref('')
const hasta = ref('')
const cargando = ref(false)
const datos = ref(null)

const canvasTorta = ref(null)
const canvasFugas = ref(null)
const canvasAccesorios = ref(null)

let chartTorta = null
let chartFugas = null
let chartAccesorios = null

const config = useRuntimeConfig()

async function cargarDatos() {
  cargando.value = true
  datos.value = null

  try {
    const params = new URLSearchParams()
    if (desde.value) params.append('desde', desde.value)
    if (hasta.value) params.append('hasta', hasta.value)

    datos.value = await $fetch(`${config.public.apiBase}/dashboard?${params.toString()}`)
    await nextTick()
    renderGraficas()
  } catch (e) {
    console.error('Error al cargar dashboard:', e)
  } finally {
    cargando.value = false
  }
}

function renderGraficas() {
  const Chart = window.Chart
  if (!Chart) return

  if (chartTorta) chartTorta.destroy()
  if (chartFugas) chartFugas.destroy()
  if (chartAccesorios) chartAccesorios.destroy()

  const r = datos.value.resumen

  // ── Torta ────────────────────────────────────────────
  chartTorta = new Chart(canvasTorta.value, {
    type: 'doughnut',
    data: {
      labels: ['Funcionamiento', 'Fuera de servicio', 'Con fallas', 'Repuesto pendiente'],
      datasets: [{
        data: [r.funcionamiento, r.fueraServicio, r.conFallas, r.repuestoPendiente],
        backgroundColor: ['#22c55e', '#ef4444', '#f59e0b', '#f97316'],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom', labels: { font: { size: 11 } } }
      }
    }
  })

  // ── Fugas ────────────────────────────────────────────
  const fugas = datos.value.historialFugas
  const placasFugas = [...new Set(fugas.map(f => f.placa))]
  const fechasFugas = [...new Set(fugas.map(f => f.fecha))].sort()
  const coloresFugas = ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6']

  chartFugas = new Chart(canvasFugas.value, {
    type: 'line',
    data: {
      labels: fechasFugas,
      datasets: placasFugas.map((placa, i) => ({
        label: placa,
        data: fechasFugas.map(fecha => {
          const punto = fugas.find(f => f.placa === placa && f.fecha === fecha)
          return punto ? punto.valor : null
        }),
        borderColor: coloresFugas[i % coloresFugas.length],
        backgroundColor: coloresFugas[i % coloresFugas.length] + '20',
        fill: true,
        tension: 0.3,
        spanGaps: true
      }))
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
      scales: { y: { beginAtZero: true } }
    }
  })

  // ── Accesorios ───────────────────────────────────────
  const accesorios = datos.value.cambioAccesorios
  const tiposAccesorio = [...new Set(accesorios.map(a => a.accesorio))]
  const placasAccesorio = [...new Set(accesorios.map(a => a.placa))]
  const coloresAccesorios = ['#ef4444', '#22c55e', '#f59e0b', '#3b82f6']

  chartAccesorios = new Chart(canvasAccesorios.value, {
    type: 'bar',
    data: {
      labels: placasAccesorio,
      datasets: tiposAccesorio.map((tipo, i) => ({
        label: tipo,
        data: placasAccesorio.map(placa =>
          accesorios.filter(a => a.placa === placa && a.accesorio === tipo).length
        ),
        backgroundColor: coloresAccesorios[i % coloresAccesorios.length]
      }))
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
  })
}

function formatearFecha(fecha) {
  return new Date(fecha + 'T12:00:00').toLocaleDateString('es-CO', {
    year: 'numeric', month: 'short', day: 'numeric'
  })
}

onMounted(() => {
  const script = document.createElement('script')
  script.src = 'https://cdn.jsdelivr.net/npm/chart.js'
  document.head.appendChild(script)
})
</script>

<style scoped>
.pagina {
  max-width: 1100px;
  margin: 0 auto;
  padding: 32px 20px 80px;
  font-family: 'Segoe UI', sans-serif;
  background: #f1f5f9;
  min-height: 100vh;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #1e3a5f;
  color: white;
  padding: 28px 32px;
  border-radius: 14px;
  margin-bottom: 20px;
}

.header-sub {
  font-size: 11px;
  color: #93c5fd;
  margin: 0 0 6px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.header h1 { font-size: 20px; font-weight: 700; margin: 0; }

.btn-volver {
  color: white;
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  background: rgba(255,255,255,0.15);
  padding: 10px 16px;
  border-radius: 8px;
}

.btn-volver:hover { background: rgba(255,255,255,0.25); }

.filtros {
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 20px 24px;
  margin-bottom: 20px;
  display: flex;
  align-items: flex-end;
  gap: 16px;
}

.filtro-campo { display: flex; flex-direction: column; gap: 6px; }

label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

input[type="date"] {
  padding: 9px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 13px;
  font-family: inherit;
  outline: none;
  color: #0f172a;
  background: #f8fafc;
}

input[type="date"]:focus {
  border-color: #3b82f6;
  background: white;
}

input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(1) sepia(1) saturate(5) hue-rotate(190deg);
  opacity: 0.8;
  cursor: pointer;
}

input[type="date"]:focus { border-color: #3b82f6; }

.btn-filtrar {
  background: #1e3a5f;
  color: white;
  padding: 10px 24px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  white-space: nowrap;
}

.btn-filtrar:hover:not(:disabled) { background: #1a3354; }
.btn-filtrar:disabled { background: #94a3b8; cursor: not-allowed; }

/* Resumen */
.resumen-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}

.resumen-card {
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 16px;
  text-align: center;
}

.resumen-card.verde { border-color: #22c55e; background: #f0fdf4; }
.resumen-card.rojo { border-color: #ef4444; background: #fef2f2; }
.resumen-card.amarillo { border-color: #f59e0b; background: #fffbeb; }
.resumen-card.naranja { border-color: #f97316; background: #fff7ed; }
.resumen-card.azul { border-color: #3b82f6; background: #eff6ff; }

.resumen-num { font-size: 28px; font-weight: 800; color: #1e3a5f; margin: 0 0 4px; }
.resumen-label { font-size: 12px; color: #64748b; margin: 0; }

/* Gráficas */
.fila-graficas {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 16px;
  margin-bottom: 16px;
}

.grafica-card {
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 20px;
}

.grafica-card.full {
  margin-bottom: 16px;
}

.grafica-titulo {
  font-size: 13px;
  font-weight: 700;
  color: #1e3a5f;
  margin: 0 0 16px;
}

.canvas-wrapper { position: relative; max-height: 400px; }

/* Fila inferior */
.fila-inferior {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 16px;
}

.card-seccion {
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 20px;
}

.seccion-titulo {
  font-size: 14px;
  font-weight: 700;
  color: #1e3a5f;
  margin: 0 0 14px;
}

/* Equipos en riesgo */
.riesgo-item {
  border-radius: 10px;
  padding: 12px 16px;
  margin-bottom: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.riesgo-item.critico { background: #fef2f2; border: 1px solid #fecaca; }
.riesgo-item.alto { background: #fffbeb; border: 1px solid #fde68a; }
.riesgo-item.seguimiento { background: #f0fdf4; border: 1px solid #bbf7d0; }

.riesgo-info { flex: 1; }

.riesgo-nombre {
  font-size: 13px;
  font-weight: 700;
  color: #1e3a5f;
  margin: 0 0 2px;
}

.riesgo-placa {
  font-size: 11px;
  color: #64748b;
  font-weight: 400;
  margin-left: 8px;
}

.riesgo-desc {
  font-size: 11px;
  color: #64748b;
  margin: 0 0 6px;
}

.riesgo-barra-wrapper {
  background: #e2e8f0;
  border-radius: 4px;
  height: 5px;
  overflow: hidden;
}

.riesgo-barra {
  height: 100%;
  border-radius: 4px;
  background: #ef4444;
  transition: width 0.3s;
}

.riesgo-item.alto .riesgo-barra { background: #f59e0b; }
.riesgo-item.seguimiento .riesgo-barra { background: #22c55e; }

.riesgo-dias { text-align: right; min-width: 80px; }

.riesgo-num {
  font-size: 22px;
  font-weight: 800;
  color: #ef4444;
  margin: 0;
}

.riesgo-item.alto .riesgo-num { color: #f59e0b; }
.riesgo-item.seguimiento .riesgo-num { color: #22c55e; }

.riesgo-label { font-size: 10px; color: #64748b; margin: 0; }

.riesgo-leyenda {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  flex-wrap: wrap;
}

.leyenda-item {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 20px;
}

.leyenda-item.critico { background: #fee2e2; color: #dc2626; }
.leyenda-item.alto { background: #fef9c3; color: #854d0e; }
.leyenda-item.seguimiento { background: #dcfce7; color: #15803d; }

/* Movimientos */
.movimiento-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #f1f5f9;
  gap: 12px;
}

.movimiento-item:last-child { border-bottom: none; }

.mov-equipo { font-size: 13px; font-weight: 600; color: #1e3a5f; margin: 0 0 2px; }
.mov-placa { font-size: 11px; color: #64748b; margin: 0 0 2px; }
.mov-fecha { font-size: 11px; color: #94a3b8; margin: 0; }

.mov-badge {
  font-size: 11px;
  font-weight: 700;
  background: #ffedd5;
  color: #9a3412;
  border: 1px solid #f97316;
  padding: 4px 10px;
  border-radius: 20px;
  white-space: nowrap;
}

.sin-datos { padding: 20px; text-align: center; color: #94a3b8; font-size: 13px; }
.sin-datos-global { text-align: center; color: #94a3b8; font-size: 14px; margin-top: 60px; }

@media (max-width: 768px) {
  .resumen-grid { grid-template-columns: repeat(3, 1fr); }
  .fila-graficas { grid-template-columns: 1fr; }
  .fila-inferior { grid-template-columns: 1fr; }
  .filtros { flex-direction: column; }
  .header { flex-direction: column; gap: 16px; align-items: flex-start; }
}

.mov-ruta {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.mov-area-anterior {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  background: #f1f5f9;
  padding: 3px 8px;
  border-radius: 6px;
}

.mov-flecha {
  font-size: 12px;
  color: #94a3b8;
}

.mov-area-actual {
  font-size: 11px;
  font-weight: 600;
  color: #15803d;
  background: #dcfce7;
  padding: 3px 8px;
  border-radius: 6px;
}

.mov-area-actual.perdido {
  color: #9a3412;
  background: #ffedd5;
}

</style>