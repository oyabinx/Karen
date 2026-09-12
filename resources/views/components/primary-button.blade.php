{{--
    Tombol aksi utama (tema tombol Karen — docs/feature/ui_responsive.md):
    indigo 48px tebal, rata tengah. Warna semantik (emerald/amber/red)
    ditimpa via class bg-*/hover:bg-* di call site.
--}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center min-h-[48px] px-5 rounded-lg bg-indigo-600 border border-transparent text-white text-sm font-semibold hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors']) }}>
    {{ $slot }}
</button>
