<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Padroniza as respostas de erro da API no formato { data, meta, errors }.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            // A InvalidStatusTransitionException já possui seu próprio render() 422.

            if ($e instanceof ValidationException) {
                return response()->json([
                    'data' => null,
                    'meta' => null,
                    'errors' => $e->errors(),
                ], 422);
            }

            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'data' => null,
                    'meta' => null,
                    'errors' => ['message' => 'Recurso não encontrado.'],
                ], 404);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'data' => null,
                    'meta' => null,
                    'errors' => ['message' => 'Não autenticado.'],
                ], 401);
            }

            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'data' => null,
                    'meta' => null,
                    'errors' => ['message' => $e->getMessage() ?: 'Erro na requisição.'],
                ], $e->getStatusCode());
            }

            return null;
        });
    })->create();
