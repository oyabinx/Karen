<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Anti back-button-cache (temuan UAT A5): setelah logout, tombol
 * back browser tidak boleh menampilkan lagi halaman terproteksi dari
 * cache. Header no-store mencegah browser menyimpan/menghidupkan
 * ulang (bfcache) halaman — permintaan ulang akan ditolak middleware
 * auth dan diarahkan ke halaman login.
 */
class NoCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $response;
    }
}
