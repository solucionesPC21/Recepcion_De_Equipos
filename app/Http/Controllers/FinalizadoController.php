<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TipoEquipo;
use App\Models\Concepto;
use App\Models\Recibo;
use PDF;

class FinalizadoController extends Controller
{
    public function index()
    {
        $recibos = Recibo::where('recibos.id_estado', 3)
            ->leftJoin('tickets', 'recibos.id', '=', 'tickets.id_recibo')
            ->where(function ($query) {
                $query->where('tickets.estado_id', 3)
                    ->orWhereNull('tickets.id');
            })
            ->orderBy('tickets.fecha', 'DESC') // Ordenar por fecha del ticket
            ->select('recibos.*', 'tickets.id as ticket_id', 'tickets.fecha as ticket_fechas')
            ->paginate(5);

        $totalRecibos = Recibo::where('recibos.id_estado', 3)
            ->leftJoin('tickets', 'recibos.id', '=', 'tickets.id_recibo')
            ->where(function ($query) {
                $query->where('tickets.estado_id', 3)
                    ->orWhereNull('tickets.id');
            })
            ->count();

        return view('completados.completados', compact('recibos', 'totalRecibos'));
    }


    public function pdf($id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket || !$ticket->pdf_blob) {
            abort(404, 'Ticket no encontrado o PDF no disponible');
        }

        // Devolver el PDF directamente desde el BLOB
        return response($ticket->pdf_blob)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="ticket_'.$ticket->id.'.pdf"');
    }


}
