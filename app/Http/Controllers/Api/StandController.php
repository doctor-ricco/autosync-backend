<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stand;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class StandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Stand::query();

        // Filtros
        if ($request->has('city')) {
            $query->inCity($request->city);
        }

        if ($request->has('active')) {
            $query->active();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('city', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $stands = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $stands->items(),
            'pagination' => [
                'current_page' => $stands->currentPage(),
                'last_page' => $stands->lastPage(),
                'per_page' => $stands->perPage(),
                'total' => $stands->total(),
            ],
            'message' => 'Stands retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:10',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'website' => 'nullable|url|max:255',
            'logo_url' => 'nullable|url|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'business_hours' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Stand::generateSlug($data['name']);

        $stand = Stand::create($data);

        return response()->json([
            'success' => true,
            'data' => $stand,
            'message' => 'Stand created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $stand = Stand::with(['vehicles', 'users', 'inquiries', 'sales'])->find($id);

        if (!$stand) {
            return response()->json([
                'success' => false,
                'message' => 'Stand not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $stand,
            'message' => 'Stand retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $stand = Stand::find($id);

        if (!$stand) {
            return response()->json([
                'success' => false,
                'message' => 'Stand not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'sometimes|required|string',
            'city' => 'sometimes|required|string|max:100',
            'postal_code' => 'sometimes|required|string|max:10',
            'phone' => 'sometimes|required|string|max:20',
            'email' => 'sometimes|required|email|max:255',
            'website' => 'nullable|url|max:255',
            'logo_url' => 'nullable|url|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'business_hours' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Gerar novo slug se o nome foi alterado
        if (isset($data['name']) && $data['name'] !== $stand->name) {
            $data['slug'] = Stand::generateSlug($data['name']);
        }

        $stand->update($data);

        return response()->json([
            'success' => true,
            'data' => $stand->fresh(),
            'message' => 'Stand updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $stand = Stand::find($id);

        if (!$stand) {
            return response()->json([
                'success' => false,
                'message' => 'Stand not found'
            ], 404);
        }

        // Verificar se o stand tem veículos ou usuários associados
        if ($stand->vehicles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete stand with associated vehicles'
            ], 422);
        }

        if ($stand->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete stand with associated users'
            ], 422);
        }

        $stand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stand deleted successfully'
        ]);
    }

    /**
     * Get stands statistics.
     */
    public function statistics(): JsonResponse
    {
        $totalStands = Stand::count();
        $activeStands = Stand::active()->count();
        $totalVehicles = Stand::withCount('vehicles')->get()->sum('vehicles_count');
        $totalSales = Stand::withCount('sales')->get()->sum('sales_count');

        return response()->json([
            'success' => true,
            'data' => [
                'total_stands' => $totalStands,
                'active_stands' => $activeStands,
                'total_vehicles' => $totalVehicles,
                'total_sales' => $totalSales,
            ],
            'message' => 'Statistics retrieved successfully'
        ]);
    }

    /**
     * Get stands by city.
     */
    public function byCity(string $city): JsonResponse
    {
        $stands = Stand::inCity($city)->active()->get();

        return response()->json([
            'success' => true,
            'data' => $stands,
            'message' => "Stands in {$city} retrieved successfully"
        ]);
    }

    /**
     * Get stand with detailed information.
     */
    public function details(string $id): JsonResponse
    {
        $stand = Stand::with([
            'vehicles' => function ($query) {
                $query->available()->orderBy('created_at', 'desc');
            },
            'users' => function ($query) {
                $query->where('role', 'seller')->where('is_active', true);
            },
            'sales' => function ($query) {
                $query->orderBy('sold_at', 'desc');
            }
        ])->find($id);

        if (!$stand) {
            return response()->json([
                'success' => false,
                'message' => 'Stand not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $stand,
            'message' => 'Stand details retrieved successfully'
        ]);
    }
}
