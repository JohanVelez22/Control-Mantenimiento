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

    public function test_format_title_case_capitaliza_nombres_y_direcciones(): void
    {
        // 1. Minúsculas a Title Case con acentos
        $this->assertEquals('Carlos Arturo Mendoza', ColombiaHelper::formatTitleCase('carlos arturo mendoza'));
        $this->assertEquals('Ángel María Córdoba', ColombiaHelper::formatTitleCase('ángel maría córdoba'));

        // 2. Mayúsculas a Title Case
        $this->assertEquals('Juan Carlos Pérez', ColombiaHelper::formatTitleCase('JUAN CARLOS PÉREZ'));

        // 3. Direcciones con nomenclatura colombiana y D.C.
        $this->assertEquals('Calle 45 # 12-34 Apto 201 Bogotá D.C.', ColombiaHelper::formatTitleCase('calle 45 # 12-34 apto 201 bogotá d.c.'));
        $this->assertEquals('Cra 15 # 20-30 Oficina 504', ColombiaHelper::formatTitleCase('CRA 15 # 20-30 OFICINA 504'));
        $this->assertEquals('Transversal 14 Bis # 100 - 20', ColombiaHelper::formatTitleCase('transversal 14 bis # 100 - 20'));

        // 4. Preservación estricta de siglas societarias y comerciales
        $this->assertEquals('Mayorista Tecnológico Dusk SAS', ColombiaHelper::formatTitleCase('mayorista tecnológico dusk sas'));
        $this->assertEquals('Distribuciones ABC S.A.S. LTDA', ColombiaHelper::formatTitleCase('DISTRIBUCIONES ABC S.A.S. LTDA'));
        $this->assertEquals('Inversiones El Triunfo S.A.', ColombiaHelper::formatTitleCase('inversiones el triunfo s.a.'));

        // 5. Casos límite (null y strings vacíos)
        $this->assertNull(ColombiaHelper::formatTitleCase(null));
        $this->assertEquals('', ColombiaHelper::formatTitleCase(''));
        $this->assertEquals('', ColombiaHelper::formatTitleCase('   '));
    }
}
