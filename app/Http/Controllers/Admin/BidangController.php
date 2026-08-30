<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BidangRequest;
use App\Models\Bidang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BidangController extends Controller
{
    /**
     * Struktur organisasi: 5 bidang + seksi + kuota
     * (docs/feature/organisasi.md & kuota_bidang.md).
     */
    public function index(): View
    {
        $bidang = Bidang::with(['seksi' => fn ($q) => $q->withCount('users')])
            ->withCount('seksi')
            ->orderBy('id')
            ->get();

        return view('admin.bidang.index', ['bidangList' => $bidang]);
    }

    public function store(BidangRequest $request): RedirectResponse
    {
        Bidang::create($request->validated());

        return back()->with('success', 'Bidang berhasil dibuat.');
    }

    public function update(BidangRequest $request, Bidang $bidang): RedirectResponse
    {
        $bidang->update($request->validated());

        return back()->with('success', 'Bidang berhasil diperbarui.');
    }

    /**
     * Hapus bidang — HANYA bila tidak memiliki seksi dan tidak ada
     * user terkait (lewat seksi).
     */
    public function destroy(Bidang $bidang): RedirectResponse
    {
        if ($bidang->seksi()->exists()) {
            return back()->with('error', 'Bidang masih memiliki seksi — hapus/pindahkan sekinya dahulu.');
        }

        $bidang->delete();

        return back()->with('success', 'Bidang dihapus.');
    }
}
