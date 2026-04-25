<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class OperationalLogger
{
    public static function info(string $message, array $context = [], ?string $channel = null): void
    {
        self::write('info', $message, $context, null, null, $channel);
    }

    public static function warning(string $message, array $context = [], ?Request $request = null, ?string $channel = null): void
    {
        self::write('warning', $message, $context, $request, null, $channel);
    }

    public static function error(
        string $message,
        array $context = [],
        ?Throwable $exception = null,
        ?Request $request = null,
        ?string $channel = null,
    ): void {
        self::write('error', $message, $context, $request, $exception, $channel);
    }

    public static function critical(
        string $message,
        array $context = [],
        ?Throwable $exception = null,
        ?Request $request = null,
        ?string $channel = null,
    ): void {
        self::write('critical', $message, $context, $request, $exception, $channel);
    }

    private static function write(
        string $level,
        string $message,
        array $context = [],
        ?Request $request = null,
        ?Throwable $exception = null,
        ?string $channel = null,
    ): void {
        $request ??= app()->bound('request') ? app(Request::class) : null;

        $payload = array_filter([
            'context' => $context,
            'request_id' => self::requestId(),
            'user_id' => auth()->id(),
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
            'ip' => $request?->ip(),
        ], fn ($value) => $value !== null && $value !== []);

        $logger = $channel ? Log::channel($channel) : Log::channel('operations');
        $logger->log($level, $message, $payload);

        if (in_array($level, ['warning', 'error', 'critical'], true)) {
            self::persistToDatabase($level, $message, $payload, $exception, $request);
        }
    }

    private static function persistToDatabase(
        string $level,
        string $message,
        array $payload,
        ?Throwable $exception,
        ?Request $request,
    ): void {
        try {
            ErrorLog::create([
                'level' => $level,
                'message' => $message,
                'exception' => $exception ? get_class($exception) : null,
                'file' => $exception?->getFile(),
                'line' => $exception?->getLine(),
                'trace' => self::buildTrace($payload, $exception),
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'ip' => $request?->ip(),
                'user_id' => auth()->id(),
                'occurred_at' => now(),
            ]);
        } catch (Throwable $loggingException) {
            Log::channel('emergency')->error('OperationalLogger persistence failed', [
                'message' => $message,
                'error' => $loggingException->getMessage(),
            ]);
        }
    }

    private static function buildTrace(array $payload, ?Throwable $exception): string
    {
        $parts = [];

        if ($payload !== []) {
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $parts[] = 'Context: '.($json === false ? '[unserializable]' : $json);
        }

        if ($exception) {
            $parts[] = 'Exception: '.$exception->getMessage();
            $parts[] = $exception->getTraceAsString();
        }

        return mb_substr(implode("\n\n", $parts), 0, 65535);
    }

    private static function requestId(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = app(Request::class);

        return $request->headers->get('X-Request-Id')
            ?? $request->attributes->get('request_id');
    }
}
