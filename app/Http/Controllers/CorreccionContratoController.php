<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CorreccionContratoController extends Controller
{
    // Nombre de la conexión a la base de datos externa
    protected $conexionExterna = 'contratacion_externa';

    // Campos permitidos para editar (sin relaciones)
    protected $camposPermitidosContrato = [
        'orden_servicio',
        'objeto',
        'desc_general_act',
        'tiempo_ejecucion',
        'fecha_inicio',
        'canales',
        'interventor',
        'administrador',
        'dotacion',
        'alimentacion',
        'transporte',
        'horario',
        'requisitos',
        'forma_fecha',
        'observaciones',
        'obs_proceso',
        'status',
        'moderation_status'
    ];

    protected $camposPermitidosNecesidad = [
        'nombre',
        'tipo_unidad',
        'total',
        'empresa',
        'local',
        'descripcion',
        'vacante',
        'tipo_requerimiento',
        'tipo_salario',
        'tipo_contrato',
        'pruebas',
        'examenes',
        'status',
        'moderation_status'
    ];

    protected $camposPermitidosResultado = [
        'r_empresa',
        'r_nit',
        'r_municipio',
        'r_observaciones',
        'r_codigo_vacante',
        'r_codigo_certi_resi',
        'r_prestador_spe',
        'r_postulados'
    ];

    /**
     * Mostrar el formulario principal
     */
    public function index()
    {
        return view('correcciones.index');
    }

    /**
     * Buscar contrato por ID
     */
    public function buscarContrato(Request $request)
    {
        $request->validate([
            'contrato_id' => 'required|integer'
        ]);

        try {
            $contrato = DB::connection($this->conexionExterna)
                ->table('ordenes')
                ->where('id', $request->contrato_id)
                ->first();

            if (!$contrato) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contrato no encontrado'
                ], 404);
            }

            $necesidades = DB::connection($this->conexionExterna)
                ->table('necesidades')
                ->where('contrato_id', $contrato->contrato_id)
                ->orderBy('id', 'DESC')
                ->get();

            return response()->json([
                'success' => true,
                'contrato' => $contrato,
                'necesidades' => $necesidades
            ]);

        } catch (\Exception $e) {
            Log::error('Error al buscar contrato', [
                'error' => $e->getMessage(),
                'conexion' => $this->conexionExterna
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ], 500);
        }
    }   

    /**
     * Actualizar información del contrato (sin relaciones)
     */
    public function actualizarContrato(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'campo' => 'required|string',
            'valor' => 'nullable'
        ]);

        if ($request->campo === 'status') {
            if ($request->valor !== 'publicado' && $request->valor !== 'cerrado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Valor no permitido para el campo estado'
                ], 400);
            }
        }

        // Validar que el campo sea permitido
        if (!in_array($request->campo, $this->camposPermitidosContrato)) {
            return response()->json([
                'success' => false,
                'message' => 'Campo no permitido para modificación'
            ], 400);
        }

        try {
            DB::connection($this->conexionExterna)->beginTransaction();

            $actualizado = DB::connection($this->conexionExterna)
                ->table('ordenes')
                ->where('id', $request->id)
                ->update([
                    $request->campo => $request->valor,
                    'updated_at' => now()
                ]);

            if ($actualizado) {
                Log::info('Contrato actualizado', [
                    'orden_id' => $request->id,
                    'campo' => $request->campo,
                    'valor' => $request->valor,
                    'usuario' => auth()->user()->email ?? 'sistema',
                    'conexion' => $this->conexionExterna
                ]);

                DB::connection($this->conexionExterna)->commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Contrato actualizado correctamente'
                ]);
            }

            DB::connection($this->conexionExterna)->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el contrato'
            ], 400);

        } catch (\Exception $e) {
            DB::connection($this->conexionExterna)->rollBack();
            Log::error('Error al actualizar contrato', [
                'error' => $e->getMessage(),
                'conexion' => $this->conexionExterna
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener necesidades por contrato_id
     */
    public function obtenerNecesidades(Request $request)
    {
        $request->validate([
            'contrato_id' => 'required|integer'
        ]);

        $necesidades = DB::connection($this->conexionExterna)
            ->table('necesidades')
            ->where('contrato_id', $request->contrato_id)
            ->orderBy('id', 'DESC')
            ->get();

        return response()->json([
            'success' => true,
            'necesidades' => $necesidades
        ]);
    }

    /**
     * Actualizar una necesidad específica (sin relaciones)
     */
    public function actualizarNecesidad(Request $request)
    {
        $request->validate([
            'necesidad_id' => 'required|integer',
            'campo' => 'required|string',
            'valor' => 'nullable'
        ]);

        // Validar que el campo sea permitido
        if (!in_array($request->campo, $this->camposPermitidosNecesidad)) {
            return response()->json([
                'success' => false,
                'message' => 'Campo no permitido para modificación'
            ], 400);
        }

        try {
            DB::connection($this->conexionExterna)->beginTransaction();

            $actualizado = DB::connection($this->conexionExterna)
                ->table('necesidades')
                ->where('id', $request->necesidad_id)
                ->update([
                    $request->campo => $request->valor,
                    'updated_at' => now()
                ]);

            if ($actualizado) {
                Log::info('Necesidad actualizada', [
                    'necesidad_id' => $request->necesidad_id,
                    'campo' => $request->campo,
                    'valor' => $request->valor,
                    'usuario' => auth()->user()->email ?? 'sistema',
                    'conexion' => $this->conexionExterna
                ]);

                DB::connection($this->conexionExterna)->commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Necesidad actualizada correctamente'
                ]);
            }

            DB::connection($this->conexionExterna)->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar la necesidad'
            ], 400);

        } catch (\Exception $e) {
            DB::connection($this->conexionExterna)->rollBack();
            Log::error('Error al actualizar necesidad', [
                'error' => $e->getMessage(),
                'conexion' => $this->conexionExterna
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener resultados de una necesidad
     */
    public function obtenerResultados(Request $request)
    {
        $request->validate([
            'necesidad_id' => 'required|integer'
        ]);

        $resultados = DB::connection($this->conexionExterna)
            ->table('necesidades_resultados')
            ->where('necesidad_id', $request->necesidad_id)
            ->get();

        return response()->json([
            'success' => true,
            'resultados' => $resultados
        ]);
    }

    /**
     * Actualizar resultado de necesidad (sin relaciones)
     */
    public function actualizarResultado(Request $request)
    {
        $request->validate([
            'resultado_id' => 'required|integer',
            'campo' => 'required|string',
            'valor' => 'nullable'
        ]);

        // Validar que el campo sea permitido
        if (!in_array($request->campo, $this->camposPermitidosResultado)) {
            return response()->json([
                'success' => false,
                'message' => 'Campo no permitido para modificación'
            ], 400);
        }

        try {
            DB::connection($this->conexionExterna)->beginTransaction();

            $actualizado = DB::connection($this->conexionExterna)
                ->table('necesidades_resultados')
                ->where('id', $request->resultado_id)
                ->update([
                    $request->campo => $request->valor,
                    'updated_at' => now()
                ]);

            if ($actualizado) {
                Log::info('Resultado actualizado', [
                    'resultado_id' => $request->resultado_id,
                    'campo' => $request->campo,
                    'valor' => $request->valor,
                    'usuario' => auth()->user()->email ?? 'sistema',
                    'conexion' => $this->conexionExterna
                ]);

                DB::connection($this->conexionExterna)->commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Resultado actualizado correctamente'
                ]);
            }

            DB::connection($this->conexionExterna)->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el resultado'
            ], 400);

        } catch (\Exception $e) {
            DB::connection($this->conexionExterna)->rollBack();
            Log::error('Error al actualizar resultado', [
                'error' => $e->getMessage(),
                'conexion' => $this->conexionExterna
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Probar la conexión a la base de datos externa
     */
    public function probarConexion()
    {
        try {
            DB::connection($this->conexionExterna)->getPdo();

            $tablas = DB::connection($this->conexionExterna)
                ->select('SHOW TABLES');

            return response()->json([
                'success' => true,
                'message' => 'Conexión exitosa a la base de datos externa',
                'tablas_encontradas' => count($tablas)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ], 500);
        }
    }
}
