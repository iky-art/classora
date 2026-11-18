<?php

use App\Tenancy\NoTenantException;
use App\Tenancy\ResolveTenant;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api/v1',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->throttleApi();
        $middleware->alias(['tenant' => ResolveTenant::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $json = fn (string $code, string $message, int $status, array $details = []) => response()->json(
            ['error' => ['code' => $code, 'message' => $message, 'details' => $details ?: null]],
            $status
        );

        $exceptions->shouldRenderJsonWhen(fn (Request $r) => $r->is('api/*') || $r->expectsJson());

        $exceptions->render(fn (ValidationException $e) => $json('validation_failed', 'Data tidak valid.', 422, $e->errors()));
        $exceptions->render(fn (AuthenticationException $e) => $json('unauthenticated', 'Silakan masuk terlebih dahulu.', 401));
        $exceptions->render(fn (NoTenantException $e) => $json('no_tenant', $e->getMessage(), 403));
        $exceptions->render(function (HttpExceptionInterface $e) use ($json) {
            $status = $e->getStatusCode();
            $message = $e->getMessage();
            if ($status === 404) {
                $message = 'Data tidak ditemukan.';
            } elseif ($status === 403 && ($message === '' || $message === 'This action is unauthorized.')) {
                $message = 'Kamu tidak memiliki izin untuk tindakan ini.';
            } elseif ($message === '') {
                $message = 'Permintaan gagal.';
            }

            return $json($status >= 500 ? 'server_error' : 'http_'.$status, $message, $status);
        });
    })->create();
