<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\Bidang;
use App\Models\Seksi;
use App\Models\User;
use App\Services\UserCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Manajemen user — hanya admin (docs/feature/manajemen_user.md).
     */
    public function index(Request $request): View
    {
        $users = User::with(['seksi.bidang'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('email', 'like', '%'.$request->q.'%')))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->when($request->filled('bidang'), fn ($q) => $q->whereHas('seksi.bidang', fn ($b) => $b->where('id', $request->bidang)));

        // Status: default aktif saja; 'nonaktif' hanya ter-nonaktifkan; 'semua' keduanya
        match ($request->input('status')) {
            'nonaktif' => $users->onlyTrashed(),
            'semua' => $users->withTrashed(),
            default => null,
        };

        $users = $users->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'bidangList' => Bidang::orderBy('name')->get(),
            'filters' => $request->only(['q', 'role', 'bidang', 'status']),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(),
            'seksiList' => Seksi::with('bidang')->orderBy('name')->get(),
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'seksiList' => Seksi::with('bidang')->orderBy('name')->get(),
        ]);
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Password kosong = tidak diganti
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->fill($data)->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Nonaktifkan (soft delete) — riwayat peminjaman tetap tersimpan.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'User dinonaktifkan.');
    }

    public function restore(int $id): RedirectResponse
    {
        // User nonaktif tersembunyi dari binding default — cari manual
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return back()->with('success', 'User diaktifkan kembali.');
    }

    /**
     * Reset kata sandi oleh admin (UAT 09-C1): aplikasi TIDAK punya
     * skema "lupa kata sandi" (keputusan desain dipertahankan) —
     * pegawai yang lupa minta admin, admin membuatkan kata sandi baru
     * (ditampilkan SEKALI di pesan sukses untuk disampaikan), lalu
     * pegawai menggantinya sendiri di menu Profil. Hash satu arah
     * membuat kata sandi lama mustahil dilihat — reset inilah
     * satu-satunya jalurnya.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Ubah kata sandi Anda sendiri lewat menu Profil.');
        }

        // Acak tanpa karakter yang mudah tertukar (l/1, O/0, dsb.)
        $karakter = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $sandibaru = substr(str_shuffle($karakter), 0, 10);

        $user->update(['password' => $sandibaru]);

        // Jejak audit TANPA nilai sandi (keamanan log tetap dijaga)
        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => \App\Models\ActivityLog::ACTION_UPDATED,
            'model_type' => User::class,
            'model_id' => $user->id,
            'model_label' => $user->activityLabel(),
            'description' => 'Mereset kata sandi '.$user->activityLabel(),
            'changes' => ['password' => ['lama' => '(tersembunyi)', 'baru' => '(tersembunyi)']],
        ]);

        return back()->with('success', "Kata sandi baru {$user->name}: {$sandibaru} — sampaikan ke pegawai, lalu minta diganti sendiri di menu Profil. Kata sandi hanya ditampilkan sekali ini.");
    }

    // ── Impor massal CSV ─────────────────────────────────────────

    public function template(UserCsvImporter $importer)
    {
        return $importer->template();
    }

    public function import(Request $request, UserCsvImporter $importer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $preview = $importer->preview($request->file('file'));

        if ($preview['total'] === 0) {
            return back()->with('error', 'File CSV kosong atau tidak terbaca.');
        }

        session(['user_import_preview' => $preview]);

        return redirect()
            ->route('admin.users.import')
            ->with('success', "Pratinjau siap: {$preview['valid_count']} baris valid, {$preview['invalid_count']} bermasalah.");
    }

    public function importPreview(): View
    {
        return view('admin.users.import', [
            'preview' => session('user_import_preview'),
        ]);
    }

    public function importCommit(UserCsvImporter $importer): RedirectResponse
    {
        $preview = session('user_import_preview');

        if (! $preview) {
            return redirect()->route('admin.users.import')->with('error', 'Tidak ada pratinjau impor. Unggah ulang file CSV.');
        }

        $result = $importer->commit($preview['rows']);

        session()->forget('user_import_preview');

        $pesan = "Impor selesai: {$result['created']} user dibuat.";
        if ($result['skipped'] > 0) {
            $pesan .= " {$result['skipped']} dilewati (email sudah terpakai).";
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', $pesan);
    }

    public function importFailed(UserCsvImporter $importer)
    {
        $preview = session('user_import_preview');

        if (! $preview) {
            return back()->with('error', 'Tidak ada data pratinjau.');
        }

        return $importer->failedReport($preview['rows']);
    }
}
