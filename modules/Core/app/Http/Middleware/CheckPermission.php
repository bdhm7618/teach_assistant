<?php

namespace Modules\Core\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * Usage: check.permission:users.view
     *        check.permission:users.view,users.create  (requires ALL listed)
     *
     * Owner role (permissions = "all") always passes.
     */
    public function handle(Request $request, Closure $next, string $required): Response
    {
        $user = auth('user')->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => trans('channel::app.auth.invalid_credentials'),
            ], 401);
        }

        // Comma-separated → require ALL
        $permissions = array_map('trim', explode(',', $required));

        if (! $user->hasAllPermissions($permissions)) {
            return response()->json([
                'success' => false,
                'message' => trans('channel::app.user.unauthorized'),
            ], 403);
        }

        return $next($request);
    }
}
