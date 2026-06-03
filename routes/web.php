<?php

use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ComisionesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ModuleDataController;
use App\Http\Controllers\ModuleIndexController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductosController;
use App\Http\Controllers\DispositivosController;
use App\Http\Controllers\TicketsController;
use App\Http\Controllers\VentasController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\SucursalesController;
use App\Http\Controllers\SoporteVideosWebController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\MeController;
use Illuminate\Support\Facades\Route;

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

// Archivos (branding público)
Route::get('/files/settings/{y}/{m}/{file}', [FileController::class, 'showSettings'])
    ->where(['y' => '\\d{4}', 'm' => '\\d{2}', 'file' => '.+'])
    ->name('files.settings');

Route::redirect('/', '/dashboard');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Archivos protegidos
    Route::get('/files/{cat}/{y}/{m}/{file}', [FileController::class, 'show'])
        ->where(['cat' => '[a-z_]+', 'y' => '\\d{4}', 'm' => '\\d{2}', 'file' => '.+'])
        ->name('files.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/configuracion', [ConfiguracionController::class, 'edit'])->name('configuracion.edit');
    Route::post('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');

    // Clientes
    Route::get('/clientes', [ClientesController::class, 'index'])->middleware('rbac:clientes,ver')->name('clientes.index');
    Route::get('/clientes/data', ModuleDataController::class)->defaults('module', 'clientes')->middleware('rbac:clientes,ver')->name('clientes.data');
    Route::get('/clientes/crear', [ClientesController::class, 'create'])->middleware('rbac:clientes,crear')->name('clientes.create');
    Route::post('/clientes/crear', [ClientesController::class, 'store'])->middleware('rbac:clientes,crear')->name('clientes.store');
    Route::get('/clientes/{id}', [ClientesController::class, 'show'])->middleware('rbac:clientes,ver')->name('clientes.show');
    Route::get('/clientes/{id}/editar', [ClientesController::class, 'edit'])->middleware('rbac:clientes,editar')->name('clientes.edit');
    Route::post('/clientes/{id}/editar', [ClientesController::class, 'update'])->middleware('rbac:clientes,editar')->name('clientes.update');
    Route::post('/clientes/{id}/eliminar', [ClientesController::class, 'destroy'])->middleware('rbac:clientes,eliminar')->name('clientes.destroy');

    // Compatibilidad Stock (legacy)
    Route::redirect('/stock', '/productos')->name('stock.redirect');
    Route::redirect('/stock/data', '/productos/stock-data')->middleware('rbac:productos,ver')->name('stock.data');
    Route::get('/stock/movimiento', [ProductosController::class, 'movimientoForm'])->middleware('rbac:productos,editar')->name('stock.movimiento-form');
    Route::post('/stock/movimiento', [ProductosController::class, 'movimiento'])->middleware('rbac:productos,editar')->name('stock.movimiento');

    // Módulos (pantallas base)
    // Tickets
    Route::get('/tickets', [TicketsController::class, 'index'])->middleware('rbac:tickets,ver')->name('tickets.index');
    Route::get('/tickets/data', ModuleDataController::class)->defaults('module', 'tickets')->middleware('rbac:tickets,ver')->name('tickets.data');
    Route::get('/tickets/{ticketId}', [TicketsController::class, 'show'])->middleware('rbac:tickets,ver')->name('tickets.show');
    Route::post('/tickets/{ticketId}/asignar', [TicketsController::class, 'asignar'])->middleware('rbac:tickets,asignar')->name('tickets.asignar');
    Route::post('/tickets/{ticketId}/cerrar', [TicketsController::class, 'cerrar'])->middleware('rbac:tickets,editar')->name('tickets.cerrar');

    // Agenda
    Route::get('/agenda', [AgendaController::class, 'index'])->middleware('rbac:agenda,ver')->name('agenda.index');
    Route::get('/agenda/data', [AgendaController::class, 'data'])->middleware('rbac:agenda,ver')->name('agenda.data');
    Route::get('/agenda/tecnicos-data', [AgendaController::class, 'tecnicosData'])->middleware('rbac:agenda,ver')->name('agenda.tecnicos-data');
    Route::get('/agenda/{id}', [AgendaController::class, 'show'])->middleware('rbac:agenda,ver')->name('agenda.show');
    Route::post('/agenda/crear', [AgendaController::class, 'store'])->middleware('rbac:agenda,crear')->name('agenda.store');
    Route::post('/agenda/{id}/editar', [AgendaController::class, 'update'])->middleware('rbac:agenda,editar')->name('agenda.update');
    Route::post('/agenda/{id}/completar', [AgendaController::class, 'complete'])
        ->middleware('rbac:agenda,editar')
        ->name('agenda.complete');
    Route::post('/agenda/{id}/eliminar', [AgendaController::class, 'destroy'])->middleware('rbac:agenda,eliminar')->name('agenda.destroy');

    // Productos + Stock
    Route::get('/productos', [ProductosController::class, 'index'])->middleware('rbac:productos,ver')->name('productos.index');
    Route::get('/productos/data', ModuleDataController::class)->defaults('module', 'productos')->middleware('rbac:productos,ver')->name('productos.data');
    Route::get('/productos/stock-data', [ProductosController::class, 'stockData'])->middleware('rbac:productos,ver')->name('productos.stock-data');
    Route::get('/productos/stock-data/data', [ProductosController::class, 'stockDataJson'])->middleware('rbac:productos,ver')->name('productos.stock-data.json');
    Route::get('/productos/kardex-data', [ProductosController::class, 'kardexData'])->middleware('rbac:productos,ver')->name('productos.kardex-data');
    Route::get('/productos/kardex-data/data', [ProductosController::class, 'kardexDataJson'])->middleware('rbac:productos,ver')->name('productos.kardex-data.json');
    Route::get('/productos/movimiento-stock', [ProductosController::class, 'movimientoForm'])->middleware('rbac:productos,editar')->name('productos.movimiento-form');
    Route::post('/productos/movimiento-stock', [ProductosController::class, 'movimiento'])->middleware('rbac:productos,editar')->name('productos.movimiento');
    Route::get('/productos/import', [ProductosController::class, 'importForm'])->middleware('rbac:productos,crear')->name('productos.import-form');
    Route::post('/productos/import', [ProductosController::class, 'import'])->middleware('rbac:productos,crear')->name('productos.import');
    Route::get('/productos/crear', [ProductosController::class, 'create'])->middleware('rbac:productos,crear')->name('productos.create');
    Route::post('/productos/crear', [ProductosController::class, 'store'])->middleware('rbac:productos,crear')->name('productos.store');
    Route::get('/productos/{id}', [ProductosController::class, 'show'])->middleware('rbac:productos,ver')->name('productos.show');
    Route::get('/productos/{id}/editar', [ProductosController::class, 'edit'])->middleware('rbac:productos,editar')->name('productos.edit');
    Route::post('/productos/{id}/editar', [ProductosController::class, 'update'])->middleware('rbac:productos,editar')->name('productos.update');
    Route::post('/productos/{id}/eliminar', [ProductosController::class, 'destroy'])->middleware('rbac:productos,eliminar')->name('productos.destroy');

    Route::get('/ventas', [VentasController::class, 'index'])->middleware('rbac:ventas,ver')->name('ventas.index');
    Route::get('/ventas/data', ModuleDataController::class)->defaults('module', 'ventas')->middleware('rbac:ventas,ver')->name('ventas.data');
    Route::get('/ventas/stats', [VentasController::class, 'stats'])->middleware('rbac:ventas,ver')->name('ventas.stats');
    Route::get('/ventas/next-correlativo', [VentasController::class, 'nextCorrelativo'])->middleware('rbac:ventas,crear')->name('ventas.next-correlativo');
    Route::get('/ventas/crear', [VentasController::class, 'create'])->middleware('rbac:ventas,crear')->name('ventas.create');
    Route::post('/ventas/crear', [VentasController::class, 'store'])->middleware('rbac:ventas,crear')->name('ventas.store');
    Route::get('/ventas/{id}', [VentasController::class, 'show'])->middleware('rbac:ventas,ver')->name('ventas.show');
    Route::post('/ventas/{id}/pagar', [VentasController::class, 'pagar'])->middleware('rbac:ventas,aprobar')->name('ventas.pagar');
    Route::post('/ventas/{id}/anular', [VentasController::class, 'anular'])->middleware('rbac:ventas,aprobar')->name('ventas.anular');
    // Comisiones
    Route::get('/comisiones', [ComisionesController::class, 'index'])->middleware('rbac:comisiones,ver')->name('comisiones.index');
    Route::get('/comisiones/data', [ComisionesController::class, 'data'])->middleware('rbac:comisiones,ver')->name('comisiones.data');
    Route::get('/comisiones/ventas-periodo', [ComisionesController::class, 'ventasPeriodo'])->middleware('rbac:comisiones,ver')->name('comisiones.ventas-periodo');
    Route::post('/comisiones/{id}/aprobar', [ComisionesController::class, 'aprobar'])->middleware('rbac:comisiones,aprobar')->name('comisiones.aprobar');
    Route::post('/comisiones/aprobar', [ComisionesController::class, 'aprobarBulk'])->middleware('rbac:comisiones,aprobar')->name('comisiones.aprobar-bulk');
    Route::post('/comisiones/pagar', [ComisionesController::class, 'pagar'])->middleware('rbac:comisiones,aprobar')->name('comisiones.pagar');
    Route::post('/comisiones/liquidar', [ComisionesController::class, 'liquidar'])->middleware('rbac:comisiones,aprobar')->name('comisiones.liquidar');
    Route::get('/comisiones/export', [ComisionesController::class, 'export'])->middleware('rbac:comisiones,exportar')->name('comisiones.export');
    Route::get('/dispositivos', [DispositivosController::class, 'index'])->middleware('rbac:dispositivos,ver')->name('dispositivos.index');
    Route::get('/dispositivos/data', [DispositivosController::class, 'data'])->middleware('rbac:dispositivos,ver')->name('dispositivos.data');
    Route::get('/dispositivos/{id}', [DispositivosController::class, 'show'])->middleware('rbac:dispositivos,ver')->name('dispositivos.show');
    Route::post('/dispositivos/crear', [DispositivosController::class, 'store'])->middleware('rbac:dispositivos,crear')->name('dispositivos.store');
    Route::get('/soporte-videos', [SoporteVideosWebController::class, 'index'])->middleware('rbac:soporte_videos,ver')->name('soporte-videos.index');
    Route::get('/soporte-videos/data', [SoporteVideosWebController::class, 'data'])->middleware('rbac:soporte_videos,ver')->name('soporte-videos.data');
    Route::get('/soporte-videos/{id}', [SoporteVideosWebController::class, 'show'])->middleware('rbac:soporte_videos,ver')->name('soporte-videos.show');
    Route::get('/reportes', [ReportesController::class, 'index'])->middleware('rbac:reportes,ver')->name('reportes.index');
    Route::get('/reportes/pdf', [ReportesController::class, 'pdf'])->middleware('rbac:reportes,exportar')->name('reportes.pdf');
    Route::get('/usuarios', [UsuariosController::class, 'index'])->middleware('rbac:usuarios,ver')->name('usuarios.index');
    Route::get('/usuarios/data', [UsuariosController::class, 'data'])->middleware('rbac:usuarios,ver')->name('usuarios.data');
    Route::post('/usuarios/crear', [UsuariosController::class, 'store'])->middleware('rbac:usuarios,crear')->name('usuarios.store');
    Route::post('/usuarios/{id}/editar', [UsuariosController::class, 'update'])->middleware('rbac:usuarios,editar')->name('usuarios.update');
    Route::post('/usuarios/{id}/eliminar', [UsuariosController::class, 'destroy'])->middleware('rbac:usuarios,eliminar')->name('usuarios.destroy');
    Route::get('/usuarios/{id}/permisos', [UsuariosController::class, 'permisos'])->middleware('rbac:usuarios,editar')->name('usuarios.permisos');
    Route::post('/usuarios/{id}/permisos', [UsuariosController::class, 'permisosUpdate'])->middleware('rbac:usuarios,editar')->name('usuarios.permisos.update');
    Route::get('/permisos', [PermisosController::class, 'index'])->middleware('rbac:permisos,ver')->name('permisos.index');
    Route::get('/permisos/matrix-data', [PermisosController::class, 'matrixData'])->middleware('rbac:permisos,ver')->name('permisos.matrix-data');
    Route::post('/permisos', [PermisosController::class, 'update'])->middleware('rbac:permisos,editar')->name('permisos.update');

    // Roles
    Route::get('/roles', [RolesController::class, 'index'])->middleware('rbac:roles,ver')->name('roles.index');
    Route::get('/roles/data', [RolesController::class, 'data'])->middleware('rbac:roles,ver')->name('roles.data');
    Route::post('/roles/crear', [RolesController::class, 'store'])->middleware('rbac:roles,crear')->name('roles.store');
    Route::post('/roles/{id}/editar', [RolesController::class, 'update'])->middleware('rbac:roles,editar')->name('roles.update');
    Route::post('/roles/{id}/eliminar', [RolesController::class, 'destroy'])->middleware('rbac:roles,eliminar')->name('roles.destroy');
    Route::get('/sucursales', [SucursalesController::class, 'index'])->middleware('rbac:sucursales,ver')->name('sucursales.index');
    Route::get('/sucursales/data', [SucursalesController::class, 'data'])->middleware('rbac:sucursales,ver')->name('sucursales.data');
    Route::post('/sucursales/crear', [SucursalesController::class, 'store'])->middleware('rbac:sucursales,crear')->name('sucursales.store');
    Route::post('/sucursales/{id}/editar', [SucursalesController::class, 'update'])->middleware('rbac:sucursales,editar')->name('sucursales.update');
    Route::post('/sucursales/{id}/eliminar', [SucursalesController::class, 'destroy'])->middleware('rbac:sucursales,eliminar')->name('sucursales.destroy');
    Route::get('/auditoria', [AuditoriaController::class, 'index'])->middleware('rbac:auditoria,ver')->name('auditoria.index');
    Route::get('/auditoria/data', ModuleDataController::class)->defaults('module', 'auditoria')->middleware('rbac:auditoria,ver')->name('auditoria.data');

    // Debug (solo autenticado): ver RBAC efectivo desde API remota.
    Route::get('/me/rbac', [MeController::class, 'rbac'])->name('me.rbac');
});

require __DIR__.'/auth.php';
