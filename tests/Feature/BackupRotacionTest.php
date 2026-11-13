<?php

namespace Tests\Feature;

use App\Console\Commands\BackupDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * La rotación y la retención solo tocan respaldos propios, cuentan el cupo POR TIPO y
 * conservan siempre un mínimo de copias de cada tipo.
 */
class BackupRotacionTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = storage_path('app/rotacion_test_'.uniqid());
        File::makeDirectory($this->dir, 0755, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function crear(string $nombre, int $diasAtras): void
    {
        $ruta = $this->dir.DIRECTORY_SEPARATOR.$nombre;
        File::put($ruta, 'x');
        touch($ruta, now()->subDays($diasAtras)->timestamp);
    }

    private function comando(): BackupDatabase
    {
        $cmd = new class extends BackupDatabase
        {
            public function rotar(string $d, int $m): void
            {
                $this->aplicarPoliticaRotacion($d, $m);
            }

            public function retener(string $d, int $dias): void
            {
                $this->limpiarBackupsAntiguos($d, $dias, 'test');
            }

            public function line($string, $style = null, $verbosity = null) {}
        };

        return $cmd;
    }

    public function test_reconoce_solo_los_nombres_propios(): void
    {
        $this->assertSame('bd', BackupDatabase::tipoRespaldo('db_tecni_systemas_2026-11-13_02-54-41.sql'));
        $this->assertSame('codigo', BackupDatabase::tipoRespaldo('code_tecni_systemas_2026-11-13_02-54-41.zip'));
        $this->assertSame('integral', BackupDatabase::tipoRespaldo('backup_integral_tecni_systemas_2026-11-13_02-54-41.zip'));
        $this->assertSame('bd_multimedia', BackupDatabase::tipoRespaldo('backup_bd_multimedia_tecni_systemas_2026-11-13_02-54-41.zip'));
        $this->assertNull(BackupDatabase::tipoRespaldo('copia_manual_importante.sql'));
        $this->assertNull(BackupDatabase::tipoRespaldo('db_sin_fecha.sql'));
    }

    public function test_las_copias_de_codigo_no_desplazan_los_respaldos_de_base_de_datos(): void
    {
        foreach (range(1, 4) as $i) {
            $this->crear("db_x_2026-11-0{$i}_10-00-00.sql", 20 - $i);
        }
        foreach (range(1, 9) as $i) {
            $this->crear("code_x_2026-11-1{$i}_10-00-00.zip", 9 - $i);
        }
        $this->crear('copia_manual_importante.sql', 400);

        $this->comando()->rotar($this->dir, 5);

        $this->assertCount(4, File::glob($this->dir.'/db_*.sql'), 'Los respaldos de BD no deben rotar por culpa de las copias de código');
        $this->assertCount(5, File::glob($this->dir.'/code_*.zip'));
        $this->assertFileExists($this->dir.'/copia_manual_importante.sql');
    }

    public function test_la_retencion_conserva_un_minimo_aunque_todo_sea_antiguo(): void
    {
        foreach (range(1, 6) as $i) {
            $this->crear("db_x_2026-01-0{$i}_10-00-00.sql", 100 + $i);
        }
        $this->crear('otro_archivo_antiguo.sql', 400);

        $this->comando()->retener($this->dir, 15);

        $this->assertCount(3, File::glob($this->dir.'/db_*.sql'), 'Siempre deben quedar las 3 copias más recientes');
        $this->assertFileExists($this->dir.'/db_x_2026-01-01_10-00-00.sql');
        $this->assertFileExists($this->dir.'/otro_archivo_antiguo.sql');
    }
}
