<?php

namespace App\Services;

use App\Models\Bidang;
use App\Models\Seksi;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Impor massal pegawai via CSV (docs/feature/manajemen_user.md).
 *
 * Alur: unduh template → unggah (dry-run/pratinjau) → commit baris
 * valid → laporan baris gagal dapat diunduh.
 * Kolom: nama,email,password,bidang,seksi,role (role opsional,
 * kosong = pegawai).
 */
class UserCsvImporter
{
    public const HEADERS = ['nama', 'email', 'password', 'bidang', 'seksi', 'role'];

    /**
     * Unduhan template CSV berisi header + 1 baris contoh.
     */
    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::HEADERS, separator: ';');
            fputcsv($out, ['Budi Santoso', 'budi@karen.test', 'Password123', 'Bidang Umum', 'Seksi Kepegawaian', 'pegawai'], separator: ';');

            fclose($out);
        }, 'template-impor-pegawai-karen.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Dry-run: validasi seluruh baris TANPA menulis ke database.
     *
     * @return array{rows: array<int, array{line: int, data: array<string, string|null>, valid: bool, errors: array<int, string>}>, valid_count: int, invalid_count: int, total: int}
     */
    public function preview(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle, separator: ';') ?: [];

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        if ($header !== self::HEADERS) {
            fclose($handle);

            return [
                'rows' => [[
                    'line' => 1,
                    'data' => array_combine(self::HEADERS, array_fill(0, 6, null)),
                    'valid' => false,
                    'errors' => ['Header CSV tidak sesuai template. Pastikan memakai file template yang diunduh dari sistem (kolom: '.implode(',', self::HEADERS).').'],
                ]],
                'valid_count' => 0,
                'invalid_count' => 1,
                'total' => 1,
            ];
        }

        $emailsInFile = [];
        $rows = [];
        $line = 1;

        while (($raw = fgetcsv($handle, separator: ';')) !== false) {
            $line++;

            // Baris kosong dilewati
            if (count(array_filter($raw, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $data = array_combine(self::HEADERS, array_pad(array_map(fn ($v) => trim((string) $v), $raw), 6, null));
            $rows[] = $this->validateRow($data, $line, $emailsInFile);
        }
        fclose($handle);

        return [
            'rows' => $rows,
            'valid_count' => count(array_filter($rows, fn ($r) => $r['valid'])),
            'invalid_count' => count(array_filter($rows, fn ($r) => ! $r['valid'])),
            'total' => count($rows),
        ];
    }

    /**
     * Commit: buat user HANYA untuk baris valid; email yang ternyata
     * sudah terpakai sejak pratinjau dilewati (aman diulang).
     *
     * @param  array<int, array{data: array<string, string|null>, valid: bool}>  $rows
     * @return array{created: int, skipped: int}
     */
    public function commit(array $rows): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (! ($row['valid'] ?? false)) {
                continue;
            }

            $data = $row['data'];

            // Cek ulang: email mungkin sudah dipakai sejak pratinjau
            if (User::where('email', $data['email'])->exists()) {
                $skipped++;
                continue;
            }

            $seksi = Seksi::where('name', $data['seksi'])
                ->whereHas('bidang', fn ($q) => $q->where('name', $data['bidang']))
                ->first();

            User::create([
                'name' => $data['nama'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?: 'pegawai',
                'phone' => null,
                'seksi_id' => $seksi?->id,
            ]);

            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Laporan baris gagal (CSV) — untuk diperbaiki & diunggah ulang.
     */
    public function failedReport(array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [...self::HEADERS, 'alasan_gagal'], separator: ';');

            foreach ($rows as $row) {
                if ($row['valid'] ?? true) {
                    continue;
                }
                fputcsv($out, [
                    $row['data']['nama'], $row['data']['email'], $row['data']['password'],
                    $row['data']['bidang'], $row['data']['seksi'], $row['data']['role'],
                    implode(' | ', $row['errors']),
                ], separator: ';');
            }

            fclose($out);
        }, 'laporan-impor-gagal-karen.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Validasi satu baris terhadap aturan manajemen user.
     *
     * @param  array<string, string|null>  $data
     * @param  array<int, string>  $emailsInFile  akumulator duplikasi dalam file
     */
    private function validateRow(array $data, int $line, array &$emailsInFile): array
    {
        $errors = [];
        $role = $data['role'] ?: 'pegawai';

        if ($data['nama'] === null || $data['nama'] === '') {
            $errors[] = 'Nama kosong.';
        }
        if ($data['email'] === null || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email tidak valid.';
        } elseif (User::where('email', $data['email'])->exists()) {
            $errors[] = 'Email sudah terdaftar di database.';
        } elseif (in_array(strtolower($data['email']), $emailsInFile, true)) {
            $errors[] = 'Email duplikat di dalam file.';
        } else {
            $emailsInFile[] = strtolower($data['email']);
        }
        if ($data['password'] === null || strlen($data['password']) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        }
        if (! in_array($role, ['admin', 'pengurus', 'pegawai'], true)) {
            $errors[] = 'Role harus admin/pengurus/pegawai (atau kosong untuk pegawai).';
        }

        $seksi = null;
        if ($data['bidang'] === null || $data['bidang'] === '') {
            $errors[] = 'Bidang kosong.';
        } elseif (! Bidang::where('name', $data['bidang'])->exists()) {
            $errors[] = "Bidang '{$data['bidang']}' tidak ditemukan.";
        } elseif ($data['seksi'] === null || $data['seksi'] === '') {
            $errors[] = 'Seksi kosong.';
        } else {
            $seksi = Seksi::where('name', $data['seksi'])
                ->whereHas('bidang', fn ($q) => $q->where('name', $data['bidang']))
                ->first();

            if (! $seksi) {
                $errors[] = "Seksi '{$data['seksi']}' tidak terdaftar di bawah bidang '{$data['bidang']}'.";
            }
        }

        // Seksi wajib untuk pegawai & pengurus (konsisten aturan CRUD manual)
        if (in_array($role, ['pegawai', 'pengurus'], true) && $seksi === null && ! in_array('Seksi kosong.', $errors, true)) {
            $errors[] = "Role '{$role}' wajib memiliki seksi yang valid.";
        }

        return [
            'line' => $line,
            'data' => $data,
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }
}
