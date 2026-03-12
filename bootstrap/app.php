<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $renderUnauthorizedJson = function ($request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Você não tem permissão para executar esta ação.',
                ], 403);
            }
        };

        $exceptions->render(function (AuthorizationException $e, $request) use ($renderUnauthorizedJson) {
            return $renderUnauthorizedJson($request);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, $request) use ($renderUnauthorizedJson) {
            return $renderUnauthorizedJson($request);
        });
    })->create();
