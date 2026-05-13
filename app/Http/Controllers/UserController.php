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
        $users = User::orderBy('id', 'desc')->get();
        return view('usuarios.index', compact('users'));
    }

    public function show(User $usuario)
    {
        return redirect()->route('usuarios.edit', $usuario);
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
            'role' => 'required|in:admin,tecnico,invitado',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
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
        return view('usuarios.edit', ['user' => $usuario]);
    }

    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $usuario->id,
            'role' => 'required|in:admin,tecnico,invitado',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Opcional: define en .env ROLE_PROMOTE_ADMIN_SECRET y ROLE_PROMOTE_TECNICO_SECRET (mismos valores por defecto si no existen).
        $secretAdmin = env('ROLE_PROMOTE_ADMIN_SECRET', 'Admin2026*');
        $secretTecnico = env('ROLE_PROMOTE_TECNICO_SECRET', 'Tecny2026*');

        if ($request->role === 'admin' && $request->admin_password !== $secretAdmin) {
            return back()->withErrors(['admin_password' => 'Contraseña de autorización incorrecta para el rol de Administrador.'])->withInput();
        }

        if ($request->role === 'tecnico' && $request->admin_password !== $secretTecnico) {
            return back()->withErrors(['admin_password' => 'Contraseña de autorización incorrecta para el rol de Técnico.'])->withInput();
        }

        if ($request->filled('password') || $request->filled('password_confirmation')) {
            $request->validate([
                'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed']
            ]);
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

    /**
     * Cambia solo la contraseña de un usuario (ruta dedicada; útil para integraciones o formularios separados).
     */
    public function changePassword(Request $request, User $usuario)
    {
        $request->validate([
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
        ]);

        $usuario->password = Hash::make($request->password);
        $usuario->save();

        return redirect()->route('usuarios.edit', $usuario)->with('success', 'Contraseña actualizada correctamente.');
    }
}
