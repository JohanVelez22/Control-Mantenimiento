@extends('layouts.app')

@section('title', 'Detalle de Evento')

@section('page-title', 'Evento de Auditoría')

@section('content')
    <div class="card" style="max-width:700px; padding:1.8rem;">
        <div class="flex-between mb-3">
            <div>
                <span class="badge badge-blue" style="text-transform:capitalize;">{{ $evento->accion }}</span>
                <span class="text-secondary" style="margin-left:0.5rem;">{{ $evento->created_at?->format('d/m/Y H:i') }}</span>
            </div>
            <span class="text-secondary">{{ $evento->user->name ?? 'Sistema' }}</span>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem;">
            <div><span class="text-secondary" style="font-size:0.8rem;">Modelo</span><br><strong>{{ class_basename($evento->modelo_tipo) ?? '—' }}</strong></div>
            <div><span class="text-secondary" style="font-size:0.8rem;">ID del modelo</span><br><strong>{{ $evento->modelo_id ?? '—' }}</strong></div>
        </div>

        <div class="mt-3"><span class="text-secondary" style="font-size:0.8rem;">Descripción</span><p style="margin:0.2rem 0 0;">{{ $evento->descripcion }}</p></div>

        <hr style="border:none; border-top:1px solid rgba(0,0,0,0.08); margin:1.2rem 0;">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.2rem;">
            <div>
                <h4 style="margin:0 0 0.6rem;">Valores anteriores</h4>
                <pre style="background:rgba(0,0,0,0.04); padding:0.8rem; border-radius:10px; font-size:0.78rem; overflow:auto; max-height:300px;">{{ json_encode($viejos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            <div>
                <h4 style="margin:0 0 0.6rem;">Valores nuevos</h4>
                <pre style="background:rgba(0,0,0,0.04); padding:0.8rem; border-radius:10px; font-size:0.78rem; overflow:auto; max-height:300px;">{{ json_encode($nuevos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>

        <div class="flex-center mt-3" style="justify-content:flex-start;">
            <a href="{{ route('eventos.index') }}" class="btn btn-secondary">← Volver</a>
        </div>
    </div>
@endsection
