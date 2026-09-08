{{--
    Pagination "Karen" (revisi user UAT):
    tombol SEBELUMNYA tidak dirender di halaman pertama, tombol
    BERIKUTNYA tidak dirender di halaman terakhir — menggantikan
    perilaku bawaan Laravel yang menampilkan tombol disabled.
    View ini menjadi default seluruh paginator aplikasi.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-between">
        {{-- Mobile --}}
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 hover:text-indigo-600 min-h-[44px]">
                    ‹ Sebelumnya
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="ml-auto relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 hover:text-indigo-600 min-h-[44px]">
                    Berikutnya ›
                </a>
            @else
                <span></span>
            @endif
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                @unless ($paginator->onFirstPage())
                    <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-white rounded-lg border border-gray-300 hover:bg-gray-50 min-h-[44px]">
                        ‹ Sebelumnya
                    </a>
                @endunless
            </div>

            <div>
                <span class="relative z-0 inline-flex rounded-lg shadow-sm">
                    @foreach ($elements as $element)
                        {{-- "…" --}}
                        @if (is_string($element))
                            <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-white bg-indigo-600 border border-indigo-600 rounded-lg">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 min-h-[44px]">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </span>
            </div>

            <div>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-white rounded-lg border border-gray-300 hover:bg-gray-50 min-h-[44px]">
                        Berikutnya ›
                    </a>
                @endif
            </div>
        </div>
    </nav>
@endif
