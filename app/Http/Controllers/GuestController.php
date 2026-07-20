<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class GuestController extends Controller
{
    /**
     * Mostrar el panel dedicado para invitados
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        return view('guest.dashboard');
    }

    /**
     * Redirigir búsquedas desde el panel de invitado a las consultas específicas
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:mantenimiento,electronica',
            'query' => 'required|string|min:5|max:30|regex:/^[\d\s\-\.#]+$/',
        ]);

        $tipo = $validated['tipo'];
        $query = $validated['query'];

        // Redirigir a la consulta apropiada según el tipo
        if ($tipo === 'mantenimiento') {
            return Redirect::to(route('consulta.mantenimientos', ['q' => $query]));
        } else {
            return Redirect::to(route('consulta.electronicas', ['q' => $query]));
        }
    }
}