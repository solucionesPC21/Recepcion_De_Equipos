<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDF; 
use App\Models\NombreConcepto;
use Carbon\Carbon;
use App\Models\Concepto;
use App\Models\Ticket;
use App\Models\Gasto;
use App\Models\Abono;
use App\Models\VentaAbono;
use App\Models\VentaNotaAbono; // si aún no lo tienes
use App\Models\CierreNotaAbono;

class ReporteController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth'); // Requiere autenticación primero
        $this->middleware('admin')->only('generarReporte'); // Solo admin para generarReporte
    }


    public function generarReporte(Request $request)
    {
        // Validación de fechas
        $request->validate([
            'fechaInicio' => 'sometimes|date',
            'fechaFin' => 'sometimes|date|after_or_equal:fechaInicio'
        ]);
    
        // Usar la fecha actual si no se proporcionan fechas
        $fechaInicio = $request->input('fechaInicio') ?? now()->startOfDay()->toDateString();
        $fechaFin = $request->input('fechaFin') ?? now()->endOfDay()->toDateString();
        
        $esCorteDiario = $fechaInicio == now()->startOfDay()->toDateString() && 
                         $fechaFin == now()->endOfDay()->toDateString();
    
        //pago de diferencia de sistema de nota de abono
       $pagosDiferencia = CierreNotaAbono::whereBetween('created_at', [
        Carbon::parse($fechaInicio)->startOfDay(),
        Carbon::parse($fechaFin)->endOfDay()
        ])
        ->where(function ($q) {
            $q->where('monto_efectivo', '>', 0)
            ->orWhere('monto_transferencia', '>', 0);
        })
        ->get();
        $totalDiferenciaEfectivo = 0;
        $totalDiferenciaTransferencia = 0;

        foreach ($pagosDiferencia as $pago) {
            $totalDiferenciaEfectivo += $pago->monto_efectivo ?? 0;
            $totalDiferenciaTransferencia += $pago->monto_transferencia ?? 0;
        }


        // Obtener los tickets dentro del rango de fechas con relaciones necesarias
        $tickets = Ticket::with(['concepto.nombreConcepto', 'cliente', 'tipoPago'])
                        ->where('estado_id', 3)  // Filtro para solo tickets cancelados
                        ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                        ->get();
    
        // Obtener los gastos dentro del mismo rango de fechas
        $gastos = Gasto::whereBetween('fecha', [$fechaInicio, $fechaFin])
                      ->orderBy('fecha', 'desc')
                      ->get();

        // Obtener los abonos dentro del rango de fechas
         // Obtener los abonos dentro del rango de fechas
        // Obtener los abonos (pagos a créditos)
        // Asegúrate que estás obteniendo los abonos correctamente
        $abonos = Abono::with(['tipoPago', 'venta.cliente'])
            ->whereBetween('fecha_abono', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ])
            ->get();

        // Obtener las ventas a crédito creadas en el periodo
        $ventasCredito = VentaAbono::with(['cliente', 'abonos'])
                        ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])
                        ->orderBy('fecha_venta', 'desc')
                        ->get();
    
        // Verificar si hay datos para el rango seleccionado
        // Si no hay datos, devuelve un error JSON
        // Verificar si hay datos para el rango seleccionado
        // Si no hay tickets, ni gastos, ni abonos, devuelve un error JSON
         // Verificar si hay datos
        if (
            $tickets->isEmpty() &&
            $gastos->isEmpty() &&
            $abonos->isEmpty() &&
            $ventasCredito->isEmpty() &&
            $pagosDiferencia->isEmpty()
        ) {

            return response()->json([
                'success' => false,
                'message' => 'No hay registros en el rango de fechas seleccionado'
            ], 404);
        }

        // Generar el PDF
        $pdf = PDF::loadView('corte.pdfcorte', compact(
            'tickets', 
            'gastos', 
            'abonos',
            'ventasCredito',
            'pagosDiferencia',   
            'totalDiferenciaEfectivo',
            'totalDiferenciaTransferencia',
            'fechaInicio', 
            'fechaFin', 
            'esCorteDiario'
        ));
        
        // Nombre del archivo con marca de tiempo
        $filename = 'reporte_corte_' . now()->format('Ymd_His') . '.pdf';
        
        
        return $pdf->stream($filename);
    }

}
