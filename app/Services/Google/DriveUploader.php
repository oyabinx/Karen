<?php

namespace App\Services\Google;

use App\Models\IntegrationSetting;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;

/**
 * Arsip sekunder PDF ke Google Drive (opsional — docs/feature/
 * integrasi_google.md bagian Google Drive).
 */
class DriveUploader
{
    public function __construct(
        private readonly GoogleSettings $settings,
    ) {}

    public function enabled(): bool
    {
        return $this->settings->get(IntegrationSetting::KEY_DRIVE_ENABLED) === '1'
            && $this->settings->get(IntegrationSetting::KEY_DRIVE_FOLDER_ID)
            && $this->settings->getServiceAccountKey() !== null;
    }

    public function assertFolderAccessible(): void
    {
        $folderId = $this->settings->get(IntegrationSetting::KEY_DRIVE_FOLDER_ID);
        $this->drive()->files->get($folderId, ['fields' => 'id, name']);
    }

    /**
     * Unggah file (path relatif disk lokal) ke folder arsip.
     */
    public function upload(string $absolutePath, string $filename): ?string
    {
        if (! $this->enabled() || ! is_file($absolutePath)) {
            return null;
        }

        $file = new DriveFile([
            'name' => $filename,
            'parents' => [$this->settings->get(IntegrationSetting::KEY_DRIVE_FOLDER_ID)],
        ]);

        $created = $this->drive()->files->create($file, [
            'data' => file_get_contents($absolutePath),
            'mimeType' => 'application/pdf',
            'uploadType' => 'multipart',
        ]);

        return $created->id;
    }

    private function drive(): GoogleDrive
    {
        $key = $this->settings->getServiceAccountKey();

        if (! $key) {
            throw new \RuntimeException('Kredensial Google belum dikonfigurasi.');
        }

        $client = new GoogleClient();
        $client->setAuthConfig($key);
        $client->addScope(GoogleDrive::DRIVE_FILE);

        return new GoogleDrive($client);
    }
}
