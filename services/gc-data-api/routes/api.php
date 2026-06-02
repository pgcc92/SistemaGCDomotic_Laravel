<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthLoginController;
use App\Http\Controllers\Api\V1\AuthMeController;
use App\Http\Controllers\Api\V1\ClientesController;
use App\Http\Controllers\Api\V1\ComisionesController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\PermisosMatrixController;
use App\Http\Controllers\Api\V1\ProductosController;
use App\Http\Controllers\Api\V1\RbacMeController;
use App\Http\Controllers\Api\V1\SoporteVideosController;
use App\Http\Controllers\Api\V1\StockController;
use App\Http\Controllers\Api\V1\SucursalesController;
use App\Http\Controllers\Api\V1\TicketsController;
use App\Http\Controllers\Api\V1\AuditoriaController;
use App\Http\Controllers\Api\V1\UsuariosController;
use App\Http\Controllers\Api\V1\VentasController;
use App\Http\Controllers\Api\V1\PermisosUpdateController;
use App\Http\Controllers\Api\V1\DispositivosController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\BrandingController;
use App\Http\Controllers\Api\V1\RolesController;
use App\Http\Controllers\Api\V1\AgendaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')
    ->group(function () {
        // Login: solo rate limit específico. No aplicamos el throttle global para evitar bloqueos "pegajosos" en dev.
        Route::post('/auth/login', AuthLoginController::class)->middleware(['api.auth', 'throttle:auth_login']);

        Route::middleware(['api.auth'])->group(function () {
            Route::get('/health', HealthController::class);
            Route::get('/auth/me', AuthMeController::class);
            Route::get('/rbac/me', RbacMeController::class);
            Route::get('/dashboard', DashboardController::class);
            Route::get('/branding', [BrandingController::class, 'show']);
            Route::put('/branding', [BrandingController::class, 'update']);

            Route::get('/roles', [RolesController::class, 'index']);
            Route::post('/roles', [RolesController::class, 'store']);
            Route::put('/roles/{id}', [RolesController::class, 'update']);
            Route::delete('/roles/{id}', [RolesController::class, 'destroy']);

            Route::get('/agenda', [AgendaController::class, 'index']);
            Route::get('/agenda/{id}', [AgendaController::class, 'show']);
            Route::post('/agenda', [AgendaController::class, 'store']);
            Route::put('/agenda/{id}', [AgendaController::class, 'update']);
            Route::delete('/agenda/{id}', [AgendaController::class, 'destroy']);

            Route::get('/ventas', [VentasController::class, 'index']);
            Route::get('/ventas/{id}', [VentasController::class, 'show']);
            Route::post('/ventas', [VentasController::class, 'store']);
            Route::get('/ventas/stats', [VentasController::class, 'stats']);
            Route::get('/ventas/next-correlativo', [VentasController::class, 'nextCorrelativo']);
            Route::post('/ventas/{id}/pagar', [VentasController::class, 'pagar']);
            Route::post('/ventas/{id}/anular', [VentasController::class, 'anular']);

            Route::get('/stock', StockController::class);
            Route::get('/productos', [ProductosController::class, 'index']);
            Route::get('/productos/stock-data', [ProductosController::class, 'stockData']);
            Route::get('/productos/kardex-data', [ProductosController::class, 'kardexData']);
            Route::post('/productos/movimiento-stock', [ProductosController::class, 'movimiento']);
            Route::post('/productos/import', [ProductosController::class, 'import']);
            Route::get('/productos/{id}', [ProductosController::class, 'show']);
            Route::post('/productos', [ProductosController::class, 'store']);
            Route::put('/productos/{id}', [ProductosController::class, 'update']);
            Route::delete('/productos/{id}', [ProductosController::class, 'destroy']);
            Route::get('/clientes', [ClientesController::class, 'index']);
            Route::get('/clientes/{id}', [ClientesController::class, 'show']);
            Route::post('/clientes', [ClientesController::class, 'store']);
            Route::put('/clientes/{id}', [ClientesController::class, 'update']);
            Route::delete('/clientes/{id}', [ClientesController::class, 'destroy']);

            Route::get('/tickets', [TicketsController::class, 'index']);
            Route::post('/tickets', [TicketsController::class, 'store']);
            Route::patch('/tickets/{id}', [TicketsController::class, 'update']);
            Route::get('/tickets/{ticketId}', [TicketsController::class, 'show']);
            Route::post('/tickets/{ticketId}/asignar', [TicketsController::class, 'asignar']);
            Route::post('/tickets/{ticketId}/cerrar', [TicketsController::class, 'cerrar']);

            Route::get('/comisiones', [ComisionesController::class, 'index']);
            Route::get('/comisiones/ventas-periodo', [ComisionesController::class, 'ventasPeriodo']);
            Route::post('/comisiones/{id}/aprobar', [ComisionesController::class, 'aprobar']);
            Route::post('/comisiones/aprobar', [ComisionesController::class, 'aprobarBulk']);
            Route::post('/comisiones/pagar', [ComisionesController::class, 'pagar']);
            Route::post('/comisiones/liquidar', [ComisionesController::class, 'liquidar']);
            Route::get('/comisiones/export', [ComisionesController::class, 'export']);
            Route::get('/soporte-videos', [SoporteVideosController::class, 'index']);
            Route::get('/soporte-videos/{id}', [SoporteVideosController::class, 'show']);
            Route::get('/sucursales', [SucursalesController::class, 'index']);
            Route::post('/sucursales', [SucursalesController::class, 'store']);
            Route::put('/sucursales/{id}', [SucursalesController::class, 'update']);
            Route::delete('/sucursales/{id}', [SucursalesController::class, 'destroy']);
            Route::get('/auditoria', [AuditoriaController::class, 'index']);
            Route::get('/usuarios', [UsuariosController::class, 'index']);
            Route::post('/usuarios', [UsuariosController::class, 'store']);
            Route::put('/usuarios/{id}', [UsuariosController::class, 'update']);
            Route::delete('/usuarios/{id}', [UsuariosController::class, 'destroy']);
            Route::get('/usuarios/{id}/permisos', [UsuariosController::class, 'permisos']);
            Route::post('/usuarios/{id}/permisos', [UsuariosController::class, 'permisosUpdate']);
            Route::get('/permisos/matrix', PermisosMatrixController::class);
            Route::post('/permisos', PermisosUpdateController::class);

            Route::get('/dispositivos', [DispositivosController::class, 'index']);
            Route::get('/dispositivos/{id}', [DispositivosController::class, 'show']);
            Route::post('/dispositivos', [DispositivosController::class, 'store']);
        });
    });
