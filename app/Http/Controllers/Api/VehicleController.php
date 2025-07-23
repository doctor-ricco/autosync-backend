<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::with(['stand', 'images', 'primaryImage']);

        // Filtros básicos
        if ($request->has('stand_id')) {
            $query->where('stand_id', $request->stand_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('brand')) {
            $query->byBrand($request->brand);
        }

        if ($request->has('fuel_type')) {
            $query->byFuelType($request->fuel_type);
        }

        if ($request->has('transmission')) {
            $query->byTransmission($request->transmission);
        }

        if ($request->has('featured')) {
            $query->featured();
        }

        if ($request->has('new')) {
            $query->new();
        }

        // Filtros de preço
        if ($request->has('min_price') && $request->has('max_price')) {
            $query->priceRange($request->min_price, $request->max_price);
        }

        // Filtros de ano
        if ($request->has('min_year') && $request->has('max_year')) {
            $query->yearRange($request->min_year, $request->max_year);
        }

        // Filtros de quilometragem
        if ($request->has('min_mileage') && $request->has('max_mileage')) {
            $query->mileageRange($request->min_mileage, $request->max_mileage);
        }

        // Busca por texto
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'ilike', "%{$search}%")
                  ->orWhere('model', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%")
                  ->orWhere('reference', 'ilike', "%{$search}%");
            });
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $vehicles = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $vehicles->items(),
            'pagination' => [
                'current_page' => $vehicles->currentPage(),
                'last_page' => $vehicles->lastPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total(),
            ],
            'message' => 'Vehicles retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'stand_id' => 'required|exists:stands,id',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'mileage' => 'required|integer|min:0',
            'fuel_type' => 'required|in:gasoline,diesel,hybrid,electric,lpg',
            'transmission' => 'required|in:manual,automatic,semi_automatic',
            'engine_size' => 'nullable|numeric|min:0.1|max:10.0',
            'power_hp' => 'nullable|integer|min:1',
            'doors' => 'integer|min:2|max:5',
            'seats' => 'integer|min:1|max:9',
            'color' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'status' => 'in:available,sold,reserved,maintenance',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['reference'] = Vehicle::generateReference();

        $vehicle = Vehicle::create($data);

        return response()->json([
            'success' => true,
            'data' => $vehicle->load(['stand', 'images']),
            'message' => 'Vehicle created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $vehicle = Vehicle::with([
            'stand',
            'images' => function ($query) {
                $query->ordered();
            },
            'favorites',
            'inquiries',
            'sale',
            'views'
        ])->find($id);

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found'
            ], 404);
        }

        // Incrementar contador de visualizações
        $vehicle->incrementViews();

        return response()->json([
            'success' => true,
            'data' => $vehicle,
            'message' => 'Vehicle retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'stand_id' => 'sometimes|required|exists:stands,id',
            'brand' => 'sometimes|required|string|max:100',
            'model' => 'sometimes|required|string|max:100',
            'year' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
            'mileage' => 'sometimes|required|integer|min:0',
            'fuel_type' => 'sometimes|required|in:gasoline,diesel,hybrid,electric,lpg',
            'transmission' => 'sometimes|required|in:manual,automatic,semi_automatic',
            'engine_size' => 'nullable|numeric|min:0.1|max:10.0',
            'power_hp' => 'nullable|integer|min:1',
            'doors' => 'integer|min:2|max:5',
            'seats' => 'integer|min:1|max:9',
            'color' => 'sometimes|required|string|max:50',
            'price' => 'sometimes|required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'status' => 'in:available,sold,reserved,maintenance',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vehicle->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $vehicle->fresh()->load(['stand', 'images']),
            'message' => 'Vehicle updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found'
            ], 404);
        }

        // Verificar se o veículo foi vendido
        if ($vehicle->isSold()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete sold vehicle'
            ], 422);
        }

        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle deleted successfully'
        ]);
    }

    /**
     * Get featured vehicles.
     */
    public function featured(): JsonResponse
    {
        $vehicles = Vehicle::with(['stand', 'images', 'primaryImage'])
            ->featured()
            ->available()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles,
            'message' => 'Featured vehicles retrieved successfully'
        ]);
    }

    /**
     * Get vehicles by brand.
     */
    public function byBrand(string $brand): JsonResponse
    {
        $vehicles = Vehicle::with(['stand', 'primaryImage'])
            ->byBrand($brand)
            ->available()
            ->orderBy('price', 'asc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $vehicles->items(),
            'pagination' => [
                'current_page' => $vehicles->currentPage(),
                'last_page' => $vehicles->lastPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total(),
            ],
            'message' => "Vehicles by brand {$brand} retrieved successfully"
        ]);
    }

    /**
     * Get vehicles statistics.
     */
    public function statistics(): JsonResponse
    {
        $totalVehicles = Vehicle::count();
        $availableVehicles = Vehicle::available()->count();
        $soldVehicles = Vehicle::where('status', 'sold')->count();
        $featuredVehicles = Vehicle::featured()->count();
        $totalViews = Vehicle::sum('views_count');

        return response()->json([
            'success' => true,
            'data' => [
                'total_vehicles' => $totalVehicles,
                'available_vehicles' => $availableVehicles,
                'sold_vehicles' => $soldVehicles,
                'featured_vehicles' => $featuredVehicles,
                'total_views' => $totalViews,
            ],
            'message' => 'Vehicle statistics retrieved successfully'
        ]);
    }

    /**
     * Get vehicles by price range.
     */
    public function byPriceRange(Request $request): JsonResponse
    {
        $minPrice = $request->get('min_price', 0);
        $maxPrice = $request->get('max_price', 1000000);

        $vehicles = Vehicle::with(['stand', 'primaryImage'])
            ->priceRange($minPrice, $maxPrice)
            ->available()
            ->orderBy('price', 'asc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $vehicles->items(),
            'pagination' => [
                'current_page' => $vehicles->currentPage(),
                'last_page' => $vehicles->lastPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total(),
            ],
            'message' => "Vehicles in price range €{$minPrice} - €{$maxPrice} retrieved successfully"
        ]);
    }

    /**
     * Get most viewed vehicles.
     */
    public function mostViewed(): JsonResponse
    {
        $vehicles = Vehicle::with(['stand', 'primaryImage'])
            ->orderBy('views_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles,
            'message' => 'Most viewed vehicles retrieved successfully'
        ]);
    }
}
