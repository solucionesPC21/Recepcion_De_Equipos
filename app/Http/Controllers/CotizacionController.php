<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\DB; // NUEVO: Agregar esta línea
use Illuminate\Support\Facades\Log;
use App\Models\Cotizacion;


class CotizacionController extends Controller
{
    public function index()
    {
        //vista principal cotizacion
        return view('cotizacion.cotizacion');
    }

 //generar y guardar PDF
public function generarYGuardarPDF(Request $request)
{
    try {
        

        $datos = $request->all();

        // ✅ DEBUG: Verificar datos de descuento
       

        // Validación básica
        $validated = $request->validate([
            'cliente' => 'required|string',
            'tipo_cliente' => 'required|string|in:persona_fisica,publico_general,persona_moral',
            'valido_hasta' => 'required|date',
            'productos' => 'required|array|min:1',
        ]);

        $subtotalDescuento = $request->input('subtotalDescuento', 0);
        // Obtener campos principales
        $descuentoPorcentaje = $request->input('descuentoPorcentaje', 0);
        $totalDescuentoAcumulado = $datos['totalDescuentoAcumulado'] ?? 0;
        $subtotalSinIvaDescuento = $datos['subtotalSinIva'] ?? 0;

        // ✅ CALCULAR VALORES PARA PDF INTERNO
        $subtotalParaPdfInterno = 0;
        $ivaParaPdfInterno = 0;

        foreach ($datos['productos'] as $producto) {
            // ✅ CORRECCIÓN: Usar precioSinIvaFinalConDescuento (precio con descuento aplicado)
            $subtotalParaPdfInterno += $producto['precioSinIvaFinalConDescuento'] * $producto['cantidad'];
        }
        $ivaParaPdfInterno = $subtotalParaPdfInterno * 0.08;
        $subtotalPdfClienteF = $ivaParaPdfInterno + $subtotalParaPdfInterno + $totalDescuentoAcumulado;

        // ISR: calcular del subtotal (solo para persona moral)
        $isrParaPdfInterno = $datos['tipo_cliente'] === 'persona_moral' ? $subtotalParaPdfInterno * 0.0125 : 0;
        $ivaFinalTotal = $datos['ivaFinalTotal'] ?? 0;
        
        // Total: Subtotal + IVA - ISR
        $totalParaPdfInterno = $subtotalParaPdfInterno + $ivaParaPdfInterno - $isrParaPdfInterno;

        // ✅ CALCULAR VALORES PARA PDF CLIENTE
        $subtotalParaPdfCliente = 0;
        foreach ($datos['productos'] as $producto) {
            // Para el cliente: mostrar precio final con IVA incluido
            $precioUnitarioCliente = $producto['precioFinalSinDescuento'] ?? $producto['precioFinal'];
            $subtotalParaPdfCliente += $precioUnitarioCliente * $producto['cantidad'];
        }

        $subtotalSinIvaClienteMoral = $subtotalPdfClienteF / 1.08;  // Subtotal sin IVA
        
        // ✅ PREPARAR DATOS PARA LA VISTA
        $cotizacion = [
            'cliente' => $datos['cliente'],
            'tipo_cliente' => $datos['tipo_cliente'],
            'valido_hasta' => date('d/m/Y', strtotime($datos['valido_hasta'])),
            'fecha' => date('d/m/Y'),
            'direccion' => $datos['direccion'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'productos' => $datos['productos'],
            
            // ✅ CAMPOS PARA PDF INTERNO
            'subtotalParaPdfInterno' => $subtotalParaPdfInterno,
            'ivaParaPdfInterno' => $ivaParaPdfInterno,
            'isrParaPdfInterno' => $isrParaPdfInterno,
            'totalParaPdfInterno' => $totalParaPdfInterno,
            
            // ✅ CAMPOS PARA PDF CLIENTE
            'subtotalParaPdf' => $subtotalParaPdfCliente,
            'subtotalPdfClienteF' => $subtotalPdfClienteF,
            'subtotalSinIvaClienteMoral' => $subtotalSinIvaClienteMoral,
            'subtotalSinIvaDescuento' => $subtotalSinIvaDescuento,
            
            // Campos existentes del JavaScript
            'subtotalProductos' => $datos['subtotalProductos'] ?? 0,
            'ivaFinalTotal' => $datos['ivaFinalTotal'] ?? 0,
            'totalTransporte' => $datos['totalTransporte'] ?? 0,
            'descuentoPorcentaje' => $descuentoPorcentaje,
            'totalDescuentoAcumulado' => $totalDescuentoAcumulado,
            'isrTotal' => $datos['isrTotal'] ?? 0,
            'total' => $datos['total'] ?? 0
        ];

    
        
        // ✅ GENERAR PDF PARA EL CLIENTE
        $pdfCliente = PDF::loadView('cotizacion.cotizacionPdfCliente', compact('cotizacion'))
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $pdfClienteBlob = $pdfCliente->output();

        // ✅ GENERAR PDF INTERNO (detallado)
        $pdfInterno = PDF::loadView('cotizacion.cotizacionPdf', compact('cotizacion'))
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $pdfInternoBlob = $pdfInterno->output();

      
        
        // ✅ GUARDAR AMBOS PDFs Y DATOS JSON EN LA BASE DE DATOS
        $cotizacionGuardada = Cotizacion::create([
            'nombre_cliente' => $datos['cliente'],
            'pdf_cliente' => $pdfClienteBlob,     // ✅ GUARDAR PDF CLIENTE
            'pdf_interno' => $pdfInternoBlob,     // ✅ GUARDAR PDF INTERNO
            'datos_json' => json_encode($datos),  // ✅ NUEVO: GUARDAR TODOS LOS DATOS EN JSON
            'tipo_cliente' => $datos['tipo_cliente'],
            'total' => $datos['total'] ?? 0,
            'valido_hasta' => $datos['valido_hasta'],
            'direccion' => $datos['direccion'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'descuento_porcentaje' => $descuentoPorcentaje,
            'fecha_creacion' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

       

        // ✅ DEVOLVER EL PDF DEL CLIENTE PARA DESCARGA
        return response($pdfClienteBlob, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Cotizacion_' . str_replace(' ', '_', $datos['cliente']) . '.pdf"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);

    } catch (\Exception $e) {
        Log::error('=== ERROR GENERANDO PDF ===');
        Log::error('Error: ' . $e->getMessage());
        Log::error('File: ' . $e->getFile());
        Log::error('Line: ' . $e->getLine());
        Log::error('Trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'error' => 'Error: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
}
// **NUEVO MÉTODO: Para ver el PDF en el navegador**
public function verPDF($filename)
{
    try {
        $filePath = public_path('temp/' . $filename);
        
        if (!file_exists($filePath)) {
            abort(404, 'Archivo no encontrado');
        }

        $fileContent = file_get_contents($filePath);
        
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');

    } catch (\Exception $e) {
        Log::error('Error mostrando PDF: ' . $e->getMessage());
        abort(404, 'Error al cargar el PDF');
    }
}

public function historial()
{
     $cotizaciones = Cotizacion::orderBy('fecha_creacion', 'desc')->paginate(10);

    return view('cotizacionHistorial.cotizacionHistorial', compact('cotizaciones'));
}

public function ver($id, $tipo)
{
   $cotizacion = Cotizacion::find($id);
    
    if (!$cotizacion) {
        abort(404);
    }

    $pdfBlob = null;

    if ($tipo === 'cliente' && $cotizacion->pdf_cliente) {
        $pdfBlob = $cotizacion->pdf_cliente;
    } elseif ($tipo === 'interno' && $cotizacion->pdf_interno) {
        $pdfBlob = $cotizacion->pdf_interno;
    } else {
        abort(404);
    }

    return response($pdfBlob)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="cotizacion.pdf"');
}

public function eliminar($id)
{
    try {
        $cotizacion = Cotizacion::findOrFail($id);
        $cotizacion->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Cotización eliminada correctamente'
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Error eliminando cotización: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error al eliminar la cotización: ' . $e->getMessage()
        ], 500);
    }
}
//
/**
 * Obtener datos de cotización para editar (API)
 */
public function obtenerParaEditar($id)
{
    try {
        $cotizacion = Cotizacion::findOrFail($id);
        
        // Obtener datos JSON y decodificar
        $datos = $cotizacion->datos_json ? json_decode($cotizacion->datos_json, true) : [];
        
        // Agregar información adicional
        $datos['cotizacion_id'] = $cotizacion->id;
        $datos['fecha_creacion'] = $cotizacion->fecha_creacion;
        
        return response()->json([
            'success' => true,
            'data' => $datos
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Cotización no encontrada'
        ], 404);
    }
}
/**
 * Actualizar cotización existente
 */
/**
 * Actualizar cotización existente
 */
public function actualizarCotizacion(Request $request, $id)
{
    try {
     
        
        $cotizacionModel = Cotizacion::findOrFail($id);
        $datos = $request->all();

        // Validación
        $validated = $request->validate([
            'cliente' => 'required|string',
            'tipo_cliente' => 'required|string|in:persona_fisica,publico_general,persona_moral',
            'valido_hasta' => 'required|date',
            'productos' => 'required|array|min:1',
        ]);

        // ✅ 1. OBTENER DATOS DEL REQUEST
        $subtotalDescuento = $request->input('subtotalDescuento', 0);
        $descuentoPorcentaje = $request->input('descuentoPorcentaje', 0);
        $totalDescuentoAcumulado = $datos['totalDescuentoAcumulado'] ?? 0;
        $subtotalSinIvaDescuento = $datos['subtotalSinIva'] ?? 0;

        // ✅ 2. CALCULAR VALORES PARA PDF INTERNO (COPIAR DE generarYGuardarPDF)
        $subtotalParaPdfInterno = 0;
        $ivaParaPdfInterno = 0;

        // VERIFICAR QUE productos NO SEA NULL
        if (empty($datos['productos']) || !is_array($datos['productos'])) {
            throw new \Exception('No hay productos en la cotización');
        }

        foreach ($datos['productos'] as $producto) {
            $subtotalParaPdfInterno += $producto['precioSinIvaFinalConDescuento'] * $producto['cantidad'];
        }
        
        $ivaParaPdfInterno = $subtotalParaPdfInterno * 0.08;
        $subtotalPdfClienteF = $ivaParaPdfInterno + $subtotalParaPdfInterno + $totalDescuentoAcumulado;

        // ISR: calcular del subtotal (solo para persona moral)
        $isrParaPdfInterno = $datos['tipo_cliente'] === 'persona_moral' ? $subtotalParaPdfInterno * 0.0125 : 0;
        $ivaFinalTotal = $datos['ivaFinalTotal'] ?? 0;
        
        // Total: Subtotal + IVA - ISR
        $totalParaPdfInterno = $subtotalParaPdfInterno + $ivaParaPdfInterno - $isrParaPdfInterno;

        // ✅ 3. CALCULAR VALORES PARA PDF CLIENTE
        $subtotalParaPdfCliente = 0;
        foreach ($datos['productos'] as $producto) {
            $precioUnitarioCliente = $producto['precioFinalSinDescuento'] ?? $producto['precioFinal'];
            $subtotalParaPdfCliente += $precioUnitarioCliente * $producto['cantidad'];
        }

        $subtotalSinIvaClienteMoral = $subtotalPdfClienteF / 1.08;

        // ✅ 4. CONSTRUIR ARRAY $cotizacion PARA LAS VISTAS PDF (MUY IMPORTANTE)
        $cotizacion = [
            'cliente' => $datos['cliente'],
            'tipo_cliente' => $datos['tipo_cliente'],
            'valido_hasta' => date('d/m/Y', strtotime($datos['valido_hasta'])),
            'fecha' => date('d/m/Y'),
            'direccion' => $datos['direccion'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'productos' => $datos['productos'], // ✅ ESTE ES EL QUE FALTA
            
            // CAMPOS PARA PDF INTERNO
            'subtotalParaPdfInterno' => $subtotalParaPdfInterno,
            'ivaParaPdfInterno' => $ivaParaPdfInterno,
            'isrParaPdfInterno' => $isrParaPdfInterno,
            'totalParaPdfInterno' => $totalParaPdfInterno,
            
            // CAMPOS PARA PDF CLIENTE
            'subtotalParaPdf' => $subtotalParaPdfCliente,
            'subtotalPdfClienteF' => $subtotalPdfClienteF,
            'subtotalSinIvaClienteMoral' => $subtotalSinIvaClienteMoral,
            'subtotalSinIvaDescuento' => $subtotalSinIvaDescuento,
            
            // Campos del JavaScript
            'subtotalProductos' => $datos['subtotalProductos'] ?? 0,
            'ivaFinalTotal' => $datos['ivaFinalTotal'] ?? 0,
            'totalTransporte' => $datos['totalTransporte'] ?? 0,
            'descuentoPorcentaje' => $descuentoPorcentaje,
            'totalDescuentoAcumulado' => $totalDescuentoAcumulado,
            'isrTotal' => $datos['isrTotal'] ?? 0,
            'total' => $datos['total'] ?? 0
        ];

        // ✅ 5. GENERAR NUEVOS PDFs CON EL ARRAY CORRECTO
        $pdfCliente = PDF::loadView('cotizacion.cotizacionPdfCliente', compact('cotizacion'))
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $pdfClienteBlob = $pdfCliente->output();

        $pdfInterno = PDF::loadView('cotizacion.cotizacionPdf', compact('cotizacion'))
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $pdfInternoBlob = $pdfInterno->output();
        
        // ✅ 6. ACTUALIZAR LA COTIZACIÓN EN LA BD
        $cotizacionModel->update([
            'nombre_cliente' => $datos['cliente'],
            'pdf_cliente' => $pdfClienteBlob,
            'pdf_interno' => $pdfInternoBlob,
            'datos_json' => json_encode($datos),
            'tipo_cliente' => $datos['tipo_cliente'],
            'total' => $datos['total'] ?? 0,
            'valido_hasta' => $datos['valido_hasta'],
            'direccion' => $datos['direccion'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'descuento_porcentaje' => $descuentoPorcentaje ?? 0,
            'updated_at' => now(),
        ]);
        

        
        // ✅ 7. DEVOLVER EL PDF ACTUALIZADO
        return response($pdfClienteBlob, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Cotizacion_Actualizada_' . str_replace(' ', '_', $datos['cliente']) . '.pdf"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
        
    } catch (\Exception $e) {
        Log::error('Error actualizando cotización: ' . $e->getMessage());
        Log::error('Trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'error' => 'Error: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
}



}
