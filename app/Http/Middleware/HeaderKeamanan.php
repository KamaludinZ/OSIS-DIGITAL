<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Header keamanan dasar untuk semua respons web. */
class HeaderKeamanan
{
    public function handle(Request $request, Closure $next): Response
    {
        $respons = $next($request);

        $respons->headers->set('X-Content-Type-Options', 'nosniff');
        $respons->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $respons->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respons->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if ($request->isSecure()) {
            $respons->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $respons;
    }
}
