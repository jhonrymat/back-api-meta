@extends('adminlte::page')

@section('title', 'Contratación local')

@section('content_header')
    <h1>Solicitudes de Contratacion local</h1>
@stop

@section('content')

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <div class="row">
        <!-- Primera vista que ocupa el 100% del contenedor -->
        <div class="col-md-6" style="overflow-y: auto; max-height: 74vh">
            <div class="card">
                <div class="card-header text-white bg-secondary mb-3">
                    Donde publicar
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Difundir en
                            @php
                                // Intenta decodificar la cadena JSON. Si falla, usa un array vacío como respaldo.
                                $publicArray = json_decode($solicitud->publicacion, true) ?? [];
                            @endphp
                            <span>{{ collect($publicArray)->filter()->implode(', ') }}
                                <i class="fas fa-copy" style="margin-left: 10px; cursor: pointer;"
                                    onclick="copyToClipboard('{{ collect($publicArray)->filter()->implode(', ') }}')">
                                </i></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Estado
                            <span
                                class="badge {{ $solicitud->estado == 'publicado' ? 'badge-primary' : 'badge-danger' }} badge-pill">{{ $solicitud->estado }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Tipo de contrato
                            <span class="badge badge-primary badge-pill">{{ $solicitud->contrato }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            link PDF
                            <span>
                                <a href="{{ $solicitud->contrato == 'MO' ? env('PROJECT2_URL_MANO') : env('PROJECT2_URL_SERVICIO') }}/{{ $solicitud->id_pdf }}"
                                    target="_blank" rel="noopener noreferrer" style="text-decoration: none">
                                    {{ $solicitud->contrato == 'MO' ? env('PROJECT2_URL_MANO') : env('PROJECT2_URL_SERVICIO') }}/{{ $solicitud->id_pdf }}
                                </a>
                                <i class="fas fa-copy" style="margin-left: 10px; cursor: pointer;"
                                    onclick="copyToClipboard('{{ $solicitud->contrato == 'MO' ? env('PROJECT2_URL_MANO') : env('PROJECT2_URL_SERVICIO') }}/{{ $solicitud->id_pdf }}')"></i>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header text-white bg-secondary mb-3">
                    Información de la solicitud
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <input id="solicitudId" name="solicitudId" type="hidden" value="{{ $solicitud->id }}" />
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Contrato
                            <span>
                                {{ $solicitud->codigo_contrato }}
                                <i class="fas fa-copy" style="margin-left: 10px; cursor: pointer;"
                                    onclick="copyToClipboard('{{ $solicitud->codigo_contrato }}')">
                                </i>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Contratista
                            <span>{{ $solicitud->empresa }}
                                <i class="fas fa-copy" style="margin-left: 10px; cursor: pointer;"
                                    onclick="copyToClipboard('{{ $solicitud->empresa }}')">
                                </i></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Objeto
                            <span>{{ $solicitud->objeto }}
                                <i class="fas fa-copy" style="margin-left: 10px; cursor: pointer;"
                                    onclick="copyToClipboard('{{ $solicitud->objeto }}')">
                                </i></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Descripción de actividades
                            <span>{{ $solicitud->desc_general_act }}
                                <i class="fas fa-copy" style="margin-left: 10px; cursor: pointer;"
                                    onclick="copyToClipboard('{{ $solicitud->desc_general_act }}')">
                                </i></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Requerimientos
                            @php
                                // Intenta decodificar la cadena JSON. Si falla, usa un array vacío como respaldo.
                                $requerimientoArray = json_decode($solicitud->requerimientos, true) ?? [];
                            @endphp
                            <ul class="list-unstyled mb-0">
                                @foreach (collect($requerimientoArray)->filter() as $requerimiento)
                                    <li>
                                        <h8>
                                            - {{ $requerimiento }}
                                    </li>
                                    </h8>
                                @endforeach
                            </ul>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Tiempo de Ejecución
                            <span>{{ $solicitud->tiempo_ejecucion }}
                                <i class="fas fa-copy" style="margin-left: 10px; cursor: pointer;"
                                    onclick="copyToClipboard('{{ $solicitud->tiempo_ejecucion }}')">
                                </i></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Fecha inicio
                            <span>{{ $solicitud->fecha_inicio }}
                                <i class="fas fa-copy" style="margin-left: 10px; cursor: pointer;"
                                    onclick="copyToClipboard('{{ $solicitud->fecha_inicio }}')">
                                </i></span>
                        </li>
                        {{-- <li class="list-group-item d-flex justify-content-between align-items-center">
                            Orden de servicio
                            <span class="badge badge-primary badge-pill">{{ $solicitud->tipo_orden_id }}</span>
                        </li> --}}
                        @if (!is_null($solicitud->fecha_recibo))
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Fecha límite entrega ofertas
                                <span>{{ $solicitud->fecha_recibo }}
                                    {{ $solicitud->hora_limite }}
                                    <i class="fas fa-copy" style="margin-left: 10px; cursor: pointer;"
                                        onclick="copyToClipboard('{{ $solicitud->hora_limite }}')">
                                    </i></span>
                            </li>
                        @endif

                        {{-- @if (!is_null($solicitud->hora_limite))
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Hora límite
                                <span class="badge badge-primary badge-pill">{{ $solicitud->hora_limite }}</span>
                            </li>
                        @endif --}}
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Ubicación de los trabajos
                            @php
                                // Intenta decodificar la cadena JSON. Si falla, usa un array vacío como respaldo.
                                $tagsArray = json_decode($solicitud->tag, true) ?? [];
                            @endphp

                            <span>{{ collect($tagsArray)->filter()->implode(', ') }}
                                <i class="fas fa-copy" style="margin-left: 10px; cursor: pointer;"
                                    onclick="copyToClipboard('{{ collect($tagsArray)->filter()->implode(', ') }}')">
                                </i></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Segunda vista inicialmente oculta -->
        <div class="col-md-6" style="overflow-y: auto; max-height: 74vh">
            <form id="createSend">
                <div class="card">
                    <div class="card-header text-white bg-secondary mb-3">
                        Envios masivos WhatsApp
                    </div>
                    <div class="card-body">
                        <div>
                            <label for="selectPlantilla">Seleccione un numero disponible</label>
                            <select id="selectPlantilla" class="form-select form-control mb-3" required>
                                <option value="">Selecciona un Número</option>
                                @foreach ($numeros as $numero)
                                    <option value="{{ $numero->id }}" data-id_telefono="{{ $numero->id_telefono }}"
                                        data-id_c_business="{{ $numero->aplicacion->id_c_business }}"
                                        data-token_api="{{ $numero->aplicacion->token_api }}">
                                        {{ $numero->nombre }} - {{ $numero->numero }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="templatesSelect">Seleccione una plantilla disponible</label>
                            <select id="templatesSelect" class="form-select form-control mb-3" required>
                                <option value="">Selecciona una plantilla</option>
                                <!-- Las opciones se cargarán aquí dinámicamente -->
                            </select>
                        </div>
                        <div>
                            <label for="distintivoSelect">Seleccione un distintivo</label>
                            <select id="distintivoSelect" class="form-select form-control mb-3" required>
                                <option value="">Esto servirá para los reportes</option>
                                @foreach ($distintivos as $dis)
                                    <option value="{{ $dis->id }}">
                                        {{ $dis->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="selectTag">Seleccione un grupo para enviar</label>
                            <div>
                                <select id="etiqueta" name="etiqueta[]" class="form-select form-control mb-3" multiple>
                                    <option value="">Selecciona una Etiqueta</option>
                                    @foreach ($tags as $tag)
                                        <option value="{{ $tag->id }}"
                                            data-numeros="{{ implode("\n", $tag->contactos->pluck('telefono')->toArray()) }}">
                                            {{ $tag->nombre }} - {{ $tag->contactos->count() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="exampleFormControlTextarea1">Lista de contactos</label>
                            <textarea class="form-control" id="exampleFormControlTextarea1" rows="3"></textarea>
                        </div>

                        <div id="templateDetails">
                            <!-- Los detalles de la plantilla se inyectarán aquí -->
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <label for="status_send">Cambiar estado del envio</label>
                                <select id="status_send" class="form-select form-control mb-3" required>
                                    <option value="">Selecciona estado</option>
                                    <option value="enviado">
                                        Enviado
                                    </option>
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label for="fechaInicio" class="form-label">Desea programar el envío?</label>
                                <input type="datetime-local" class="form-control" id="programar" name="programar">
                            </div>
                        </div>
                        <br>
                        <button type="submit" class="btn btn-primary">Enviar mensajes</button>

                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        /* Personalización de SweetAlert */
        .swal-wide {
            font-size: 12px !important;
            /* Tamaño de letra más pequeño */
            background-color: #f1fff4 !important;
            /* Color de fondo verde éxito */
            color: #ffffff !important;
            /* Color de letra blanco */
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/drag-and-drop.css') }}">
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.0.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        function findPlaceholders(text) {
            const regexp = /{{ '{' }}\d+{{ '}' }}/g;
            const matches = [];
            let match;
            while ((match = regexp.exec(text)) !== null) {
                matches.push({
                    text: match[0],
                    value: ''
                });
            }
            return matches;
        };
    </script>
    <script>
        function contienePlantilla(texto) {
            const regex = /\{\{.*?\}\}/;


            // Usa el método test() para buscar la coincidencia en el texto
            return regex.test(texto);
        }
    </script>
    <script>
        $(document).ready(function() {
            $('#etiqueta').change(function() {
                updateContactList();
            });

            function updateContactList() {
                var selectedTag = $('#etiqueta option:selected');
                var numeros = selectedTag.map(function() {
                    return $(this).data('numeros');
                }).get().join('\n');

                $('#exampleFormControlTextarea1').val(numeros);
            }
        });
    </script>
    <script>
        var templatesData = []; // Almacenará la información de las plantillas
        var templateLanguage = null;
        var templateName = null;
        var templateType = null;

        $(document).ready(function() {
            $('#selectPlantilla').change(function() {
                var selectedOption = $(this).find('option:selected');
                var idCBusiness = selectedOption.data('id_c_business'); //id_telefono
                var tokenApi = selectedOption.data('token_api');
                var messageTemplatesUrl = "{{ route('message.templates') }}";

                if (idCBusiness && tokenApi) {
                    Swal.fire({
                        title: 'Cargando plantillas...',
                        text: 'Por favor espera',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                    });
                    $.ajax({
                        url: messageTemplatesUrl, // Cambia esto por la ruta real a tu controlador
                        type: 'GET',
                        data: {
                            id_c_business: idCBusiness,
                            token_api: tokenApi
                        },
                        success: function(response) {
                            Swal.close(); // Cierra el SweetAlert de carga
                            if (response.success) {
                                console.log(response.data);
                                // Actualiza la variable global con la respuesta
                                templatesData = response.data;

                                // Vacía el select antes de cargar nuevos datos para evitar duplicados
                                //$('#templatesSelect').empty();

                                // Elimina todas las opciones excepto la primera
                                $('#templatesSelect option:not(:first)').remove();

                                // Itera sobre la respuesta y añade cada opción al select
                                response.data.forEach(function(item) {
                                    $('#templatesSelect').append(new Option(item.name,
                                        item.name
                                    )); // El texto y el valor de la opción son el nombre
                                });
                                Swal.fire('¡Cargado!',
                                    'Las plantillas se han cargado correctamente.',
                                    'success');
                                // No olvides añadir aquí el código para manejar la selección inicial si es necesario
                            } else {
                                Swal.fire('Error', 'No se pudieron cargar las plantillas.',
                                    'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.close();
                            Swal.fire('Error', 'Ocurrió un error al cargar las plantillas: ' +
                                error, 'error');
                        }
                    });
                }
            });

            $('#templatesSelect').change(function() {
                var selectedTemplateName = $(this).val();
                var selectedTemplate = templatesData.find(function(template) {
                    return template.name === selectedTemplateName;
                });
                // console.log(selectedTemplate);
                // console.log(selectedTemplate.language);
                if (selectedTemplate) {
                    templateLanguage = selectedTemplate.language; // Asigna el idioma a la variable
                    templateName = selectedTemplate.name; // Asigna el nombre a la variable
                    Swal.fire({
                        title: 'Cargando plantilla...',
                        text: 'Por favor espera.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                    });
                } else {
                    templateLanguage = null; // Reinicia a null si no se encuentra la plantilla
                    Swal.fire({
                        icon: 'error',
                        title: 'Plantilla no encontrada',
                        text: 'La plantilla seleccionada no pudo ser cargada. Por favor, intenta con otra.',
                    });
                }
                // Construye el HTML para los detalles de la plantilla
                // Inicializa el HTML para los detalles de la plantilla

                var detailsHtml = '';
                Swal.close();

                // Itera sobre los componentes de la plantilla
                selectedTemplate.components.forEach(component => {
                    if (component.type === 'HEADER') {
                        if (component.format === 'DOCUMENT') {
                            templateType = 'DOCUMENT';
                            detailsHtml +=
                                `<div class="my-5"><h5 class="text-h5">Header</h5>
                                    <div class="form-group">
                                    <label>Seleccione cómo desea proporcionar el documento:</label>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="useLink" name="PDFInputType" class="custom-control-input" value="PDFlink" checked>
                                        <label class="custom-control-label" for="useLink">Usar un link</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="useUpload" name="PDFInputType" class="custom-control-input" value="upload">
                                        <label class="custom-control-label" for="useUpload">Subir un PDF</label>
                                    </div>
                                </div>
                                <div class="form-group linkInput">
                                    <label for="header">Link del documento en formato (PDF)</label>
                                    <input type="text" class="form-control" id="header" name="header" required>
                                </div>
                                <div class="form-group uploadInput files drag-area" style="display:none;">
                                    <h2>Arrastre y suelta archivos</h2>
                                    <span>O</span>
                                    <button type="button">Seleccione su archivo</button>
                                    <input type="file" class="form-control-file" id="input-file" name="input-file" hidden accept=".pdf"/>
                                    <div id="preview"></div>
                                </div>
                            </div>`;
                        } else if (component.format === 'IMAGE') {
                            templateType = 'IMAGE';
                            detailsHtml +=
                                `<div class="my-5"><h5 class="text-h5">Header</h5>
                                <div class="form-group">
                                    <label for="header">Link de la imagen en formato (PNG o JPG)</label>
                                    <input type="text" class="form-control" id="header" name="header" required>
                                </div>
                            </div>`;
                        } else if (component.format === 'VIDEO') {
                            templateType = 'VIDEO';
                            detailsHtml +=
                                `<div class="my-5"><h5 class="text-h5">Header</h5>
                                <div class="form-group">
                                    <label for="header">Link del video en formato (MP4)</label>
                                    <input type="text" class="form-control" id="header" name="header" required>
                                </div>
                            </div>`;
                        } else {
                            templateType = 'TEXT';
                            detailsHtml +=
                                `<div class="my-5"><h5 class="text-h5">Header</h5><p>${component.text}</p></div>`;
                        }
                    } else if (component.type === 'BODY') {
                        var formattedText = component.text.replace(/\n/g, '<br>');
                        var placeholders = findPlaceholders(component.text);


                        // Genera HTML para los inputs de cada placeholder encontrado
                        var inputsHtml = placeholders.map(function(placeholder, index) {
                            return `<div class="form-group"><label for="${index}">${placeholder.text}</label><input type="text" class="form-control format" id="${index}" name="${index}" value="" required/></div>`;
                        }).join('');

                        detailsHtml +=
                            `<div class="my-5"><h5 class="text-h5">Body</h5><p class="pre-wrap">${formattedText}</p>${inputsHtml}</div>`;
                    } else if (component.type === 'FOOTER') {
                        detailsHtml +=
                            `<div class="my-5"><h5 class="text-h5">Footer</h5><p class="pre-wrap">${component.text}</p></div>`;
                    } else if (component.type === 'BUTTONS') {
                        detailsHtml += '<div class="my-5"><h5 class="text-h5">Buttons</h5>';

                        // Verificar si hay elementos en el array "buttons"
                        if (component.buttons && component.buttons.length > 0) {
                            detailsHtml +=
                                '<ul>'; // Puedes usar una lista para mostrar los botones
                            // Acceder a la URL de cada botón

                            // Iterar sobre cada botón en el array "buttons"
                            component.buttons.forEach(button => {
                                if (contienePlantilla(button.url)) {
                                    if (button.type = 'URL') {
                                        const buttonUrl = button.url;
                                        // Acceder a otros detalles del botón si es necesario (text, type, etc.)
                                        const buttonText = button.text;
                                        detailsHtml +=
                                            `<li>
                                                    <p class="pre-wrap">${buttonText}</p>
                                                    <p class="pre-wrap">${buttonUrl}</p>
                                                    <div class="my-5"><h5 class="text-h5">Boton Dinamico</h5>
                                                    <div class="form-group">
                                                        <label for="buttons">Completa la url del botton</label>
                                                        <input type="text" class="form-control" id="buttons" name="buttons" required>
                                                    </div>
                                                </li>`;
                                    } else if (button.type = 'PHONE_NUMBER') {
                                        const buttonUrl = button.phone_number;
                                        // Acceder a otros detalles del botón si es necesario (text, type, etc.)
                                        const buttonText = button.text;
                                        detailsHtml +=
                                            `<li>
                                                    <p class="pre-wrap">${buttonText}</p>
                                                    <p class="pre-wrap">${buttonUrl}</p>
                                                    <div class="my-5"><h5 class="text-h5">Boton Dinamico</h5>
                                                    <div class="form-group">
                                                        <label for="buttons">Escribe el numero de telefono</label>
                                                        <input type="text" class="form-control" id="buttons" name="buttons" required>
                                                    </div>
                                                </li>`;
                                    } else {
                                        detailsHtml +=
                                            `<li>
                                                    <p class="pre-wrap">Boton dinamico desconocido</p>
                                                </li>`;
                                    }
                                } else {
                                    if (button.type = 'URL') {
                                        const buttonUrl = button.url;
                                        const buttonPhone = button.phone_number;
                                        // Acceder a otros detalles del botón si es necesario (text, type, etc.)
                                        const buttonText = button.text;
                                        if (buttonUrl) {
                                            detailsHtml +=
                                                `<li>
                                            <p class="pre-wrap">${buttonText}</p>
                                            <p class="pre-wrap">${buttonUrl}</p>
                                            <div class="my-5"><h5 class="text-h5">Boton Estatico</h5>
                                        </li>`;
                                        } else {
                                            detailsHtml +=
                                                `<li>
                                            <p class="pre-wrap">${buttonText}</p>
                                            <p class="pre-wrap">${buttonPhone}</p>
                                            <div class="my-5"><h5 class="text-h5">Boton Estatico</h5>
                                        </li>`;
                                        }

                                    } else if (button.type = 'PHONE_NUMBER') {
                                        const buttonUrl = button.phone_number;

                                        // Acceder a otros detalles del botón si es necesario (text, type, etc.)
                                        const buttonText = button.text;

                                        detailsHtml +=
                                            `<li>
                                        <p class="pre-wrap">${buttonText}</p>
                                        <p class="pre-wrap">${buttonUrl}</p>
                                        <div class="my-5"><h5 class="text-h5">Boton Estatico</h5>
                                    </li>`;
                                    } else {
                                        detailsHtml +=
                                            `<li>
                                                    <p class="pre-wrap">Boton dinamico desconocido</p>
                                                </li>`;
                                    }
                                }

                            });

                            detailsHtml += '</ul>';
                        } else {
                            detailsHtml += '<p class="pre-wrap">No hay botones disponibles.</p>';
                        }

                        detailsHtml += '</div>';

                    }
                });

                // Inyecta los detalles construidos en el contenedor
                $('#templateDetails').html(detailsHtml);
                inicializarEventListenersDeImagen();
            });
        });

        $(document).ready(function() {
            $('#createSend').submit(function(e) {
                var fileInput = document.getElementById('input-file');
                var imageUrl = "{{ route('upload.pdf') }}";
                e.preventDefault(); // Prevenir la recarga de la página
                Swal.fire({
                    title: 'Enviando...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                });

                if ($('#header').val()) {
                    header_url = $('#header').val();
                    enviarDatos(header_url); // Envía los datos inmediatamente si solo es un enlace
                } else if (fileInput !== null) {
                    var formData = new FormData();
                    if (fileInput.files[0]) {
                        formData.append('pdf', fileInput.files[0]);
                    }
                    // Ahora, envía formData a Laravel usando AJAX
                    $.ajax({
                        url: imageUrl,
                        type: 'POST',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        processData: false, // Importante: no procesar los datos
                        contentType: false, // Importante: no establecer el tipo de contenido
                        success: function(response) {
                            // Puedes continuar aquí con el envío de los demás datos de tu formulario
                            enviarDatos(response.url);
                        },
                        error: function(xhr, status, error) {
                            Swal.close();
                            Swal.fire({
                                title: 'Error en el envío',
                                text: 'Error al subir pdf. Inténtalo de nuevo. Error: ' +
                                    xhr.responseText,
                                icon: 'error'
                            });
                            console.log('Error:', error);
                            console.log('Status:', status);
                            console.log('Response:', xhr.responseText);

                            // Datos del error
                            var errorData = {
                                message: error,
                                status: status,
                                response: xhr.responseText,
                                url: imageUrl
                            };

                            // Enviar el error al servidor
                            $.ajax({
                                type: "POST",
                                url: "/log-client-error", // Asegúrate de que esta ruta sea correcta
                                data: errorData,
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                        'content')
                                },
                                success: function(response) {
                                    console.log(
                                        "Datos del error enviadosa al administrador"
                                    );
                                },
                                error: function(xhr) {
                                    console.log(
                                        "No se pudieron enviar datos del error enviadosa al administrador"
                                    );
                                }
                            });
                        }
                    });
                } else {
                    const url = null;
                    enviarDatos(url);
                }
            });
        });
    </script>
    <script>
        function enviarDatos(url) {
            // Preparar los datos de los placeholders como un array
            var body_placeholders = [];

            $('#templateDetails .format').each(function() {
                body_placeholders.push($(this).val());
            });

            // Inicializar variables en null
            var header_type = null;
            var header_url = null;
            var buttons_url = null;
            var id_c_business = null;
            var id_c_business2 = null;
            var phone_id2 = null;
            var phone_id = null;
            var recipients = null;
            var template_language = null;
            var template_name = null;
            var token_api = null;
            var programar = null;
            var status_send = null;
            var solicitudId = null;
            var messageTemplatesUrl = "{{ route('send.message.templates') }}";
            var selectedTags = null;
            var distintivoSelect = null;

            // header_url = $('#header').val() ? $('#header').val() :
            //     null;
            // Luego, asignar valores a las variables
            header_type = templateType; // Ahora asignas el valor deseado
            header_url = url;
            buttons_url = $('#buttons').val() ? $('#buttons').val() :
                null; // Ahora asignas el valor deseado
            id_c_business2 = $('#selectPlantilla option:selected').data('id_c_business');
            id_c_business = id_c_business2.toString();
            phone_id2 = $('#selectPlantilla option:selected').data(
                'id_telefono'); // Ahora asignas el valor deseado, si es dinámico, ajusta
            phone_id = phone_id2.toString();
            recipients = $('#exampleFormControlTextarea1')
                .val(); // Ahora asignas el valor deseado, si es dinámico, ajusta
            template_language = templateLanguage; // Ahora asignas el valor deseado
            template_name = templateName; // Ahora asignas el valor deseado
            token_api = $('#selectPlantilla option:selected').data('token_api');
            programar = $('#programar').val() ? $('#programar').val() :
                null;
            status_send = $('#status_send').val();
            solicitudId = $('#solicitudId').val();
            // Obtener los IDs seleccionados en el select con id "etiqueta"
            selectedTags = $('#etiqueta').val();
            distintivoSelect = $('#distintivoSelect').val();


            // Organizar la información en un objeto
            var dataToSend = {
                body_placeholders: body_placeholders,
                header_type: header_type,
                header_url: header_url,
                buttons_url: buttons_url,
                id_c_business: id_c_business,
                phone_id: phone_id,
                recipients: recipients,
                template_language: template_language,
                template_name: template_name,
                token_api: token_api,
                programar: programar,
                status_send: status_send,
                solicitudId: solicitudId,
                selectedTags: selectedTags,
                distintivoSelect: distintivoSelect
            };
            $.ajax({
                type: "POST",
                url: messageTemplatesUrl,
                contentType: "application/json",
                data: JSON.stringify(dataToSend),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                async: true,
                success: function(response) {
                    // Cerrar loader
                    Swal.close();

                    // ✅ Respuesta inmediata del backend
                    // El batch se procesa en segundo plano

                    // Extraer info de la respuesta
                    const envioId = response.envio_id || 'N/A';
                    const totalRecipients = response.total_recipients || 0;
                    const message = response.message || 'Envío encolado correctamente';

                    // Limpiar formulario
                    $('#createSend').trigger("reset");

                    // Mostrar success con más info
                    Swal.fire({
                        icon: 'success',
                        title: '¡Envío Encolado!',
                        html: `
                    <p><strong>ID de Envío:</strong> ${envioId}</p>
                    <p><strong>Destinatarios:</strong> ${totalRecipients}</p>
                    <p class="text-muted mt-3">
                        Los mensajes se están enviando en segundo plano.<br>
                        Recibirás una notificación cuando finalice.
                    </p>
                `,
                        confirmButtonText: 'Entendido',
                        timer: 5000,
                        timerProgressBar: true
                    }).then(() => {
                        // Redirigir a página de seguimiento (opcional)
                        // window.location.href = `/envios/${envioId}`;

                        // O simplemente recargar
                        window.location.reload();
                    });
                },

                error: function(xhr, status, error) {
                    // Cerrar loader
                    Swal.close();

                    // Log detallado para debug
                    console.error("❌ AJAX Error:", {
                        status: status,
                        error: error,
                        statusCode: xhr.status,
                        response: xhr.responseText
                    });

                    // Preparar mensaje de error más claro
                    let errorMessage = 'No se pudo encolar el envío. Inténtalo de nuevo.';
                    let errorDetails = '';

                    try {
                        const errorData = JSON.parse(xhr.responseText);

                        if (errorData.message) {
                            errorMessage = errorData.message;
                        }

                        if (errorData.errors) {
                            errorDetails = '<ul class="text-left mt-2">';
                            for (const [field, messages] of Object.entries(errorData.errors)) {
                                messages.forEach(msg => {
                                    errorDetails += `<li>${msg}</li>`;
                                });
                            }
                            errorDetails += '</ul>';
                        }
                    } catch (e) {
                        // Si no es JSON, mostrar texto plano
                        errorDetails =
                            `<pre class="text-left text-xs mt-2">${xhr.responseText.substring(0, 300)}</pre>`;
                    }

                    // Registrar error en servidor (para análisis posterior)
                    $.ajax({
                        type: "POST",
                        url: "log-client-error",
                        contentType: "application/json",
                        data: JSON.stringify({
                            error: status + ' - ' + error,
                            details: xhr.responseText || 'No response text',
                            status_code: xhr.status,
                            endpoint: 'send-message-templates'
                        }),
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function() {
                            console.log("✅ Error logged on server");
                        },
                        error: function() {
                            console.log("⚠️ Failed to log error on server");
                        }
                    });

                    // Mostrar error al usuario
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en el Envío',
                        html: `
                    <p>${errorMessage}</p>
                    ${errorDetails}
                `,
                        confirmButtonText: 'Cerrar',
                        footer: xhr.status === 500 ?
                            '<span class="text-muted">Código de error: 500 - Error del servidor</span>' :
                            xhr.status === 422 ?
                            '<span class="text-muted">Verifica los datos del formulario</span>' :
                            ''
                    });
                }
            });
        }
    </script>
    <script>
        function inicializarEventListenersDeImagen() {
            document.getElementsByName('PDFInputType').forEach((radio) => {
                radio.addEventListener('change', function(e) {
                    toggleImageInput(e.target.value);
                });
            });

            inicializarEventosDeSubida();
        }

        function toggleImageInput(value) {
            if (value === 'PDFlink') {
                document.querySelector('.linkInput').style.display = '';
                document.querySelector('.uploadInput').style.display = 'none';
                document.querySelector('.linkInput input[type="text"]').required = true;
                document.querySelector('.uploadInput input[type="file"]').required = false;
                limpiarFormularioDeSubida();
            } else {
                document.querySelector('.linkInput').style.display = 'none';
                document.querySelector('.uploadInput').style.display = '';
                document.querySelector('.uploadInput input[type="file"]').required = true;
                document.querySelector('.linkInput input[type="text"]').required = false;
                document.querySelector('.linkInput input[type="text"]').value = '';
            }

            inicializarEventosDeSubida();
        }

        function inicializarEventosDeSubida() {
            const dropArea = document.querySelector(".drag-area");

            if (!dropArea) return;

            const input = dropArea.querySelector("#input-file");
            const button = dropArea.querySelector("button");

            // Remover event listeners previos
            button.removeEventListener("click", handleButtonClick);
            input.removeEventListener("change", handleFileInputChange);
            dropArea.removeEventListener("dragover", handleDragOver);
            dropArea.removeEventListener("dragleave", handleDragLeave);
            dropArea.removeEventListener("drop", handleDrop);

            // Añadir event listeners
            button.addEventListener("click", handleButtonClick);
            input.addEventListener("change", handleFileInputChange);
            dropArea.addEventListener("dragover", handleDragOver);
            dropArea.addEventListener("dragleave", handleDragLeave);
            dropArea.addEventListener("drop", handleDrop);
        }

        function handleButtonClick(e) {
            e.preventDefault();
            const input = document.querySelector(".drag-area #input-file");
            input.click();
        }

        function handleFileInputChange(e) {
            e.preventDefault();
            const files = e.target.files;
            mostrarArchivos(files);
        }

        function handleDragOver(e) {
            e.preventDefault();
            const dropArea = document.querySelector(".drag-area");
            dropArea.classList.add("active");
        }

        function handleDragLeave(e) {
            e.preventDefault();
            const dropArea = document.querySelector(".drag-area");
            dropArea.classList.remove("active");
        }

        function handleDrop(e) {
            e.preventDefault();
            const files = e.dataTransfer.files;
            mostrarArchivos(files);
            const dropArea = document.querySelector(".drag-area");
            dropArea.classList.remove("active");
        }

        function mostrarArchivos(files) {
            if (files.length === undefined) {
                procesarArchivo(files);
            } else {
                for (const file of files) {
                    procesarArchivo(file);
                }
            }
        }

        function procesarArchivo(file) {
            const docType = file.type;
            const validExtensions = ["application/pdf"];

            if (validExtensions.includes(docType)) {
                const fileReader = new FileReader();
                const id = `file-${Math.random().toString(32).substring(7)}`;

                fileReader.addEventListener("load", (e) => {
                    const pdfPreview = `
                        <div id="${id}" class="file-container">
                            <div class="status">
                                <span><i class="fa fa-file-pdf"></i></span>
                                <span>${file.name}</span>
                            </div>
                        </div>
                    `;
                    document.querySelector("#preview").innerHTML = pdfPreview;
                });

                fileReader.readAsDataURL(file);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Archivo no válido',
                    text: 'Por favor, selecciona un archivo PDF.',
                });
            }
        }

        function limpiarFormularioDeSubida() {
            const inputArchivo = document.getElementById('input-file');
            if (inputArchivo) {
                const nuevoInputArchivo = inputArchivo.cloneNode(true);
                inputArchivo.parentNode.replaceChild(nuevoInputArchivo, inputArchivo);
            }
            document.getElementById('preview').innerHTML = '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            inicializarEventListenersDeImagen();
        });
    </script>




    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                Swal.fire({
                    position: 'bottom-end', // Posiciona la alerta en la esquina inferior derecha.
                    title: 'Enlace copiado en portapapeles',
                    showConfirmButton: false,
                    timer: 1500, // El mensaje se cierra después de 1.5 segundos.
                    backdrop: 'rgba(0,0,0,0)', // hace el fondo completamente transparente
                    allowOutsideClick: true,
                    customClass: {
                        popup: 'swal-wide' // Clase personalizada para ajustar el tamaño del texto y el color.
                    }
                });
            }, function(err) {
                Swal.fire({
                    position: 'bottom-end',
                    title: 'Error al copiar el enlace',
                    showConfirmButton: false,
                    timer: 1500,
                    backdrop: 'rgba(0,0,0,0)', // hace el fondo completamente transparente
                    allowOutsideClick: true,
                    customClass: {
                        popup: 'swal-wide'
                    }
                });
                console.error('Error al copiar el enlace: ', err);
            });
        }
    </script>



@stop
