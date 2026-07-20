@extends('layouts.consulta')

@section('title', 'Consulta de Servicios')

@section('content')
    <div class="consulta-card">
        <div style="text-align:center; margin-bottom:2rem;">
            <div style="width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,#0071e3,#42a5f5);display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;margin:0 auto 1rem;box-shadow:0 8px 24px rgba(0,113,227,0.4);">
                ⚙
            </div>
            <h1 style="font-size:1.6rem; margin:0;">Consulta de servicios</h1>
            <p class="text-secondary" style="margin:0.4rem 0 0;">Ingrese su cédula, teléfono u número de orden</p>
        </div>

        <form method="GET" action="{{ route('guest.search') }}" class="consulta-form" id="consulta-form">
            <div class="consulta-tabs">
                <button type="button" class="consulta-tab active" data-tipo="mantenimiento">🔧 Mantenimientos</button>
                <button type="button" class="consulta-tab" data-tipo="electronica">⚡ Electrónica</button>
            </div>
            <input type="hidden" name="tipo" id="tipo" value="mantenimiento">
            <input type="text" name="query" class="consulta-input" placeholder="Ej: 123456789, 3001234567, ORD-001" required autofocus>
            <button type="submit" class="consulta-btn">Buscar</button>
        </form>
    </div>

    <script>
    document.querySelectorAll('.consulta-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.consulta-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById('tipo').value = tab.dataset.tipo;
        });
    });
    </script>

    <style>
        .consulta-card { max-width: 480px; margin: 4rem auto; background: rgba(255,255,255,0.6); backdrop-filter: blur(24px); border-radius: 24px; padding: 2.5rem; box-shadow: 0 12px 40px rgba(31,38,135,0.15); }
        .consulta-form { display: flex; flex-direction: column; gap: 1rem; }
        .consulta-tabs { display: flex; gap: 0.5rem; }
        .consulta-tab { flex: 1; padding: 0.6rem; border-radius: 12px; border: 1px solid transparent; background: rgba(0,0,0,0.04); font-weight: 500; cursor: pointer; transition: all 0.2s; }
        .consulta-tab.active { background: var(--accent-soft); color: var(--accent); }
        .consulta-input { padding: 0.9rem 1.1rem; border-radius: 14px; border: 1px solid rgba(0,0,0,0.1); font-size: 1rem; outline: none; }
        .consulta-input:focus { border-color: var(--accent); box-shadow: 0 0 0 4px var(--accent-soft); }
        .consulta-btn { padding: 0.9rem; border-radius: 14px; border: none; background: var(--accent); color: #fff; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.2s; }
        .consulta-btn:hover { background: #0077ed; }
    </style>
@endsection
