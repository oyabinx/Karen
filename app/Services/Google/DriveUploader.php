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
        // supportsAllDrives: folder di Shared Drive/Drive Bersama butuh
        // flag ini — tanpanya API selalu menjawab 404 (UAT 10-C6).
        $this->drive()->files->get($folderId, [
            'fields' => 'id, name',
            'supportsAllDrives' => true,
        ]);
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
            'supportsAllDrives' => true,
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
        // Scope penuh diperlukan: scope sempit DRIVE_FILE hanya melihat
        // file yang dibuat aplikasi sendiri — folder yang di-share ke
        // service account menjawab 404 (temuan UAT 10-C6). Service
        // account tetap hanya melihat apa yang dibagikan kepadanya.
        $client->addScope(GoogleDrive::DRIVE);

        return new GoogleDrive($client);
    }
}
