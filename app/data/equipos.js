const pruebasMonitor = [
  { id: 'nibp', label: 'Brazalete y cable NIBP', tipo: 'accesorio' },
  { id: 'spo2', label: 'Pinza de SpO2', tipo: 'accesorio' },
  { id: 'cables_ekg', label: 'Cables EKG', tipo: 'accesorio' },
  { id: 'sensor_temp', label: 'Sensor de temperatura', tipo: 'accesorio' },
]

const crearMonitores = (cantidad, sufijo) =>
  Array.from({ length: cantidad }, (_, i) => ({
    id: `monitor_${sufijo}_${i + 1}`,
    nombre: `Monitor multiparámetro ${i + 1}`,
    pruebas: pruebasMonitor.map(p => ({ ...p, id: `${p.id}_${sufijo}_${i + 1}` }))
  }))

const equiposQuirofano = (sufijo) => [
  {
    id: `maq_anestesia_${sufijo}`,
    nombre: 'Máquina de anestesia',
    pruebas: [
      { id: 'fuga_auto', label: 'Prueba de fuga auto (ml/min)', tipo: 'numero' },
      { id: 'calibracion_o2', label: 'Calibración de O2', tipo: 'si_no', informativo: true },
    ]
  },
  {
    id: `monitores_${sufijo}`,
    nombre: 'Monitor multiparámetro (N19 + N1)',
    dosPlacas: true,
    placaLabel1: 'Placa monitor N19',
    placaLabel2: 'Placa monitor N1',
    pruebas: [
      { id: 'nibp', label: 'Brazalete y cable NIBP', tipo: 'accesorio' },
      { id: 'spo2', label: 'Pinza de SpO2', tipo: 'accesorio' },
      { id: 'cables_ekg', label: 'Cables EKG', tipo: 'accesorio' },
      { id: 'sensor_temp', label: 'Sensor de temperatura', tipo: 'accesorio' },
    ]
  },
  {
    id: `mesa_cirugia_${sufijo}`,
    nombre: 'Mesa de cirugía',
    pruebas: [
      { id: 'movimientos', label: 'Movimientos', tipo: 'estado' },
      { id: 'conexion_toma', label: 'Conectada al toma', tipo: 'si_no', informativo: true },
      { id: 'control_mesa', label: 'Control de mesa', tipo: 'estado' },
    ]
  },
  {
    id: `lamparas_${sufijo}`,
    nombre: 'Lámparas cielíticas orbitales',
    pruebas: [
      { id: 'brazo1', label: 'Brazo articulado 1', tipo: 'estado' },
      { id: 'brazo2', label: 'Brazo articulado 2', tipo: 'estado' },
      { id: 'display1', label: 'Display 1', tipo: 'estado' },
      { id: 'display2', label: 'Display 2', tipo: 'estado' },
      { id: 'intensidad1', label: 'Intensidad lumínica 1', tipo: 'estado' },
      { id: 'intensidad2', label: 'Intensidad lumínica 2', tipo: 'estado' },
    ]
  },
  {
    id: `electrobisturi_${sufijo}`,
    nombre: 'Electrobisturí',
    pruebas: [
      { id: 'modos_corte', label: 'N° modos de corte funcionales', tipo: 'numero', minEsperado: 3 },
      { id: 'modos_coagulacion', label: 'N° modos de coagulación funcionales', tipo: 'numero', minEsperado: 3 },
      { id: 'pedales', label: 'Pedales funcionales', tipo: 'estado' },
    ]
  },
  {
    id: `fonendoscopio_${sufijo}`,
    nombre: 'Fonendoscopio',
    equipoPequeno: true,
    pruebas: [
      { id: 'olivas', label: 'Olivas y membrana', tipo: 'estado' },
    ]
  },
  {
    id: `vacuometro_${sufijo}`,
    nombre: 'Vacuómetro',
    equipoPequeno: true,
    pruebas: [
      { id: 'vacio', label: 'Vacío', tipo: 'estado' },
    ]
  },
  {
    id: `laringoscopio_${sufijo}`,
    nombre: 'Laringoscopio',
    equipoPequeno: true,
    pruebas: [
      { id: 'intensidad_laring', label: 'Intensidad lumínica', tipo: 'estado' },
    ]
  },
]

const equiposFlujometrosVacuometros = (sufijo) => [
  {
    id: `vacuometros_${sufijo}`,
    nombre: 'Vacuómetros',
    sinPlaca: true,
    pruebas: [
      { id: 'funcionales_vac', label: 'N° vacuómetros funcionales', tipo: 'numero' },
      { id: 'escala', label: 'Escala e indicador', tipo: 'estado' },
    ]
  },
  {
    id: `flujometros_${sufijo}`,
    nombre: 'Flujómetros',
    sinPlaca: true,
    pruebas: [
      { id: 'funcionales_flu', label: 'N° flujómetros funcionales', tipo: 'numero' },
      { id: 'escala_flu', label: 'Escala e indicador', tipo: 'estado' },
    ]
  },
]

export const areasRonda = [
  {
    id: 'quirofano1',
    nombre: 'Quirófano 1',
    equipos: equiposQuirofano('q1')
  },
  {
    id: 'quirofano2',
    nombre: 'Quirófano 2',
    equipos: equiposQuirofano('q2')
  },
  {
    id: 'quirofano3',
    nombre: 'Quirófano 3',
    equipos: equiposQuirofano('q3')
  },
  {
    id: 'quirofano4',
    nombre: 'Quirófano 4',
    equipos: equiposQuirofano('q4')
  },
  {
    id: 'preparacion',
    nombre: 'Preparación',
    equipos: [
      ...crearMonitores(4, 'prep'),
      {
        id: 'desfibrilador_prep',
        nombre: 'Desfibrilador',
        pruebas: [
          { id: 'pruebas_diarias_prep', label: 'Pruebas diarias', tipo: 'si_no', informativo: true },
          { id: 'palas_prep', label: 'Palas', tipo: 'estado' },
          { id: 'cables_ekg_desf_prep', label: 'Cables EKG', tipo: 'estado' },
          { id: 'cable_parches_prep', label: 'Cable parches', tipo: 'estado' },
        ]
      },
      {
        id: 'electrocardiógrafo_prep',
        nombre: 'Electrocardiógrafo',
        pruebas: [
          { id: 'calidad_impresion', label: 'Calidad de impresión', tipo: 'si_no' },
          { id: 'cables_ekg_ecg', label: 'Cables EKG', tipo: 'estado' },
          { id: 'electrodos', label: 'Electrodos limpios', tipo: 'estado', informativo: true },
        ]
      },
      ...equiposFlujometrosVacuometros('prep'),
    ]
  },
  {
    id: 'recuperacion',
    nombre: 'Recuperación',
    equipos: [
      ...crearMonitores(9, 'rec'),
      {
        id: 'desfibrilador_rec',
        nombre: 'Desfibrilador',
        pruebas: [
          { id: 'pruebas_diarias_rec', label: 'Pruebas diarias', tipo: 'si_no', informativo: true },
          { id: 'palas_rec', label: 'Palas', tipo: 'estado' },
          { id: 'cables_ekg_desf_rec', label: 'Cables EKG', tipo: 'estado' },
          { id: 'cable_parches_rec', label: 'Cable parches', tipo: 'estado' },
        ]
      },
      ...equiposFlujometrosVacuometros('rec'),
    ]
  },
  {
    id: 'cuarto_equipos',
    nombre: 'Cuarto de equipos',
    equipos: [
      {
        id: 'torre_lap1',
        nombre: 'Torre de laparoscopia Storz 1',
        sinPlaca: true,
        tieneUbicacion: true,
        pruebas: [
          { id: 'monitor_lap1', label: 'Monitor', tipo: 'estado' },
          { id: 'procesador_cam1', label: 'Procesador de cámara', tipo: 'estado' },
          { id: 'neumoinsuflador1', label: 'Neumoinsuflador', tipo: 'estado' },
          { id: 'fuente_luz1', label: 'Fuente de luz', tipo: 'estado' },
        ]
      },
      {
        id: 'torre_lap2',
        nombre: 'Torre de laparoscopia Storz 2',
        sinPlaca: true,
        tieneUbicacion: true,
        pruebas: [
          { id: 'monitor_lap2', label: 'Monitor', tipo: 'estado' },
          { id: 'procesador_cam2', label: 'Procesador de cámara', tipo: 'estado' },
          { id: 'neumoinsuflador2', label: 'Neumoinsuflador', tipo: 'estado' },
          { id: 'fuente_luz2', label: 'Fuente de luz', tipo: 'estado' },
        ]
      },
      {
        id: 'torre_lap_stryker',
        nombre: 'Torre de laparoscopia Stryker',
        sinPlaca: true,
        tieneUbicacion: true,
        pruebas: [
          { id: 'monitor_strk', label: 'Monitor', tipo: 'estado' },
          { id: 'procesador_cam_strk', label: 'Procesador de cámara', tipo: 'estado' },
          { id: 'neumoinsuflador_strk', label: 'Neumoinsuflador', tipo: 'estado' },
          { id: 'fuente_luz_strk', label: 'Fuente de luz', tipo: 'estado' },
        ]
      },
      {
        id: 'arco_c1',
        nombre: 'Arco en C Phillips',
        sinPlaca: true,
        tieneUbicacion: true,
        pruebas: [
          { id: 'conexion_arco1', label: 'Correcta conexión', tipo: 'si_no', informativo: true },
        ]
      },
      {
        id: 'arco_c2',
        nombre: 'Arco en C General Electric',
        sinPlaca: true,
        tieneUbicacion: true,
        pruebas: [
          { id: 'conexion_arco2', label: 'Correcta conexión', tipo: 'si_no', informativo: true },
        ]
      },
      {
        id: 'terapias_vac',
        nombre: 'Terapias VAC',
        sinPlaca: true,
        sinRevision: true,
        pruebas: [
          { id: 'cantidad_total', label: 'Cantidad total de equipos', tipo: 'numero' },
          { id: 'cantidad_cargador', label: 'Cantidad con cargador', tipo: 'numero' },
        ]
      },
    ]
  }
]