<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Alias para usarlo en las rutas: ->middleware('admin')
        $middleware->alias(['admin' => \App\Http\Middleware\EsAdmin::class]);

        // Sin sesión: en /admin → login de administración; en la tienda → login con Google
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('login')
            : route('social.redirect', 'google'));

        // Si alguien con sesión entra a /login: admin → panel, cliente → tienda
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->is_admin
            ? route('admin.dashboard')
            : route('home'));

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
