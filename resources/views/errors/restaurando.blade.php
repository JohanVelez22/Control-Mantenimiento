<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="30">
    <title>Mantenimiento en curso</title>
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f1f5f9; color: #0f172a; }
        @media (prefers-color-scheme: dark) { body { background: #0f172a; color: #e2e8f0; } .tarjeta { background: #1e293b; } }
        .tarjeta { max-width: 28rem; margin: 1rem; padding: 2rem; border-radius: 1rem; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,.08); text-align: center; }
        h1 { font-size: 1.25rem; margin: 0 0 .75rem; }
        p { margin: 0; line-height: 1.5; }
        small { display: block; margin-top: 1rem; opacity: .7; }
    </style>
</head>
<body>
    <main class="tarjeta" role="status" aria-live="polite">
        <h1>Mantenimiento en curso</h1>
        <p>{{ $mensaje }}</p>
        <small>Esta página se actualiza sola cada 30 segundos.</small>
    </main>
</body>
</html>
