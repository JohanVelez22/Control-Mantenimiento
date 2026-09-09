@extends('layouts.app')

@section('content')

<div class="glass-card p-6">
 <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">👨🏻‍💻</span> Usuarios
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Gestiona los accesos y credenciales de los colaboradores del sistema</p>
 </div>
 <div class="flex flex-wrap items-center gap-2">
   <div class="relative">
   <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
   <input type="text" id="search-usuarios" placeholder="Buscar usuario..." class="glass-input pl-9 w-48 sm:w-64">
   </div>
 @if(auth()->user()->isAdmin())
 <a href="{{ route('usuarios.create') }}" class="btn-primary">➕ Nuevo Usuario</a>
 @endif
 </div>
 </div>

 <div class="overflow-x-auto pb-2">
 <table id="tabla-usuarios" class="ts-table responsive-table">
 <thead>
 <tr>
 <th class="w-16 text-center">ID</th>
 <th class="w-16 text-center">Foto</th>
 <th>Nombre</th>
 <th>Email</th>
 <th>Rol</th>
 <th>Estado</th>
 <th>Creado</th>
 <th class="text-center w-28">Acciones</th>
 </tr>
 </thead>
 <tbody>
 @forelse($users as $u)
 @php $dim = !$u->active ? 'opacity-60 grayscale' : ''; @endphp
 <tr id="usuario-{{ $u->id }}" class="scroll-mt-[6.5rem]">
 <td class="text-center font-bold text-slate-800 dark:text-white {{ $dim }}">{{ $u->id }}</td>
 <td class="text-center {{ $dim }}">
  @if($u->photo)
    <img src="{{ asset('storage/' . $u->photo) }}" width="40" height="40" class="rounded-xl object-cover mx-auto shadow-sm cursor-pointer hover:opacity-80 transition" onclick="openImageLightbox('{{ asset('storage/' . $u->photo) }}', '{{ addslashes($u->name) }}', this)" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
    <div class="hidden w-10 h-10 rounded-xl bg-gray-200 dark:bg-gray-700 items-center justify-center text-gray-400 mx-auto text-xs font-bold shadow-sm">
      N/A
    </div>
  @else
    <div class="w-10 h-10 rounded-xl bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-400 mx-auto text-xs font-bold shadow-sm">
      N/A
    </div>
  @endif
 </td>
 <td class="font-bold text-slate-800 dark:text-white {{ $dim }}">{{ $u->name }}</td>
 <td class="{{ $dim }}">{{ $u->email }}</td>
 <td class="{{ $dim }}"><span class="capitalize">{{ $u->role }}</span></td>
 <td>
 <span class="pill {{ $u->active ? 'pill-done' : 'pill-anulado' }}">
 {{ $u->active ? 'Activo' : 'Inactivo' }}
 </span>
 </td>
 <td class="text-gray-500 {{ $dim }}">{{ $u->created_at->format('d/m/Y') }}</td>
 <td data-label="Acciones:" class="text-center w-28 {{ $dim }}">
   <div class="actions-grid flex justify-center items-center mx-auto">
   @if(auth()->user()->isAdmin() || auth()->id() === $u->id)
   <a href="{{ route('usuarios.edit', $u->id) }}" class="btn-ghost w-8 h-8 flex items-center justify-center p-0 text-xs text-yellow-600" title="Editar">✏️</a>
   @else
   <button type="button" onclick="openUserDetailModal({{ json_encode([
       'id' => $u->id,
       'name' => $u->name,
       'email' => $u->email,
       'role' => ucfirst($u->role),
       'active' => (bool)$u->active,
       'created_at' => $u->created_at->format('d/m/Y H:i'),
       'photo' => $u->photo ? asset('storage/' . $u->photo) : null,
   ]) }})" class="btn-ghost w-8 h-8 flex items-center justify-center p-0 text-xs text-indigo-600 dark:text-indigo-400" title="Ver Detalles">👁️</button>
   @endif
   
   @if(auth()->user()->isAdmin() && auth()->id() !== $u->id)
                              <button type="button" onclick="openAnularModal('{{ route('usuarios.anular', $u->id) }}', {{ !$u->active ? 'true' : 'false' }})" class="btn-ghost w-8 h-8 flex items-center justify-center p-0 text-xs {{ $u->active ? 'text-red-600' : 'text-emerald-600' }}" title="{{ $u->active ? 'Anular Usuario' : 'Reactivar Usuario' }}">
   {{ $u->active ? '🚫' : '✅' }}
   </button>
   @endif
   </div>
   </td>
 </tr>
 @empty
 <tr>
 <td colspan="8" class="p-16 text-center">
 <div class="flex flex-col items-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">👨🏻‍💻</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin otros usuarios</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">Actualmente solo existes tú en el sistema. Puedes invitar a más colaboradores.</p>
 @if(auth()->user()->isAdmin())
 <a href="{{ route('usuarios.create') }}" class="btn-primary">➕ Crear Nuevo Usuario</a>
 @endif
 </div>
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
 <div class="mt-6 flex justify-end">
 {{ $users->appends(request()->query())->links() }}
 </div>
</div>

{{-- Modal Detalle de Usuario (Homogéneo Claro / Oscuro) --}}
<div id="modal-user-detail" onclick="if(event.target === this) closeUserDetailModal()" class="ts-modal-overlay opacity-0 hidden transition-opacity duration-300 z-[200]">
    <div id="modal-user-detail-card" class="ts-modal-card scale-95 opacity-0 p-6 w-full max-w-md mx-4 relative transition-all duration-300 shadow-2xl">
        {{-- Header con línea divisoria sutil --}}
        <div class="user-detail-divider">
            <h3 class="text-lg font-black text-slate-800 dark:text-white flex items-center gap-2">
                <span>👁️</span> Detalle del Usuario
            </h3>
        </div>

        {{-- Contenido con espacio vertical optimizado --}}
        <div class="text-center">
            {{-- Contenedor Avatar / Foto con separación vertical ajustada --}}
            <div id="u-detail-photo-container" class="user-detail-avatar-box">
                <img id="u-detail-photo" src="" class="w-full h-full object-cover hidden">
                <span id="u-detail-avatar" class="text-3xl">👨🏻‍💻</span>
            </div>

            <h4 id="u-detail-name" class="text-xl font-extrabold text-slate-800 dark:text-white mb-1"></h4>
            <p id="u-detail-email" class="text-base font-medium text-slate-700 dark:text-white mb-6"></p>

            {{-- Cajas de información adaptadas al tema claro y oscuro --}}
            <div class="grid grid-cols-2 gap-3 mb-6 text-left">
                <div class="user-detail-box">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 block mb-1">Rol en el sistema</span>
                    <span id="u-detail-role" class="text-sm font-bold text-indigo-600 dark:text-indigo-400 capitalize"></span>
                </div>
                <div class="user-detail-box">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 block mb-1">Estado de cuenta</span>
                    <span id="u-detail-status" class="pill text-xs"></span>
                </div>
                <div class="col-span-2 user-detail-box flex justify-between items-center">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Fecha de registro:</span>
                    <span id="u-detail-date" class="text-xs font-bold text-slate-700 dark:text-gray-300 font-mono"></span>
                </div>
            </div>

            <button type="button" onclick="closeUserDetailModal()" class="w-full btn-primary py-3 justify-center text-sm font-bold">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => filterTable('search-usuarios', 'tabla-usuarios'));

function openUserDetailModal(user) {
    document.getElementById('u-detail-name').textContent = user.name || 'Sin nombre';
    document.getElementById('u-detail-email').textContent = user.email || '—';
    document.getElementById('u-detail-role').textContent = user.role || '—';
    document.getElementById('u-detail-date').textContent = user.created_at || '—';

    const statusEl = document.getElementById('u-detail-status');
    if (user.active) {
        statusEl.className = 'pill pill-done';
        statusEl.textContent = 'Activo';
    } else {
        statusEl.className = 'pill pill-anulado';
        statusEl.textContent = 'Inactivo';
    }

    const photoImg = document.getElementById('u-detail-photo');
    const avatarSpan = document.getElementById('u-detail-avatar');
    if (user.photo) {
        photoImg.src = user.photo;
        photoImg.classList.remove('hidden');
        avatarSpan.classList.add('hidden');
    } else {
        photoImg.classList.add('hidden');
        avatarSpan.classList.remove('hidden');
    }

    const modal = document.getElementById('modal-user-detail');
    const card  = document.getElementById('modal-user-detail-card');
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        if (card) card.classList.remove('scale-95', 'opacity-0');
    }, 10);
}

function closeUserDetailModal() {
    const modal = document.getElementById('modal-user-detail');
    const card  = document.getElementById('modal-user-detail-card');
    if (!modal) return;
    modal.classList.add('opacity-0');
    if (card) card.classList.add('scale-95', 'opacity-0');
    document.body.style.overflow = 'auto';
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modal = document.getElementById('modal-user-detail');
        if (modal && !modal.classList.contains('hidden')) {
            closeUserDetailModal();
        }
    }
});
</script>
@endsection
