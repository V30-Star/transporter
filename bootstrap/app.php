<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $route = $request->route();
            $routeName = $route?->getName();

            if (!is_string($routeName) || !str_ends_with($routeName, '.update')) {
                return null;
            }

            $viewRouteName = substr($routeName, 0, -strlen('.update')).'.view';
            if (!Route::has($viewRouteName)) {
                return null;
            }

            $parameters = array_values($route?->parameters() ?? []);
            if (count($parameters) < 1) {
                return null;
            }

            $message = collect($exception->errors())
                ->flatten()
                ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                ->first();

            return redirect()
                ->route($viewRouteName, count($parameters) === 1 ? $parameters[0] : $parameters)
                ->with('error', $message ?: 'Data belum bisa diperbarui. Silakan cek kembali isian Anda.');
        });
    })->create();
