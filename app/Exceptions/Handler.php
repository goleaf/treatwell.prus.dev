<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\BadRequestException;
use Illuminate\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\Exceptions\MethodNotAllowedHttpException;
use Illuminate\Http\Exceptions\HttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Auth\AuthenticationException;
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
            if (!app()->environment('production')) {
                $response['details'] = [
                    'message' => $exception->getMessage(),
                    'exception' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ];
            }
        }
        
        // Add details for debugging in non-production environments
        if (!app()->environment('production') && $status === 500) {
            $response['details'] = [
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return response()->json($response, $status);
    }
} 