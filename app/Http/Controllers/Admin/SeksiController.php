<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SeksiRequest;
use App\Models\Bidang;
use App\Models\Seksi;
use Illuminate\Http\RedirectResponse;

class SeksiController extends Controller
{
    /**
     * Tambah seksi pada bidang tertentu.
     */
    public function store(SeksiRequest $request, Bidang $bidang): RedirectResponse
    {
        $bidang->seksi()->create(['name' => $request->input('name')]);

        return back()->with('success', 'Seksi berhasil ditambahkan.');
    }

    /**
     * Ubah nama seksi / pindah bidang.
     */
    public function update(SeksiRequest $request, Seksi $seksi): RedirectResponse
    {
        $seksi->update($request->only(['name', 'bidang_id']));

        return back()->with('success', 'Seksi berhasil diperbarui.');
    }

    /**
     * Hapus seksi — HANYA bila tidak ada user terdaftar.
     */
    public function destroy(Seksi $seksi): RedirectResponse
    {
        if ($seksi->users()->exists()) {
            return back()->with('error', 'Seksi masih memiliki anggota — pindahkan user dahulu.');
        }

        $seksi->delete();

        return back()->with('success', 'Seksi dihapus.');
    }
}
