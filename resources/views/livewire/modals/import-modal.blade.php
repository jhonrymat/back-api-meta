<div class="modal fade" id="importAppModal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('importar-contactos') }}" method="POST" enctype="multipart/form-data">
            {{-- <form id="createForm"> --}}
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Importar contactos</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @csrf
                    {{-- estos son los datos que se deben llenar automaticamente --}}
                    <div class="row">
                        <div class="col-12 mb-3 mt-3">
                            <p>Cargue CSV en el formato indicado
                                <a href="{{ route('descargar-plantilla') }}" target="_blank">Formato CSV de muestra</a>
                            </p>
                        </div>

                        <div class="col-12 col-md-12 mb-3 mt-3">
                            <label for="file">
                                <span style="color: red;">*</span>Entrada de archivo (Datasheet)
                            </label>
                            <input type="file" class="form-control-file" id="fileUpload" name="file"
                                accept=".csv" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    {{-- <button type="submit" id="uploadBtn" class="btn btn-primary">Cargar</button> --}}
                    <button type="submit" class="btn btn-primary">Cargar</button>
                </div>
            </form>
        </div>
    </div>
</div>
