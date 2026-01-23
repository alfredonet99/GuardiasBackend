<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * ✅ AQUÍ SE "USA": este método se ejecuta SIEMPRE que revienta algo.
     * Si la petición es API/JSON, devolvemos un JSON estándar con code.
     */
    public function render($request, Throwable $e)
    {
        // Ajusta este criterio a tu app:
        $isApi = $request->is('api/*') || $request->expectsJson();

        if ($isApi) {
            // 1) Validación 422
            if ($e instanceof ValidationException) {
                return response()->json([
                    'ok'      => false,
                    'code'    => 'VALIDATION_FAILED',
                    'message' => 'Revisa la información capturada.',
                    'errors'  => $e->errors(),
                ], 422);
            }

            // 2) Ruta no encontrada 404
            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'ok'      => false,
                    'code'    => 'ROUTE_NOT_FOUND',
                    'message' => 'No pudimos cargar la información. Intenta más tarde.',
                ], 404);
            }

            // 3) HttpException (403, 405, etc.)
            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();


                // 405 method not allowed, etc.
                return response()->json([
                    'ok'      => false,
                    'code'    => 'HTTP_ERROR',
                    'message' => 'No se pudo completar la solicitud.',
                    'status'  => $status,
                ], $status);
            }

            
        }

        // Si NO es API, deja el comportamiento normal de Laravel (vistas error)
        return parent::render($request, $e);
    }
}
