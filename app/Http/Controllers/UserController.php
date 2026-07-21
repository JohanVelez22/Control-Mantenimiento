<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('id', 'desc')->paginate(10);
        return view('usuarios.index', compact('users'));
    }

    public function show(User $usuario)
    {
        return redirect()->route('usuarios.edit', $usuario);
    }

    public function create()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('usuarios.index')->with('error', 'Solo el administrador puede crear usuarios.');
        }
        return view('usuarios.create');
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('usuarios.index')->with('error', 'Solo el administrador puede crear usuarios.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
            'role' => 'required|in:admin,tecnico,invitado',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'active' => $request->boolean('active'), // Captura correctamente el estado
        ];

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('users', 'public');
        }

        User::create($data);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        if (auth()->user()->role !== 'admin' && auth()->id() !== $usuario->id) {
            return redirect()->route('usuarios.index')->with('error', 'Solo puedes editar tu propio perfil.');
        }
        return view('usuarios.edit', ['user' => $usuario]);
    }

    public function update(Request $request, User $usuario)
    {
        if (auth()->user()->role !== 'admin' && auth()->id() !== $usuario->id) {
            return redirect()->route('usuarios.index')->with('error', 'Solo puedes editar tu propio perfil.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $usuario->id,
            'role' => 'required|in:admin,tecnico,invitado',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
        ]);

        // El administrador puede cambiar roles
        if (auth()->user()->role === 'admin') {
            $usuario->role = $request->role;
        }

        if ($request->filled('password') || $request->filled('password_confirmation')) {
            $rules = [
                'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
            ];
            // Al editar tu propia cuenta se exige la contraseña actual (seguridad).
            // Un administrador editando a otro usuario no necesita conocer su contraseña.
            if (auth()->id() === $usuario->id) {
                $rules['current_password'] = ['required', 'current_password'];
            }
            $request->validate($rules);
            $usuario->password = Hash::make($request->password);
        }

        if ($request->hasFile('photo')) {
            if ($usuario->photo && Storage::disk('public')->exists($usuario->photo)) {
                Storage::disk('public')->delete($usuario->photo);
            }
            $usuario->photo = $request->file('photo')->store('users', 'public');
        }

        $usuario->name = $request->name;
        $usuario->email = $request->email;

        $usuario->save();

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    
    public function anular(User $usuario)
    {
        if (\Illuminate\Support\Facades\Auth::user()->role !== 'admin') {
            return back()->with('error', 'No tienes permisos para modificar el estado.');
        }

        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes anular tu propio usuario.');
        }

        $usuario->update(['active' => !$usuario->active]);
        $action = $usuario->active ? 'reactivado' : 'desactivado (anulado)';

        return back()->with('success', "El usuario ha sido {$action} exitosamente.");
    }
}