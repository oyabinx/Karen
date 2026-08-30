<x-app-layout title="{{ $vehicle->exists ? 'Ubah Kendaraan' : 'Tambah Kendaraan' }}">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">{{ $vehicle->exists ? 'Ubah Kendaraan' : 'Tambah Kendaraan' }}</h1>
        <p class="text-sm text-gray-500 mt-1">Tahun pembuatan menentukan besaran anggaran maintenance 4 pos unit ini.</p>
    </div>

    <form method="POST"
          action="{{ $vehicle->exists ? route('pengurus.vehicles.update', $vehicle) : route('pengurus.vehicles.store') }}"
          enctype="multipart/form-data"
          class="max-w-2xl bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        @csrf
        @method($vehicle->exists ? 'PUT' : 'POST')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <x-input-label for="name" value="Nama / Unit" />
                <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" :value="old('name', $vehicle->name)" required placeholder="mis. Avanza B 1234 XYZ" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="plate_number" value="Plat Nomor" />
                <x-text-input id="plate_number" name="plate_number" type="text" class="block mt-1 w-full" :value="old('plate_number', $vehicle->plate_number)" required maxlength="15" placeholder="mis. B 1234 XYZ" />
                <x-input-error :messages="$errors->get('plate_number')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="year" value="Tahun Pembuatan" />
                <x-text-input id="year" name="year" type="number" class="block mt-1 w-full" :value="old('year', $vehicle->year)" required min="1980" max="{{ now()->year + 1 }}" />
                <x-input-error :messages="$errors->get('year')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="capacity" value="Kapasitas (kursi)" />
                <x-text-input id="capacity" name="capacity" type="number" class="block mt-1 w-full" :value="old('capacity', $vehicle->capacity)" required min="1" max="20" />
                <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="photo" value="Foto Kendaraan (opsional, maks 2 MB)" />
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
                       class="block mt-1 w-full text-sm rounded-lg border-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700">
                @if ($vehicle->photo_path)
                    <p class="text-xs text-gray-400 mt-1">Foto saat ini:</p>
                    <img src="{{ Storage::url($vehicle->photo_path) }}" alt="{{ $vehicle->name }}" class="mt-1 h-24 rounded-lg object-cover">
                @endif
                <x-input-error :messages="$errors->get('photo')" class="mt-2" />
            </div>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
            <a href="{{ route('pengurus.vehicles.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-center hover:bg-gray-50 min-h-[44px] leading-[44px]">Batal</a>
            <x-primary-button>{{ $vehicle->exists ? 'Simpan Perubahan' : 'Tambah Kendaraan' }}</x-primary-button>
        </div>
    </form>
</x-app-layout>
