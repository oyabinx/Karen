@if ($user && empty($user->phone))
<div class="mb-6 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 flex items-start gap-3">
    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div class="text-sm text-amber-800">
        <p class="font-semibold">Nomor HP belum diisi</p>
        <p>Nomor HP wajib tercantum pada profil Anda. <a href="{{ route('profile.edit') }}" class="underline font-medium">Lengkapi sekarang</a>.</p>
    </div>
</div>
@endif
