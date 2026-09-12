<x-app-layout title="{{ $user->exists ? 'Ubah User' : 'Tambah User' }}">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">{{ $user->exists ? 'Ubah User' : 'Tambah User' }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            Seksi wajib diisi untuk role <strong>pegawai</strong> dan <strong>pengurus</strong> (dasar kuota bidang) — opsional untuk admin.
        </p>
    </div>

    <form method="POST"
          action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}"
          class="max-w-2xl bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        @csrf
        @method($user->exists ? 'PUT' : 'POST')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <x-input-label for="name" value="Nama" />
                <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" :value="old('name', $user->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" :value="old('email', $user->email)" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" value="{{ $user->exists ? 'Kata Sandi (kosongkan agar tidak berubah)' : 'Kata Sandi Awal (min. 8 karakter)' }}" />
                <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" :required="$user->exists ? false : true" autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="role" value="Role" />
                <select id="role" name="role" class="block mt-1 w-full rounded-lg border-gray-300" required>
                    @foreach (['pegawai' => 'Pegawai', 'pengurus' => 'Pengurus', 'admin' => 'Administrator'] as $value => $label)
                        <option value="{{ $value }}" {{ old('role', $user->role ?? 'pegawai') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="phone" value="Nomor HP (opsional — wajib diisi user di profil)" />
                <x-text-input id="phone" name="phone" type="tel" class="block mt-1 w-full" :value="old('phone', $user->phone)" placeholder="contoh: 081234567890" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="seksi_id" value="Seksi" />
                <select id="seksi_id" name="seksi_id" class="block mt-1 w-full rounded-lg border-gray-300">
                    <option value="">— tanpa seksi (khusus admin) —</option>
                    @foreach ($seksiList->groupBy('bidang.name') as $bidangName => $seksiGroup)
                        <optgroup label="{{ $bidangName }}">
                            @foreach ($seksiGroup as $s)
                                <option value="{{ $s->id }}" {{ old('seksi_id', $user->seksi_id) === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('seksi_id')" class="mt-2" />
            </div>
        </div>

        {{-- Hierarki mobile: aksi utama LEBIH BESAR (48px, tebal, indigo)
             di atas; sekunder Batal lebih ringan di bawah --}}
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
            <a href="{{ route('admin.users.index') }}"
               class="w-full sm:w-auto px-4 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 text-center min-h-[40px] leading-[40px]">Batal</a>
            <button type="submit"
                    class="w-full sm:w-auto min-h-[48px] px-6 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 flex items-center justify-center transition-colors">
                {{ $user->exists ? 'Simpan Perubahan' : 'Buat User' }}
            </button>
        </div>
    </form>
</x-app-layout>
