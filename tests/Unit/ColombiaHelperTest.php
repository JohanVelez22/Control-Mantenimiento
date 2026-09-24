<?php

namespace Tests\Unit;

use App\Helpers\ColombiaHelper;
use PHPUnit\Framework\TestCase;

class ColombiaHelperTest extends TestCase
{
    public function test_departamentos_retorna_lista_valida(): void
    {
        $departamentos = ColombiaHelper::departamentos();

        $this->assertIsArray($departamentos);
        $this->assertNotEmpty($departamentos);
        $this->assertContains('Antioquia', $departamentos);
        $this->assertContains('Cundinamarca', $departamentos);
        $this->assertContains('Valle del Cauca', $departamentos);
    }

    public function test_municipios_de_departamento_retorna_ciudades_esperadas(): void
    {
        $municipiosAntioquia = ColombiaHelper::municipiosDe('Antioquia');
        $this->assertContains('Medellín', $municipiosAntioquia);
        $this->assertContains('Envigado', $municipiosAntioquia);

        $municipiosCundinamarca = ColombiaHelper::municipiosDe('Cundinamarca');
        $this->assertContains('Bogotá D.C.', $municipiosCundinamarca);

        $municipiosInexistente = ColombiaHelper::municipiosDe('DepartamentoInexistente');
        $this->assertEmpty($municipiosInexistente);
    }

    public function test_tipos_identificacion_retorna_mapeo_correcto(): void
    {
        $tipos = ColombiaHelper::tiposIdentificacion();

        $this->assertArrayHasKey('cedula_ciudadania', $tipos);
        $this->assertArrayHasKey('nit', $tipos);
        $this->assertStringContainsString('CC', $tipos['cedula_ciudadania']);
    }

    public function test_whatsapp_url_formatea_numeros_colombianos(): void
    {
        // 10 dígitos (añade prefijo 57)
        $url1 = ColombiaHelper::whatsappUrl('3001234567');
        $this->assertEquals('https://wa.me/573001234567', $url1);

        // Con caracteres especiales (espacios, guiones, paréntesis)
        $url2 = ColombiaHelper::whatsappUrl('(300) 123-4567');
        $this->assertEquals('https://wa.me/573001234567', $url2);

        // Con mensaje codificado
        $url3 = ColombiaHelper::whatsappUrl('3001234567', 'Hola servicio');
        $this->assertEquals('https://wa.me/573001234567?text=Hola+servicio', $url3);

        // Teléfono nulo o inválido
        $this->assertNull(ColombiaHelper::whatsappUrl(null));
        $this->assertNull(ColombiaHelper::whatsappUrl('123'));
    }
}
