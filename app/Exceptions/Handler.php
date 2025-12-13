<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpException;
use Illuminate\Http\Exceptions\MethodNotAllowedHttpException;
use Illuminate\Http\Exceptions\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                return $this->handleApiException($e, $request);
            }

            // Handle web exceptions
            return $this->handleWebException($e, $request);
        });
    }

    /**
     * Handle API exceptions and return JSON responses
     */
    private function handleApiException(Throwable $exception, $request)
    {
        $status = 500;
        $response = [
            'success' => false,
            'message' => 'Internal Server Error',
        ];

        // Get the right status code
        if ($exception instanceof ValidationException) {
            $status = 422;
            $response['message'] = 'Validation Error';
            $response['errors'] = $exception->errors();
        } elseif ($exception instanceof AuthenticationException) {
            $status = 401;
            $response['message'] = 'Unauthenticated';
        } elseif ($exception instanceof AuthorizationException) {
            $status = 403;
            $response['message'] = 'Unauthorized';
        } elseif ($exception instanceof ModelNotFoundException) {
            $status = 404;
            $response['message'] = 'Resource Not Found';
        } elseif ($exception instanceof NotFoundHttpException) {
            $status = 404;
            $response['message'] = 'Endpoint Not Found';
        } elseif ($exception instanceof MethodNotAllowedHttpException) {
            $status = 405;
            $response['message'] = 'Method Not Allowed';
        } elseif ($exception instanceof HttpException) {
            $status = $exception->getStatusCode();
            $response['message'] = $exception->getMessage() ?: 'Http Exception';
        } elseif ($exception instanceof QueryException) {
            $status = 500;
            $response['message'] = 'Database Query Error';

            // Only add SQL error details in non-production environments
            if (! app()->environment('production')) {
                $response['details'] = [
                    'message' => $exception->getMessage(),
                    'exception' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ];
            }
        }

        // Add details for debugging in non-production environments
        if (! app()->environment('production') && $status === 500) {
            $response['details'] = [
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return response()->json($response, $status);
    }

    /**
     * Handle web exceptions and return appropriate responses
     */
    private function handleWebException(Throwable $exception, $request)
    {
        // Only handle specific exceptions for web routes
        if ($exception instanceof ValidationException) {
            // Let Laravel handle validation exceptions normally
            return null;
        }

        if ($exception instanceof AuthenticationException) {
            return redirect()->route('home')
                ->with('error', 'Access denied.');
        }

        if ($exception instanceof AuthorizationException) {
            return redirect()->back()
                ->with('error', 'You are not authorized to perform this action.');
        }

        if ($exception instanceof ModelNotFoundException) {
            return redirect()->route('web.venues.index')
                ->with('error', 'The requested resource was not found.');
        }

        if ($exception instanceof NotFoundHttpException) {
            return response()->view('errors.404', [], 404);
        }

        if ($exception instanceof QueryException) {
            // Log the error but don't expose database details to users
            Log::error('Database error in web request', [
                'error' => $exception->getMessage(),
                'url' => $request->url(),
            ]);

            return redirect()->back()
                ->with('error', 'A database error occurred. Please try again.');
        }

        // For other exceptions in production, show generic error
        if (app()->environment('production')) {
            Log::error('Unexpected error in web request', [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'url' => $request->url(),
            ]);

            return redirect()->back()
                ->with('error', 'An unexpected error occurred. Please try again.');
        }

        // In development, let Laravel handle it normally for debugging
        return null;
    }
}
