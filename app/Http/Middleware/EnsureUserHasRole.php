<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware role — pemakaian: role:admin | role:pegawai|pengurus
 * (pemisah "|" atau "," keduanya didukung).
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = collect($roles)
            ->flatMap(fn (string $role) => preg_split('/[|,]/', $role))
            ->map(fn (string $role) => trim($role))
            ->filter()
            ->all();

        $user = $request->user();

        abort_unless($user !== null && in_array($user->role, $allowed, true), 403);

        return $next($request);
    }
}
