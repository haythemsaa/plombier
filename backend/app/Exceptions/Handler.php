<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Sentry integration
            if (app()->bound('sentry') && $this->shouldReport($e)) {
                app('sentry')->captureException($e);
            }
        });

        // Custom rendering for API responses
        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $this->handleApiException($e);
            }
        });
    }

    /**
     * Handle API exceptions with consistent JSON responses.
     */
    protected function handleApiException(Throwable $e)
    {
        $status = 500;
        $message = 'Internal Server Error';
        $errors = null;

        // Authentication exceptions
        if ($e instanceof AuthenticationException) {
            $status = 401;
            $message = 'Unauthenticated';
        }

        // Validation exceptions
        elseif ($e instanceof ValidationException) {
            $status = 422;
            $message = 'Validation failed';
            $errors = $e->errors();
        }

        // Not found exceptions
        elseif ($e instanceof NotFoundHttpException) {
            $status = 404;
            $message = 'Resource not found';
        }

        // HTTP exceptions
        elseif ($e instanceof HttpException) {
            $status = $e->getStatusCode();
            $message = $e->getMessage() ?: $message;
        }

        // Other exceptions
        else {
            // In production, don't expose internal error messages
            if (app()->environment('production')) {
                $message = 'An unexpected error occurred';
            } else {
                $message = $e->getMessage();
            }
        }

        $response = [
            'success' => false,
            'message' => $message,
            'status_code' => $status,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        // Include trace in non-production environments
        if (!app()->environment('production')) {
            $response['trace'] = $e->getTraceAsString();
            $response['file'] = $e->getFile();
            $response['line'] = $e->getLine();
        }

        // Include error ID for Sentry tracking
        if (app()->bound('sentry')) {
            $sentryId = app('sentry')->getLastEventId();
            if ($sentryId) {
                $response['error_id'] = $sentryId;
            }
        }

        return response()->json($response, $status);
    }
}
