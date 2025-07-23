<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Services\CloudinaryService;
use Illuminate\Http\UploadedFile;

class UploadTestImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:upload-test {vehicle_id} {image_path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload test images for a vehicle';

    /**
     * Execute the console command.
     */
    public function handle(CloudinaryService $cloudinaryService)
    {
        $vehicleId = $this->argument('vehicle_id');
        $imagePath = $this->argument('image_path');

        // Verificar se o veículo existe
        $vehicle = Vehicle::find($vehicleId);
        if (!$vehicle) {
            $this->error("Veículo com ID {$vehicleId} não encontrado!");
            return Command::FAILURE;
        }

        // Verificar se o arquivo existe
        if (!file_exists($imagePath)) {
            $this->error("Arquivo não encontrado: {$imagePath}");
            return Command::FAILURE;
        }

        try {
            $this->info("Fazendo upload da imagem para o veículo: {$vehicle->brand} {$vehicle->model}");

            // Criar um UploadedFile a partir do arquivo local
            $uploadedFile = new UploadedFile(
                $imagePath,
                basename($imagePath),
                mime_content_type($imagePath),
                null,
                true
            );

            // Fazer upload para o Cloudinary
            $result = $cloudinaryService->uploadImage($uploadedFile, "autosync/vehicles/{$vehicleId}");

            if (!$result['success']) {
                $this->error("Erro no upload: " . $result['error']);
                return Command::FAILURE;
            }

            // Verificar se já existe uma imagem primária
            $isPrimary = !$vehicle->images()->where('is_primary', true)->exists();
            $orderIndex = $vehicle->images()->count();

            // Criar registro na base de dados
            $image = VehicleImage::create([
                'vehicle_id' => $vehicleId,
                'cloudinary_id' => $result['cloudinary_id'],
                'url' => $result['url'],
                'alt_text' => "{$vehicle->brand} {$vehicle->model} {$vehicle->year}",
                'is_primary' => $isPrimary,
                'order_index' => $orderIndex,
                'file_size' => $result['bytes'],
                'width' => $result['width'],
                'height' => $result['height'],
            ]);

            $this->info("✅ Imagem carregada com sucesso!");
            $this->info("Cloudinary ID: {$result['cloudinary_id']}");
            $this->info("URL: {$result['url']}");
            $this->info("É primária: " . ($isPrimary ? 'Sim' : 'Não'));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Erro: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
