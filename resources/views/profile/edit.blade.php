<x-app-layout title="Profil Saya">
    <div class="mb-8">
        <h1 class="text-2xl font-semibold">Profil Saya</h1>
        <p class="text-sm text-gray-500 mt-1">Perbarui informasi akun Anda. Nomor HP wajib terisi.</p>
    </div>

    <div class="max-w-2xl space-y-8">
        {{-- Informasi profil: nama, email, nomor HP (WAJIB) --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-medium mb-6">Informasi Profil</h2>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <x-input-label for="name" value="Nama" />
                        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" :value="old('name', $user->name)" required autocomplete="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" :value="old('email', $user->email)" required autocomplete="email" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="phone" value="Nomor HP (wajib)" />
                        <x-text-input id="phone" name="phone" type="tel" class="block mt-1 w-full" :value="old('phone', $user->phone)" required autocomplete="tel" placeholder="contoh: 081234567890" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        <p class="text-xs text-gray-400 mt-1">Format: 08xx, 62xxx, atau +62xxx.</p>
                    </div>

                    <div>
                        <x-input-label for="role" value="Role" />
                        <input id="role" type="text" disabled
                               value="{{ $user->role }}"
                               class="block mt-1 w-full rounded-md border-gray-300 bg-gray-50 text-gray-500" />
                        <p class="text-xs text-gray-400 mt-1">Role & penempatan hanya dapat diubah admin.</p>
                    </div>

                    @if ($user->seksi)
                        <div class="sm:col-span-2">
                            <x-input-label for="seksi" value="Penempatan" />
                            <input id="seksi" type="text" disabled
                                   value="{{ $user->seksi->name }} — {{ $user->seksi->bidang->name }}"
                                   class="block mt-1 w-full rounded-md border-gray-300 bg-gray-50 text-gray-500" />
                        </div>
                    @endif
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Simpan Perubahan</x-primary-button>
                </div>
            </form>
        </section>

        {{-- Ganti kata sandi --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-medium mb-6">Ganti Kata Sandi</h2>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <x-input-label for="current_password" value="Kata Sandi Saat Ini" />
                        <x-text-input id="current_password" name="current_password" type="password" class="block mt-1 w-full" required autocomplete="current-password" />
                        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Kata Sandi Baru" />
                        <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Ulangi Kata Sandi Baru" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Ganti Kata Sandi</x-primary-button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
