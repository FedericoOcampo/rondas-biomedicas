<template>
  <div class="pagina">

    <!-- Header -->
    <div class="header">
      <div>
        <p class="header-sub">Ingeniería Biomédica — Sumimedical</p>
        <h1>Consulta de Rondas</h1>
      </div>
      <NuxtLink to="/" class="btn-volver">← Volver</NuxtLink>
    </div>

    <!-- Buscador -->
    <div class="buscador">
      <div class="buscador-campo">
        <label>Selecciona una fecha</label>
        <input v-model="fechaBusqueda" type="date" />
      </div>
      <button class="btn-buscar" @click="buscar" :disabled="!fechaBusqueda || cargando">
        {{ cargando ? 'Buscando...' : 'Buscar ronda' }}
      </button>
    </div>

    <!-- Error -->
    <div v-if="error" class="mensaje-error">{{ error }}</div>

    <!-- Resultado -->
    <div v-if="ronda" class="resultado">

      <!-- Encabezado ronda -->
      <div class="ronda-header">
        <div class="ronda-info">
          <p class="ronda-fecha">{{ formatearFecha(ronda.fecha) }}</p>
          <span class="ronda-estado" :class="ronda.estado">{{ labelEstado(ronda.estado) }}</span>
        </div>
        <button class="btn-descargar" @click="descargarPDF">⬇ Descargar PDF</button>
      </div>

      <!-- Resumen global -->
      <div class="resumen-grid">
        <div class="resumen-card">
          <p class="resumen-num">{{ resumen.total }}</p>
          <p class="resumen-label">Total equipos</p>
        </div>
        <div class="resumen-card verde">
          <p class="resumen-num">{{ resumen.operativos }}</p>
          <p class="resumen-label">Operativos</p>
        </div>
        <div class="resumen-card amarillo">
          <p class="resumen-num">{{ resumen.seguimiento }}</p>
          <p class="resumen-label">En seguimiento</p>
        </div>
        <div class="resumen-card naranja">
          <p class="resumen-num">{{ resumen.reparacion }}</p>
          <p class="resumen-label">En reparación</p>
        </div>
        <div class="resumen-card rojo">
          <p class="resumen-num">{{ resumen.fueraServicio }}</p>
          <p class="resumen-label">Fuera de servicio</p>
        </div>
        <div class="resumen-card azul">
          <p class="resumen-num">{{ resumen.noEncontrados }}</p>
          <p class="resumen-label">No encontrados</p>
        </div>
      </div>

      <!-- Áreas -->
      <div v-for="area in ronda.areas" :key="area.id" class="area">

        <!-- Cabecera área -->
        <div class="area-header" :class="{ 'no-realizada': area.no_realizada }">
          <span class="area-nombre">{{ area.area_nombre }}</span>
          <span v-if="area.no_realizada" class="badge-no-realizada">No realizada</span>
          <span v-else class="badge-realizada">Realizada</span>
        </div>

        <!-- Área no realizada -->
      <div v-if="area.no_realizada" class="area-no-realizada-msg">
        Esta área no fue incluida en la ronda del día.
      </div>

        <!-- Tabla de equipos -->
        <div v-if="!area.no_realizada" class="tabla-wrapper">
          <table class="tabla-equipos">
            <thead>
              <tr>
                <th>Equipo</th>
                <th>Placa</th>
                <th>Rev. física</th>
                <th>Apto para uso</th>
                <th>Hallazgos</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="equipo in area.equipos" :key="equipo.id">
                <!-- Fila principal -->
                <tr
                  class="fila-equipo"
                  :class="{ expandida: equipoExpandido === equipo.id, 'tiene-fallas': contarFallas(equipo) > 0 }"
                  @click="toggleEquipo(equipo.id)"
                >
                  <td>
                    <div class="equipo-nombre-celda">
                      <span class="expand-icon">{{ equipoExpandido === equipo.id ? '▾' : '▸' }}</span>
                      {{ equipo.equipo_nombre }}
                      <span v-if="equipo.no_encontrado" class="badge-no-encontrado">No encontrado</span>
                    </div>
                  </td>
                  <td class="celda-placa">
                    <span v-if="equipo.placa">{{ equipo.placa }}</span>
                    <span v-if="equipo.placa2" class="placa2">{{ equipo.placa2 }}</span>
                    <span v-if="equipo.ubicacion" class="ubicacion-badge">📍 {{ equipo.ubicacion }}</span>
                  </td>
                  <td>
                    <span v-if="equipo.revision_fisica" class="badge-valor" :class="colorValor(equipo.revision_fisica)">
                      {{ labelValor(equipo.revision_fisica) }}
                    </span>
                    <span v-else class="sin-dato">—</span>
                  </td>
                  <td>
                    <span v-if="equipo.apto_para_uso" class="badge-valor" :class="colorValor(equipo.apto_para_uso)">
                      {{ labelValor(equipo.apto_para_uso) }}
                    </span>
                    <span v-else class="sin-dato">—</span>
                  </td>
                  <td>
                    <span v-if="contarFallas(equipo) > 0" class="badge-fallas rojo">{{ contarFallas(equipo) }} falla(s)</span>
                    <span v-else class="badge-fallas verde">Sin fallas</span>
                  </td>
                  <td>
                    <span v-if="equipo.no_encontrado" class="badge-valor naranja">No encontrado</span>
                    <span v-else-if="equipo.apto_para_uso === 'no'" class="badge-valor rojo">Fuera servicio</span>
                    <span v-else-if="contarFallas(equipo) > 0" class="badge-valor amarillo">Con fallas</span>
                    <span v-else class="badge-valor verde">Operativo</span>
                  </td>
                </tr>

                <!-- Fila expandida con pruebas -->
                <tr v-if="equipoExpandido === equipo.id" class="fila-detalle">
                  <td colspan="6">
                    <div class="detalle-contenido">
                      <div class="pruebas-detalle">
                        <div v-for="prueba in equipo.pruebas" :key="prueba.id" class="prueba-detalle-fila">
                          <span class="prueba-detalle-label">{{ prueba.prueba_label }}</span>
                          <span class="badge-valor" :class="colorValor(prueba.valor)">{{ labelValor(prueba.valor) }}</span>
                        </div>
                      </div>
                      <p v-if="equipo.observaciones" class="observaciones-detalle">
                        <strong>Observaciones:</strong> {{ equipo.observaciones }}
                      </p>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Fotos -->
      <div v-if="ronda.fotos && ronda.fotos.length > 0" class="fotos-seccion">
        <h2 class="seccion-titulo">Evidencia fotográfica</h2>
        <div class="fotos-grid">
          <div v-for="(foto, index) in ronda.fotos" :key="index" class="foto-item" @click="abrirFoto(foto)">
            <img :src="foto.base64" :alt="foto.nombre" class="foto-preview" />
            <p class="foto-nombre">{{ foto.nombre }}</p>
            <span class="foto-expandir">Ver foto</span>
          </div>
        </div>
      </div>

      <!-- Firmas -->
      <div class="firmas-seccion">
        <h2 class="seccion-titulo">Firmas de verificación</h2>
        <div class="firmas-grid">
          <div class="firma-bloque">
            <p class="firma-cargo">Responsable de ronda</p>
            <p class="firma-nombre">{{ ronda.firma_responsable_nombre }}</p>
            <img :src="ronda.firma_responsable_imagen" class="firma-imagen" />
          </div>
          <div class="firma-bloque">
            <p class="firma-cargo">Jefe de Servicio CX</p>
            <p class="firma-nombre">{{ ronda.firma_jefe_nombre }}</p>
            <img :src="ronda.firma_jefe_imagen" class="firma-imagen" />
          </div>
          <div class="firma-bloque">
            <p class="firma-cargo">Líder de Ingeniería</p>
            <div v-if="ronda.firma_lider_nombre && ronda.firma_lider_imagen">
              <p class="firma-nombre">{{ ronda.firma_lider_nombre }}</p>
              <img :src="ronda.firma_lider_imagen" class="firma-imagen" />
            </div>
            <div v-else class="firma-pendiente-bloque">
              <p class="firma-pendiente">Pendiente</p>
              <div class="canvas-wrapper">
                <canvas
                  ref="canvasLider"
                  width="300"
                  height="140"
                  @mousedown="startDraw($event)"
                  @mousemove="draw($event)"
                  @mouseup="stopDraw"
                  @mouseleave="stopDraw"
                  @touchstart.prevent="startDraw($event)"
                  @touchmove.prevent="draw($event)"
                  @touchend="stopDraw"
                ></canvas>
                <p v-if="!firmaLider.firmado" class="canvas-placeholder">Firmar aquí</p>
              </div>
              <div class="firma-acciones">
                <button type="button" class="btn-limpiar" @click="limpiarFirma">Limpiar</button>
                <span class="firma-estado" :class="{ firmado: firmaLider.firmado }">
                  {{ firmaLider.firmado ? '✓ Firmado' : 'Pendiente' }}
                </span>
              </div>
              <input v-model="firmaLider.nombre" type="text" placeholder="Nombre del líder de ingeniería" class="firma-nombre-input" />
              <button class="btn-guardar-firma" :disabled="!firmaLider.firmado || !firmaLider.nombre || guardandoFirma" @click="guardarFirmaLider">
                {{ guardandoFirma ? 'Guardando...' : 'Guardar firma' }}
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal foto -->
    <div v-if="fotoExpandida" class="modal-overlay" @click="fotoExpandida = null">
      <div class="modal-contenido" @click.stop>
        <button class="modal-cerrar" @click="fotoExpandida = null">✕</button>
        <img :src="fotoExpandida.base64" :alt="fotoExpandida.nombre" class="modal-imagen" />
        <p class="modal-nombre">{{ fotoExpandida.nombre }}</p>
      </div>
    </div>

  </div>
</template>

<script setup>
const fechaBusqueda = ref('')
const cargando = ref(false)
const error = ref('')
const ronda = ref(null)
const equipoExpandido = ref(null)
const fotoExpandida = ref(null)
const canvasLider = ref(null)
const firmaLider = reactive({ firmado: false, nombre: '', dibujando: false })
const guardandoFirma = ref(false)

// ── Resumen calculado ─────────────────────────────────
const resumen = computed(() => {
  if (!ronda.value) return {}
  let total = 0, operativos = 0, seguimiento = 0, reparacion = 0, fueraServicio = 0, noEncontrados = 0

  ronda.value.areas.forEach(area => {
    if (area.no_realizada) return
    area.equipos.forEach(equipo => {
      total++
      if (equipo.no_encontrado) { noEncontrados++; return }
      if (equipo.apto_para_uso === 'no') { fueraServicio++; return }
      const fallas = contarFallas(equipo)
      const tieneReparacion = equipo.pruebas.some(p => p.valor === 'reparacion') || equipo.revision_fisica === 'reparacion'
      const tieneSeguimiento = equipo.pruebas.some(p => p.valor === 'seguimiento') || equipo.revision_fisica === 'seguimiento'
      if (tieneReparacion) reparacion++
      else if (tieneSeguimiento) seguimiento++
      else if (fallas > 0) seguimiento++
      else operativos++
    })
  })
  return { total, operativos, seguimiento, reparacion, fueraServicio, noEncontrados }
})

function contarFallas(equipo) {
  if (equipo.no_encontrado) return 0

  const pruebasInformativas = [
    'calibracion_o2', 'conexion_toma',
    'pruebas_diarias_prep', 'pruebas_diarias_rec',
    'electrodos', 'conexion_arco1', 'conexion_arco2'
  ]

  const falasPruebas = equipo.pruebas.filter(p => {
    if (pruebasInformativas.includes(p.prueba_id)) return false
    // Electrobisturí: contar como falla si tiene menos del mínimo esperado
    if (['modos_corte', 'modos_coagulacion'].includes(p.prueba_id)) {
      return p.valor !== null && parseInt(p.valor) < 3
    }
    return ['malo', 'mala', 'no', 'reparacion', 'seguimiento'].includes(p.valor)
  }).length

  const fallasRevision = ['malo', 'mala', 'reparacion', 'seguimiento'].includes(equipo.revision_fisica) ? 1 : 0
  const fallasApto = equipo.apto_para_uso === 'no' ? 1 : 0
  return falasPruebas + fallasRevision + fallasApto
}

function toggleEquipo(id) {
  equipoExpandido.value = equipoExpandido.value === id ? null : id
}

// ── Búsqueda ──────────────────────────────────────────
async function buscar() {
  cargando.value = true
  error.value = ''
  ronda.value = null
  equipoExpandido.value = null

  try {
    const config = useRuntimeConfig()
    ronda.value = await $fetch(`${config.public.apiBase}/rondas/fecha/${fechaBusqueda.value}`)
  } catch (e) {
    error.value = 'No se encontró ninguna ronda para esa fecha.'
  } finally {
    cargando.value = false
  }
}

// ── Helpers ───────────────────────────────────────────
function formatearFecha(fecha) {
  return new Date(fecha + 'T12:00:00').toLocaleDateString('es-CO', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  })
}

function labelEstado(estado) {
  if (estado === 'guardado') return 'Guardado — Pendiente firma líder'
  if (estado === 'completado') return 'Completado'
  return 'Borrador'
}

function colorValor(valor) {
  if (!valor) return ''
  if (['bueno', 'buena', 'si'].includes(valor)) return 'verde'
  if (['malo', 'mala', 'no'].includes(valor)) return 'rojo'
  if (['seguimiento'].includes(valor)) return 'amarillo'
  if (['reparacion', 'se_repone'].includes(valor)) return 'naranja'
  if (['no_encontrado'].includes(valor)) return 'rojo'
  return ''
}

function labelValor(valor) {
  const labels = {
    bueno: 'Bueno', buena: 'Buena', malo: 'Malo', mala: 'Mala',
    si: 'Sí', no: 'No', seguimiento: 'Seguimiento',
    reparacion: 'En reparación', se_repone: 'Se repone', no_encontrado: 'No encontrado'
  }
  return labels[valor] || valor
}

// ── Firma líder ───────────────────────────────────────
function startDraw(e) {
  const canvas = canvasLider.value
  const ctx = canvas.getContext('2d')
  const rect = canvas.getBoundingClientRect()
  const scaleX = canvas.width / rect.width
  const scaleY = canvas.height / rect.height
  const x = e.touches ? (e.touches[0].clientX - rect.left) * scaleX : (e.clientX - rect.left) * scaleX
  const y = e.touches ? (e.touches[0].clientY - rect.top) * scaleY : (e.clientY - rect.top) * scaleY
  firmaLider.dibujando = true
  ctx.beginPath()
  ctx.moveTo(x, y)
}

function draw(e) {
  if (!firmaLider.dibujando) return
  const canvas = canvasLider.value
  const ctx = canvas.getContext('2d')
  const rect = canvas.getBoundingClientRect()
  const scaleX = canvas.width / rect.width
  const scaleY = canvas.height / rect.height
  const x = e.touches ? (e.touches[0].clientX - rect.left) * scaleX : (e.clientX - rect.left) * scaleX
  const y = e.touches ? (e.touches[0].clientY - rect.top) * scaleY : (e.clientY - rect.top) * scaleY
  ctx.lineWidth = 2
  ctx.lineCap = 'round'
  ctx.strokeStyle = '#0f172a'
  ctx.lineTo(x, y)
  ctx.stroke()
  ctx.beginPath()
  ctx.moveTo(x, y)
  firmaLider.firmado = true
}

function stopDraw() { firmaLider.dibujando = false }

function limpiarFirma() {
  const canvas = canvasLider.value
  canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height)
  firmaLider.firmado = false
}

async function guardarFirmaLider() {
  guardandoFirma.value = true
  try {
    const config = useRuntimeConfig()
    await $fetch(`${config.public.apiBase}/rondas/${ronda.value.id}/firma-lider`, {
      method: 'PATCH',
      body: { nombre: firmaLider.nombre, imagen: canvasLider.value.toDataURL('image/png') }
    })
    alert('✓ Firma del líder guardada correctamente')
    await buscar()
  } catch (e) {
    alert('Error al guardar la firma.')
  } finally {
    guardandoFirma.value = false
  }
}

// ── Fotos ─────────────────────────────────────────────
function abrirFoto(foto) { fotoExpandida.value = foto }

// ── PDF ───────────────────────────────────────────────
async function descargarPDF() {
  const { jsPDF } = await import('jspdf')
  const doc = new jsPDF()
  const azul = [30, 58, 95]
  const gris = [100, 116, 139]
  const negro = [15, 23, 42]
  let y = 20

  doc.setFillColor(...azul)
  doc.rect(0, 0, 210, 35, 'F')
  doc.setTextColor(255, 255, 255)
  doc.setFontSize(10)
  doc.text('INGENIERÍA BIOMÉDICA — SUMIMEDICAL', 14, 12)
  doc.setFontSize(16)
  doc.setFont('helvetica', 'bold')
  doc.text('Ronda Biomédica — Servicio de Cirugía', 14, 22)
  doc.setFontSize(10)
  doc.setFont('helvetica', 'normal')
  doc.text(`Fecha: ${formatearFecha(ronda.value.fecha)}`, 14, 30)
  doc.text(`Estado: ${labelEstado(ronda.value.estado)}`, 130, 30)
  y = 45

  for (const area of ronda.value.areas) {
    if (y > 260) { doc.addPage(); y = 20 }
    doc.setFillColor(...azul)
    doc.rect(10, y, 190, 8, 'F')
    doc.setTextColor(255, 255, 255)
    doc.setFontSize(11)
    doc.setFont('helvetica', 'bold')
    doc.text(area.area_nombre, 14, y + 5.5)
    if (area.no_realizada) doc.text('NO REALIZADA', 160, y + 5.5)
    y += 12
    if (area.no_realizada) continue

    // Tabla de equipos
    doc.setFillColor(241, 245, 249)
    doc.rect(10, y, 190, 7, 'F')
    doc.setTextColor(...gris)
    doc.setFontSize(8)
    doc.setFont('helvetica', 'bold')
    doc.text('EQUIPO', 14, y + 4.5)
    doc.text('PLACA', 75, y + 4.5)
    doc.text('REV. FÍSICA', 110, y + 4.5)
    doc.text('APTO', 140, y + 4.5)
    doc.text('ESTADO', 165, y + 4.5)
    y += 9

    for (const equipo of area.equipos) {
      if (y > 270) { doc.addPage(); y = 20 }
      doc.setTextColor(...negro)
      doc.setFontSize(8)
      doc.setFont('helvetica', 'normal')
      doc.text(equipo.equipo_nombre.substring(0, 35), 14, y + 4)
      doc.text(equipo.placa || '—', 75, y + 4)
      doc.text(labelValor(equipo.revision_fisica) || '—', 110, y + 4)
      doc.text(labelValor(equipo.apto_para_uso) || '—', 140, y + 4)

      const estado = equipo.no_encontrado ? 'No encontrado'
        : equipo.apto_para_uso === 'no' ? 'Fuera servicio'
        : contarFallas(equipo) > 0 ? 'Con fallas'
        : 'Operativo'
      doc.text(estado, 165, y + 4)

      if (equipo.observaciones) {
        y += 6
        doc.setTextColor(...gris)
        doc.setFontSize(7)
        doc.text(`Obs: ${equipo.observaciones}`, 16, y + 4)
        doc.setTextColor(...negro)
        doc.setFontSize(8)
      }

      doc.setDrawColor(226, 232, 240)
      doc.line(10, y + 7, 200, y + 7)
      y += 8
    }
    y += 4
  }
  // Fotos de evidencia
  if (ronda.value.fotos && ronda.value.fotos.length > 0) {
    if (y > 220) { doc.addPage(); y = 20 }
    doc.setFillColor(...azul)
    doc.rect(10, y, 190, 8, 'F')
    doc.setTextColor(255, 255, 255)
    doc.setFontSize(11)
    doc.setFont('helvetica', 'bold')
    doc.text('Evidencia fotográfica', 14, y + 5.5)
    y += 14

    const fotosPerRow = 2
    const fotoWidth = 88
    const fotoHeight = 60

    for (let i = 0; i < ronda.value.fotos.length; i++) {
      const foto = ronda.value.fotos[i]
      const col = i % fotosPerRow
      const x = 14 + col * (fotoWidth + 10)

      if (col === 0 && i > 0) y += fotoHeight + 8
      if (y + fotoHeight > 270) { doc.addPage(); y = 20 }

      try {
        doc.addImage(foto.base64, 'JPEG', x, y, fotoWidth, fotoHeight)
        doc.setTextColor(...gris)
        doc.setFontSize(7)
        doc.setFont('helvetica', 'normal')
        doc.text(foto.nombre.substring(0, 30), x, y + fotoHeight + 4)
      } catch (e) {
        console.error('Error al agregar foto al PDF:', e)
      }
    }
    y += fotoHeight + 14
  }

  // Firmas
  if (y > 220) { doc.addPage(); y = 20 }
  doc.setFillColor(...azul)
  doc.rect(10, y, 190, 8, 'F')
  doc.setTextColor(255, 255, 255)
  doc.setFontSize(11)
  doc.setFont('helvetica', 'bold')
  doc.text('Firmas de verificación', 14, y + 5.5)
  y += 14

  const firmasData = [
    { cargo: 'Responsable de ronda', nombre: ronda.value.firma_responsable_nombre, imagen: ronda.value.firma_responsable_imagen },
    { cargo: 'Jefe de Servicio CX', nombre: ronda.value.firma_jefe_nombre, imagen: ronda.value.firma_jefe_imagen },
    { cargo: 'Líder de Ingeniería', nombre: ronda.value.firma_lider_nombre, imagen: ronda.value.firma_lider_imagen },
  ]

  for (const firma of firmasData) {
    if (y > 250) { doc.addPage(); y = 20 }
    doc.setTextColor(...azul)
    doc.setFontSize(9)
    doc.setFont('helvetica', 'bold')
    doc.text(firma.cargo.toUpperCase(), 14, y)
    doc.setTextColor(...negro)
    doc.setFont('helvetica', 'normal')
    doc.text(firma.nombre || 'Pendiente', 14, y + 5)
    if (firma.imagen) { doc.addImage(firma.imagen, 'PNG', 14, y + 8, 50, 20); y += 34 }
    else y += 12
  }

  doc.save(`ronda-${ronda.value.fecha}.pdf`)
}
</script>

<style scoped>
.pagina {
  max-width: 960px;
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
  transition: background 0.15s;
}

.btn-volver:hover { background: rgba(255,255,255,0.25); }

.buscador {
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 20px 24px;
  margin-bottom: 20px;
  display: flex;
  align-items: flex-end;
  gap: 16px;
}

.buscador-campo {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
}

label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

input[type="date"], input[type="text"] {
  padding: 9px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 13px;
  font-family: inherit;
  outline: none;
  color: #0f172a;
  background: #f8fafc;
}

input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(1) sepia(1) saturate(5) hue-rotate(190deg);
  opacity: 0.8;
  cursor: pointer;
}

input:focus { border-color: #3b82f6; background: white; }

.btn-buscar {
  background: #1e3a5f;
  color: white;
  padding: 10px 28px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  white-space: nowrap;
}

.btn-buscar:hover:not(:disabled) { background: #1a3354; }
.btn-buscar:disabled { background: #94a3b8; cursor: not-allowed; }

.mensaje-error {
  background: #fee2e2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 14px 18px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 20px;
}

.ronda-header {
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 20px 24px;
  margin-bottom: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.ronda-fecha {
  font-size: 16px;
  font-weight: 700;
  color: #1e3a5f;
  margin: 0 0 6px;
  text-transform: capitalize;
}

.ronda-estado {
  font-size: 12px;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 20px;
  display: inline-block;
}

.ronda-estado.guardado { background: #fef9c3; color: #854d0e; }
.ronda-estado.completado { background: #dcfce7; color: #15803d; }

.btn-descargar {
  background: #1e3a5f;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
}

.btn-descargar:hover { background: #1a3354; }

/* Resumen */
.resumen-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 10px;
  margin-bottom: 16px;
}

.resumen-card {
  background: white;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  padding: 14px;
  text-align: center;
}

.resumen-card.verde { border-color: #22c55e; background: #f0fdf4; }
.resumen-card.amarillo { border-color: #eab308; background: #fefce8; }
.resumen-card.naranja { border-color: #f97316; background: #fff7ed; }
.resumen-card.rojo { border-color: #ef4444; background: #fef2f2; }
.resumen-card.azul { border-color: #3b82f6; background: #eff6ff; }

.resumen-num {
  font-size: 24px;
  font-weight: 800;
  color: #1e3a5f;
  margin: 0 0 2px;
}

.resumen-label {
  font-size: 11px;
  color: #64748b;
  margin: 0;
}

/* Áreas */
.area {
  margin-bottom: 12px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.area-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #1e3a5f;
  color: white;
  padding: 12px 20px;
}

.area-header.no-realizada { background: #64748b; }
.area-nombre { font-size: 14px; font-weight: 700; }

.badge-realizada {
  background: #22c55e;
  color: white;
  font-size: 11px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
}

.badge-no-realizada {
  background: #ef4444;
  color: white;
  font-size: 11px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
}

/* Tabla */
.tabla-wrapper {
  background: white;
  overflow-x: auto;
}

.tabla-equipos {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.tabla-equipos thead tr {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.tabla-equipos th {
  padding: 10px 14px;
  text-align: left;
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.fila-equipo {
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  transition: background 0.1s;
}

.fila-equipo:hover { background: #f8fafc; }
.fila-equipo.expandida { background: #eff6ff; }
.fila-equipo.tiene-fallas { border-left: 3px solid #f59e0b; }

.tabla-equipos td {
  padding: 10px 14px;
  color: #334155;
  vertical-align: middle;
}

.equipo-nombre-celda {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  color: #1e3a5f;
}

.expand-icon { font-size: 11px; color: #94a3b8; }

.celda-placa {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.placa2 { font-size: 11px; color: #94a3b8; }

.ubicacion-badge {
  font-size: 11px;
  color: #1e3a5f;
  background: #eff6ff;
  padding: 1px 6px;
  border-radius: 4px;
}

.badge-no-encontrado {
  font-size: 11px;
  background: #ffedd5;
  color: #9a3412;
  padding: 2px 8px;
  border-radius: 20px;
  font-weight: 600;
}

.badge-valor {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  display: inline-block;
}

.badge-valor.verde { background: #dcfce7; color: #15803d; }
.badge-valor.rojo { background: #fee2e2; color: #dc2626; }
.badge-valor.amarillo { background: #fef9c3; color: #854d0e; }
.badge-valor.naranja { background: #ffedd5; color: #9a3412; }

.badge-fallas {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  display: inline-block;
}

.badge-fallas.verde { background: #dcfce7; color: #15803d; }
.badge-fallas.rojo { background: #fee2e2; color: #dc2626; }

.sin-dato { color: #94a3b8; font-size: 12px; }

/* Detalle expandido */
.fila-detalle td {
  background: #f8fafc;
  padding: 0;
}

.detalle-contenido {
  padding: 16px 20px;
  border-top: 1px solid #e2e8f0;
}

.pruebas-detalle {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin-bottom: 10px;
}

.prueba-detalle-fila {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
  padding: 7px 12px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  font-size: 12px;
}

.prueba-detalle-label { color: #334155; }

.observaciones-detalle {
  font-size: 12px;
  color: #64748b;
  margin: 0;
  padding: 8px 12px;
  background: #fef9c3;
  border-radius: 8px;
}

/* Fotos */
.seccion-titulo {
  font-size: 15px;
  font-weight: 700;
  color: #1e3a5f;
  margin: 0 0 14px;
}

.fotos-seccion {
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 24px;
  margin-top: 16px;
}

.fotos-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.foto-item {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.15s;
}

.foto-item:hover { transform: translateY(-2px); }

.foto-preview {
  width: 100%;
  height: 140px;
  object-fit: cover;
}

.foto-nombre {
  font-size: 11px;
  color: #64748b;
  padding: 6px 10px;
  margin: 0;
}

.foto-expandir {
  display: block;
  font-size: 11px;
  color: #3b82f6;
  padding: 4px 10px 8px;
  font-weight: 600;
}

/* Firmas */
.firmas-seccion {
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 24px;
  margin-top: 16px;
}

.firmas-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.firma-bloque { display: flex; flex-direction: column; gap: 8px; }

.firma-cargo {
  font-size: 11px;
  font-weight: 700;
  color: #1e3a5f;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0;
}

.firma-nombre {
  font-size: 13px;
  font-weight: 600;
  color: #1e3a5f;
  margin: 0;
}

.firma-imagen {
  width: 100%;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
}

.firma-pendiente-bloque { display: flex; flex-direction: column; gap: 8px; }

.firma-pendiente {
  font-size: 12px;
  color: #94a3b8;
  font-style: italic;
  margin: 0;
}

.canvas-wrapper {
  position: relative;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
  background: #f8fafc;
  cursor: crosshair;
}

.canvas-wrapper canvas {
  display: block;
  width: 100%;
  height: 140px;
  touch-action: none;
}

.canvas-placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  color: #cbd5e1;
  pointer-events: none;
  margin: 0;
}

.firma-acciones {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.btn-limpiar {
  font-size: 12px;
  padding: 4px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  color: #64748b;
  cursor: pointer;
  font-family: inherit;
}

.firma-estado {
  font-size: 12px;
  font-weight: 600;
  color: #94a3b8;
  padding: 3px 10px;
  border-radius: 20px;
  background: #f1f5f9;
}

.firma-estado.firmado { color: #15803d; background: #dcfce7; }

.firma-nombre-input { width: 100%; }

.btn-guardar-firma {
  background: #1e3a5f;
  color: white;
  padding: 10px;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  width: 100%;
}

.btn-guardar-firma:hover:not(:disabled) { background: #1a3354; }
.btn-guardar-firma:disabled { background: #94a3b8; cursor: not-allowed; }

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-contenido {
  background: white;
  border-radius: 14px;
  padding: 20px;
  max-width: 90vw;
  max-height: 90vh;
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.modal-cerrar {
  position: absolute;
  top: 12px;
  right: 12px;
  background: #f1f5f9;
  border: none;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  font-size: 14px;
  cursor: pointer;
}

.modal-imagen {
  max-width: 80vw;
  max-height: 75vh;
  object-fit: contain;
  border-radius: 8px;
}

.modal-nombre { font-size: 13px; color: #64748b; margin: 0; }

@media (max-width: 640px) {
  .resumen-grid { grid-template-columns: repeat(3, 1fr); }
  .pruebas-detalle { grid-template-columns: 1fr; }
  .firmas-grid { grid-template-columns: 1fr; }
  .fotos-grid { grid-template-columns: repeat(2, 1fr); }
  .buscador { flex-direction: column; }
  .header { flex-direction: column; gap: 16px; align-items: flex-start; }
}

.area-no-realizada-msg {
  background: #f8fafc;
  padding: 16px 20px;
  font-size: 13px;
  color: #64748b;
  font-style: italic;
  border-top: 1px solid #e2e8f0;
}
</style>