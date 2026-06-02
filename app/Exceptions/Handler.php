<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
        $this->renderable(function (ConnectionException $exception, Request $request) {
            report($exception);

            $message = 'No se pudo conectar con el servicio de datos. Inténtalo nuevamente.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'error' => $message], 503);
            }

            if (!$request->isMethod('GET')) {
                return back()->withErrors(['sistema' => $message])->withInput();
            }

            return response($message, 503);
        });

        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
