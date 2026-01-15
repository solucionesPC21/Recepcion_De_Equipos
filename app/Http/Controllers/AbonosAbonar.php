<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClienteAbono;
use App\Models\NotaAbono;

class AbonosAbonar extends Controller
{
      public function index(Request $request, $cliente_id = null)
    {
        // Primero intentar obtener el cliente_id del parámetro de ruta
        if (!$cliente_id) {
            // Si no viene por ruta, intentar por query parameter
            $cliente_id = $request->query('cliente_id');
        }
        
        // Buscar el cliente en la base de datos
        $cliente = null;
        $notasAbono = collect();
        $notasAbonoActivas = collect();
        $notasAbonoFinalizadas = collect();
        $notasAbonoOtrosEstados = collect();
        $resumenEstados = [];
        
        if ($cliente_id) {
            $cliente = ClienteAbono::with('regimen')->find($cliente_id);
            
            if ($cliente) {
                // ==============================================
                // BUSCAR TODAS LAS NOTAS CON PAGINACIÓN
                // ==============================================
                $notasAbono = NotaAbono::where('cliente_id', $cliente_id)
                                      ->orderBy('created_at', 'desc')
                                      ->paginate(4);
                
                // ==============================================
                // NOTAS ACTIVAS (PARA COMPATIBILIDAD)
                // ==============================================
                $notasAbonoActivas = NotaAbono::where('cliente_id', $cliente_id)
                                             ->where('estado', 'activa')
                                             ->orderBy('created_at', 'desc')
                                             ->get();
                
                // ==============================================
                // NOTAS FINALIZADAS (NUEVO)
                // ==============================================
                $notasAbonoFinalizadas = NotaAbono::where('cliente_id', $cliente_id)
                                                 ->where('estado', 'finalizada')
                                                 ->orderBy('created_at', 'desc')
                                                 ->get();
                
                // ==============================================
                // OTROS ESTADOS (SALDO_FAVOR, SALDO_DEUDA, CANCELADA)
                // ==============================================
                $notasAbonoOtrosEstados = NotaAbono::where('cliente_id', $cliente_id)
                                                  ->whereIn('estado', ['saldo_favor', 'saldo_deuda', 'cancelada'])
                                                  ->orderBy('created_at', 'desc')
                                                  ->get();
                
                // ==============================================
                // RESUMEN POR ESTADO (PARA FILTROS/ESTADÍSTICAS)
                // ==============================================
                $resumenEstados = [
                    'activa' => [
                        'count' => $notasAbonoActivas->count(),
                        'total_abono' => $notasAbonoActivas->sum('abono_inicial'),
                        'total_saldo' => $notasAbonoActivas->sum('saldo_actual'),
                    ],
                    'finalizada' => [
                        'count' => $notasAbonoFinalizadas->count(),
                        'total_abono' => $notasAbonoFinalizadas->sum('abono_inicial'),
                        'total_saldo' => $notasAbonoFinalizadas->sum('saldo_actual'),
                    ],
                    'saldo_favor' => [
                        'count' => $notasAbonoOtrosEstados->where('estado', 'saldo_favor')->count(),
                        'total_abono' => $notasAbonoOtrosEstados->where('estado', 'saldo_favor')->sum('abono_inicial'),
                        'total_saldo' => $notasAbonoOtrosEstados->where('estado', 'saldo_favor')->sum('saldo_actual'),
                    ],
                    'saldo_deuda' => [
                        'count' => $notasAbonoOtrosEstados->where('estado', 'saldo_deuda')->count(),
                        'total_abono' => $notasAbonoOtrosEstados->where('estado', 'saldo_deuda')->sum('abono_inicial'),
                        'total_saldo' => $notasAbonoOtrosEstados->where('estado', 'saldo_deuda')->sum('saldo_actual'),
                    ],
                    'cancelada' => [
                        'count' => $notasAbonoOtrosEstados->where('estado', 'cancelada')->count(),
                        'total_abono' => $notasAbonoOtrosEstados->where('estado', 'cancelada')->sum('abono_inicial'),
                        'total_saldo' => $notasAbonoOtrosEstados->where('estado', 'cancelada')->sum('saldo_actual'),
                    ],
                ];
                
                // Mantener compatibilidad
                $notaAbonoActiva = $notasAbonoActivas->first();
                
            } else {
                return redirect()->back()->with('error', 'Cliente no encontrado');
            }
        }
        
        // ==============================================
        // RETORNAR LA VISTA CON TODOS LOS DATOS
        // ==============================================
        return view('notaAbonos.abonosAbonar', compact(
            'cliente', 
            'cliente_id', 
            'notaAbonoActiva', 
            'notasAbonoActivas',
            'notasAbonoFinalizadas',
            'notasAbonoOtrosEstados',
            'resumenEstados',
            'notasAbono'
        ));
    }
    
}
