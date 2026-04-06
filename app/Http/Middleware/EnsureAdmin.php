<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== User::ROLE_ADMIN) {
            return response()->errorJson('Forbidden. Faqat admin uchun.', 403);
        }

        return $next($request);
    }
}

