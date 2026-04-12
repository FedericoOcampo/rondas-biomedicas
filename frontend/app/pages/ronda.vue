<template>
  <div class="pagina">

  <!-- Header -->
  <div class="header">
    <div>
      <div class="header-top">
        <p class="header-sub">Ingeniería Biomédica — Sumimedical</p>
        <span class="estado-ronda" :style="{ background: infoEstado.color }">
          {{ infoEstado.label }}
        </span>
      </div>
      <h1>Ronda Biomédica — Servicio de Cirugía</h1>
    </div>
    <div class="header-meta">
      <div class="fecha-wrapper">
        <p class="fecha">{{ fechaFormateada }}</p>
        <input
          v-model="fechaSeleccionada"
          type="date"
          class="fecha-input"
        />
      </div>
      <div class="progreso-global">
        {{ equiposCompletos }} / {{ totalEquipos }} equipos completados
      </div>
    </div>
  </div>

    <!-- Áreas -->
    <div v-for="area in areasConEstado" :key="area.id" class="area">

      <!-- Cabecera área -->
      <div class="area-header-wrapper">
        <button class="area-header" @click="toggleArea(area.id)">
          <div class="area-header-left">
            <span class="area-icono">{{ areaAbierta === area.id ? '▾' : '▸' }}</span>
            <span class="area-nombre">{{ area.nombre }}</span>
            <span class="area-badge">{{ area.completados }}/{{ area.equipos.length }}</span>
          </div>
          <span v-if="area.completados === area.equipos.length && area.equipos.length > 0" class="check-area">✓ Completado</span>
        </button>
        <div class="toggle-no-realizada">
          <button
            type="button"
            class="btn-no-realizada"
            :class="{ activo: areasNoRealizadas[area.id] }"
            @click="toggleNoRealizada(area.id)"
          >
            {{ areasNoRealizadas[area.id] ? '✕ No realizada' : 'Marcar como no realizada' }}
          </button>
        </div>
      </div>

      <!-- Equipos del área -->
      <div v-show="areaAbierta === area.id && !areasNoRealizadas[area.id]" class="area-contenido">
        <div v-for="equipo in area.equipos" :key="equipo.id" class="equipo-wrapper">

          <!-- Cabecera equipo -->
          <button
            class="equipo-header"
            :class="{ completado: equipoCompleto(area.id, equipo) }"
            @click="toggleEquipo(area.id, equipo.id)"
          >
            <div class="equipo-header-left">
              <span class="equipo-icono">{{ equipoAbierto === area.id + equipo.id ? '▾' : '▸' }}</span>
              <span class="equipo-nombre">{{ equipo.nombre }}</span>
            </div>
            <span v-if="equipoCompleto(area.id, equipo)" class="check-equipo">✓ Completado</span>
          </button>

          <!-- Contenido equipo -->
          <div v-show="equipoAbierto === area.id + equipo.id" class="equipo-contenido">
            <div class="barra-acciones" v-if="!equiposNoEncontrados[area.id]?.[equipo.id]">
              <button
                type="button"
                class="btn-copiar-ronda"
                :disabled="cargandoUltimaRonda"
                @click="copiarUltimaRonda(area.id, equipo.id)"
              >
                {{ cargandoUltimaRonda ? 'Cargando...' : '↩ Copiar última ronda' }}
              </button>
            </div>
            <!-- Identificador: placa o etiqueta -->
            <div v-if="!equipo.sinPlaca" class="campos-placa">
              <div class="campo-placa">
                <label>{{ equipo.dosPlacas ? equipo.placaLabel1 : 'Placa' }}</label>
                <input
                  v-model="respuestas[area.id][equipo.id].placa"
                  type="text"
                  placeholder="Ej: CVEB-0011"
                />
              </div>
              <div v-if="equipo.dosPlacas" class="campo-placa">
                <label>{{ equipo.placaLabel2 }}</label>
                <input
                  v-model="respuestas[area.id][equipo.id].placa2"
                  type="text"
                  placeholder="Ej: CVEB-0012"
                />
              </div>
            </div>

            <!-- Ubicación del equipo -->
            <div v-if="equipo.tieneUbicacion" class="campos-placa">
              <div class="campo-placa">
                <label>Ubicación del equipo</label>
                <select v-model="respuestas[area.id][equipo.id].ubicacion">
                  <option value="">Seleccionar ubicación</option>
                  <option>Quirófano 1</option>
                  <option>Quirófano 2</option>
                  <option>Quirófano 3</option>
                  <option>Quirófano 4</option>
                  <option>Cuarto de equipos</option>
                </select>
              </div>
            </div>

            <!-- Botón No encontrado (solo equipos pequeños) -->
            <div v-if="equipo.equipoPequeno" class="fila-no-encontrado">
              <button
                type="button"
                class="btn-no-encontrado"
                :class="{ activo: equiposNoEncontrados[area.id]?.[equipo.id] }"
                @click="toggleNoEncontrado(area.id, equipo.id)"
              >
                {{ equiposNoEncontrados[area.id]?.[equipo.id] ? '✕ No encontrado' : 'Marcar como no encontrado' }}
              </button>
            </div>


            <!-- Pruebas -->
            <div class="pruebas-lista" :class="{ bloqueado: equipo.equipoPequeno && equiposNoEncontrados[area.id]?.[equipo.id] }">
              <div v-for="prueba in equipo.pruebas" :key="prueba.id" class="prueba-fila">
                <span class="prueba-label">{{ prueba.label }}</span>
                <div class="prueba-botones">

                  <!-- Número -->
                  <template v-if="prueba.tipo === 'numero'">
                    <input
                      v-model="respuestas[area.id][equipo.id].pruebas[prueba.id]"
                      type="number"
                      class="input-numero"
                      placeholder="0"
                      min="0"
                    />
                  </template>

                  <!-- Estado: Bueno / Seguimiento / En reparación / Malo -->
                  <template v-if="prueba.tipo === 'estado'">
                    <button type="button"
                      :class="['btn-opcion', { 'btn-verde': respuestas[area.id][equipo.id].pruebas[prueba.id] === 'bueno' }]"
                      @click="respuestas[area.id][equipo.id].pruebas[prueba.id] = 'bueno'"
                    >Bueno</button>
                    <button type="button"
                      :class="['btn-opcion', { 'btn-amarillo': respuestas[area.id][equipo.id].pruebas[prueba.id] === 'seguimiento' }]"
                      @click="respuestas[area.id][equipo.id].pruebas[prueba.id] = 'seguimiento'"
                    >Seguimiento</button>
                    <button type="button"
                      :class="['btn-opcion', { 'btn-naranja': respuestas[area.id][equipo.id].pruebas[prueba.id] === 'reparacion' }]"
                      @click="respuestas[area.id][equipo.id].pruebas[prueba.id] = 'reparacion'"
                    >En reparación</button>
                    <button type="button"
                      :class="['btn-opcion', { 'btn-rojo': respuestas[area.id][equipo.id].pruebas[prueba.id] === 'malo' }]"
                      @click="respuestas[area.id][equipo.id].pruebas[prueba.id] = 'malo'"
                    >Malo</button>
                  </template>

                  <!-- Opciones accesorios -->
                  <template v-if="prueba.tipo === 'accesorio'">
                    <button type="button"
                      :class="['btn-opcion', { 'btn-verde': respuestas[area.id][equipo.id].pruebas[prueba.id] === 'bueno' }]"
                      @click="respuestas[area.id][equipo.id].pruebas[prueba.id] = 'bueno'"
                    >Bueno</button>
                    <button type="button"
                      :class="['btn-opcion', { 'btn-amarillo': respuestas[area.id][equipo.id].pruebas[prueba.id] === 'se_repone' }]"
                      @click="respuestas[area.id][equipo.id].pruebas[prueba.id] = 'se_repone'"
                    >Se repone</button>
                    <button type="button"
                      :class="['btn-opcion', { 'btn-naranja': respuestas[area.id][equipo.id].pruebas[prueba.id] === 'no_encontrado' }]"
                      @click="respuestas[area.id][equipo.id].pruebas[prueba.id] = 'no_encontrado'"
                    >No encontrado</button>
                    <button type="button"
                      :class="['btn-opcion', { 'btn-rojo': respuestas[area.id][equipo.id].pruebas[prueba.id] === 'malo' }]"
                      @click="respuestas[area.id][equipo.id].pruebas[prueba.id] = 'malo'"
                    >Malo</button>
                  </template>

                  <!-- Sí / No -->
                  <template v-if="prueba.tipo === 'si_no'">
                    <button type="button"
                      :class="['btn-opcion', { 'btn-verde': respuestas[area.id][equipo.id].pruebas[prueba.id] === 'si' }]"
                      @click="respuestas[area.id][equipo.id].pruebas[prueba.id] = 'si'"
                    >Sí</button>
                    <button type="button"
                      :class="['btn-opcion', { 'btn-rojo': respuestas[area.id][equipo.id].pruebas[prueba.id] === 'no' }]"
                      @click="respuestas[area.id][equipo.id].pruebas[prueba.id] = 'no'"
                    >No</button>
                  </template>

                </div>
              </div>

              <!-- Revisión física -->
              <div v-if="!equipo.sinRevision" class="prueba-fila">
                <span class="prueba-label">Revisión física</span>
                <div class="prueba-botones">
                  <button type="button"
                    :class="['btn-opcion', { 'btn-verde': respuestas[area.id][equipo.id].revisionFisica === 'buena' }]"
                    @click="respuestas[area.id][equipo.id].revisionFisica = 'buena'"
                  >Buena</button>
                  <button type="button"
                    :class="['btn-opcion', { 'btn-amarillo': respuestas[area.id][equipo.id].revisionFisica === 'seguimiento' }]"
                    @click="respuestas[area.id][equipo.id].revisionFisica = 'seguimiento'"
                  >Seguimiento</button>
                  <button type="button"
                    :class="['btn-opcion', { 'btn-naranja': respuestas[area.id][equipo.id].revisionFisica === 'reparacion' }]"
                    @click="respuestas[area.id][equipo.id].revisionFisica = 'reparacion'"
                  >En reparación</button>
                  <button type="button"
                    :class="['btn-opcion', { 'btn-rojo': respuestas[area.id][equipo.id].revisionFisica === 'mala' }]"
                    @click="respuestas[area.id][equipo.id].revisionFisica = 'mala'"
                  >Mala</button>
                </div>
              </div>

              <!-- Apto para uso -->
              <div v-if="!equipo.sinRevision" class="prueba-fila">
                <span class="prueba-label">Apto para uso</span>
                <div class="prueba-botones">
                  <button type="button"
                    :class="['btn-opcion', { 'btn-verde': respuestas[area.id][equipo.id].aptoParaUso === 'si' }]"
                    @click="respuestas[area.id][equipo.id].aptoParaUso = 'si'"
                  >Sí</button>
                  <button type="button"
                    :class="['btn-opcion', { 'btn-rojo': respuestas[area.id][equipo.id].aptoParaUso === 'no' }]"
                    @click="respuestas[area.id][equipo.id].aptoParaUso = 'no'"
                  >No</button>
                </div>
              </div>
            </div>

            <!-- Observaciones -->
            <div class="campo-obs">
              <label>Observaciones <span class="opcional">(opcional)</span></label>
              <textarea
                v-model="respuestas[area.id][equipo.id].observaciones"
                rows="2"
                placeholder="Escribir observaciones..."
              ></textarea>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- Evidencia fotográfica -->
    <div class="fotos-seccion">
      <h2 class="fotos-titulo">Evidencia fotográfica</h2>
      <p class="fotos-desc">Adjunta imágenes relacionadas con la ronda (opcional)</p>

      <label class="btn-subir-foto">
        + Agregar fotos
        <input
          type="file"
          accept="image/jpeg,image/png"
          multiple
          @change="agregarFotos"
          style="display: none"
        />
      </label>

      <div v-if="fotos.length > 0" class="fotos-grid">
        <div v-for="(foto, index) in fotos" :key="index" class="foto-item">
          <img :src="foto.base64" :alt="foto.nombre" class="foto-preview" />
          <div class="foto-info">
            <p class="foto-nombre">{{ foto.nombre }}</p>
            <button type="button" class="btn-eliminar-foto" @click="eliminarFoto(index)">✕</button>
          </div>
        </div>
      </div>
    </div>



    <!-- Firmas -->
    <div class="firmas-seccion">
      <h2 class="firmas-titulo">Firmas de verificación</h2>
      <div class="firmas-grid">

        <!-- Firma 1: Responsable de Ingeniería -->
        <div class="firma-bloque">
          <p class="firma-cargo">Responsable de ronda</p>
          <div class="firma-canvas-wrapper">
            <canvas
              ref="canvasResponsable"
              width="300"
              height="140"
              @mousedown="startDraw($event, 'responsable')"
              @mousemove="draw($event, 'responsable')"
              @mouseup="stopDraw('responsable')"
              @mouseleave="stopDraw('responsable')"
              @touchstart.prevent="startDraw($event, 'responsable')"
              @touchmove.prevent="draw($event, 'responsable')"
              @touchend="stopDraw('responsable')"
            ></canvas>
            <p v-if="!firmas.responsable.firmado" class="firma-placeholder">Firmar aquí</p>
          </div>
          <div class="firma-acciones">
            <button type="button" class="btn-limpiar-firma" @click="limpiarFirma('responsable')">
              Limpiar
            </button>
            <span class="firma-estado" :class="{ firmado: firmas.responsable.firmado }">
              {{ firmas.responsable.firmado ? '✓ Firmado' : 'Pendiente' }}
            </span>
          </div>
          <input
            v-model="firmas.responsable.nombre"
            type="text"
            placeholder="Nombre completo"
            class="firma-nombre-input"
          />
        </div>

        <!-- Firma 2: Jefe de Servicios UGI -->
        <div class="firma-bloque">
          <p class="firma-cargo">Jefe de Servicio CX</p>
          <div class="firma-canvas-wrapper">
            <canvas
              ref="canvasJefe"
              width="300"
              height="140"
              @mousedown="startDraw($event, 'jefe')"
              @mousemove="draw($event, 'jefe')"
              @mouseup="stopDraw('jefe')"
              @mouseleave="stopDraw('jefe')"
              @touchstart.prevent="startDraw($event, 'jefe')"
              @touchmove.prevent="draw($event, 'jefe')"
              @touchend="stopDraw('jefe')"
            ></canvas>
            <p v-if="!firmas.jefe.firmado" class="firma-placeholder">Firmar aquí</p>
          </div>
          <div class="firma-acciones">
            <button type="button" class="btn-limpiar-firma" @click="limpiarFirma('jefe')">
              Limpiar
            </button>
            <span class="firma-estado" :class="{ firmado: firmas.jefe.firmado }">
              {{ firmas.jefe.firmado ? '✓ Firmado' : 'Pendiente' }}
            </span>
          </div>
          <input
            v-model="firmas.jefe.nombre"
            type="text"
            placeholder="Nombre completo"
            class="firma-nombre-input"
          />
        </div>

        <!-- Firma 3: Líder de Ingeniería -->
        <div class="firma-bloque">
          <p class="firma-cargo">Líder de Ingeniería</p>
          <div class="firma-canvas-wrapper">
            <canvas
              ref="canvasLider"
              width="300"
              height="140"
              @mousedown="startDraw($event, 'lider')"
              @mousemove="draw($event, 'lider')"
              @mouseup="stopDraw('lider')"
              @mouseleave="stopDraw('lider')"
              @touchstart.prevent="startDraw($event, 'lider')"
              @touchmove.prevent="draw($event, 'lider')"
              @touchend="stopDraw('lider')"
            ></canvas>
            <p v-if="!firmas.lider.firmado" class="firma-placeholder">Firmar aquí</p>
          </div>
          <div class="firma-acciones">
            <button type="button" class="btn-limpiar-firma" @click="limpiarFirma('lider')">
              Limpiar
            </button>
            <span class="firma-estado" :class="{ firmado: firmas.lider.firmado }">
              {{ firmas.lider.firmado ? '✓ Firmado' : 'Pendiente' }}
            </span>
          </div>
          <input
            v-model="firmas.lider.nombre"
            type="text"
            placeholder="Nombre completo"
            class="firma-nombre-input"
          />
        </div>

      </div>
    </div>

<!-- Botón guardar -->
<div class="footer">
  <div class="footer-contenido">
    <div v-if="mostrarValidacion && !puedeGuardar" class="resumen-validacion">      <p class="validacion-titulo">⚠️ Falta completar la siguiente información:</p>
      <div v-for="error in validacion" :key="error.area" class="validacion-area">
        <p class="validacion-area-nombre">{{ error.area }}</p>
        <ul>
          <li v-for="eq in error.equipos" :key="eq">{{ eq }}</li>
        </ul>
      </div>
    </div>
    <button
      class="btn-guardar"
      :class="{ deshabilitado: !puedeGuardar }"
      :disabled="!puedeGuardar"
      @click="guardar"
    >
      Guardar ronda
    </button>
  </div>
</div>

  </div>
</template>

<script setup>
import { nextTick } from 'vue'
import { areasRonda } from '~/data/equipos.js'
import { ultimaRonda } from '~/data/ultimaRonda.js'

const canvasResponsable = ref(null)
const canvasJefe = ref(null)
const canvasLider = ref(null)

const firmas = reactive({
  responsable: { firmado: false, nombre: '', dibujando: false },
  jefe:        { firmado: false, nombre: '', dibujando: false },
  lider:       { firmado: false, nombre: '', dibujando: false }
})

function getCanvas(quien) {
  if (quien === 'responsable') return canvasResponsable.value
  if (quien === 'jefe') return canvasJefe.value
  if (quien === 'lider') return canvasLider.value
}

function getPos(e, canvas) {
  const rect = canvas.getBoundingClientRect()
  const scaleX = canvas.width / rect.width
  const scaleY = canvas.height / rect.height
  if (e.touches) {
    return {
      x: (e.touches[0].clientX - rect.left) * scaleX,
      y: (e.touches[0].clientY - rect.top) * scaleY
    }
  }
  return {
    x: (e.clientX - rect.left) * scaleX,
    y: (e.clientY - rect.top) * scaleY
  }
}

function startDraw(e, quien) {
  const canvas = getCanvas(quien)
  const ctx = canvas.getContext('2d')
  const pos = getPos(e, canvas)
  firmas[quien].dibujando = true
  ctx.beginPath()
  ctx.moveTo(pos.x, pos.y)
}

function draw(e, quien) {
  if (!firmas[quien].dibujando) return
  const canvas = getCanvas(quien)
  const ctx = canvas.getContext('2d')
  const pos = getPos(e, canvas)
  ctx.lineWidth = 2
  ctx.lineCap = 'round'
  ctx.strokeStyle = '#0f172a'
  ctx.lineTo(pos.x, pos.y)
  ctx.stroke()
  ctx.beginPath()
  ctx.moveTo(pos.x, pos.y)
  firmas[quien].firmado = true
}

function stopDraw(quien) {
  firmas[quien].dibujando = false
}

function limpiarFirma(quien) {
  const canvas = getCanvas(quien)
  const ctx = canvas.getContext('2d')
  ctx.clearRect(0, 0, canvas.width, canvas.height)
  firmas[quien].firmado = false
}

const cargandoUltimaRonda = ref(false)

async function copiarUltimaRonda(areaId, equipoId) {
  try {
    cargandoUltimaRonda.value = true
    const config = useRuntimeConfig()
    const fecha = fechaSeleccionada.value || new Date().toISOString().slice(0, 10)

    const [ultimaRondaAPI, rondasAnteriores] = await Promise.all([
      $fetch(`${config.public.apiBase}/rondas/ultima`).catch(() => null),
      $fetch(`${config.public.apiBase}/rondas/anteriores/${fecha}`).catch(() => [])
    ])

    const todasLasRondas = [ultimaRondaAPI, ...rondasAnteriores].filter(Boolean)
    let equipoEncontrado = null

    for (const ronda of todasLasRondas) {
      const area = ronda.areas?.find(a => a.area_id === areaId)
      if (!area || area.no_realizada) continue
      const equipo = area.equipos?.find(e => e.equipo_id === equipoId)
      if (equipo) {
        equipoEncontrado = equipo
        break
      }
    }

    if (!equipoEncontrado) {
      const datosLocales = ultimaRonda[areaId]?.[equipoId]
      if (datosLocales) {
        respuestas[areaId][equipoId].placa = datosLocales.placa || ''
        if (datosLocales.placa2) respuestas[areaId][equipoId].placa2 = datosLocales.placa2
        return
      }
      return
    }

    if (equipoEncontrado.placa)
      respuestas[areaId][equipoId].placa = equipoEncontrado.placa
    if (equipoEncontrado.placa2)
      respuestas[areaId][equipoId].placa2 = equipoEncontrado.placa2

    equipoEncontrado.pruebas?.forEach(prueba => {
      let valor = prueba.valor

      // Buscar coincidencia exacta primero
      if (respuestas[areaId][equipoId].pruebas[prueba.prueba_id] !== undefined) {
        respuestas[areaId][equipoId].pruebas[prueba.prueba_id] = valor
        return
      }

      // Buscar coincidencia parcial (ej: 'nibp' coincide con 'nibp_q1')
      const claveLocal = Object.keys(respuestas[areaId][equipoId].pruebas).find(k =>
        k.startsWith(prueba.prueba_id) || prueba.prueba_id.startsWith(k)
      )
      if (claveLocal) {
        // Obtener la definición del campo para saber su tipo
        const area = areasRonda.find(a => a.id === areaId)
        const equipo = area?.equipos.find(e => e.id === equipoId)
        const pruebaDef = equipo?.pruebas.find(p => p.id === claveLocal)

        // Solo convertir si el campo es tipo 'estado', no si es 'si_no'
        if (pruebaDef?.tipo === 'estado') {
          if (valor === 'si') valor = 'bueno'
          if (valor === 'no') valor = 'malo'
        }
        respuestas[areaId][equipoId].pruebas[claveLocal] = valor
      }
    })

    if (equipoEncontrado.revision_fisica)
      respuestas[areaId][equipoId].revisionFisica = equipoEncontrado.revision_fisica
    if (equipoEncontrado.apto_para_uso)
      respuestas[areaId][equipoId].aptoParaUso = equipoEncontrado.apto_para_uso
    if (equipoEncontrado.observaciones)
      respuestas[areaId][equipoId].observaciones = equipoEncontrado.observaciones
    if (equipoEncontrado.ubicacion)
      respuestas[areaId][equipoId].ubicacion = equipoEncontrado.ubicacion

  } catch (error) {
    console.error('Error al copiar última ronda:', error)
  } finally {
    cargandoUltimaRonda.value = false
  }
}

async function buscarEnRondasAnteriores(areaId, equipoId) {
  try {
    const config = useRuntimeConfig()
    const fecha = fechaSeleccionada.value || new Date().toISOString().slice(0, 10)
    const rondas = await $fetch(`${config.public.apiBase}/rondas/anteriores/${fecha}`)
    if (!rondas || rondas.length === 0) return null
    for (const ronda of rondas) {
      const area = ronda.areas?.find(a => a.area_id === areaId)
      if (!area || area.no_realizada) continue
      const equipo = area.equipos?.find(e => e.equipo_id === equipoId)
      if (equipo && equipo.placa) return equipo
    }
    return null
  } catch (e) {
    console.error('Error buscando rondas anteriores:', e)
    return null
  }
}

const fechaSeleccionada = ref('')

const fechaFormateada = computed(() =>
  fechaSeleccionada.value
    ? new Date(fechaSeleccionada.value + 'T12:00:00').toLocaleDateString('es-CO', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
      })
    : 'Selecciona una fecha'
)

const areaAbierta = ref(null)
const equipoAbierto = ref(null)
const areasNoRealizadas = reactive({})

function toggleNoRealizada(areaId) {
  if (areasNoRealizadas[areaId]) {
    areasNoRealizadas[areaId] = false
    return
  }
  const confirmado = window.confirm(
    '¿Estás seguro de que no se realizará la ronda en este quirófano o servicio?\n\n' +
    'Toda la información ingresada se eliminará y no se guardarán datos de los equipos.'
  )
  if (!confirmado) return
  areasNoRealizadas[areaId] = true
  const area = areasRonda.find(a => a.id === areaId)
  area.equipos.forEach(equipo => {
    respuestas[areaId][equipo.id] = {
      placa: '',
      placa2: equipo.dosPlacas ? '' : undefined,
      pruebas: Object.fromEntries(equipo.pruebas.map(p => [p.id, ''])),
      revisionFisica: '',
      aptoParaUso: '',
      observaciones: ''
    }
  })
  areaAbierta.value = null
}

async function toggleNoEncontrado(areaId, equipoId) {
  const estado = equiposNoEncontrados[areaId][equipoId]
  if (estado) {
    equiposNoEncontrados[areaId][equipoId] = false
    return
  }
  equiposNoEncontrados[areaId][equipoId] = true
  const equipo = areasRonda.find(a => a.id === areaId)?.equipos.find(e => e.id === equipoId)
  if (equipo) {
    respuestas[areaId][equipoId].pruebas = Object.fromEntries(equipo.pruebas.map(p => [p.id, '']))
    respuestas[areaId][equipoId].revisionFisica = ''
    respuestas[areaId][equipoId].aptoParaUso = ''
    respuestas[areaId][equipoId].observaciones = ''
  }
  try {
    const config = useRuntimeConfig()
    const fecha = fechaSeleccionada.value || new Date().toISOString().slice(0, 10)
    const rondas = await $fetch(`${config.public.apiBase}/rondas/anteriores/${fecha}`)
    for (const ronda of rondas) {
      const area = ronda.areas?.find(a => a.area_id === areaId)
      if (!area || area.no_realizada) continue
      const equipoAnterior = area.equipos?.find(e => e.equipo_id === equipoId)
      if (equipoAnterior?.placa) {
        respuestas[areaId][equipoId].placa = equipoAnterior.placa
        break
      }
    }
  } catch (e) {
    console.error('Error buscando placa anterior:', e)
  }
}

function toggleArea(id) {
  areaAbierta.value = areaAbierta.value === id ? null : id
  equipoAbierto.value = null
}

function toggleEquipo(areaId, equipoId) {
  const key = areaId + equipoId
  equipoAbierto.value = equipoAbierto.value === key ? null : key
}

const respuestas = reactive({})
const equiposNoEncontrados = reactive({})

areasRonda.forEach(area => {
  respuestas[area.id] = {}
  area.equipos.forEach(equipo => {
    const placaAnterior = equipo.equipoPequeno
      ? (ultimaRonda[area.id]?.[equipo.id]?.placa || '')
      : ''
    respuestas[area.id][equipo.id] = {
      placa: placaAnterior,
      placa2: equipo.dosPlacas ? '' : undefined,
      etiqueta: '',
      ubicacion: '',
      pruebas: Object.fromEntries(equipo.pruebas.map(p => [p.id, ''])),
      revisionFisica: '',
      aptoParaUso: '',
      observaciones: ''
    }
    if (equipo.equipoPequeno) {
      if (!equiposNoEncontrados[area.id]) equiposNoEncontrados[area.id] = {}
      equiposNoEncontrados[area.id][equipo.id] = false
    }
  })
})

function equipoCompleto(areaId, equipo) {
  if (equiposNoEncontrados[areaId]?.[equipo.id]) return true
  const r = respuestas[areaId][equipo.id]

  // Equipos con placa y revisión normales
  if (!equipo.sinRevision && !equipo.sinPlaca) {
    if (!r.placa || !r.revisionFisica || !r.aptoParaUso) return false
  }

  // Equipos con placa pero sin revisión (ej: terapias VAC)
  if (equipo.sinRevision && !equipo.sinPlaca) {
    if (!r.placa) return false
  }

  // Equipos con doble placa
  if (equipo.dosPlacas && !r.placa2) return false

  // Equipos con ubicación obligatoria
  if (equipo.tieneUbicacion && !r.ubicacion) return false

  // Todas las pruebas deben estar llenas (incluyendo sinPlaca y sinRevision)
  return equipo.pruebas.every(p =>
    r.pruebas[p.id] !== '' &&
    r.pruebas[p.id] !== null &&
    r.pruebas[p.id] !== undefined
  )
}

const areasConEstado = computed(() =>
  areasRonda.map(area => ({
    ...area,
    completados: area.equipos.filter(eq => equipoCompleto(area.id, eq)).length
  }))
)

const totalEquipos = computed(() =>
  areasRonda.reduce((sum, a) => sum + a.equipos.length, 0)
)

const equiposCompletos = computed(() =>
  areasRonda.reduce((sum, a) =>
    sum + a.equipos.filter(eq => equipoCompleto(a.id, eq)).length, 0)
)

const validacion = computed(() => {
  const errores = []
  areasRonda.forEach(area => {
    if (areasNoRealizadas[area.id]) return
    const equiposFaltantes = area.equipos.filter(eq => !equipoCompleto(area.id, eq))
    if (equiposFaltantes.length > 0) {
      errores.push({
        area: area.nombre,
        equipos: equiposFaltantes.map(eq => eq.nombre)
      })
    }
  })
  return errores
})

const puedeGuardar = computed(() => validacion.value.length === 0)
const mostrarValidacion = ref(false)
const estadoRonda = ref('borrador')

const infoEstado = computed(() => {
  if (estadoRonda.value === 'borrador') return { label: 'Borrador', color: '#94a3b8' }
  if (estadoRonda.value === 'guardado') return { label: 'Guardado — Pendiente firma líder', color: '#f59e0b' }
  if (estadoRonda.value === 'completado') return { label: 'Completado', color: '#22c55e' }
})

const guardando = ref(false)
const errorGuardar = ref('')
const fotos = ref([])

function agregarFotos(event) {
  const archivos = Array.from(event.target.files)
  archivos.forEach(archivo => {
    const reader = new FileReader()
    reader.onload = (e) => {
      fotos.value.push({ nombre: archivo.name, base64: e.target.result })
    }
    reader.readAsDataURL(archivo)
  })
}

function eliminarFoto(index) {
  fotos.value.splice(index, 1)
}

async function guardar() {
  mostrarValidacion.value = true
  if (!puedeGuardar.value) return

  const firmasPendientes = []
  if (!firmas.responsable.firmado || !firmas.responsable.nombre)
    firmasPendientes.push('Responsable de ronda')
  if (!firmas.jefe.firmado || !firmas.jefe.nombre)
    firmasPendientes.push('Jefe de Servicio CX')
  // Validar fecha
  if (!fechaSeleccionada.value) {
    alert('⚠️ Debes seleccionar una fecha antes de guardar la ronda.')
    return
  }
  if (firmasPendientes.length > 0) {
    alert('Faltan las siguientes firmas:\n\n• ' + firmasPendientes.join('\n• '))
    return
  }

  const areas = {}
  areasRonda.forEach(area => {
    const equipos = {}
    area.equipos.forEach(equipo => {
      const pruebasConLabel = {}
      equipo.pruebas.forEach(prueba => {
        pruebasConLabel[prueba.id] = {
          label: prueba.label,
          valor: respuestas[area.id][equipo.id].pruebas[prueba.id]
        }
      })
      equipos[equipo.id] = {
        nombre: equipo.nombre,
        placa: respuestas[area.id][equipo.id].placa,
        placa2: respuestas[area.id][equipo.id].placa2,
        revisionFisica: respuestas[area.id][equipo.id].revisionFisica,
        aptoParaUso: respuestas[area.id][equipo.id].aptoParaUso,
        observaciones: respuestas[area.id][equipo.id].observaciones,
        ubicacion: respuestas[area.id][equipo.id].ubicacion || null,
        noEncontrado: equiposNoEncontrados[area.id]?.[equipo.id] || false,
        pruebas: pruebasConLabel
      }
    })
    areas[area.id] = {
      nombre: area.nombre,
      noRealizada: areasNoRealizadas[area.id] || false,
      equipos
    }
  })

  const payload = {
    fecha: fechaSeleccionada.value,
    estado: firmas.lider.firmado && firmas.lider.nombre ? 'completado' : 'guardado',
    fotos: fotos.value,
    firmas: {
      responsable: {
        nombre: firmas.responsable.nombre,
        imagen: canvasResponsable.value.toDataURL('image/png')
      },
      jefe: {
        nombre: firmas.jefe.nombre,
        imagen: canvasJefe.value.toDataURL('image/png')
      },
      lider: {
        nombre: firmas.lider.nombre || null,
        imagen: firmas.lider.firmado ? canvasLider.value.toDataURL('image/png') : null
      }
    },
    areas
  }

  try {
    guardando.value = true
    errorGuardar.value = ''

    const config = useRuntimeConfig()
    await $fetch(`${config.public.apiBase}/rondas`, {
      method: 'POST',
      body: payload
    })

    estadoRonda.value = payload.estado
    alert('✓ Ronda guardada correctamente')

  } catch (error) {
    const status = error?.status || error?.response?.status
    const mensaje = error?.data?.message || error?.response?._data?.message
    if (status === 422 && mensaje) {
      alert('⚠️ ' + mensaje)
    } else {
      errorGuardar.value = 'Error al guardar la ronda. Intenta de nuevo.'
    }
  } finally {
    guardando.value = false
  }
}
</script>

<style scoped>
.pagina {
  max-width: 860px;
  margin: 0 auto;
  padding: 32px 20px 80px;
  font-family: 'Segoe UI', sans-serif;
  background: #f1f5f9;
  min-height: 100vh;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
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

.header h1 {
  font-size: 20px;
  font-weight: 700;
  margin: 0;
}

.header-meta { text-align: right; }

.fecha {
  font-size: 12px;
  color: #93c5fd;
  margin: 0 0 8px;
  text-transform: capitalize;
}

.progreso-global {
  background: rgba(255,255,255,0.1);
  border: 1px solid rgba(255,255,255,0.2);
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
}

.area {
  margin-bottom: 10px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  background: white;
}

.area-header {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #1e3a5f;
  color: white;
  padding: 14px 20px;
  border: none;
  cursor: pointer;
  text-align: left;
  font-family: inherit;
}

.area-header:hover { background: #1a3354; }

.area-header-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.area-icono { font-size: 14px; width: 14px; }
.area-nombre { font-size: 15px; font-weight: 700; }

.area-badge {
  background: rgba(255,255,255,0.15);
  font-size: 12px;
  padding: 2px 10px;
  border-radius: 20px;
}

.check-area {
  background: #22c55e;
  color: white;
  font-size: 12px;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 20px;
}

.area-contenido {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  background: #f8fafc;
}

.equipo-wrapper {
  border-radius: 10px;
  overflow: hidden;
  border: 1.5px solid #e2e8f0;
}

.equipo-header {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
  padding: 12px 16px;
  border: none;
  cursor: pointer;
  text-align: left;
  font-family: inherit;
  transition: background 0.15s;
}

.equipo-header:hover { background: #f8fafc; }

.equipo-header.completado {
  background: #f0fdf4;
  border-left: 3px solid #22c55e;
}

.equipo-header-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.equipo-icono { font-size: 12px; color: #64748b; }

.equipo-nombre {
  font-size: 14px;
  font-weight: 600;
  color: #1e3a5f;
}

.check-equipo {
  font-size: 12px;
  font-weight: 700;
  color: #22c55e;
}

.equipo-contenido {
  padding: 16px;
  background: white;
  border-top: 1px solid #f1f5f9;
}

.campos-placa {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}

.campo-placa {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.opcional {
  font-weight: 400;
  text-transform: none;
  color: #94a3b8;
  font-size: 11px;
}

input, select, textarea {
  padding: 8px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 13px;
  color: #0f172a;
  background: #f8fafc;
  font-family: inherit;
  outline: none;
}

input:focus, textarea:focus {
  border-color: #3b82f6;
  background: white;
}

.input-numero {
  width: 80px;
  text-align: center;
}

.pruebas-lista {
  display: flex;
  flex-direction: column;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
  margin-bottom: 14px;
}

.prueba-fila {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  border-bottom: 1px solid #f1f5f9;
}

.prueba-fila:last-child { border-bottom: none; }

.prueba-label {
  font-size: 13px;
  color: #334155;
}

.prueba-botones {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.btn-opcion {
  padding: 5px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  background: white;
  color: #64748b;
  font-family: inherit;
  transition: all 0.15s;
}

.btn-opcion:hover { border-color: #94a3b8; }

.btn-verde {
  background: #dcfce7;
  border-color: #22c55e;
  color: #15803d;
}

.btn-amarillo {
  background: #fef9c3;
  border-color: #eab308;
  color: #854d0e;
}

.btn-naranja {
  background: #ffedd5;
  border-color: #f97316;
  color: #9a3412;
}

.btn-rojo {
  background: #fee2e2;
  border-color: #ef4444;
  color: #dc2626;
}

.campo-obs {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

textarea { resize: vertical; }

.footer {
  margin-top: 28px;
  display: flex;
  justify-content: flex-end;
}

.btn-guardar {
  background: #1e3a5f;
  color: white;
  padding: 14px 36px;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: background 0.2s;
}

.btn-guardar:hover { background: #1a3354; }

@media (max-width: 600px) {
  .header { flex-direction: column; gap: 16px; }
  .header-meta { text-align: left; }
  .prueba-fila { flex-direction: column; align-items: flex-start; gap: 8px; }
  .campos-placa { flex-direction: column; }
}
.toggle-no-realizada {
  display: flex;
  align-items: center;
}

.btn-no-realizada {
  font-size: 12px;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 20px;
  border: 1.5px solid rgba(255,255,255,0.3);
  background: transparent;
  color: rgba(255,255,255,0.7);
  cursor: pointer;
  font-family: inherit;
  transition: all 0.15s;
}

.btn-no-realizada:hover {
  border-color: #fca5a5;
  color: #fca5a5;
}

.btn-no-realizada.activo {
  background: #ef4444;
  border-color: #ef4444;
  color: white;
}

.toggle-label input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: #ef4444;
  cursor: pointer;
}

.barra-acciones {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 14px;
}

.btn-copiar-ronda {
  font-size: 12px;
  font-weight: 600;
  padding: 5px 14px;
  border-radius: 20px;
  border: 1.5px solid #3b82f6;
  background: transparent;
  color: #3b82f6;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.15s;
}

.btn-copiar-ronda:hover {
  background: #eff6ff;
}

.area-header-wrapper {
  display: flex;
  align-items: center;
  background: #1e3a5f;
}

.area-header-wrapper .area-header {
  flex: 1;
}

.firmas-seccion {
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 28px;
  margin-top: 20px;
}

.firmas-titulo {
  font-size: 16px;
  font-weight: 700;
  color: #1e3a5f;
  margin: 0 0 20px;
}

.firmas-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.firma-bloque {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.firma-cargo {
  font-size: 12px;
  font-weight: 700;
  color: #1e3a5f;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0;
}

.firma-canvas-wrapper {
  position: relative;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
  background: #f8fafc;
  cursor: crosshair;
}

.firma-canvas-wrapper canvas {
  display: block;
  width: 100%;
  height: 140px;
  touch-action: none;
}

.firma-placeholder {
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

.btn-limpiar-firma {
  font-size: 12px;
  padding: 4px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  color: #64748b;
  cursor: pointer;
  font-family: inherit;
  transition: border-color 0.15s;
}

.btn-limpiar-firma:hover {
  border-color: #94a3b8;
}

.firma-estado {
  font-size: 12px;
  font-weight: 600;
  color: #94a3b8;
  padding: 3px 10px;
  border-radius: 20px;
  background: #f1f5f9;
}

.firma-estado.firmado {
  color: #15803d;
  background: #dcfce7;
}

.firma-nombre-input {
  width: 100%;
}

.fila-no-encontrado {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 12px;
}

.btn-no-encontrado {
  font-size: 12px;
  font-weight: 600;
  padding: 5px 14px;
  border-radius: 20px;
  border: 1.5px solid #f97316;
  background: transparent;
  color: #f97316;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.15s;
}

.btn-no-encontrado:hover {
  background: #ffedd5;
}

.btn-no-encontrado.activo {
  background: #f97316;
  border-color: #f97316;
  color: white;
}

.pruebas-lista.bloqueado {
  opacity: 0.4;
  pointer-events: none;
}

@media (max-width: 768px) {
  .firmas-grid { grid-template-columns: 1fr; }
}

.fecha-wrapper {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.fecha-input {
  font-size: 11px;
  padding: 4px 8px;
  border-radius: 6px;
  border: 1px solid rgba(255,255,255,0.4);
  background: rgba(255,255,255,0.9);
  color: #1e3a5f;
  font-family: inherit;
  cursor: pointer;
}

.fecha-input::-webkit-calendar-picker-indicator {
  filter: invert(1) sepia(1) saturate(5) hue-rotate(190deg);
  opacity: 0.8;
  cursor: pointer;
}

.fecha-input:focus {
  outline: none;
  border-color: rgba(255,255,255,0.5);
}

.footer-contenido {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 16px;
  margin-top: 28px;
}

.resumen-validacion {
  width: 100%;
  background: #fff7ed;
  border: 1.5px solid #f97316;
  border-radius: 10px;
  padding: 16px 20px;
}

.validacion-titulo {
  font-size: 13px;
  font-weight: 700;
  color: #9a3412;
  margin: 0 0 12px;
}

.validacion-area {
  margin-bottom: 10px;
}

.validacion-area-nombre {
  font-size: 13px;
  font-weight: 700;
  color: #1e3a5f;
  margin: 0 0 4px;
}

.validacion-area ul {
  margin: 0;
  padding-left: 18px;
}

.validacion-area li {
  font-size: 12px;
  color: #334155;
  margin-bottom: 2px;
}

.btn-guardar.deshabilitado {
  background: #94a3b8;
  cursor: not-allowed;
  transform: none;
}

.header-top {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 6px;
}

.estado-ronda {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
  color: white;
  letter-spacing: 0.04em;
}

.fotos-seccion {
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 28px;
  margin-top: 20px;
}

.fotos-titulo {
  font-size: 16px;
  font-weight: 700;
  color: #1e3a5f;
  margin: 0 0 6px;
}

.fotos-desc {
  font-size: 13px;
  color: #64748b;
  margin: 0 0 16px;
}

.btn-subir-foto {
  display: inline-block;
  background: none;
  border: 1.5px dashed #3b82f6;
  color: #3b82f6;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-subir-foto:hover {
  background: #eff6ff;
}

.fotos-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-top: 16px;
}

.foto-item {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
}

.foto-preview {
  width: 100%;
  height: 140px;
  object-fit: cover;
}

.foto-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 10px;
}

.foto-nombre {
  font-size: 11px;
  color: #64748b;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 120px;
}

.btn-eliminar-foto {
  background: none;
  border: none;
  color: #ef4444;
  cursor: pointer;
  font-size: 13px;
  padding: 2px 6px;
  border-radius: 4px;
}

.btn-eliminar-foto:hover {
  background: #fee2e2;
}
</style>