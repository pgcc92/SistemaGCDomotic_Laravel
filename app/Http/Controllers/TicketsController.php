<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TicketsController
{
    public function __construct(
        private readonly RemoteDataClient $data,
    ) {
    }

    public function index(): View
    {
        return view('tickets.index');
    }

    public function show(string $ticketId)
    {
        $res = $this->data->ticket($ticketId);

        $ticket = (array) ($res['ticket'] ?? []);
        $tecnicos = (array) ($res['tecnicos_activos'] ?? []);
        $cliente = $res['cliente'] ?? null;
        $mensajes = (array) ($res['mensajes'] ?? []);

        if (request()->expectsJson()) {
            return response()->json([
                'ok' => !isset($res['error']),
                'data' => $res,
            ]);
        }

        return view('tickets.show', [
            'ticketId' => $ticketId,
            'ticket' => $ticket,
            'tecnicos' => $tecnicos,
            'cliente' => $cliente,
            'mensajes' => $mensajes,
            'error' => $res['error'] ?? null,
        ]);
    }

    public function asignar(Request $request, string $ticketId)
    {
        $payload = $request->validate([
            'tecnico_id' => ['required', 'integer', 'min:1'],
            'comentario' => ['nullable', 'string', 'max:1000'],
        ]);

        $ok = $this->data->asignarTicket($ticketId, (int) $payload['tecnico_id'], $payload['comentario'] ?? null);

        if ($request->expectsJson()) {
            return $ok
                ? response()->json(['ok' => true])
                : response()->json(['ok' => false, 'error' => 'No se pudo asignar el ticket.'], 422);
        }

        return $ok
            ? back()->with('status', 'Ticket asignado.')
            : back()->withErrors(['tecnico_id' => 'No se pudo asignar el ticket.']);
    }

    public function cerrar(string $ticketId)
    {
        $ok = $this->data->cerrarTicket($ticketId);

        if (request()->expectsJson()) {
            return $ok
                ? response()->json(['ok' => true])
                : response()->json(['ok' => false, 'error' => 'No se pudo cerrar el ticket.'], 422);
        }

        return $ok
            ? back()->with('status', 'Ticket cerrado.')
            : back()->withErrors(['ticket' => 'No se pudo cerrar el ticket.']);
    }
}
