<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Hanya role admin yang boleh lewat (mis. kelola pengguna). */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Hanya admin yang dapat mengakses halaman ini.');

        return $next($request);
    }
}
