<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleView;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class VehicleViewController extends Controller
{
    /**
     * Listar visualizações de veículos com filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $query = VehicleView::with(['vehicle', 'user']);

        // Filtros
        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('ip_address')) {
            $query->where('ip_address', $request->ip_address);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $views = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $views->items(),
            'pagination' => [
                'current_page' => $views->currentPage(),
                'last_page' => $views->lastPage(),
                'per_page' => $views->perPage(),
                'total' => $views->total(),
            ],
            'message' => 'Vehicle views retrieved successfully'
        ]);
    }

    /**
     * Registrar nova visualização de veículo.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'user_id' => 'nullable|exists:users,id',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string|max:500',
            'referer' => 'nullable|string|max:500',
            'session_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Se não foi fornecido IP, tentar obter do request
        if (!isset($data['ip_address'])) {
            $data['ip_address'] = $request->ip();
    }

        // Se não foi fornecido User-Agent, tentar obter do request
        if (!isset($data['user_agent'])) {
            $data['user_agent'] = $request->userAgent();
        }

        // Se não foi fornecido Referer, tentar obter do request
        if (!isset($data['referer'])) {
            $data['referer'] = $request->header('referer');
        }

        $view = VehicleView::create($data);

        // Incrementar contador de visualizações do veículo
        $vehicle = Vehicle::find($data['vehicle_id']);
        $vehicle->incrementViews();

        return response()->json([
            'success' => true,
            'data' => $view,
            'message' => 'Vehicle view recorded successfully'
        ], 201);
    }

    /**
     * Visualizar detalhes de uma visualização.
     */
    public function show(string $id): JsonResponse
    {
        $view = VehicleView::with(['vehicle', 'user'])->find($id);

        if (!$view) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle view not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $view,
            'message' => 'Vehicle view retrieved successfully'
        ]);
    }

    /**
     * Atualizar visualização (geralmente não usado, mas mantido para completude).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $view = VehicleView::find($id);

        if (!$view) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle view not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string|max:500',
            'referer' => 'nullable|string|max:500',
            'session_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $view->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $view->fresh(),
            'message' => 'Vehicle view updated successfully'
        ]);
    }

    /**
     * Remover visualização.
     */
    public function destroy(string $id): JsonResponse
    {
        $view = VehicleView::find($id);

        if (!$view) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle view not found'
            ], 404);
        }

        $view->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle view deleted successfully'
        ]);
    }

    /**
     * Obter estatísticas de visualizações.
     */
    public function statistics(): JsonResponse
    {
        $totalViews = VehicleView::count();
        $uniqueVehicles = VehicleView::distinct('vehicle_id')->count();
        $uniqueUsers = VehicleView::whereNotNull('user_id')->distinct('user_id')->count();
        $todayViews = VehicleView::today()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_views' => $totalViews,
                'unique_vehicles_viewed' => $uniqueVehicles,
                'unique_users' => $uniqueUsers,
                'today_views' => $todayViews,
            ],
            'message' => 'View statistics retrieved successfully'
        ]);
    }

    /**
     * Obter veículos mais visualizados.
     */
    public function mostViewed(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $period = $request->get('period', 'all'); // all, today, week, month

        $query = Vehicle::with(['stand', 'primaryImage']);

        switch ($period) {
            case 'today':
                $query->whereHas('views', function ($q) {
                    $q->today();
                });
                break;
            case 'week':
                $query->whereHas('views', function ($q) {
                    $q->where('created_at', '>=', now()->subWeek());
                });
                break;
            case 'month':
                $query->whereHas('views', function ($q) {
                    $q->where('created_at', '>=', now()->subMonth());
                });
                break;
        }

        $vehicles = $query->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles,
            'message' => 'Most viewed vehicles retrieved successfully'
        ]);
    }

    /**
     * Obter analytics de visualizações por período.
     */
    public function analytics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'in:day,week,month,vehicle',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = VehicleView::whereBetween('created_at', [
            $request->start_date,
            $request->end_date . ' 23:59:59'
        ]);

        switch ($request->group_by) {
            case 'day':
                $analytics = $query->selectRaw('DATE(created_at) as date, COUNT(*) as views_count, COUNT(DISTINCT vehicle_id) as unique_vehicles')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
            case 'vehicle':
                $analytics = $query->selectRaw('vehicle_id, COUNT(*) as views_count')
                    ->with('vehicle')
                    ->groupBy('vehicle_id')
                    ->orderBy('views_count', 'desc')
                    ->get();
                break;
            default:
                $analytics = $query->selectRaw('COUNT(*) as total_views, COUNT(DISTINCT vehicle_id) as unique_vehicles, COUNT(DISTINCT user_id) as unique_users')
                    ->first();
        }

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'View analytics retrieved successfully'
        ]);
    }

    /**
     * Obter tendência de visualizações.
     */
    public function trend(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $vehicleId = $request->get('vehicle_id');

        $query = VehicleView::where('created_at', '>=', now()->subDays($days));

        if ($vehicleId) {
            $query->where('vehicle_id', $vehicleId);
        }

        $trend = $query->selectRaw('DATE(created_at) as date, COUNT(*) as views_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trend,
            'message' => 'View trend retrieved successfully'
        ]);
    }

    /**
     * Obter visualizações por veículo.
     */
    public function byVehicle(string $vehicleId): JsonResponse
    {
        $views = VehicleView::with(['user'])
            ->where('vehicle_id', $vehicleId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $totalViews = VehicleView::where('vehicle_id', $vehicleId)->count();
        $uniqueUsers = VehicleView::where('vehicle_id', $vehicleId)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'views' => $views->items(),
                'total_views' => $totalViews,
                'unique_users' => $uniqueUsers,
                'pagination' => [
                    'current_page' => $views->currentPage(),
                    'last_page' => $views->lastPage(),
                    'per_page' => $views->perPage(),
                    'total' => $views->total(),
                ],
            ],
            'message' => 'Vehicle views retrieved successfully'
        ]);
    }

    /**
     * Obter visualizações por usuário.
     */
    public function byUser(string $userId): JsonResponse
    {
        $views = VehicleView::with(['vehicle'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $views->items(),
            'pagination' => [
                'current_page' => $views->currentPage(),
                'last_page' => $views->lastPage(),
                'per_page' => $views->perPage(),
                'total' => $views->total(),
            ],
            'message' => 'User vehicle views retrieved successfully'
        ]);
    }
}
