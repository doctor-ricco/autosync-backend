<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VehicleImageController extends Controller
{
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    /**
     * Upload images for a vehicle
     */
    public function upload(Request $request, $vehicleId): JsonResponse
    {
        // Validate vehicle exists
        $vehicle = Vehicle::find($vehicleId);
        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Veículo não encontrado'
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $uploadedImages = [];
            $files = $request->file('images');

            foreach ($files as $index => $file) {
                // Upload to Cloudinary
                $result = $this->cloudinaryService->uploadImage($file, "autosync/vehicles/{$vehicleId}");

                if (!$result['success']) {
                    throw new \Exception('Erro ao fazer upload da imagem: ' . $result['error']);
                }

                // Create database record
                $image = VehicleImage::create([
                    'vehicle_id' => $vehicleId,
                    'cloudinary_id' => $result['cloudinary_id'],
                    'url' => $result['url'],
                    'alt_text' => $request->input("alt_text.{$index}"),
                    'is_primary' => $index === 0, // First image is primary
                    'order_index' => $index,
                    'file_size' => $result['bytes'],
                    'width' => $result['width'],
                    'height' => $result['height'],
                ]);

                $uploadedImages[] = $image;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Imagens carregadas com sucesso',
                'data' => $uploadedImages
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar imagens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get images for a vehicle
     */
    public function index($vehicleId): JsonResponse
    {
        $vehicle = Vehicle::find($vehicleId);
        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Veículo não encontrado'
            ], 404);
        }

        $images = $vehicle->images()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $images
        ]);
    }

    /**
     * Delete an image
     */
    public function destroy($vehicleId, $imageId): JsonResponse
    {
        $image = VehicleImage::where('vehicle_id', $vehicleId)
            ->where('id', $imageId)
            ->first();

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Imagem não encontrada'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Delete from Cloudinary
            if ($image->cloudinary_id) {
                $result = $this->cloudinaryService->deleteImage($image->cloudinary_id);
                if (!$result['success']) {
                    // Log error but continue with database deletion
                    \Log::warning('Failed to delete from Cloudinary: ' . $result['error']);
                }
            }

            // Delete from database
            $image->delete();

            // If this was the primary image, set the next one as primary
            if ($image->is_primary) {
                $nextImage = VehicleImage::where('vehicle_id', $vehicleId)
                    ->where('id', '!=', $imageId)
                    ->ordered()
                    ->first();

                if ($nextImage) {
                    $nextImage->update(['is_primary' => true]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Imagem eliminada com sucesso'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao eliminar imagem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set image as primary
     */
    public function setPrimary($vehicleId, $imageId): JsonResponse
    {
        $image = VehicleImage::where('vehicle_id', $vehicleId)
            ->where('id', $imageId)
            ->first();

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Imagem não encontrada'
            ], 404);
        }

        try {
            $image->setAsPrimary();

            return response()->json([
                'success' => true,
                'message' => 'Imagem definida como principal'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao definir imagem como principal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder images
     */
    public function reorder(Request $request, $vehicleId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image_ids' => 'required|array',
            'image_ids.*' => 'required|integer|exists:vehicle_images,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($request->image_ids as $index => $imageId) {
                VehicleImage::where('id', $imageId)
                    ->where('vehicle_id', $vehicleId)
                    ->update(['order_index' => $index]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ordem das imagens atualizada'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao reordenar imagens: ' . $e->getMessage()
            ], 500);
        }
    }
}
