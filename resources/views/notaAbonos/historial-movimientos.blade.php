<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estado de Cuenta - {{ $notaAbono->folio }}</title>

    <style>
        /* CONFIGURACIÓN DE PÁGINA PDF */
        @page {
            margin: 40px 30px;
            size: A4 portrait;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #2d3748;
        }

        h1,h2,h3 {
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .border {
            border: 1px solid #e2e8f0;
        }

        .bg-light {
            background: #f7fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .fw-bold {
            font-weight: bold;
        }

        .mt-20 {
            margin-top: 20px;
        }

        .mb-20 {
            margin-bottom: 20px;
        }

        .movimientos td {
            padding: 4px;
            font-size: 9px;
        }
    </style>
</head>
<body>

<!-- ================= HEADER ================= -->

<table class="mb-20">
    <tr>
        <td>
            <h1 style="color:#1a365d;">Soluciones PC</h1>
            <div>Sistema de Cuentas de Abono</div>
        </td>
        <td class="text-right">
            <table class="border" style="width:auto;">
                <tr>
                    <td class="fw-bold text-center" style="padding:6px;">
                        ESTADO DE CUENTA
                    </td>
                </tr>
                <tr>
                    <td style="padding:6px;">Folio: {{ $notaAbono->folio }}</td>
                </tr>
                <tr>
                    <td style="padding:6px;">Fecha: {{ $fecha_generacion }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- ================= DATOS DEL CLIENTE ================= -->

<table class="border bg-light mb-20">
    <tr>
        <td colspan="4" class="fw-bold" style="padding:8px;">
            DATOS DEL CLIENTE
        </td>
    </tr>
    <tr>
        <td style="padding:6px;">Cliente:</td>
        <td style="padding:6px;" class="fw-bold">
            {{ $notaAbono->cliente->nombre }}
        </td>
        <td style="padding:6px;">Cuenta:</td>
        <td style="padding:6px;">{{ $notaAbono->folio }}</td>
    </tr>
    <tr>
        <td style="padding:6px;">Estado:</td>
        <td style="padding:6px;">{{ strtoupper($notaAbono->estado) }}</td>
        <td style="padding:6px;">Fecha apertura:</td>
        <td style="padding:6px;">
            {{ $notaAbono->fecha_apertura->format('d/m/Y') }}
        </td>
    </tr>
</table>

<!-- ================= RESUMEN ================= -->

<table class="border mb-20">
    <tr class="bg-light fw-bold">
        <td style="padding:8px;">Concepto</td>
        <td class="text-right" style="padding:8px;">Monto</td>
    </tr>
    <tr>
        <td style="padding:6px;">Saldo inicial</td>
        <td class="text-right" style="padding:6px;">
            ${{ number_format($resumen['saldo_inicial'],2) }}
        </td>
    </tr>
    <tr>
        <td style="padding:6px;">Total abonos (+)</td>
        <td class="text-right" style="padding:6px;color:#38a169;">
            ${{ number_format($resumen['total_abonos'],2) }}
        </td>
    </tr>
    <tr>
        <td style="padding:6px;">Total compras (-)</td>
        <td class="text-right" style="padding:6px;color:#e53e3e;">
            ${{ number_format($resumen['total_compras'],2) }}
        </td>
    </tr>
    <tr class="fw-bold" style="border-top:2px solid #1a365d;">
        <td style="padding:8px;">Saldo final</td>
        <td class="text-right" style="padding:8px;">
            ${{ number_format($resumen['saldo_actual'],2) }}
        </td>
    </tr>
</table>

<!-- ================= DETALLE NOTA DE ABONO ================= -->

<table class="border bg-light mb-20">
    <tr>
        <td colspan="4" class="fw-bold" style="padding:8px;">
            DETALLE DE LA NOTA DE ABONO
        </td>
    </tr>
    <tr>
        <td style="padding:6px;">Abono inicial:</td>
        <td style="padding:6px;">
            ${{ number_format($notaAbono->abono_inicial,2) }}
        </td>
        <td style="padding:6px;">Saldo actual:</td>
        <td style="padding:6px;">
            ${{ number_format($notaAbono->saldo_actual,2) }}
        </td>
    </tr>
    <tr>
        <td style="padding:6px;">Subtotal acumulado:</td>
        <td style="padding:6px;">
            ${{ number_format($notaAbono->subtotal_acumulado,2) }}
        </td>
        <td style="padding:6px;">Total Compras:</td>
        <td style="padding:6px;">
            ${{ number_format($notaAbono->total_con_impuestos,2) }}
        </td>
    </tr>
    <tr>
        <td style="padding:6px;">IVA:</td>
        <td style="padding:6px;">
            ${{ number_format($notaAbono->iva_calculado,2) }}
        </td>
        <td style="padding:6px;">ISR:</td>
        <td style="padding:6px;">
            ${{ number_format($notaAbono->isr_calculado,2) }}
        </td>
    </tr>
    <tr>
        <td style="padding:6px;">Fecha cierre:</td>
        <td style="padding:6px;">
            {{ $notaAbono->fecha_cierre
                ? $notaAbono->fecha_cierre->format('d/m/Y')
                : '—' }}
        </td>
        <td style="padding:6px;">Estado:</td>
        <td style="padding:6px;" class="fw-bold">
            {{ strtoupper($notaAbono->estado) }}
        </td>
    </tr>

    @if($notaAbono->observaciones)
    <tr>
        <td style="padding:6px;">Observaciones:</td>
        <td colspan="3" style="padding:6px;">
            {{ $notaAbono->observaciones }}
        </td>
    </tr>
    @endif
</table>

<!-- ================= MOVIMIENTOS ================= -->

<table class="border movimientos">
    <tr class="bg-light fw-bold">
        <td style="width:12%">Fecha</td>
        <td style="width:10%">Tipo</td>
        <td style="width:26%">Concepto</td>
        <td style="width:15%" class="text-right">Monto</td>
        <td style="width:15%" class="text-right">Saldo Nuevo</td>
        <td style="width:22%">Observaciones</td>
    </tr>


    @forelse($movimientos as $m)
        <tr>
            <td>{{ $m->created_at->format('d/m/Y H:i') }}</td>

            <td>{{ strtoupper($m->tipo) }}</td>

            <td>{{ $m->concepto ?? 'MOVIMIENTO' }}</td>

            <td class="text-right">
                ${{ number_format($m->monto, 2) }}
            </td>

            <td class="text-right">
                ${{ number_format($m->nuevo_saldo, 2) }}
            </td>

            <td>
                {{ $m->observaciones ?? '—' }}
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="text-center" style="padding:20px;">
                SIN MOVIMIENTOS REGISTRADOS
            </td>
        </tr>
    @endforelse
</table>


<!-- ================= FOOTER ================= -->

<div class="mt-20" style="font-size:9px;color:#4a5568;">
    Documento generado por: <strong>{{ $usuario_actual }}</strong><br>
    Fecha de generación: {{ $fecha_generacion }}<br>
</div>

</body>
</html>
