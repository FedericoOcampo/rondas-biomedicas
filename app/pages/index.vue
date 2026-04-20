<template>
  <div class="home">

    <!-- Header -->
    <div class="home-logo-wrapper">
      <img src="/clinica-victoriana.png" alt="Clínica Victoriana" class="home-logo" />
    </div>
    <div class="home-header">
      <div>
        <p class="home-sub">Ingeniería Biomédica</p>
        <h1 class="home-titulo">Rondas Biomédicas</h1>
        <p class="home-desc">Servicio de Cirugía — Sumimedical</p>
      </div>
    </div>

    <!-- Fecha -->
    <p class="home-fecha">{{ fechaHoy }}</p>

    <!-- Tarjetas de navegación -->
    <div class="home-grid">

      <NuxtLink to="/ronda" class="home-card primary">
        <div class="card-icono">📋</div>
        <div class="card-texto">
          <h2>Agregar ronda</h2>
          <p>Registrar una nueva ronda biomédica del día</p>
        </div>
        <span class="card-flecha">→</span>
      </NuxtLink>

      <NuxtLink to="/historial" class="home-card primary">
        <div class="card-icono">📊</div>
        <div class="card-texto">
          <h2>Histórico de rondas</h2>
          <p>Visualizar registros anteriores y estadísticas</p>
        </div>
        <span class="card-flecha">→</span>
      </NuxtLink>

      <NuxtLink to="/buscar" class="home-card primary">
        <div class="card-icono">🔍</div>
        <div class="card-texto">
          <h2>Buscar ronda</h2>
          <p>Consultar una ronda específica por fecha o equipo</p>
        </div>
        <span class="card-flecha">→</span>
      </NuxtLink>

    </div>

  </div>
</template>

<script setup>
const fechaHoy = new Date().toLocaleDateString('es-CO', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
})
</script>

<style scoped>
.home {
  max-width: 860px;
  margin: 0 auto;
  padding: 48px 20px;
  font-family: 'Segoe UI', sans-serif;
  min-height: 100vh;
  background: #f1f5f9;
}

/* Header */
.home-header {
  display: flex;
  align-items: center;
  gap: 24px;
  background: #1e3a5f;
  color: white;
  padding: 32px;
  border-radius: 16px;
  margin-bottom: 16px;
}

.home-logo {
  height: 72px;
  width: auto;
  object-fit: contain;
  border-radius: 8px;
}

.home-sub {
  font-size: 11px;
  color: #93c5fd;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin: 0 0 6px;
}

.home-titulo {
  font-size: 28px;
  font-weight: 800;
  margin: 0 0 4px;
  letter-spacing: -0.02em;
}

.home-desc {
  font-size: 14px;
  color: #93c5fd;
  margin: 0;
}

.home-fecha {
  font-size: 13px;
  color: #64748b;
  text-transform: capitalize;
  margin: 0 0 28px 4px;
}

/* Grid de tarjetas */
.home-grid {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.home-card {
  display: flex;
  align-items: center;
  gap: 20px;
  background: white;
  border-radius: 14px;
  padding: 24px 28px;
  border: 1.5px solid #e2e8f0;
  text-decoration: none;
  color: inherit;
  transition: all 0.2s;
}

.home-card.primary {
  cursor: pointer;
  border-color: #1e3a5f;
}

.home-card.primary:hover {
  background: #1e3a5f;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(30, 58, 95, 0.2);
}

.home-card.primary:hover p {
  color: #93c5fd;
}

.home-card.disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.card-icono {
  font-size: 32px;
  flex-shrink: 0;
}

.card-texto {
  flex: 1;
}

.card-texto h2 {
  font-size: 17px;
  font-weight: 700;
  margin: 0 0 4px;
  color: #1e3a5f;
}

.home-card.primary:hover .card-texto h2 {
  color: white;
}

.card-texto p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.card-flecha {
  font-size: 20px;
  color: #1e3a5f;
  flex-shrink: 0;
}

.home-card.primary:hover .card-flecha {
  color: white;
}

.card-badge {
  font-size: 11px;
  font-weight: 700;
  background: #f1f5f9;
  color: #94a3b8;
  padding: 4px 12px;
  border-radius: 20px;
  flex-shrink: 0;
}

.home-logo-wrapper {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}

.home-logo {
  height: 80px;
  width: auto;
  object-fit: contain;
}

@media (max-width: 600px) {
  .home-header { flex-direction: column; align-items: flex-start; }
  .home-logo { height: 52px; }
}
</style>