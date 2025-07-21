<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    /**
     * Listar favoritos de um usuário.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->get('user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'user_id is required'
            ], 400);
        }
        $favorites = Favorite::with('vehicle')
            ->where('user_id', $userId)
            ->get();
        return response()->json([
            'success' => true,
            'data' => $favorites,
            'message' => 'Favorites retrieved successfully'
        ]);
    }

    /**
     * Adicionar veículo aos favoritos.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        $data = $validator->validated();
        $favorite = Favorite::firstOrCreate([
            'user_id' => $data['user_id'],
            'vehicle_id' => $data['vehicle_id'],
        ]);
        return response()->json([
            'success' => true,
            'data' => $favorite,
            'message' => 'Vehicle added to favorites'
        ], 201);
    }

    /**
     * Verificar se um veículo está favoritado por um usuário.
     */
    public function show(string $id, Request $request): JsonResponse
    {
        $userId = $request->get('user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'user_id is required'
            ], 400);
        }
        $isFavorited = Favorite::where('user_id', $userId)
            ->where('vehicle_id', $id)
            ->exists();
        return response()->json([
            'success' => true,
            'data' => ['is_favorited' => $isFavorited],
            'message' => 'Favorite status retrieved successfully'
        ]);
    }

    /**
     * Remover veículo dos favoritos.
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $userId = $request->get('user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'user_id is required'
            ], 400);
        }
        $favorite = Favorite::where('user_id', $userId)
            ->where('vehicle_id', $id)
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
            'message' => 'Vehicle removed from favorites'
        ]);
    }

    /**
     * Alternar favorito (toggle).
     */
    public function toggle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        $data = $validator->validated();
        $favorite = Favorite::where('user_id', $data['user_id'])
            ->where('vehicle_id', $data['vehicle_id'])
            ->first();
        if ($favorite) {
            $favorite->delete();
            $status = false;
        } else {
            Favorite::create($data);
            $status = true;
        }
        return response()->json([
            'success' => true,
            'data' => ['is_favorited' => $status],
            'message' => 'Favorite toggled successfully'
        ]);
    }
}
