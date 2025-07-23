<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\VehicleImageController;
use App\Http\Controllers\Api\StandController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\VehicleViewController;
use App\Http\Controllers\Api\AuditLogController;

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

// ============================================================================
// ROTAS PÚBLICAS (Sem autenticação)
// ============================================================================

// Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});

// ============================================================================
// ROTAS DE AUTENTICAÇÃO
// ============================================================================

Route::prefix('auth')->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [UserController::class, 'me'])->middleware('auth:sanctum');
    Route::post('/refresh', [UserController::class, 'refresh'])->middleware('auth:sanctum');
});

// ============================================================================
// ROTAS PÚBLICAS DE VEÍCULOS (Para Frontend)
// ============================================================================

Route::prefix('vehicles')->group(function () {
    // Listar veículos (público)
    Route::get('/', [VehicleController::class, 'index']);
    
    // Detalhes do veículo (público)
    Route::get('/{id}', [VehicleController::class, 'show']);
    
    // Veículos em destaque (público)
    Route::get('/featured/list', [VehicleController::class, 'featured']);
    
    // Veículos por marca (público)
    Route::get('/brand/{brand}', [VehicleController::class, 'byBrand']);
    
    // Veículos por faixa de preço (público)
    Route::get('/price-range', [VehicleController::class, 'byPriceRange']);
    
    // Veículos mais vistos (público)
    Route::get('/most-viewed', [VehicleController::class, 'mostViewed']);
    
    // Estatísticas de veículos (público)
    Route::get('/statistics/overview', [VehicleController::class, 'statistics']);
    
    // Busca avançada (público)
    Route::get('/search/advanced', [VehicleController::class, 'advancedSearch']);
    
    // Sugestões de busca (público)
    Route::get('/search/suggestions', [VehicleController::class, 'searchSuggestions']);
});

// ============================================================================
// ROTAS PÚBLICAS DE STANDS (Para Frontend)
// ============================================================================

Route::prefix('stands')->group(function () {
    // Listar stands (público)
    Route::get('/', [StandController::class, 'index']);
    
    // Detalhes do stand (público)
    Route::get('/{id}', [StandController::class, 'show']);
    
    // Stands por cidade (público)
    Route::get('/city/{city}', [StandController::class, 'byCity']);
    
    // Estatísticas do stand (público)
    Route::get('/{id}/statistics', [StandController::class, 'statistics']);
});

// ============================================================================
// ROTAS PÚBLICAS DE INQUÉRITOS (Para Frontend)
// ============================================================================

Route::prefix('inquiries')->group(function () {
    // Criar inquérito (público)
    Route::post('/', [InquiryController::class, 'store']);
    
    // Verificar status do inquérito (público)
    Route::get('/{id}/status', [InquiryController::class, 'checkStatus']);
});

// ============================================================================
// ROTAS PROTEGIDAS (Com autenticação)
// ============================================================================

Route::middleware('auth:sanctum')->group(function () {
    
    // ============================================================================
    // ROTAS DE VEÍCULOS (Admin/Manager/Seller)
    // ============================================================================
    
    Route::prefix('vehicles')->group(function () {
        // CRUD completo (Admin/Manager)
        Route::post('/', [VehicleController::class, 'store'])->middleware('can:create,App\Models\Vehicle');
        Route::put('/{id}', [VehicleController::class, 'update'])->middleware('can:update,App\Models\Vehicle');
        Route::delete('/{id}', [VehicleController::class, 'destroy'])->middleware('can:delete,App\Models\Vehicle');
        
        // Gestão de imagens (Admin/Manager)
        Route::post('/{id}/images', [VehicleImageController::class, 'upload'])->middleware('can:update,App\Models\Vehicle');
        Route::get('/{id}/images', [VehicleImageController::class, 'index'])->middleware('can:view,App\Models\Vehicle');
        Route::delete('/{id}/images/{imageId}', [VehicleImageController::class, 'destroy'])->middleware('can:update,App\Models\Vehicle');
        Route::put('/{id}/images/{imageId}/primary', [VehicleImageController::class, 'setPrimary'])->middleware('can:update,App\Models\Vehicle');
        Route::put('/{id}/images/reorder', [VehicleImageController::class, 'reorder'])->middleware('can:update,App\Models\Vehicle');
        
        // Gestão de status (Admin/Manager/Seller)
        Route::patch('/{id}/status', [VehicleController::class, 'updateStatus'])->middleware('can:update,App\Models\Vehicle');
        Route::patch('/{id}/featured', [VehicleController::class, 'toggleFeatured'])->middleware('can:update,App\Models\Vehicle');
        
        // Analytics detalhados (Admin/Manager)
        Route::get('/analytics/views', [VehicleController::class, 'viewsAnalytics'])->middleware('can:view,App\Models\Vehicle');
        Route::get('/analytics/performance', [VehicleController::class, 'performanceAnalytics'])->middleware('can:view,App\Models\Vehicle');
    });
    
    // ============================================================================
    // ROTAS DE STANDS (Admin)
    // ============================================================================
    
    Route::prefix('stands')->group(function () {
        // CRUD completo (Admin)
        Route::post('/', [StandController::class, 'store'])->middleware('can:create,App\Models\Stand');
        Route::put('/{id}', [StandController::class, 'update'])->middleware('can:update,App\Models\Stand');
        Route::delete('/{id}', [StandController::class, 'destroy'])->middleware('can:delete,App\Models\Stand');
        
        // Gestão de horários (Admin)
        Route::put('/{id}/business-hours', [StandController::class, 'updateBusinessHours'])->middleware('can:update,App\Models\Stand');
        
        // Analytics do stand (Admin/Manager)
        Route::get('/{id}/analytics/sales', [StandController::class, 'salesAnalytics'])->middleware('can:view,App\Models\Stand');
        Route::get('/{id}/analytics/vehicles', [StandController::class, 'vehiclesAnalytics'])->middleware('can:view,App\Models\Stand');
    });
    
    // ============================================================================
    // ROTAS DE UTILIZADORES (Admin)
    // ============================================================================
    
    Route::prefix('users')->group(function () {
        // CRUD completo (Admin)
        Route::get('/', [UserController::class, 'index'])->middleware('can:viewAny,App\Models\User');
        Route::post('/', [UserController::class, 'store'])->middleware('can:create,App\Models\User');
        Route::get('/{id}', [UserController::class, 'show'])->middleware('can:view,App\Models\User');
        Route::put('/{id}', [UserController::class, 'update'])->middleware('can:update,App\Models\User');
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('can:delete,App\Models\User');
        
        // Gestão de perfil (próprio utilizador)
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::put('/profile/password', [UserController::class, 'updatePassword']);
        Route::put('/profile/avatar', [UserController::class, 'updateAvatar']);
        
        // Analytics de performance (Admin/Manager)
        Route::get('/{id}/analytics/performance', [UserController::class, 'performanceAnalytics'])->middleware('can:view,App\Models\User');
        Route::get('/{id}/analytics/sales', [UserController::class, 'salesAnalytics'])->middleware('can:view,App\Models\User');
    });
    
    // ============================================================================
    // ROTAS DE FAVORITOS (Utilizador autenticado)
    // ============================================================================
    
    Route::prefix('favorites')->group(function () {
        // Listar favoritos do utilizador
        Route::get('/', [FavoriteController::class, 'index']);
        
        // Adicionar aos favoritos
        Route::post('/', [FavoriteController::class, 'store']);
        
        // Remover dos favoritos
        Route::delete('/{vehicleId}', [FavoriteController::class, 'destroy']);
        
        // Toggle favorito
        Route::post('/{vehicleId}/toggle', [FavoriteController::class, 'toggle']);
        
        // Verificar se é favorito
        Route::get('/{vehicleId}/check', [FavoriteController::class, 'check']);
    });
    
    // ============================================================================
    // ROTAS DE INQUÉRITOS (Admin/Manager/Seller)
    // ============================================================================
    
    Route::prefix('inquiries')->group(function () {
        // Listar inquéritos (Admin/Manager/Seller)
        Route::get('/', [InquiryController::class, 'index'])->middleware('can:view,App\Models\Inquiry');
        
        // Detalhes do inquérito (Admin/Manager/Seller)
        Route::get('/{id}', [InquiryController::class, 'show'])->middleware('can:view,App\Models\Inquiry');
        
        // Atualizar inquérito (Admin/Manager/Seller)
        Route::put('/{id}', [InquiryController::class, 'update'])->middleware('can:update,App\Models\Inquiry');
        
        // Atribuir inquérito (Admin/Manager)
        Route::put('/{id}/assign', [InquiryController::class, 'assign'])->middleware('can:update,App\Models\Inquiry');
        
        // Adicionar notas (Admin/Manager/Seller)
        Route::post('/{id}/notes', [InquiryController::class, 'addNote'])->middleware('can:update,App\Models\Inquiry');
        
        // Analytics de inquéritos (Admin/Manager)
        Route::get('/analytics/overview', [InquiryController::class, 'analytics'])->middleware('can:view,App\Models\Inquiry');
        Route::get('/analytics/conversion', [InquiryController::class, 'conversionAnalytics'])->middleware('can:view,App\Models\Inquiry');
    });
    
    // ============================================================================
    // ROTAS DE VENDAS (Admin/Manager/Seller)
    // ============================================================================
    
    Route::prefix('sales')->group(function () {
        // Listar vendas (Admin/Manager/Seller)
        Route::get('/', [SaleController::class, 'index'])->middleware('can:view,App\Models\Sale');
        
        // Detalhes da venda (Admin/Manager/Seller)
        Route::get('/{id}', [SaleController::class, 'show'])->middleware('can:view,App\Models\Sale');
        
        // Criar venda (Admin/Manager/Seller)
        Route::post('/', [SaleController::class, 'store'])->middleware('can:create,App\Models\Sale');
        
        // Atualizar venda (Admin/Manager)
        Route::put('/{id}', [SaleController::class, 'update'])->middleware('can:update,App\Models\Sale');
        
        // Cancelar venda (Admin)
        Route::delete('/{id}', [SaleController::class, 'destroy'])->middleware('can:delete,App\Models\Sale');
        
        // Analytics de vendas (Admin/Manager)
        Route::get('/analytics/overview', [SaleController::class, 'analytics'])->middleware('can:view,App\Models\Sale');
        Route::get('/analytics/performance', [SaleController::class, 'performanceAnalytics'])->middleware('can:view,App\Models\Sale');
        Route::get('/analytics/commission', [SaleController::class, 'commissionAnalytics'])->middleware('can:view,App\Models\Sale');
    });
    
    // ============================================================================
    // ROTAS DE VISUALIZAÇÕES (Admin/Manager)
    // ============================================================================
    
    Route::prefix('vehicle-views')->group(function () {
        // Registrar visualização
        Route::post('/', [VehicleViewController::class, 'store']);
        
        // Analytics de visualizações (Admin/Manager)
        Route::get('/analytics/overview', [VehicleViewController::class, 'analytics'])->middleware('can:view,App\Models\VehicleView');
        Route::get('/analytics/trends', [VehicleViewController::class, 'trends'])->middleware('can:view,App\Models\VehicleView');
        Route::get('/analytics/devices', [VehicleViewController::class, 'deviceAnalytics'])->middleware('can:view,App\Models\VehicleView');
    });
    
    // ============================================================================
    // ROTAS DE AUDITORIA (Admin)
    // ============================================================================
    
    Route::prefix('audit-logs')->group(function () {
        // Listar logs (Admin)
        Route::get('/', [AuditLogController::class, 'index'])->middleware('can:view,App\Models\AuditLog');
        
        // Detalhes do log (Admin)
        Route::get('/{id}', [AuditLogController::class, 'show'])->middleware('can:view,App\Models\AuditLog');
        
        // Analytics de auditoria (Admin)
        Route::get('/analytics/activity', [AuditLogController::class, 'activityAnalytics'])->middleware('can:view,App\Models\AuditLog');
        Route::get('/analytics/changes', [AuditLogController::class, 'changesAnalytics'])->middleware('can:view,App\Models\AuditLog');
    });
    
    // ============================================================================
    // ROTAS DE RELATÓRIOS (Admin/Manager)
    // ============================================================================
    
    Route::prefix('reports')->group(function () {
        // Relatório de vendas
        Route::get('/sales', [SaleController::class, 'salesReport'])->middleware('can:view,App\Models\Sale');
        
        // Relatório de performance
        Route::get('/performance', [UserController::class, 'performanceReport'])->middleware('can:view,App\Models\User');
        
        // Relatório de estoque
        Route::get('/inventory', [VehicleController::class, 'inventoryReport'])->middleware('can:view,App\Models\Vehicle');
        
        // Relatório de leads
        Route::get('/leads', [InquiryController::class, 'leadsReport'])->middleware('can:view,App\Models\Inquiry');
    });
});

// ============================================================================
// ROTA DE FALLBACK (404 para API)
// ============================================================================

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'error' => 'Not Found'
    ], 404);
}); 