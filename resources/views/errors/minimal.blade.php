{{-- Plantilla de error autónoma: sin consultas a la base de datos ni assets compilados,
     para que funcione incluso cuando el error se debe a la base de datos o al build. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · WolfCore System</title>
    <style>
        :root { --bg: #f1f5f9; --card: #ffffff; --text: #0f172a; --muted: #64748b; --accent: #2563eb; --border: #e2e8f0; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0b1220; --card: #111a2e; --text: #f1f5f9; --muted: #94a3b8; --accent: #3b82f6; --border: #1e293b; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               padding: 16px; background: var(--bg); color: var(--text);
               font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .card { width: 100%; max-width: 440px; background: var(--card); border: 1px solid var(--border);
                border-radius: 20px; padding: 32px 24px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
        .icon { font-size: 44px; line-height: 1; }
        .code { margin: 12px 0 0; font-size: 44px; font-weight: 900; letter-spacing: .02em; }
        h1 { margin: 8px 0 0; font-size: 20px; }
        p { margin: 12px 0 0; color: var(--muted); font-size: 15px; line-height: 1.5; }
        .actions { margin-top: 24px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        a, button { appearance: none; border: 1px solid var(--border); background: transparent; color: var(--text);
                    padding: 10px 18px; border-radius: 12px; font-weight: 700; font-size: 14px; cursor: pointer; text-decoration: none; }
        a.primary { background: var(--accent); border-color: var(--accent); color: #fff; }
    </style>
</head>
<body>
    <main class="card">
        <div class="icon" aria-hidden="true">@yield('icon')</div>
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            <button type="button" onclick="history.back()">⬅️ Volver</button>
            <a class="primary" href="{{ url('/') }}">Ir al inicio</a>
        </div>
    </main>
</body>
</html>
