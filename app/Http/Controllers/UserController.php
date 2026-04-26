<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('id', 'desc')->get();
        return view('usuarios.index', compact('users'));
    }

    public function create()
    {
        return view('usuarios.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
            'role' => 'required|in:admin,tecnico',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'active' => $request->boolean('active'), // Captura correctamente el estado
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        return view('usuarios.edit', ['user' => $usuario]);
    }

    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $usuario->id,
            'role' => 'required|in:admin,tecnico',
        ]);

        if ($request->filled('password') || $request->filled('password_confirmation')) {
            $request->validate([
                'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed']
            ]);
            $usuario->password = Hash::make($request->password);
        }

        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->role = $request->role;
        $usuario->active = $request->boolean('active'); 

        $usuario->save();

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return redirect()->route('usuarios.index')->with('error', 'No puedes eliminar tu propia cuenta.');
        }
        $usuario->delete();
        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado.');
    }
}
