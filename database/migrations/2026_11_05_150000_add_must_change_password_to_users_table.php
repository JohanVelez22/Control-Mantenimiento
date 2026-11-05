<?php

use App\Support\PasswordsConocidas;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cambio obligatorio de contraseña: las cuentas marcadas deben definir una contraseña
 * nueva antes de usar el sistema. Se marcan las que hoy usan una contraseña conocida.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('password');
        });

        DB::table('users')->select('id', 'password')->orderBy('id')->each(function ($usuario) {
            if ($usuario->password && PasswordsConocidas::coincideConHash($usuario->password)) {
                DB::table('users')->where('id', $usuario->id)->update(['must_change_password' => true]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
