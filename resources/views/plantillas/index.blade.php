@extends('adminlte::page')

@section('plugins.Sweetalert2', true)

@section('title', 'Plantillas')

@section('content')
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    <form id="createSend">
        <div class="card mt-4">
            <div class="card-header text-white bg-secondary mb-3">
                Envios masivos WhatsApp
            </div>
            <div class="card-body">
                <div>
                    <label for="selectPlantilla">Seleccione un numero disponible</label>
                    <select id="selectPlantilla" class="form-select mb-3" required>
                        <option value="">Selecciona un Número</option>
                        @foreach ($numeros as $numero)
                            <option value="{{ $numero->id }}" data-id_telefono="{{ $numero->id_telefono }}"
                                data-id_c_business="{{ $numero->aplicacion->id_c_business }}"
                                data-token_api="{{ $numero->aplicacion->token_api }}">
                                {{ $numero->nombre }} - {{ $numero->numero }} - {{ $numero->aplicacion->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="templatesSelect">Seleccione una plantilla disponible</label>
                    <select id="templatesSelect" class="form-select mb-3" required>
                        <option value="">Selecciona una plantilla</option>
                        <!-- Las opciones se cargarán aquí dinámicamente -->
                    </select>
                </div>
                @hasanyrole('ContratacionL|Administrador')
                    <div>
                        <label for="distintivoSelect">Seleccione un distintivo</label>
                        <select id="distintivoSelect" class="form-select mb-3" required>
                            <option value="">Esto servirá para los reportes</option>
                            @foreach ($distintivos as $dis)
                                <option value="{{ $dis->id }}">
                                    {{ $dis->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endhasanyrole
                <div>
                    <label for="etiqueta">Seleccione un grupo para enviar</label>
                    <div>
                        <select id="etiqueta" name="etiqueta[]" class="form-select mb-3" multiple>
                            <option value="">Selecciona una Etiqueta</option>
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}" data-numeros='@json($tag->contactos->pluck('telefono'))'>
                                    {{ $tag->nombre }} - {{ $tag->contactos->count() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="exampleFormControlTextarea1">Lista de contactos</label>
                    <textarea class="form-control" id="exampleFormControlTextarea1" rows="5" required></textarea>
                </div>

                <div class="mt-3">
                    <label class="form-label">¿Desea quitar los números repetidos?</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="eliminarDuplicados" id="eliminarSi"
                            value="si">
                        <label class="form-check-label" for="eliminarSi">Sí</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="eliminarDuplicados" id="eliminarNo"
                            value="no" checked>
                        <label class="form-check-label" for="eliminarNo">No</label>
                    </div>
                </div>

                <div id="templateDetails">
                    <!-- Los detalles de la plantilla se inyectarán aquí -->
                </div>

                <div class="col-lg-12">
                    <label for="fechaInicio" class="form-label">Desea programar el envío?</label>
                    <input type="datetime-local" class="form-control" id="programar" name="programar">
                </div>
                <br>
                <button type="submit" class="btn btn-primary">Enviar mensajes</button>

            </div>
        </div>

    </form>
@endsection

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/drag-and-drop.css') }}">
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.0.js" crossorigin="anonymous"></script>

    <script>
        function findPlaceholders(text) {
            const regexp = /\{\{\s*\d+\s*\}\}/g;
            const matches = [];
            let m;
            while ((m = regexp.exec(text)) !== null) {
                matches.push({
                    text: m[0],
                    value: ''
                });
            }
            return matches;
        }
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

            $('#etiqueta').on('change', updateContactList);
            $('input[name="eliminarDuplicados"]').on('change', updateContactList);

            function updateContactList() {
                const selected = $('#etiqueta option:selected');

                // 1) Construir el arreglo 'arr' desde los data-numeros (JSON)
                let arr = [];
                selected.each(function() {
                    const raw = $(this).attr('data-numeros');
                    if (!raw) return;
                    try {
                        const list = JSON.parse(raw); // array real
                        arr.push(...list);
                    } catch (e) {
                        console.error("Error parseando data-numeros", raw, e);
                    }
                });

                // 2) Limpieza básica
                arr = arr.map(s => String(s || '').trim()).filter(Boolean);

                // 3) Si no se eliminan duplicados, solo pintar y salir
                const eliminar = $('input[name="eliminarDuplicados"]:checked').val() === 'si';
                if (!eliminar) {
                    $('#exampleFormControlTextarea1').val(arr.join('\n'));
                    return;
                }

                // 4) Normalizar: comparar por últimos 10 dígitos (CO)
                const norm = s => {
                    const digits = String(s).replace(/\D/g, '');
                    return digits.slice(-10); // clave de comparación
                };

                // 5) Agrupar por clave normalizada
                const buckets = new Map(); // clave -> [originales]
                for (const n of arr) {
                    const k = norm(n);
                    if (!k) continue; // ignora líneas sin dígitos suficientes
                    if (!buckets.has(k)) buckets.set(k, []);
                    buckets.get(k).push(n);
                }

                // 6) Separar únicos y repetidos
                const uniques = [];
                const removed = [];
                for (const [, originals] of buckets) {
                    uniques.push(originals[0]); // se queda el primero
                    if (originals.length > 1) {
                        removed.push(...originals.slice(1)); // el resto son duplicados
                    }
                }

                // 7) Pintar resultado y alertar si hubo duplicados
                $('#exampleFormControlTextarea1').val(uniques.join('\n'));

                console.log("Detectados como duplicados:", removed);

                if (removed.length) {
                    Swal.fire({
                        title: "Números eliminados",
                        html: "<pre style='white-space:pre-wrap;margin:0'>" + removed.join('\n') + "</pre>",
                        icon: "info",
                        confirmButtonText: "Entendido",
                        width: 600
                    });
                }
            }



            // Ejecuta al cargar
            updateContactList();
        });
    </script>
    <script>
        var templatesData = []; // Almacenará la información de las plantillas
        var templateLanguage = null;
        var templateName = null;
        var templateType = null;

        $(document).ready(function() {
            var availableFields = @json($availableFields); // JSON con los nombres de los campos disponibles

            $('#selectPlantilla').change(function() {
                var selectedOption = $(this).find('option:selected');
                var idCBusiness = selectedOption.data('id_c_business'); //id_telefono
                var tokenApi = selectedOption.data('token_api');

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
                        url: 'message-templates',
                        type: 'GET',
                        data: {
                            id_c_business: idCBusiness,
                            token_api: tokenApi
                        },
                        success: function(response) {
                            Swal.close(); // Cierra el SweetAlert de carga
                            if (response.success) {
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
                            detailsHtml += `
                                            <div class="my-5" data-group="header-document">
                                                <h5 class="text-h5">Header</h5>
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
                                                    <button type="button" class="pick-file">Seleccione su archivo</button>
                                                    <input type="file" class="form-control-file file-input" id="input-file" name="input-file" hidden accept=".pdf,application/pdf"/>
                                                    <div class="preview" id="preview"></div>
                                                </div>
                                            </div>`;
                        } else if (component.format === 'IMAGE') {
                            templateType = 'IMAGE';
                            detailsHtml += `
                                            <div class="my-5" data-group="header-image">
                                                <h5 class="text-h5">Header</h5>
                                                <div class="form-group">
                                                    <label>Seleccione cómo desea proporcionar la imagen:</label>
                                                    <div class="custom-control custom-radio">
                                                        <input type="radio" id="useLinkImage" name="IMAGEInputType" class="custom-control-input" value="link" checked>
                                                        <label class="custom-control-label" for="useLinkImage">Usar un link</label>
                                                    </div>
                                                    <div class="custom-control custom-radio">
                                                        <input type="radio" id="useUploadImage" name="IMAGEInputType" class="custom-control-input" value="upload">
                                                        <label class="custom-control-label" for="useUploadImage">Subir una imagen</label>
                                                    </div>
                                                </div>

                                                <div class="form-group linkInput">
                                                    <label for="header-image">Link de la imagen (PNG o JPG)</label>
                                                    <input type="text" class="form-control" id="header-image" name="header" required>
                                                </div>

                                                <div class="form-group uploadInput files drag-area" style="display:none;">
                                                    <h2>Arrastra y suelta la imagen</h2>
                                                    <span>O</span>
                                                    <button type="button" class="pick-file">Selecciona tu archivo</button>
                                                    <input type="file" class="form-control-file file-input" name="input-file" hidden accept=".png,.jpg,.jpeg,image/png,image/jpeg"/>
                                                    <div class="preview mt-2"></div>
                                                </div>
                                            </div>`;
                        } else if (component.format === 'VIDEO') {
                            templateType = 'VIDEO';
                            detailsHtml += `
                                            <div class="my-5" data-group="header-video">
                                                <h5 class="text-h5">Header</h5>
                                                <div class="form-group">
                                                    <label>Seleccione cómo desea proporcionar el video:</label>
                                                    <div class="custom-control custom-radio">
                                                        <input type="radio" id="useLinkVideo" name="VIDEOInputType" class="custom-control-input" value="link" checked>
                                                        <label class="custom-control-label" for="useLinkVideo">Usar un link</label>
                                                    </div>
                                                    <div class="custom-control custom-radio">
                                                        <input type="radio" id="useUploadVideo" name="VIDEOInputType" class="custom-control-input" value="upload">
                                                        <label class="custom-control-label" for="useUploadVideo">Subir un video</label>
                                                    </div>
                                                </div>

                                                <div class="form-group linkInput">
                                                    <label for="header-video">Link del video (MP4)</label>
                                                    <input type="text" class="form-control" id="header-video" name="header" required>
                                                </div>

                                                <div class="form-group uploadInput files drag-area" style="display:none;">
                                                    <h2>Arrastra y suelta el video</h2>
                                                    <span>O</span>
                                                    <button type="button" class="pick-file">Selecciona tu archivo</button>
                                                    <input type="file" class="form-control-file file-input" name="input-file" hidden accept=".mp4,video/mp4"/>
                                                    <div class="preview mt-2"></div>
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
                            var selectOptions = availableFields.map(function(field) {
                                return `<option value="${field}">${field}</option>`;
                            }).join('');

                            return `
                                    <div class="form-group">
                                        <label for="${index}">${placeholder.text}</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control format" id="${index}" name="${index}" value="" required/>
                                            <select class="form-select mb-3" id="select-${index}" onchange="updateInput(${index}, this.value)">
                                                <option value="">Selecciona un campo</option>
                                                ${selectOptions}
                                            </select>
                                        </div>
                                    </div>`;
                        }).join('');

                        detailsHtml +=
                            `<div class="my-5"><h5 class="text-h5">Body</h5><p class="pre-wrap">${formattedText}</p>${inputsHtml}</div>`;
                    } else if (component.type === 'FOOTER') {
                        detailsHtml +=
                            `<div class="my-5"><h5 class="text-h5">Footer</h5><p class="pre-wrap">${component.text}</p></div>`;
                    } else if (component.type === 'BUTTONS') {
                        detailsHtml += '<div class="my-5"><h5 class="text-h5">Buttons</h5>';
                        if (component.buttons && component.buttons.length > 0) {
                            detailsHtml += '<ul>';
                            component.buttons.forEach((button, btnIndex) => {
                                const isUrl = (button.type === 'URL');
                                const isPhone = (button.type === 'PHONE_NUMBER');
                                const rawUrl = String(button.url || '');
                                const hasPlaceholder = /\{\{\s*\d+\s*\}\}/.test(rawUrl) || (
                                    Array.isArray(button.example) && button.example
                                    .length > 0);

                                if (isUrl && hasPlaceholder) {
                                    detailsHtml += `
                                                    <li class="mb-3">
                                                        <p class="pre-wrap mb-1"><strong>${button.text || ''}</strong></p>
                                                        <p class="pre-wrap small text-muted">${rawUrl}</p>
                                                        <div class="form-group mt-2">
                                                        <label for="button-${btnIndex}">Completa el sufijo</label>
                                                        <input type="text" class="form-control" id="button-${btnIndex}"
                                                                name="button_text[]"
                                                                data-index="${btnIndex}"
                                                                placeholder="${(Array.isArray(button.example) && button.example[0]) ? button.example[0] : 'ej: pedido-123'}"
                                                                required>
                                                        </div>
                                                    </li>`;
                                } else if (isUrl) {
                                    detailsHtml += `
                                                    <li class="mb-3">
                                                        <p class="pre-wrap mb-1"><strong>${button.text || ''}</strong></p>
                                                        <p class="pre-wrap small text-muted">${rawUrl}</p>
                                                        <div class="my-2"><span class="badge bg-secondary">Botón estático</span></div>
                                                    </li>`;
                                } else if (isPhone) {
                                    detailsHtml += `
                                                    <li class="mb-3">
                                                        <p class="pre-wrap mb-1"><strong>${button.text || ''}</strong></p>
                                                        <p class="pre-wrap small text-muted">${button.phone_number || ''}</p>
                                                        <div class="my-2"><span class="badge bg-secondary">Botón teléfono</span></div>
                                                    </li>`;
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
                initMediaInputs(); // <<< activa radios/drag&drop/preview con scope por grupo
            });
        });

        function updateInput(index, value) {
            var input = document.getElementById(index);
            if (value) {
                input.value = `--${value}--`;
            } else {
                input.value = '';
            }
        }

        $(document).ready(function() {
            $('#createSend').submit(function(e) {
                e.preventDefault();

                // Validaciones rápidas
                if (!$('#selectPlantilla').val()) {
                    return Swal.fire('Falta seleccionar el número', 'Elige un número disponible.',
                        'warning');
                }
                if (!$('#templatesSelect').val()) {
                    return Swal.fire('Falta la plantilla', 'Selecciona una plantilla disponible.',
                        'warning');
                }
                const recs = ($('#exampleFormControlTextarea1').val() || '').trim();
                if (!recs) {
                    return Swal.fire('Sin contactos', 'Agrega al menos un número de destino.', 'warning');
                }



                Swal.fire({
                    title: 'Enviando...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                // 1) Detecta el bloque HEADER visible (image/video/document)
                let $group = $('[data-group^="header-"]:visible').first();
                if (!$group.length) $group = $('[data-group^="header-"]')
                    .first(); // fallback si nada visible

                // 2) Lee link o archivo del header actual
                const linkVal = ($group.find('.linkInput input[type="text"]').val() || '').trim();
                const fileInput = $group.find('.uploadInput input[type="file"]')[0] || document
                    .getElementById('input-file');

                // 2.1) Validar link por tipo (solo si hay link)
                if (linkVal && !validaLinkPorTipo(linkVal, templateType)) {
                    Swal.close();
                    return Swal.fire(
                        'Link inválido',
                        'El link no coincide con el tipo de header seleccionado (PDF/Imagen/Video).',
                        'warning'
                    );
                }

                // 3) Si hay link, enviar directo
                if (linkVal) {
                    enviarDatos(linkVal);
                    return;
                }

                // 4) Si hay archivo, súbelo y usa la URL de respuesta
                if (fileInput && fileInput.files && fileInput.files[0]) {
                    const formData = new FormData();
                    formData.append('file', fileInput.files[0]);
                    formData.append('type', templateType);
                    // 'DOCUMENT' | 'IMAGE' | 'VIDEO'

                    $.ajax({
                        url: 'upload-pdf', // usa tu ruta existente; en el back acepta image/video/pdf según 'type'
                        type: 'POST',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        processData: false,
                        contentType: false,
                        success: function(res) {
                            enviarDatos(res.url);
                        },
                        error: function(xhr) {
                            console.log(xhr);
                            Swal.close();
                            Swal.fire('Archivo no válido', (xhr?.responseJSON?.message ||
                                'No se pudo subir el archivo.'), 'error');
                        }
                    });
                    return;
                }

                // 5) Sin link ni archivo
                enviarDatos(null);
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

            // Organizar la información en un objeto
            var dataToSend = {
                body_placeholders: body_placeholders,
                header_type: templateType,
                header_url: url,
                buttons_url: (function() {
                    const first = $('input[name="button_text[]"]').map(function() {
                        const t = ($(this).val() || '').trim();
                        return t || null;
                    }).get().find(Boolean) || null;
                    return first;
                })(),
                id_c_business: $('#selectPlantilla option:selected').data('id_c_business')?.toString(),
                phone_id: $('#selectPlantilla option:selected').data('id_telefono')?.toString(),
                recipients: $('#exampleFormControlTextarea1').val(),
                template_language: templateLanguage,
                template_name: templateName,
                token_api: $('#selectPlantilla option:selected').data('token_api'),
                programar: $('#programar').val() || null,
                selectedTags: $('#etiqueta').val(),
                distintivoSelect: $('#distintivoSelect').val() || null
            };

            // Mostrar loader
            Swal.fire({
                title: 'Encolando mensajes...',
                text: 'Por favor espera',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                type: "POST",
                url: "send-message-templates",
                contentType: "application/json",
                data: JSON.stringify(dataToSend),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                async: true,
                timeout: 30000, // ⚡ 30 segundos máximo (suficiente para respuesta inmediata)

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
        function initMediaInputs() {
            document.querySelectorAll('[data-group]').forEach((group) => wireGroup(group));
        }

        function wireGroup(group) {
            const radios = group.querySelectorAll('input[type="radio"][name$="InputType"]');
            const linkWrap = group.querySelector('.linkInput');
            const uploadWrap = group.querySelector('.uploadInput');
            const linkField = linkWrap ? linkWrap.querySelector('input[type="text"]') : null;
            const fileField = uploadWrap ? uploadWrap.querySelector('input[type="file"].file-input') : null;

            if (radios.length) {
                const selected = Array.from(radios).find(r => r.checked) || radios[0];
                toggleMode(group, selected?.value === 'upload', {
                    linkWrap,
                    uploadWrap,
                    linkField,
                    fileField
                });
            }

            radios.forEach(radio => {
                radio.addEventListener('change', (e) => {
                    toggleMode(group, e.target.value === 'upload', {
                        linkWrap,
                        uploadWrap,
                        linkField,
                        fileField
                    });
                });
            });

            const pickBtn = group.querySelector('.pick-file');
            if (pickBtn && fileField) {
                pickBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    fileField.click();
                });
            }

            if (fileField) {
                fileField.addEventListener('change', (e) => {
                    e.preventDefault();
                    const f = e.target.files?.[0];
                    if (f) renderPreview(group, f, fileField);
                });
            }

            const dropArea = group.querySelector('.drag-area');
            if (dropArea && fileField) {
                dropArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropArea.classList.add('active');
                });
                dropArea.addEventListener('dragleave', (e) => {
                    e.preventDefault();
                    dropArea.classList.remove('active');
                });
                dropArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropArea.classList.remove('active');
                    const file = e.dataTransfer.files?.[0];
                    if (!file) return;
                    if (!isAccepted(file, fileField)) {
                        alert('El tipo de archivo no es válido para este campo.');
                        return;
                    }
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    fileField.files = dt.files;
                    renderPreview(group, file, fileField);
                });
            }
        }

        function toggleMode(group, isUpload, refs) {
            const {
                linkWrap,
                uploadWrap,
                linkField,
                fileField
            } = refs;
            if (linkWrap) linkWrap.style.display = isUpload ? 'none' : '';
            if (uploadWrap) uploadWrap.style.display = isUpload ? '' : 'none';
            if (linkField) linkField.required = !isUpload;
            if (fileField) fileField.required = isUpload;
            if (isUpload && linkField) linkField.value = '';
            if (!isUpload && fileField) {
                fileField.value = '';
                clearPreview(group);
            }
        }

        function clearPreview(group) {
            const preview = group.querySelector('.preview');
            if (preview) preview.innerHTML = '';
        }

        function renderPreview(group, file, fileInput) {
            const preview = group.querySelector('.preview');
            if (!preview) return;
            preview.innerHTML = '';

            if (!isAccepted(file, fileInput)) {
                alert('El tipo de archivo no es válido para este campo.');
                return;
            }

            const type = file.type || '';
            if (type.startsWith('image/')) {
                const img = document.createElement('img');
                img.alt = 'Vista previa';
                img.style.maxWidth = '260px';
                img.style.maxHeight = '180px';
                img.src = URL.createObjectURL(file);
                preview.appendChild(img);
                return;
            }
            if (type.startsWith('video/')) {
                const video = document.createElement('video');
                video.controls = true;
                video.width = 320;
                video.src = URL.createObjectURL(file);
                preview.appendChild(video);
                return;
            }
            const p = document.createElement('p');
            p.textContent = `Archivo seleccionado: ${file.name}`;
            preview.appendChild(p);
        }

        function isAccepted(file, input) {
            const acceptAttr = (input.getAttribute('accept') || '').split(',').map(s => s.trim()).filter(Boolean);
            if (acceptAttr.length === 0) return true;
            const fileType = (file.type || '').toLowerCase();
            const fileName = (file.name || '').toLowerCase();

            return acceptAttr.some(a => {
                a = a.toLowerCase();
                if (a.includes('/')) {
                    if (a.endsWith('/*')) {
                        const base = a.split('/')[0];
                        return fileType.startsWith(base + '/');
                    }
                    return fileType === a;
                }
                return fileName.endsWith(a); // .pdf .png .mp4 ...
            });
        }

        function validaLinkPorTipo(url, type) {
            const u = String(url || '');
            if (type === 'DOCUMENT') return /\.(pdf)(\?|#|$)/i.test(u);
            if (type === 'IMAGE') return /\.(png|jpe?g)(\?|#|$)/i.test(u);
            if (type === 'VIDEO') return /\.(mp4)(\?|#|$)/i.test(u);
            return true;
        }

        // Inicializa si la vista viene ya con un header inyectado
        document.addEventListener('DOMContentLoaded', initMediaInputs);
    </script>
@stop
