<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\BusquedaClientesController;
use App\Http\Controllers\BuscarColoniasController;
use App\Http\Controllers\ColoniasController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\RegistroEquipoCliente;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ConceptoController;
use App\Http\Controllers\FinalizadoController;
use App\Http\Controllers\BusquedaRecibo;
use App\Http\Controllers\buscarTicket;
use App\Http\Controllers\BusquedaCompleto;
use App\Http\Controllers\BuscarCliente;
use App\Http\Controllers\BuscarUsuario;
use App\Http\Controllers\BusquedaConcepto;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RechazadoController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\BuscarProducto;
use App\Http\Controllers\BuscarServicio;
use Illuminate\Http\Request;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\buscarClienteVenta;
use App\Http\Controllers\PagosController;
use App\Http\Controllers\TicketPagoController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\AbonosController;
use App\Http\Controllers\BusquedaPago;
use App\Http\Controllers\BusquedaAbono;
use App\Models\Marca; // Importa el modelo correctamente
use App\Models\Ticket;
use App\Models\Recibo;
use App\Models\Abono;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\NotaAbonosController;
use App\Http\Controllers\ClienteAbonoController;
use App\Http\Controllers\RegimenController;
use App\Http\Controllers\AbonosAbonar;
use App\Http\Controllers\AdministrarNotaAbono;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\BuscarCotizacion;
use App\Http\Controllers\ReciboArchivoController;
use App\Http\Controllers\ImpresoraController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

//Route::get('/', function () {
  // return view('auth.login');
//})->name('login');

/*Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('home.index');
    }
    return view('auth.login');
})->name('login');*/

Route::get('/', function () {
  return view('auth.login');
})->name('login')->middleware('guest');


Route::post('/register',[RegisterController::class,'register'])->middleware('auth');

Route::get('/register',[RegisterController::class,'show'])->middleware('auth');

Route::get('/login', [LoginController::class, 'show'])->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
// Rutas protegidas por el middleware 'auth'
//Route::middleware('auth')->group(function () {
  //  Route::get('/home', [HomeController::class, 'index'])->name('home.index');
    //Route::post('/login',[LoginController::class,'login']);
//});


Route::get('/home',[HomeController::class,'index'])->name('home.index')->middleware('auth');
Route::get('/logout',[LogoutController::class,'logout'])->middleware('auth');
 
Route::middleware(['auth', 'admin'])->group(function () {
  Route::resource('users', UserController::class)
      ->except(['create', 'show'])
      ->names('users');
});

Route::resource('clientes', ClientesController::class)
->except(['create', 'show'])
->middleware('auth');

Route::resource('colonias', ColoniasController::class)
->except(['create', 'show'])
->middleware('auth');

Route::resource('marcas', MarcaController::class)
->except(['create', 'show'])
->middleware('auth');


Route::resource('tipo_equipos', EquipoController::class)
->except(['create', 'show'])
->middleware('auth');

Route::resource('recibos', ReciboController::class)
->except(['create', 'store', 'show', 'edit', 'update', 'destroy'])
->middleware('auth');
// Subir archivos
// SUBIR ARCHIVOS
Route::post('/recibos/{id}/archivos', [ReciboArchivoController::class, 'subirArchivos'])
    ->name('recibos.archivos.subir');

// LISTAR ARCHIVOS DEL RECIBO
Route::get('/recibos/{id}/archivos', [ReciboArchivoController::class, 'listarArchivos'])
    ->name('recibos.archivos.listar');

// DESCARGAR ARCHIVO INDIVIDUAL
Route::get('/recibos/archivo/{archivoId}/descargar', [ReciboArchivoController::class, 'descargarArchivo'])
    ->name('recibos.archivos.descargar');

Route::resource('ticket', TicketController::class)
->except(['create', 'store', 'show', 'edit', 'update', 'destroy'])
->middleware('auth');
//
//Configuaracion de impresoras
Route::get('/configuracion/impresoras', [ImpresoraController::class, 'index'])
    ->name('impresoras.index');

Route::post('/configuracion/impresoras', [ImpresoraController::class, 'store'])
    ->name('impresoras.store');

Route::delete('/configuracion/impresoras/{id}', [ImpresoraController::class, 'destroy'])
    ->name('impresoras.destroy');
//actualizar recibo a completo en el apartado de generar ticket
     // En routes/web.php
Route::post('/ticket/actualizarEstadoRecibo', [ReciboController::class, 'actualizarEstado'])
     ->name('recibos.actualizarEstado')
     ->middleware(['auth', 'admin']); // Asegúrate que 'admin' existe

Route::resource('conceptos', ConceptoController::class)
->except(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
->middleware('auth');

Route::post('/conceptos', [ConceptoController::class, 'guardar'])->name('conceptos.guardar')->middleware('auth');


// Ruta para buscar clientes en tiempo real
Route::get('/home/buscar', [BusquedaClientesController::class, 'buscar'])->middleware('auth');

// Ruta para seleccionar un cliente específico y cargar su información
Route::get('/home/seleccionarCliente/{id}', [BusquedaClientesController::class, 'seleccionarCliente'])->middleware('auth');

Route::get('/buscarUsuario', [BuscarUsuario::class, 'buscar'])->middleware('auth');
Route::get('/buscarCliente', [BuscarCliente::class, 'buscar'])->middleware('auth');
Route::get('/buscarCompleto', [BusquedaCompleto::class, 'buscar'])->middleware('auth');
Route::get('/buscarTicket', [buscarTicket::class, 'buscar'])->name('buscar.ticket')->middleware('auth');
Route::get('/buscarConcepto', [BusquedaConcepto::class, 'buscar'])->middleware('auth');
Route::get('/buscarRecibo', [BusquedaRecibo::class, 'buscar'])->name('recibos.buscar')->middleware('auth');
Route::get('/buscarRechazado', [RechazadoController::class, 'buscar'])->middleware('auth');


Route::get('recibos/pdf/{id}', [ReciboController::class, 'pdf'])->name('recibos.pdf')->middleware('auth');
Route::get('recibos/pdfImprimir/{id}', [ReciboController::class, 'pdfImprimir'])->name('pdfImprimir.pdfImprimir')->middleware('auth');


Route::middleware(['auth', 'admin'])->group(function () {
  Route::post('pagos/reporte', [ReporteController::class, 'generarReporte'])->name('generar.reporte')->middleware('auth');
});
                                                       

Route::get('recibos/cancelarCancelado/{id}', [RegistroEquipoCliente::class, 'cancelarCancelado'])
    ->name('recibos.cancelar')
    ->middleware(['auth', 'admin']);  // Requiere autenticación Y ser admin                                                                  
Route::get('recibos/estado/{id}', [RegistroEquipoCliente::class, 'estado'])->name('recibos.estado')->middleware('auth');
Route::get('recibos/cancelado/{id}', [RegistroEquipoCliente::class, 'cancelado'])
    ->name('recibos.cancelado')
    ->middleware(['auth', 'admin']);
Route::get('recibos/rechazado', [ReciboController::class, 'rechazado'])->name('recibos.rechazado')->middleware('auth');

Route::get('/imprimir', [ConceptoController::class, 'imprimir'])->middleware('auth');

Route::post('/recibos/sin-cobrar/{id}', [ReciboController::class, 'marcarSinCobrar'])->middleware('auth');
//recibos revision
Route::post('/recibos/{id}/revision', [ReciboController::class, 'marcarEnRevision'])
     ->name('recibos.revision');
//
Route::get('/home/buscarColonia', [BuscarColoniasController::class, 'buscarColonia'])->middleware('auth');

Route::post('/home/registroEquipoCliente', [RegistroEquipoCliente::class, 'recepcion'])->name('recepcion.equipos')->middleware('auth');
Route::post('/home/validarMarca', function (Request $request) {
  $marcaExiste = Marca::where('marca', $request->marca)->exists();
  return response()->json(['exists' => $marcaExiste]);
});
Route::get('completados',[FinalizadoController::class,'index'])->name('completados.index')->middleware('auth');

Route::get('completados/pdf/{id}',[FinalizadoController::class,'pdf'])->name('completados.pdf')->middleware('auth');

//Route::get('recibos/nota', [NotaController::class, 'guardarNota'])->name('guardarNota');

Route::get('/recibos/nota/{id}', [NotaController::class, 'obtenerNota']);
Route::get('/recibos/agregarnota{id}', [NotaController::class, 'guardarNota']);

//Productos

Route::resource('productos', ProductoController::class)->middleware('auth');
Route::post('/productos/validar', [ProductoController::class, 'validarProducto'])
    ->middleware('auth')
    ->name('productos.validar');

//Servicios

Route::resource('servicios', ServicioController::class)
    ->middleware('auth')
    ->names('servicios');
Route::get('/buscarServicio', [BuscarServicio::class, 'buscar'])->name('buscar.servicio');


//Route::get('/productos/buscar', [ProductoController::class, 'buscar']);
// routes/web.php
Route::get('/buscarProducto', [BuscarProducto::class, 'buscar'])->name('buscar.producto');

Route::resource('ventas', VentaController::class)->middleware('auth');

Route::resource('pagos', PagosController::class)->middleware('auth');

Route::resource('gastos', GastoController::class)->middleware('auth');

Route::middleware(['auth'])->group(function () {
  // Rutas para abonos
    Route::get('/abonos/buscar-productos', [AbonosController::class, 'buscarProductos']);
  Route::get('/abonos', [AbonosController::class, 'index'])->name('abonos.index');
  Route::post('/abonos', [AbonosController::class, 'store'])->name('abonos.store');
  Route::get('/abonos/{ventaId}', [AbonosController::class, 'getAbonos'])->name('abonos.historial');
  Route::delete('/abonos/{abono}', [AbonosController::class, 'destroy'])->name('abonos.destroy');
  Route::get('abonos/pdf/{id}', [AbonosController::class, 'verPDFAbono'])->name('abonos.pdf');
  Route::post('/abonos/reimprimir/{id}', [AbonosController::class, 'reimprimirTicket'])->name('abonos.reimprimir');

  // Rutas para ventas a crédito
  Route::post('/ventas-abonos', [AbonosController::class, 'storeVenta'])->name('ventas-abonos.store');
  Route::get('/ventas-abonos/pdf/{id}', [AbonosController::class, 'verPDF'])->name('ventas-abonos.pdf');
  Route::delete('/ventas-abonos/{id}', [AbonosController::class, 'destroyVenta'])->name('ventas-abonos.destroy');
  Route::get('/ventas-abonos/{id}/detalles', [AbonosController::class, 'getVentaDetalles'])->name('ventas-abonos.detalles');
  Route::put('/ventas-abonos/{id}/productos', [AbonosController::class, 'actualizarProductos'])->name('ventas-abonos.actualizar-productos');
    //Actualizar total del abono 
Route::put('/ventas-abonos/{id}/actualizar-total', [AbonosController::class, 'actualizarTotal'])
    ->name('ventas-abonos.actualizar-total')
    ->middleware('auth');

  Route::get('/buscar-clientes', [AbonosController::class, 'buscarClientes'])->name('clientes.buscar');
  // Nueva ruta para búsqueda AJAX
  Route::post('/clientesAbono', [AbonosController::class, 'storeCliente'])->name('clientes.abono.store');
});
/* ============================================================
|                   📌 NOTAS DE ABONO (MÓDULO PRINCIPAL)
============================================================ */

// Vista principal
Route::get('/nota-abonos', [NotaAbonosController::class, 'index'])
    ->name('nota-abonos.index')
    ->middleware('auth');

// Guardar cliente de Nota de Abono
Route::post('/clientesNotaAbono', [ClienteAbonoController::class, 'store'])
    ->name('clientesNotaAbono.store');

// Guardar régimen
Route::post('/regimenes', [RegimenController::class, 'store'])
    ->name('regimenes.store');

// Comprobar nombres repetidos
Route::get('/verificar-clienteAbono', [ClienteAbonoController::class, 'verificarNombreAbono']);

// Buscar clientes
Route::get('/buscarClienteNotaAbono', [ClienteAbonoController::class, 'buscar'])
    ->middleware('auth');


/* ============================================================
|                  📌 CLIENTES NOTA DE ABONO
============================================================ */

Route::get('/clientesNotaAbono/{id}', [ClienteAbonoController::class, 'show']);
Route::put('/clientesNotaAbono/{id}', [ClienteAbonoController::class, 'update'])
    ->name('clientesNotaAbono.update');


/* ============================================================
|                   📌 ABONOS A CLIENTES
============================================================ */

Route::get('/abonosAbonar/{cliente_id}', [AbonosAbonar::class, 'index'])
    ->name('abonos-abonar.index')
    ->middleware('auth');


/* ============================================================
|                  📌 NOTAS DE ABONO CRUD
============================================================ */

Route::post('/notas-abonoCliente', [NotaAbonosController::class, 'store'])
    ->name('notas-abono.store');

Route::get('/notas-abono/cliente/{clienteId}', [NotaAbonosController::class, 'getByCliente'])
    ->name('notas-abono.by-cliente');

Route::get('/notas-abono/{id}', [NotaAbonosController::class, 'show'])
    ->name('notas-abono.show');

Route::put('/notas-abono/{id}', [NotaAbonosController::class, 'update'])
    ->name('notas-abono.update');

Route::post('/notas-abono/{id}/cerrar', [NotaAbonosController::class, 'cerrar'])
    ->name('notas-abono.cerrar');

Route::get('/notas-abono/{id}/historial', [NotaAbonosController::class, 'historial'])
    ->name('notas-abono.historial');


/* ============================================================
|                 📌 ADMINISTRAR NOTA DE ABONO
============================================================ */

// Vista principal de administración
Route::get('/notas-abono/administrar/{id}', [AdministrarNotaAbono::class, 'administrar'])
    ->name('administrar-notas-abono.administrar');


/* ============================================================
|                 📌 ADMINISTRAR API (SIN PARÁMETROS)
============================================================ */

// Estas deben ir ANTES de las rutas con {id}
Route::get('/buscar-productos-NotaAbono', [AdministrarNotaAbono::class, 'buscarProductos'])
    ->name('administrar-notas-abono.buscar-productos');

Route::get('/buscar-responsables', [AdministrarNotaAbono::class, 'buscarResponsables'])
    ->name('administrar-notas-abono.buscar-responsables');

Route::post('/registrar-responsable', [AdministrarNotaAbono::class, 'registrarResponsable'])
    ->name('administrar-notas-abono.registrar-responsable');

Route::get('/obtener-regimen-cliente', [AdministrarNotaAbono::class, 'obtenerRegimenCliente'])
    ->name('administrar-notas-abono.obtener-regimen-cliente');
// routes/web.php
Route::get('/ventas/{id}/pdf', [AdministrarNotaAbono::class, 'obtenerPDF'])
    ->name('ventas.pdf');

Route::post('/ventas/reimprimir/{id}', [AdministrarNotaAbono::class, 'reimprimir'])
    ->name('ventas.reimprimir')
    ->middleware('auth');

Route::post('/ventas/cancelar/{id}', [AdministrarNotaAbono::class, 'cancelarVenta'])
    ->name('ventas.cancelar')
    ->middleware('auth');

Route::get('/ventas/{id}/productos-devolucion', [AdministrarNotaAbono::class, 'obtenerProductosParaDevolucion'])
    ->name('ventas.productos.devolucion')
    ->middleware('auth');
    
Route::post('/ventas/devolucion/{id}', [AdministrarNotaAbono::class, 'devolverProductos'])
    ->name('ventas.devolucion')
    ->middleware('auth');

// En routes/web.php
Route::get('/ventas/{id}/historial-devoluciones', [AdministrarNotaAbono::class, 'obtenerHistorialDevoluciones'])
    ->name('ventas.historial.devoluciones')
    ->middleware('auth');

Route::get('/devoluciones/{id}/detalle', [AdministrarNotaAbono::class, 'obtenerDetalleDevolucion'])
    ->name('devoluciones.detalle')
    ->middleware('auth');

/* ============================================================
|                 📌 ADMINISTRAR API (CON {id})
============================================================ */

Route::post('/notas-abono/{id}/registrar-venta', [AdministrarNotaAbono::class, 'registrarVenta'])
    ->name('administrar-notas-abono.registrar-venta');

Route::post('/notas-abono/{id}/agregar-abono', [AdministrarNotaAbono::class, 'agregarAbono'])
    ->name('administrar-notas-abono.agregar-abono');

Route::post('/notas-abono/{id}/cerrar', [AdministrarNotaAbono::class, 'cerrarNota'])
    ->name('administrar-notas-abono.cerrar');

Route::get('/notas-abono/{id}/historial', [AdministrarNotaAbono::class, 'obtenerHistorial'])
    ->name('administrar-notas-abono.historial');
//
//filtros de notaABONO
// RUTAS PARA FILTROS DE NOTAS DE ABONO
Route::get('/filtros/notas-abono', [NotaAbonosController::class, 'filtrarNotas'])
     ->name('filtros.notas-abono');
// RUTA PARA EXPORTAR HISTORIAL DE MOVIMIENTOS A PDF
Route::get('/notas-abono/{notaAbono}/exportar-pdf', [NotaAbonosController::class, 'exportarHistorialPDF'])
     ->name('notas-abono.exportar-pdf');
// RUTAS PARA FILTROS DE MOVIMIENTOS
Route::get('/filtros/movimientos/{notaAbonoId}', [NotaAbonosController::class, 'filtrarMovimientos'])
     ->name('filtros.movimientos');


//ruta para pago
Route::patch('/pagos/{id}/cancelar', [PagosController::class, 'cancelar'])
     ->name('pagos.cancelar');


//ticket pagos
Route::get('/pagos/pdf/{id}', [TicketPagoController::class, 'pdf'])
     ->name('pagos.pdf');

// Ruta para registrar un cliente
Route::post('/ventas/registrar', [VentaController::class, 'crearCliente'])->name('clientes.registrar');
Route::get('/buscar-productos', [VentaController::class, 'buscarProducto'])->name('productos.buscar');

Route::post('/ventas/realizar-cobro', [VentaController::class, 'realizarCobro'])->name('venta.realizar');;

Route::get('/buscarClienteVentas', [BuscarClienteVenta::class, 'buscar'])->middleware('auth');


// Ruta para seleccionar un cliente específico y cargar su información
Route::get('/seleccionarClienteVenta/{id}', [BuscarClienteVenta::class, 'seleccionarCliente'])->middleware('auth');

//buscar cliente en pagos
Route::get('/buscarPago', [BusquedaPago::class, 'buscar'])->name('pagos.buscar')->middleware('auth');


//buscar cliente abonos
Route::get('/buscarAbono', [BusquedaAbono::class, 'buscar'])->middleware('auth');

//sistema de cotizacion
Route::get('/cotizacion', [CotizacionController::class, 'index'])
     ->name('cotizacion.index')->middleware('auth');

// Rutas para editar cotizaciones
Route::get('/cotizaciones/{id}/editar', [CotizacionController::class, 'obtenerParaEditar'])->name('cotizaciones.obtener-editar');
Route::post('/cotizaciones/{id}/actualizar', [CotizacionController::class, 'actualizarCotizacion'])->name('cotizaciones.actualizar');
//

// En routes/web.php
Route::post('/cotizacion/generar-y-guardar-pdf', [CotizacionController::class, 'generarYGuardarPDF'])->name('cotizaciones.generar-pdf');
// Rutas para cotizaciones
Route::get('/cotizaciones/historial', [CotizacionController::class, 'historial'])->name('cotizaciones.historial');
Route::get('/cotizaciones/ver/{id}/{tipo}', [CotizacionController::class, 'ver'])->name('cotizaciones.ver');
Route::get('/cotizaciones/ver-pdf/{filename}', [CotizacionController::class, 'verPDF'])->name('cotizacion.ver-pdf');
Route::delete('/cotizaciones/{id}', [CotizacionController::class, 'eliminar'])->name('cotizaciones.eliminar');
Route::get('/buscarCotizacion', [BuscarCotizacion::class, 'buscar'])->middleware('auth');
// ACTUALIZAR SALDO RESTANTE DEL CLIENTE
/*Route::put('/ventas-abonos/{id}/actualizar-saldo', [AbonosController::class, 'actualizarSaldo'])
    ->name('ventas-abonos.actualizar-saldo')
    ->middleware('auth');*/