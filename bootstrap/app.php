<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SetLocaleFromRequest::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (\App\Exceptions\ApiExceptionInterface $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => trans($e->translationKey(), $e->translationParams()),
                'error_code' => $e->errorCode(),
            ], $e->httpStatus());
        });

        // NOTE: the top-level `message` here is now a fixed, localized
        // summary (per FR-006/Article IX). The field-level `errors` array
        // is localized separately by Laravel's own validator once
        // lang/{locale}/validation.php exists for both 'en' and 'ar' —
        // no code change needed for that part, only the lang files.
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => trans('http.validation_failed'),
                'error_code' => 'VALIDATION_ERROR',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => trans('http.unauthenticated'),
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => trans('http.forbidden'),
                'error_code' => 'FORBIDDEN',
            ], 403);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => trans('http.forbidden'),
                'error_code' => 'FORBIDDEN',
            ], 403);
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => trans('http.not_found'),
                'error_code' => 'NOT_FOUND',
            ], 404);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => trans('http.not_found'),
                'error_code' => 'NOT_FOUND',
            ], 404);
        });

        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => trans('http.too_many_requests'),
                'error_code' => 'TOO_MANY_REQUESTS',
            ], 429);
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                // Debug mode still shows the real message locally — never
                // localized, since it's a developer-facing diagnostic, not
                // user-facing copy.
                $message = config('app.debug') ? $e->getMessage() : trans('http.server_error');

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error_code' => 'SERVER_ERROR',
                ], $status);
            }
        });
    })->create();
