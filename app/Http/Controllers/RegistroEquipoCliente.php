<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\TipoEquipo;
use App\Models\Clientes;
use App\Models\Marca;
use App\Models\Equipo;
use App\Models\Recibo;
use App\Models\Estado;
use App\Models\ReciboController;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log; // Asegúrate de tener este import
use PDF;

class RegistroEquipoCliente extends Controller
{
    public function recepcion(Request $request)
    {
        $ultimoReciboId = null;
        
        try {
            DB::transaction(function () use ($request, &$ultimoReciboId) {
                
                // Verificar cliente_id en lugar de nombre_cliente
                if (!$request->has('cliente_id') || empty($request->cliente_id)) {
                    throw new \Exception('El ID del cliente es requerido.');
                }

                if (!$request->has('tipo_equipo') || empty($request->tipo_equipo)) {
                    throw new \Exception('Debe agregar al menos un equipo.');
                }

                // Obtener el ID del cliente
                $cliente_id = $request->input('cliente_id');
                $cliente = Clientes::find($cliente_id);
                if (!$cliente) {
                    throw new \Exception('Cliente no encontrado en la base de datos.');
                }

                // Crear un nuevo objeto Recibo
                $recibo = new Recibo();
                $recibo->id_estado = 1;
                $recibo->save();
                $ultimoReciboId = $recibo->id;

                // Iterar sobre los datos del formulario y guardar cada equipo
                foreach ($request->tipo_equipo as $key => $value) {
                    $marcaId = $request->marca[$key];

                    // Manejo de nueva marca
                    if ($marcaId === 'nueva_marca') {
                        $nuevaMarcaNombre = $request->nueva_marca[$key] ?? null;
                        
                        if (empty($nuevaMarcaNombre)) {
                            throw new \Exception('Debe proporcionar un nombre para la nueva marca.');
                        }

                        // Verificar si la nueva marca ya existe
                        $marcaExistente = Marca::where('marca', $nuevaMarcaNombre)->first();

                        if ($marcaExistente) {
                            $marcaId = $marcaExistente->id;
                        } else {
                            // Crear nueva marca
                            $nuevaMarca = new Marca();
                            $nuevaMarca->marca = $nuevaMarcaNombre;
                            $nuevaMarca->save();
                            $marcaId = $nuevaMarca->id;
                        }
                    }

                    // Validaciones adicionales
                    if (empty($marcaId)) {
                        throw new \Exception('Debe seleccionar o proporcionar una marca válida.');
                    }

                    if (empty($request->falla[$key])) {
                        throw new \Exception('La descripción de la falla es requerida para todos los equipos.');
                    }

                    // Crear el equipo
                    $equipo = new TipoEquipo();
                    $equipo->id_cliente = $cliente_id;
                    $equipo->id_equipo = $request->tipo_equipo[$key];
                    $equipo->id_marca = $marcaId;
                    $equipo->modelo = $request->modelo[$key] ?? null;
                    $equipo->ns = $request->ns[$key] ?? null;
                    $equipo->falla = $request->falla[$key];
                    $equipo->accesorio = $request->accesorios[$key] ?? null;
                    $equipo->usuario = Auth::user()->nombre;
                    $equipo->fecha = now()->toDateString();
                    $equipo->hora = now()->toTimeString();
                    $equipo->id_recibo = $recibo->id;
                    $equipo->save();
                }

                // Cargar la relación para el PDF
                $recibo->load('tipoEquipo');
                $cantidadTiposEquipo = $recibo->tipoEquipo->count();

                // Generar PDF del recibo
                $pdf = PDF::loadView('recibos.pdf', [
                    'recibo' => $recibo,
                    'cantidadTiposEquipo' => $cantidadTiposEquipo
                ])->setPaper([0, 0, 612.00, 792.00], 'portrait');
                    
                // Guardar PDF en BLOB
                $recibo->pdf_blob = $pdf->output();
                $recibo->save();
            });

            // Respuesta para AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Equipos registrados con éxito',
                    'redirect_url' => route('pdfImprimir.pdfImprimir', ['id' => $ultimoReciboId])
                ]);
            }

            // Redirección normal
            return redirect()->route('pdfImprimir.pdfImprimir', ['id' => $ultimoReciboId]);

        } catch (\Exception $e) {
            // Respuesta JSON incluso en errores
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al registrar el equipo: ' . $e->getMessage()
                ], 422);
            }

            return back()
                ->withErrors(['error_general' => 'Error al registrar el equipo: ' . $e->getMessage()])
                ->withInput();
        }
    }
    public function estado($id)
    {
        // Buscar el TipoEquipo por su ID
        $recibo = Recibo::find($id);
    
        // Verificar si se encontró el TipoEquipo
        if ($recibo) {
            // Actualizar el campo id_estado a 2
            $recibo->id_estado = 2;

            if ($recibo->id_estado == 2) {
                // Establecer la fecha actual en el campo fechaReparacion
               // Obtiene la fecha actual en formato 'YYYY-MM-DD'
                $recibo->fechaReparacion = Carbon::now()->toDateString(); 
            }
            
            // Guardar los cambios en la base de datos
            $recibo->save();
            
            // Devolver el ID del TipoEquipo actualizado junto con un mensaje de éxito
            return response()->json(['message' => 'Reparación Completada'], 200);
           
        } else {
            // Si no se encuentra el TipoEquipo, devolver un mensaje de error con información adicional
            return response()->json(['error' => 'No se encontró el TipoEquipo con el ID proporcionado: ' . $id], 404);
        }
        
    }
    //cancelar cancelado
    public function cancelarCancelado($id)
    {
        // Buscar el Recibo por su ID
        $recibo = Recibo::find($id);

        // Verificar si se encontró el Recibo
        if ($recibo) {
            // Actualizar el campo id_estado a 4 (Estado de cancelación, según lo entendido)
            $recibo->id_estado = 1;
            $recibo->save();

            // Devolver una respuesta JSON con un mensaje de éxito
            return response()->json(['message' => 'Estado del recibo actualizado correctamente'], 200);
        } else {
            // Si no se encuentra el Recibo, devolver una respuesta JSON con un mensaje de error
            return response()->json(['error' => 'No se encontró el recibo con el ID proporcionado: ' . $id], 404);
        }
    }

    //cancelar recibo
    public function cancelado($id)
    {
        // Buscar el Recibo por su ID
        $recibo = Recibo::find($id);

        // Verificar si se encontró el Recibo
        if ($recibo) {
            // Actualizar el campo id_estado a 4 (Estado de cancelación, según lo entendido)
            $recibo->id_estado = 4;
            $recibo->save();

            // Devolver una respuesta JSON con un mensaje de éxito
            return response()->json(['message' => 'El Recibo Ha Sido Cancelado'], 200);
        } else {
            // Si no se encuentra el Recibo, devolver una respuesta JSON con un mensaje de error
            return response()->json(['error' => 'No se encontró el recibo con el ID proporcionado: ' . $id], 404);
        }
    }
   

}
