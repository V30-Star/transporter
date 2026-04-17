<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected Client $client;

    protected Drive $drive;

    public function __construct()
    {
        $this->client = new Client;
        $path = storage_path('app/google-drive-credentials.json');

        if (!file_exists($path)) {
            throw new \Exception("Kredensial tidak ditemukan.");
        }

        $this->client->setAuthConfig($path);
        $this->client->setScopes(Drive::DRIVE_FILE);

        // --- TAMBAHKAN KODE INI UNTUK BYPASS SSL ---
        $httpClient = new \GuzzleHttp\Client([
            'verify' => false, // Mengabaikan verifikasi SSL
        ]);
        $this->client->setHttpClient($httpClient);
        // ------------------------------------------

        $this->drive = new Drive($this->client);
    }

    public function uploadImage(Request $request, string $fileInputName = 'image'): ?string
    {
        if (! $request->hasFile($fileInputName)) {
            return null;
        }

        $file = $request->file($fileInputName);

        if (! $file->isValid()) {
            return null;
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (! in_array($file->getMimeType(), $allowedMimeTypes)) {
            return null;
        }

        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->getPathname();

        $folderId = $this->getOrCreateProductFolder();

        $fileMetadata = new Drive\DriveFile([
            'name' => $fileName,
            'parents' => [$folderId],
        ]);

        $content = file_get_contents($filePath);

        try {
            $uploadedFile = $this->drive->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $file->getMimeType(),
                'uploadType' => 'multipart',
            ]);

            $this->setFilePublic($uploadedFile->getId());

            return $uploadedFile->getId();
        } catch (\Exception $e) {
            // Log ini akan sangat membantu kita tahu titik gagalnya
            \Log::error('Google Drive Detail Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return null;
        }
    }

    protected function getOrCreateProductFolder(): string
    {
        return env('GOOGLE_DRIVE_FOLDER_ID', '155oOIQKOmTkyOvtoyvPVw2RVQU16_eHy');
    }
    
    protected function setFilePublic(string $fileId): void
    {
        try {
            $this->drive->permissions->create($fileId, new Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]));
        } catch (\Exception $e) {
            Log::warning('Could not set file public: ' . $e->getMessage());
        }
    }

    public function getImageUrl(string $fileId): string
    {
        return 'https://drive.google.com/uc?id=' . $fileId . '&export=view';
    }

    public function deleteImage(string $fileId): bool
    {
        try {
            $this->drive->files->delete($fileId);

            return true;
        } catch (\Exception $e) {
            Log::error('Google Drive Delete Error: ' . $e->getMessage());

            return false;
        }
    }
}
