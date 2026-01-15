<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use PDF;

class TicketPagoController extends Controller
{
    public function pdf($id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket || !$ticket->pdf_blob) {
            abort(404, 'Ticket no encontrado o PDF no disponible');
        }

        // Devolver el PDF directamente desde el BLOB
        return response($ticket->pdf_blob)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="ticket_pago_'.$ticket->id.'.pdf"');
    }
}
