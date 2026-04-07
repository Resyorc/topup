<?php

use App\Http\Middleware\BlockBannedIps;
use App\Http\Middleware\MaintenanceMode;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogVisitor;
use App\Http\Middleware\SecurityHeaders;
use App\Models\ErrorLog;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust semua proxy — aman karena server berada di belakang Cloudflare
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'auth.api_key' => \App\Http\Middleware\AuthenticateApiKey::class,
        ]);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            MaintenanceMode::class,
            BlockBannedIps::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SecurityHeaders::class,
            LogVisitor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e) {
            // Abaikan exception yang tidak perlu dicatat
            $ignored = [
                \Illuminate\Auth\AuthenticationException::class,
                \Illuminate\Auth\Access\AuthorizationException::class,
                \Illuminate\Database\Eloquent\ModelNotFoundException::class,
                \Illuminate\Http\Exceptions\HttpResponseException::class,
                \Illuminate\Validation\ValidationException::class,
                \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
                \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
                \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException::class,
            ];

            foreach ($ignored as $class) {
                if ($e instanceof $class) {
                    return false; // lewati — tetap biarkan Laravel log default berjalan
                }
            }

            try {
                $request = request();
                ErrorLog::create([
                    'level'      => 'error',
                    'message'    => mb_substr($e->getMessage(), 0, 65535),
                    'exception'  => get_class($e),
                    'file'       => $e->getFile(),
                    'line'       => $e->getLine(),
                    'trace'      => mb_substr($e->getTraceAsString(), 0, 65535),
                    'url'        => $request?->fullUrl(),
                    'method'     => $request?->method(),
                    'ip'         => $request?->ip(),
                    'user_id'    => $request ? optional(auth()->user())->id : null,
                    'occurred_at'=> now(),
                ]);
            } catch (\Throwable) {
                // Jangan sampai error logger malah melempar error baru
            }
        });

        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, \Illuminate\Http\Request $request) {
            $status = $response->getStatusCode();

            if (in_array($status, [403, 404, 429, 500, 503]) && ! $request->expectsJson() && ! $request->hasHeader('X-Livewire')) {
                return Inertia::render('error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
