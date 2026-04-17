<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected Client $client;

    protected Drive $drive;

    public function __construct()
{
    $this->client = new Client;

    $httpClient = new \GuzzleHttp\Client(['verify' => false]);
    $this->client->setHttpClient($httpClient);

    // Ganti ke OAuth credentials
    $oauthPath = storage_path('app/google-oauth-credentials.json');
    if (!file_exists($oauthPath)) {
        throw new \Exception("Kredensial tidak ditemukan.");
    }

    $this->client->setAuthConfig($oauthPath);
    $this->client->setScopes(Drive::DRIVE);
    $this->client->setAccessType('offline');

    // Load token
    $tokenPath = storage_path('app/google-token.json');
    if (!file_exists($tokenPath)) {
        throw new \Exception("Token tidak ditemukan. Jalankan get_google_token.php dulu.");
    }

    $token = json_decode(file_get_contents($tokenPath), true);
    $this->client->setAccessToken($token);

    // Auto refresh jika expired
    if ($this->client->isAccessTokenExpired()) {
        $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
        file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));
    }

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

    public function getImageContent(string $fileId): ?array
    {
        try {
            $fileMeta = $this->drive->files->get($fileId, ['fields' => 'mimeType,name']);
            $response = $this->drive->files->get($fileId, ['alt' => 'media']);

            return [
                'content' => $response->getBody()->getContents(),
                'mimeType' => $fileMeta->getMimeType() ?: 'application/octet-stream',
                'name' => $fileMeta->getName() ?: ('image_' . $fileId),
            ];
        } catch (GoogleServiceException $e) {
            Log::error('Google Drive Read Error: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error('Google Drive Read Error: ' . $e->getMessage());
            return null;
        }
    }
}
