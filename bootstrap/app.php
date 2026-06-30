<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(HandleCors::class);
        $middleware->append(SetLocale::class);
        $middleware->alias([
            'identify.tenant'   => \Modules\Core\App\Http\Middleware\IdentifyTenant::class,
            'check.permission'  => \Modules\Core\App\Http\Middleware\CheckPermission::class,
        ]);
        // Ensure identify.tenant runs before auth guards in the priority chain
        $middleware->prependToPriorityList(
            before: \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            prepend: \Modules\Core\App\Http\Middleware\IdentifyTenant::class,
        );
        Authenticate::redirectUsing(function (Request $request) {
            if ($request->is('api/*')) {
                return null;
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $_e) {
            return $request->is('api/*');
        });

        $exceptions->render(function (AuthenticationException $_e, Request $request) {
            if ($request->is('api/*')) {
                return errorResponse(__('auth.unauthenticated'), null, 401);
            }
        });

        $exceptions->render(function (ModelNotFoundException $_e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return errorResponse(
                    trans('channel::app.common.not_found'),
                    null,
                    404
                );
            }
        });
    })->create();
