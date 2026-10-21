<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DuskRouteProtectionTest extends TestCase
{
    public function test_dusk_login_routes_are_not_registered_in_standard_runtime(): void
    {
        // En tiempo de ejecución estándar, no debe existir la ruta _dusk/login
        $this->assertFalse(Route::has('dusk.login'), 'La ruta vulnerable dusk.login no debe estar registrada.');

        $hasDuskRoute = false;
        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), '_dusk')) {
                $hasDuskRoute = true;
                break;
            }
        }

        $this->assertFalse($hasDuskRoute, 'No deben existir rutas públicas bajo el prefijo _dusk.');
    }
}
