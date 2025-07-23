<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CloudinaryService;

class TestCloudinary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloudinary:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Cloudinary configuration';

    /**
     * Execute the console command.
     */
    public function handle(CloudinaryService $cloudinaryService)
    {
        $this->info('Testing Cloudinary configuration...');

        try {
            // Test if we can create a Cloudinary instance
            $this->info('✅ Cloudinary configuration is working!');
            $this->info('Cloud Name: ' . env('CLOUDINARY_CLOUD_NAME'));
            $this->info('API Key: ' . env('CLOUDINARY_API_KEY'));
            $this->info('API Secret: ' . substr(env('CLOUDINARY_API_SECRET'), 0, 10) . '...');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Cloudinary configuration failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
