<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ronda;
use App\Models\RondaArea;
use App\Models\RondaEquipo;
use App\Models\RondaPrueba;
use Illuminate\Support\Facades\DB;

class RondaController extends Controller
{
    public function store(Request $request)
    {
        $rondaExistente = Ronda::whereDate('fecha', $request->fecha)->first();
        if ($rondaExistente) {
            $rondaExistente->delete();
        }

        DB::beginTransaction();

        // Validar placas duplicadas (ignorar equipos no encontrados)
        $placasVistas = [];
        foreach ($request->areas as $areaId => $areaData) {
            if ($areaData['noRealizada'] ?? false) continue;
            foreach ($areaData['equipos'] as $equipoId => $equipoData) {
                // Si el equipo está marcado como no encontrado, no validar su placa
                if ($equipoData['noEncontrado'] ?? false) continue;

                $placa = $equipoData['placa'] ?? null;
                $placa2 = $equipoData['placa2'] ?? null;

                if ($placa) {
                    if (isset($placasVistas[$placa])) {
                        return response()->json([
                            'message' => "La placa {$placa} está registrada en más de un quirófano. Cada equipo solo puede estar en un lugar."
                        ], 422);
                    }
                    $placasVistas[$placa] = true;
                }
                if ($placa2) {
                    if (isset($placasVistas[$placa2])) {
                        return response()->json([
                            'message' => "La placa {$placa2} está registrada en más de un quirófano. Cada equipo solo puede estar en un lugar."
                        ], 422);
                    }
                    $placasVistas[$placa2] = true;
                }
            }
        }

        try {
            $ronda = Ronda::create([
                'fecha'                    => $request->fecha,
                'estado'                   => $request->estado,
                'firma_responsable_nombre' => $request->firmas['responsable']['nombre'],
                'firma_responsable_imagen' => $request->firmas['responsable']['imagen'],
                'firma_jefe_nombre'        => $request->firmas['jefe']['nombre'],
                'firma_jefe_imagen'        => $request->firmas['jefe']['imagen'],
                'firma_lider_nombre'       => $request->firmas['lider']['nombre'] ?? null,
                'firma_lider_imagen'       => $request->firmas['lider']['imagen'] ?? null,
                'fotos'                    => $request->fotos ?? [],
            ]);

            foreach ($request->areas as $areaId => $areaData) {
                $rondaArea = RondaArea::create([
                    'ronda_id'     => $ronda->id,
                    'area_id'      => $areaId,
                    'area_nombre'  => $areaData['nombre'],
                    'no_realizada' => $areaData['noRealizada'] ?? false,
                ]);

                if ($areaData['noRealizada'] ?? false) continue;

                foreach ($areaData['equipos'] as $equipoId => $equipoData) {
                    $rondaEquipo = RondaEquipo::create([
                        'ronda_area_id'   => $rondaArea->id,
                        'equipo_id'       => $equipoId,
                        'equipo_nombre'   => $equipoData['nombre'],
                        'placa'           => $equipoData['placa'] ?? null,
                        'placa2'          => $equipoData['placa2'] ?? null,
                        'no_encontrado'   => $equipoData['noEncontrado'] ?? false,
                        'revision_fisica' => $equipoData['revisionFisica'] ?? null,
                        'apto_para_uso'   => $equipoData['aptoParaUso'] ?? null,
                        'observaciones'   => $equipoData['observaciones'] ?? null,
                        'ubicacion'       => $equipoData['ubicacion'] ?? null,
                    ]);

                    foreach ($equipoData['pruebas'] as $pruebaId => $prueba) {
                        RondaPrueba::create([
                            'ronda_equipo_id' => $rondaEquipo->id,
                            'prueba_id'       => $pruebaId,
                            'prueba_label'    => is_array($prueba) ? ($prueba['label'] ?? $pruebaId) : $pruebaId,
                            'valor'           => is_array($prueba) ? ($prueba['valor'] ?? null) : $prueba,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['message' => 'Ronda guardada correctamente', 'id' => $ronda->id], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al guardar la ronda', 'error' => $e->getMessage()], 500);
        }
    }

    public function ultimaRonda()
    {
        $ronda = Ronda::with(['areas.equipos.pruebas'])->latest()->first();
        if (!$ronda) return response()->json(null, 404);
        return response()->json($ronda);
    }

    public function buscarPorFecha($fecha)
    {
        $ronda = Ronda::with(['areas.equipos.pruebas'])
            ->whereDate('fecha', $fecha)->latest()->first();
        if (!$ronda) return response()->json(null, 404);
        return response()->json($ronda);
    }

    public function rondasAnteriores($fecha)
    {
        $rondas = Ronda::with(['areas.equipos.pruebas'])
            ->whereDate('fecha', '<', $fecha)
            ->orderBy('fecha', 'desc')
            ->limit(6)
            ->get();
        return response()->json($rondas);
    }

    public function agregarFirmaLider(Request $request, $id)
    {
        $ronda = Ronda::findOrFail($id);
        if ($ronda->firma_lider_nombre && $ronda->firma_lider_imagen) {
            return response()->json(['message' => 'La firma del líder ya fue registrada.'], 403);
        }
        $ronda->update([
            'firma_lider_nombre' => $request->nombre,
            'firma_lider_imagen' => $request->imagen,
            'estado'             => 'completado'
        ]);
        return response()->json(['message' => 'Firma del líder registrada correctamente.']);
    }

    public function dashboard(Request $request)
    {
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $query = Ronda::with(['areas.equipos.pruebas'])->orderBy('fecha', 'asc');
        if ($desde) $query->whereDate('fecha', '>=', $desde);
        if ($hasta) $query->whereDate('fecha', '<=', $hasta);
        $rondas = $query->get();

        $pruebasInformativas = [
            'calibracion_o2', 'conexion_toma',
            'pruebas_diarias_prep', 'pruebas_diarias_rec',
            'electrodos', 'conexion_arco1', 'conexion_arco2'
        ];

        $totalEquipos = 0;
        $fueraServicio = 0;
        $conFallas = 0;
        $repuestoPendiente = 0;
        $accesoriosReemplazados = 0;
        $historialFugas = [];
        $cambioAccesorios = [];
        $historialFallasPorEquipo = [];
        $ubicacionPorEquipo = [];
        $movimientos = [];

        foreach ($rondas as $ronda) {
            foreach ($ronda->areas as $area) {
                if ($area->no_realizada) continue;

                foreach ($area->equipos as $equipo) {
                    // Excluir equipos que no participan en análisis de fallas
                    $equiposExcluidos = ['Terapias VAC', 'Vacuómetros', 'Flujómetros'];
                    $areaExcluida = in_array($area->area_nombre, ['Preparación', 'Recuperación']);

                    if (in_array($equipo->equipo_nombre, $equiposExcluidos) && $areaExcluida) continue;
                    if ($equipo->equipo_nombre === 'Terapias VAC') continue;

                    $totalEquipos++;
                    $tieneFalla = false;

                    // Identificador: placa si tiene, nombre si no
                    $clave = $equipo->placa ?: $equipo->equipo_nombre;

                    // Guardar historial de ubicaciones
                    if ($equipo->ubicacion || $area->area_nombre) {
                        if (!isset($ubicacionPorEquipo[$clave])) {
                            $ubicacionPorEquipo[$clave] = [
                                'nombre'    => $equipo->equipo_nombre,
                                'placa'     => $equipo->placa,
                                'historial' => []
                            ];
                        }
                        $ubicacionPorEquipo[$clave]['historial'][] = [
                            'fecha'     => $ronda->fecha,
                            'ubicacion' => $equipo->ubicacion ?: $area->area_nombre,
                        ];
                    }

                    // Equipos no encontrados
                    if ($equipo->no_encontrado) continue;

                    if ($equipo->apto_para_uso === 'no') {
                        $fueraServicio++;
                        $tieneFalla = true;
                    }

                    foreach ($equipo->pruebas as $prueba) {
                        if (in_array($prueba->prueba_id, $pruebasInformativas)) continue;

                        if (in_array($prueba->prueba_id, ['modos_corte', 'modos_coagulacion'])) {
                            if ($prueba->valor !== null && (int)$prueba->valor < 3) {
                                $conFallas++;
                                $tieneFalla = true;
                            }
                            continue;
                        }

                        if (in_array($prueba->valor, ['malo', 'mala'])) {
                            $conFallas++;
                            $tieneFalla = true;
                        }

                        if ($prueba->valor === 'reparacion') {
                            $repuestoPendiente++;
                            $tieneFalla = true;
                        }

                        if ($prueba->valor === 'seguimiento') {
                            $tieneFalla = true;
                        }

                        if ($prueba->valor === 'se_repone') {
                            $accesoriosReemplazados++;
                        }

                        if ($prueba->prueba_id === 'fuga_auto' && isset($prueba->valor)) {
                            $historialFugas[] = [
                                'fecha' => $ronda->fecha,
                                'placa' => $equipo->placa,
                                'valor' => (float) $prueba->valor,
                                'area'  => $area->area_nombre,
                            ];
                        }

                        if (str_starts_with($prueba->prueba_id, 'nibp') ||
                            str_starts_with($prueba->prueba_id, 'spo2') ||
                            str_starts_with($prueba->prueba_id, 'cables_ekg') ||
                            str_starts_with($prueba->prueba_id, 'sensor_temp')) {
                            if ($prueba->valor === 'se_repone') {
                                $cambioAccesorios[] = [
                                    'fecha'     => $ronda->fecha,
                                    'placa'     => $equipo->placa,
                                    'accesorio' => $prueba->prueba_label,
                                ];
                            }
                        }
                    }

                    if (in_array($equipo->revision_fisica, ['malo', 'mala', 'reparacion', 'seguimiento'])) {
                        $tieneFalla = true;
                    }

                    // Registrar fallas usando clave unificada (placa o nombre)
                    if (!isset($historialFallasPorEquipo[$clave])) {
                        $historialFallasPorEquipo[$clave] = [
                            'nombre'       => $equipo->equipo_nombre,
                            'placa'        => $equipo->placa ?? $equipo->equipo_nombre,
                            'fechasFallas' => [],
                        ];
                    }
                    if ($tieneFalla) {
                        if (!in_array($ronda->fecha, $historialFallasPorEquipo[$clave]['fechasFallas'])) {
                            $historialFallasPorEquipo[$clave]['fechasFallas'][] = $ronda->fecha;
                        }
                    }
                }
            }
        }

        // Detectar movimientos comparando historial de ubicaciones por placa
        foreach ($ubicacionPorEquipo as $placa => $datos) {
            $historial = $datos['historial'];
            for ($i = 1; $i < count($historial); $i++) {
                if ($historial[$i]['ubicacion'] !== $historial[$i-1]['ubicacion']) {
                    $movimientos[] = [
                        'equipo'       => $datos['nombre'],
                        'placa'        => $placa,
                        'fecha'        => $historial[$i]['fecha'],
                        'areaAnterior' => $historial[$i-1]['ubicacion'],
                        'areaActual'   => $historial[$i]['ubicacion'],
                        'tipo'         => 'movimiento',
                    ];
                }
            }
        }

        // Calcular predicción de próximo fallo
        $equiposEnRiesgo = [];

        foreach ($historialFallasPorEquipo as $equipo) {
            $fechas = $equipo['fechasFallas'];
            if (count($fechas) === 0) continue;

            $totalFallas = count($fechas);
            $ultimaFalla = end($fechas);
            $fechaReferencia = $hasta ? \Carbon\Carbon::parse($hasta) : now();
            $diasDesdeUltimaFalla = (int) \Carbon\Carbon::parse($ultimaFalla)->diffInDays($fechaReferencia);

            // Calcular frecuencia de fallas sobre el período total
            $primerFalla = $fechas[0];
            $diasPeriodo = max(1, (int) \Carbon\Carbon::parse($primerFalla)->diffInDays(\Carbon\Carbon::parse($ultimaFalla)));
            $frecuenciaPorDia = $totalFallas / $diasPeriodo;

            // Intervalo promedio ponderado (más peso a fallas recientes)
            $intervaloPromedio = null;
            if ($totalFallas >= 2) {
                $intervalos = [];
                $pesos = [];
                for ($i = 1; $i < $totalFallas; $i++) {
                    $diff = (int) \Carbon\Carbon::parse($fechas[$i])->diffInDays(\Carbon\Carbon::parse($fechas[$i-1]));
                    $intervalos[] = max(1, $diff);
                    $pesos[] = $i;
                }
                $sumaPesos = array_sum($pesos);
                $suma = 0;
                foreach ($intervalos as $idx => $intervalo) {
                    $suma += $intervalo * ($pesos[$idx] / $sumaPesos);
                }
                $intervaloPromedio = (int) round($suma);
            }

            // Nivel basado en cantidad de fallas
            $nivel = $totalFallas <= 2 ? 'seguimiento'
                : ($totalFallas <= 5 ? 'alto' : 'critico');

            $equiposEnRiesgo[] = [
                'nombre'              => $equipo['nombre'],
                'placa'               => $equipo['placa'],
                'totalFallas'         => $totalFallas,
                'intervaloPromedio'   => $intervaloPromedio,
                'ultimaFalla'         => $ultimaFalla,
                'diasDesdeUltimaFalla'=> $diasDesdeUltimaFalla,
                'frecuenciaPorDia'    => round($frecuenciaPorDia, 2),
                'nivel'               => $nivel,
            ];
        }

        usort($equiposEnRiesgo, function($a, $b) {
            // Primero por nivel (critico > alto > seguimiento)
            $nivelOrden = ['critico' => 0, 'alto' => 1, 'seguimiento' => 2];
            $nivelA = $nivelOrden[$a['nivel']] ?? 2;
            $nivelB = $nivelOrden[$b['nivel']] ?? 2;
            if ($nivelA !== $nivelB) return $nivelA - $nivelB;
            // Luego por total de fallas descendente
            return $b['totalFallas'] - $a['totalFallas'];
        });

        $funcionamiento = max(0, $totalEquipos - $fueraServicio - $conFallas - $repuestoPendiente);


        // Deduplicar: mostrar solo el último movimiento por placa
        $movimientosPorPlaca = [];
        foreach (array_reverse($movimientos) as $mov) {
            $key = $mov['placa'] ?? ($mov['equipo'] . $mov['areaActual']);
            if (!isset($movimientosPorPlaca[$key])) {
                $movimientosPorPlaca[$key] = $mov;
            }
        }

        $funcionamiento = max(0, $totalEquipos - $fueraServicio - $conFallas - $repuestoPendiente);

        return response()->json([
            'resumen' => [
                'totalEquipos'           => $totalEquipos,
                'fueraServicio'          => $fueraServicio,
                'conFallas'              => $conFallas,
                'repuestoPendiente'      => $repuestoPendiente,
                'accesoriosReemplazados' => $accesoriosReemplazados,
                'funcionamiento'         => $funcionamiento,
            ],
            'historialFugas'     => $historialFugas,
            'cambioAccesorios'   => $cambioAccesorios,
            'ultimosMovimientos' => array_slice(array_values($movimientosPorPlaca), 0, 15),
            'equiposEnRiesgo'    => array_slice($equiposEnRiesgo, 0, 20),
        ]);
    }
}