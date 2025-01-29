<div class="modal fade" id="exportarAppModal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('exportar-contactos') }}" method="GET">
            {{-- <form id="createForm"> --}}
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Exportar contactos</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @csrf
                    {{-- estos son los datos que se deben llenar automaticamente --}}
                    <div class="row">
                        <div class="col-12 mb-3 mt-3">
                            <p>¿Desea descargar todos los contactos?</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    {{-- <button type="submit" id="uploadBtn" class="btn btn-primary">Cargar</button> --}}
                    <button type="submit" class="btn btn-primary">Descargar</button>
                </div>
            </form>
        </div>
    </div>
</div>
