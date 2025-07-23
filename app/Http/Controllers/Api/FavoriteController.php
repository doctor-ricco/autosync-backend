<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Get user's favorites.
     */
    public function index(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        
        $userId = Auth::id();
        
        $favorites = Favorite::with(['vehicle.stand', 'vehicle.images' => function($query) {
            $query->orderBy('is_primary', 'desc')->orderBy('order_index', 'asc');
        }])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $favorites->map(function ($favorite) {
                return $favorite->vehicle;
            }),
            'message' => 'Favorites retrieved successfully'
        ]);
    }

    /**
     * Add vehicle to favorites.
     */
    public function store(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = Auth::id();
        $vehicleId = $request->vehicle_id;

        // Verificar se já existe
        $existingFavorite = Favorite::where('user_id', $userId)
            ->where('vehicle_id', $vehicleId)
            ->first();

        if ($existingFavorite) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle already in favorites'
            ], 409);
        }

        $favorite = Favorite::create([
            'user_id' => $userId,
            'vehicle_id' => $vehicleId,
        ]);

        return response()->json([
            'success' => true,
            'data' => $favorite->load('vehicle'),
            'message' => 'Vehicle added to favorites successfully'
        ], 201);
    }

    /**
     * Remove vehicle from favorites.
     */
    public function destroy(Request $request, string $vehicleId): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $userId = Auth::id();

        $favorite = Favorite::where('user_id', $userId)
            ->where('vehicle_id', $vehicleId)
            ->first();

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Favorite not found'
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle removed from favorites successfully'
        ]);
    }

    /**
     * Check if vehicle is in favorites.
     */
    public function check(Request $request, string $vehicleId): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $userId = Auth::id();

        $favorite = Favorite::where('user_id', $userId)
            ->where('vehicle_id', $vehicleId)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'is_favorite' => $favorite
            ],
            'message' => 'Favorite status checked successfully'
        ]);
    }

    /**
     * Toggle favorite status.
     */
    public function toggle(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = Auth::id();
        $vehicleId = $request->vehicle_id;

        $favorite = Favorite::where('user_id', $userId)
            ->where('vehicle_id', $vehicleId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $isFavorite = false;
            $message = 'Vehicle removed from favorites';
        } else {
            Favorite::create([
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
            ]);
            $isFavorite = true;
            $message = 'Vehicle added to favorites';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_favorite' => $isFavorite
            ],
            'message' => $message
        ]);
    }
}
