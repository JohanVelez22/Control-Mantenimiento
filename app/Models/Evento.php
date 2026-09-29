<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    protected $fillable = [
        'user_id',
        'accion',
        'modelo_tipo',
        'modelo_id',
        'valores_antiguos',
        'valores_nuevos',
        'ip_direccion',
        'user_agent',
        'descripcion',
    ];

    protected $casts = [
        'valores_antiguos' => 'array',
        'valores_nuevos' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function modelo()
    {
        return $this->morphTo(__FUNCTION__, 'modelo_tipo', 'modelo_id');
    }

    public static function registrar($accion, $modelo = null, $viejos = null, $nuevos = null, $descripcion = null)
    {
        return self::create([
            'user_id' => auth()->id(),
            'accion' => $accion,
            'modelo_tipo' => $modelo ? get_class($modelo) : null,
            'modelo_id' => $modelo ? $modelo->id : null,
            'valores_antiguos' => $viejos,
            'valores_nuevos' => $nuevos,
            'ip_direccion' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
            'descripcion' => $descripcion,
        ]);
    }

    /**
     * Convierte una cadena User-Agent técnica en un formato amigable para humanos.
     * Ejemplo: "Google Chrome v153.0 · Windows 10/11 (64 bits)"
     */
    public static function parseUserAgent(?string $ua): ?string
    {
        if (! $ua || ! is_string($ua)) {
            return null;
        }

        if (! str_contains($ua, 'Mozilla/') && ! str_contains($ua, 'Chrome/') && ! str_contains($ua, 'Safari/') && ! str_contains($ua, 'Firefox/') && ! str_contains($ua, 'Windows NT')) {
            return $ua;
        }

        $browser = 'Navegador Web';
        $version = '';

        if (preg_match('/Edg(?:e)?\/([0-9.]+)/i', $ua, $m)) {
            $browser = 'Microsoft Edge';
            $version = $m[1];
        } elseif (preg_match('/OPR\/([0-9.]+)/i', $ua, $m) || preg_match('/Opera\/([0-9.]+)/i', $ua, $m)) {
            $browser = 'Opera';
            $version = $m[1];
        } elseif (preg_match('/Vivaldi\/([0-9.]+)/i', $ua, $m)) {
            $browser = 'Vivaldi';
            $version = $m[1];
        } elseif (stripos($ua, 'Brave') !== false) {
            $browser = 'Brave';
        } elseif (preg_match('/Firefox\/([0-9.]+)/i', $ua, $m)) {
            $browser = 'Mozilla Firefox';
            $version = $m[1];
        } elseif (preg_match('/Chrome\/([0-9.]+)/i', $ua, $m)) {
            $browser = 'Google Chrome';
            $version = $m[1];
        } elseif (preg_match('/Version\/([0-9.]+).*Safari/i', $ua, $m)) {
            $browser = 'Apple Safari';
            $version = $m[1];
        } elseif (stripos($ua, 'Safari') !== false && stripos($ua, 'Chrome') === false) {
            $browser = 'Apple Safari';
        }

        $versionClean = '';
        if ($version) {
            $vParts = explode('.', $version);
            $versionClean = count($vParts) > 2 ? "v{$vParts[0]}.{$vParts[1]}" : "v{$version}";
        }

        $os = 'Sistema Operativo';
        if (preg_match('/Windows NT 10\.0/i', $ua)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/Windows NT 6\.3/i', $ua)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/Windows NT 6\.2/i', $ua)) {
            $os = 'Windows 8';
        } elseif (preg_match('/Windows NT 6\.1/i', $ua)) {
            $os = 'Windows 7';
        } elseif (preg_match('/Windows NT 6\.0/i', $ua)) {
            $os = 'Windows Vista';
        } elseif (stripos($ua, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (preg_match('/Android\s+([0-9.]+)/i', $ua, $m)) {
            $os = "Android {$m[1]}";
        } elseif (preg_match('/OS ([0-9_]+) like Mac OS X/i', $ua, $m)) {
            $os = 'iOS '.str_replace('_', '.', $m[1]);
        } elseif (preg_match('/Mac OS X\s+([0-9_]+)/i', $ua, $m)) {
            $os = 'macOS '.str_replace('_', '.', $m[1]);
        } elseif (stripos($ua, 'Linux') !== false) {
            $os = 'Linux';
        } elseif (stripos($ua, 'CrOS') !== false) {
            $os = 'Chrome OS';
        }

        $arch = '';
        if (preg_match('/x86_64|x64|Win64|WOW64|amd64/i', $ua)) {
            $arch = '64 bits';
        } elseif (preg_match('/arm64|aarch64/i', $ua)) {
            $arch = 'ARM 64 bits';
        } elseif (preg_match('/i[3-6]86|x86|Win32/i', $ua)) {
            $arch = '32 bits';
        }

        $parts = [$browser];
        if ($versionClean) {
            $parts[] = $versionClean;
        }
        $osPart = $os.($arch ? " ({$arch})" : '');

        return implode(' ', $parts)." · {$osPart}";
    }
}
