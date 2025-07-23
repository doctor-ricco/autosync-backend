<?php

namespace App\Services;

use Cloudinary\Cloudinary as CloudinarySDK;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    protected $cloudinary;

    public function __construct(CloudinarySDK $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    /**
     * Upload a single image to Cloudinary
     */
    public function uploadImage(UploadedFile $file, string $folder = 'autosync/vehicles'): array
    {
        try {
            $result = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                [
                    'folder' => $folder,
                    'resource_type' => 'image',
                    'transformation' => [
                        'width' => 800,
                        'height' => 600,
                        'crop' => 'fill',
                        'quality' => 'auto',
                        'format' => 'auto'
                    ]
                ]
            );

            return [
                'success' => true,
                'cloudinary_id' => $result['public_id'],
                'url' => $result['secure_url'],
                'width' => $result['width'],
                'height' => $result['height'],
                'format' => $result['format'],
                'bytes' => $result['bytes']
            ];
        } catch (\Exception $e) {
            Log::error('Cloudinary upload failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload multiple images to Cloudinary
     */
    public function uploadMultipleImages(array $files, string $folder = 'autosync/vehicles'): array
    {
        $results = [];
        
        foreach ($files as $file) {
            $results[] = $this->uploadImage($file, $folder);
        }

        return $results;
    }

    /**
     * Delete an image from Cloudinary
     */
    public function deleteImage(string $cloudinaryId): array
    {
        try {
            $result = $this->cloudinary->uploadApi()->destroy($cloudinaryId);
            
            return [
                'success' => true,
                'result' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Cloudinary delete failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get optimized image URL
     */
    public function getOptimizedUrl(string $cloudinaryId, array $transformations = []): string
    {
        $defaultTransformations = [
            'width' => 400,
            'height' => 300,
            'crop' => 'fill',
            'quality' => 'auto',
            'format' => 'auto'
        ];

        $transformations = array_merge($defaultTransformations, $transformations);
        
        return $this->cloudinary->image($cloudinaryId)
            ->resize($transformations['crop'], $transformations['width'], $transformations['height'])
            ->delivery('quality', $transformations['quality'])
            ->delivery('format', $transformations['format'])
            ->toUrl();
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrl(string $cloudinaryId): string
    {
        return $this->getOptimizedUrl($cloudinaryId, [
            'width' => 200,
            'height' => 150
        ]);
    }

    /**
     * Get full size URL
     */
    public function getFullSizeUrl(string $cloudinaryId): string
    {
        return $this->getOptimizedUrl($cloudinaryId, [
            'width' => 1200,
            'height' => 800
        ]);
    }
} 