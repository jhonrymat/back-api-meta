 <div class="card shadow-lg">
     {{-- boton Maddigo volver a /home --}}
     <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
         <!-- Botón Volver a Inicio -->
         <a href="{{ route('home') }}" class="btn btn-info btn-sm" style="color: white;">
             <i class="fas fa-home"></i> Volver a Inicio
         </a>

         <!-- Título centrado -->
         <h3 class="mb-0 text-center flex-grow-1" style="margin-right: 7.5rem;">Mensajes enviados</h3>
     </div>
     <div class="card-body">
         <!-- Botón para refrescar tabla -->
         <div class="mb-3 d-flex justify-content-end">
             <button wire:click="$refresh" class="btn btn-secondary">
                 <i class="fas fa-sync-alt"></i> Refrescar Tabla
             </button>
         </div>
         <!-- Más contenido aquí -->
         <div>
             @livewire('messages-table')
         </div>
     </div>
 </div>
