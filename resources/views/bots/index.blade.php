@extends('adminlte::page')

@section('title', 'Bots')

@section('content')
    <div class="row">
        <div class="col-lg-12 my-3">
            <div class="d-flex justify-content-end">
                <a data-toggle="modal" data-target="#importBotModal" class="btn btn-primary btn-sm mb-2 mr-2"
                    title="Importar BOT">
                    <i class="fa fa-file-import"></i>
                    Importar IA
                </a>
                <a data-toggle="modal" data-target="#createBotModal" class="btn btn-success btn-sm mb-2" title="Crear BOT">
                    <i class="fa fa-plus-circle"></i>
                    Crear IA
                </a>
            </div>
        </div>
    </div>


    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    <table id="botsTable" class="table table-striped tabladatatable dt-responsive" style="width:100%">
        <thead>
            <tr>
                <th>IA</th>
                <th>Descripción</th>
                <th>OpenAI Org</th>
                <th>OpenAI Assistant</th>
                <th>Aplicación Asociada</th>
                <th>Acciones</th>
                <th>OpenAI Key</th>
            </tr>
        </thead>
        <tbody style="text-align: center">
            @foreach ($todosLosBots as $bot)
                <tr>
                    <td>{{ $bot->nombre }}</td>
                    <td>{{ $bot->descripcion }}</td>
                    <td>{{ $bot->openai_org }}</td>
                    <td>{{ $bot->openai_assistant }}</td>
                    <td>
                        @if ($bot->aplicaciones->isNotEmpty())
                            {{ $bot->aplicaciones->first()->nombre }}
                        @else
                            Sin aplicación
                        @endif
                    </td>
                    <td>
                        {{-- <a data-toggle="modal" data-target="#modal-edit-{{ $bot->id }}"
                            class="btn btn-success btn-sm mb-2" title="Editar">
                            <i class="fa fa-edit"></i>
                        </a> --}}
                        <a data-toggle="modal" data-target="#modal-edit" class="btn btn-success btn-sm mb-2 editBot"
                            data-botid="{{ $bot->id }}" data-openaiassistant="{{ $bot->id }}" title="Editar">
                            <i class="fa fa-edit"></i>
                        </a>
                        <button class="btn btn-danger btn-sm mb-2 deleteBot" data-botid="{{ $bot->id }}">
                            <i class="fa fa-trash"></i>
                        </button>
                        <a data-toggle="modal" data-target="#modal-bot-{{ $bot->id }}"
                            class="btn btn-primary btn-sm mb-2 Bot" title="Bot">
                            <i class="fa fa-robot"></i>
                        </a>
                        <a data-toggle="modal" data-target="#modal-code-{{ $bot->id }}"
                            class="btn btn-warning btn-sm mb-2 Bot" title="Bot">
                            <i class="fas fa-code"></i>
                        </a>
                    </td>
                    <td>{{ $bot->openai_key }}</td>
                </tr>
                {{-- Modal de edición --}}
                @include('bots.modals.edit-modal', ['bot' => $bot])
                @include('bots.modals.bot-modal', ['bot' => $bot])
                @include('bots.modals.code-modal', ['bot' => $bot])
            @endforeach
        </tbody>
    </table>

    </div>
    @include('bots.modals.create-modal')
    @include('bots.modals.import-modal')
@endsection
@section('css')
    <link rel="stylesheet" href="//cdn.datatables.net/responsive/2.2.1/css/responsive.bootstrap4.css">
    <style>
        .chat-box {
            height: 400px;
            overflow-y: auto;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .chat-message {
            margin-bottom: 10px;
        }

        .bot-message {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 10px;
            text-align: left;
            max-width: 70%;
        }

        .user-message {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border-radius: 10px;
            text-align: right;
            max-width: 70%;
            margin-left: auto;
        }

        #user-input {
            width: calc(100% - 80px);
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="//cdn.datatables.net/responsive/2.2.1/js/dataTables.responsive.min.js"></script>
    <script src="//cdn.datatables.net/responsive/2.2.1/js/responsive.bootstrap4.min.js"></script>
    <script>
        var table = $('#botsTable').DataTable({
            responsive: true
        });
    </script>
    <script>
        $(document).ready(function() {
            // Capturar el evento de envío del formulario
            $('#editForm').on('submit', function(e) {
                e
                    .preventDefault(); // Prevenir el comportamiento por defecto del formulario (recarga de la página)

                // Obtener el ID del bot que estamos editando
                var botId = $('#botId').val();
                var formData = $(this).serialize(); // Serializar los datos del formulario

                // Enviar la solicitud AJAX para actualizar el bot
                $.ajax({
                    type: "PUT", // Usar el método PUT para actualizaciones
                    url: "bots/" + botId, // URL para actualizar el bot
                    data: formData, // Datos del formulario
                    success: function(response) {
                        if (response.data) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Actualizado!',
                                text: 'Bot actualizado con éxito',
                                confirmButtonText: 'OK'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location
                                        .reload(); // Recargar la página para reflejar los cambios
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo actualizar el bot.',
                            });
                        }
                    },
                    error: function(error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Error al actualizar el bot',
                        });
                    }
                });
            });
        });
        // Eliminar bot
        $('#botsTable').on('click', '.deleteBot', function() {
            var botId = $(this).data('botid');

            // Primer cuadro de diálogo de confirmación
            Swal.fire({
                title: '¿Estás seguro?',
                text: "No podrás revertir esto!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar!',
                cancelButtonText: 'No, cancelar!',
            }).then((result) => {
                if (result.isConfirmed) {
                    // Segundo cuadro de diálogo para seleccionar dónde eliminar
                    Swal.fire({
                        title: '¿Dónde deseas eliminar el bot?',
                        icon: 'question',
                        input: 'radio',
                        inputOptions: {
                            'database': 'Solo en la base de datos',
                            'both': 'Base de datos y OpenAI'
                        },
                        inputValidator: (value) => {
                            if (!value) {
                                return 'Debes seleccionar una opción'
                            }
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((choice) => {
                        if (choice.isConfirmed) {
                            // Opciones de eliminación seleccionadas
                            var deleteOption = choice.value;

                            // Realizar la solicitud AJAX
                            $.ajax({
                                url: 'bots/' + botId,
                                type: 'POST',
                                data: {
                                    _method: 'DELETE',
                                    _token: $('meta[name="csrf-token"]').attr('content'),
                                    deleteOption: deleteOption
                                },
                                success: function(result) {
                                    Swal.fire(
                                        'Eliminado!',
                                        'El bot ha sido eliminado.',
                                        'success'
                                    );
                                    // Recargar la página
                                    location.reload();
                                },
                                error: function(request, status, error) {
                                    var errorMessage = request.responseJSON?.error ||
                                        'No se pudo eliminar el bot.';
                                    Swal.fire(
                                        'Error!',
                                        errorMessage,
                                        'error'
                                    );
                                }
                            });
                        }
                    });
                }
            });
        });

        // crear bot
        $('#importForm').submit(function(e) {
            e.preventDefault();
            var formData = $(this).serialize();

            $.ajax({
                type: "POST",
                url: "{{ route('bots.store') }}",
                data: formData,
                success: function(response) {
                    if (response.data) {

                        Swal.fire({
                            icon: 'success',
                            title: '¡Creado!',
                            text: 'bot creada con éxito',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Recarga la página para reflejar los cambios
                                location.reload();
                            }
                        });

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo obtener la información de la bot.',
                        });
                    }
                },
                error: function(error) {
                    // Captura el mensaje de error devuelto por el servidor
                    var errorMessage = error.responseJSON ? error.responseJSON.error :
                        'Error al crear la bot';

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage, // Muestra el mensaje detallado
                    });
                }
            });
        });

        // consultar asistende por id
        function getAssistantInfo(botId) {
            $.ajax({
                type: "GET",
                url: "bots/" + botId + "/assistant",
                success: function(response) {
                    if (response.data) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Información del asistente recuperada con éxito',
                            confirmButtonText: 'OK'
                        });

                        alert(response.data);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo obtener la información del asistente.',
                        });
                    }
                },
                error: function(error) {
                    // Captura el mensaje de error devuelto por el servidor
                    var errorMessage = error.responseJSON ? error.responseJSON.error :
                        'Error al recuperar la información del asistente';

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage, // Muestra el mensaje detallado
                    });
                }
            });
        }
        // recupera informacion para editar el bot
        $(document).on('click', '.editBot', function() {
            var botId = $(this).data('botid');
            var openaiAssistantId = $(this).data('openaiassistant');

            // Mostrar una alerta de carga mientras se obtienen los datos
            Swal.fire({
                title: 'Cargando...',
                text: 'Por favor, espera mientras se cargan los datos.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading(); // Mostrar el spinner de carga
                }
            });

            // Llamada AJAX para obtener la información del asistente desde la API de OpenAI
            $.ajax({
                url: 'bots/' + openaiAssistantId + '/assistant',
                type: 'GET',
                success: function(response) {
                    // Llenar los campos del modal con la información de OpenAI
                    $('#editBotName').val(response.data.name);
                    $('#editAssistantInstructions').val(response.data.instructions);
                    $('#editModel').val(response.data.model);
                    $('#editTemperature').val(response.data.temperature);
                    $('#editTopP').val(response.data.topP);
                    $('#editResponseFormat').val(response.data.responseFormat);
                },
                error: function(request, status, error) {
                    Swal.fire(
                        'Error!',
                        'No se pudo obtener la información del asistente de OpenAI.',
                        'error'
                    );
                }
            });

            // Llamada AJAX para obtener la información del bot desde la base de datos
            $.ajax({
                url: 'bots/' + botId + '/edit',
                type: 'GET',
                success: function(response) {
                    // Llenar los campos del modal con la información del bot
                    // $('#editBotName').val(response.data.nombre);
                    //id del bot
                    $('#botId').val(response.data.id);
                    $('#editBotDescription').val(response.data.descripcion);
                    $('#editOpenAIKey').val(response.data.openai_key);
                    $('#editOpenAIOrg').val(response.data.openai_org);
                    $('#editOpenAIAssistant').val(response.data.openai_assistant);
                    $('#editAplicacionId').val(response.data
                        .aplicacion_id); // Seleccionar la aplicación

                    // Una vez que se hayan cargado los datos, cerrar la alerta de SweetAlert
                    Swal.close();

                    // Mostrar el modal de edición con los datos ya llenos
                    $('#modal-edit').modal('show');
                },
                error: function(request, status, error) {
                    Swal.fire(
                        'Error!',
                        'No se pudo obtener la información del bot.',
                        'error'
                    );
                }
            });
        });

        $('#createForm').submit(function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                type: "POST",
                url: "{{ route('bots.store.asistente') }}",
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Creado!',
                        text: 'Asistente creado con éxito',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Recarga la página para reflejar los cambios
                            location.reload();
                        }
                    });

                },
                error: function(error) {
                    // Captura el mensaje de error devuelto por el servidor
                    var errorMessage = error.responseJSON ? error.responseJSON.error :
                        'Error al crear la bot';

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage, // Muestra el mensaje detallado
                    });
                }
            });
        });
    </script>

    <!-- Funciones JavaScript para actualizar los valores -->
    <script>
        function updateTemperatureValue(value) {
            document.getElementById('temperatureValue').textContent = parseFloat(value).toFixed(2);
        }

        function updateTopPValue(value) {
            document.getElementById('topPValue').textContent = parseFloat(value).toFixed(2);
        }
    </script>

    <script>
        $(document).ready(function() {
            $('[id^=send-btn-]').click(function() {
                sendMessage($(this).data('bot-id'));
            });

            $('[id^=user-input-]').keypress(function(e) {
                if (e.which === 13) { // Detecta la tecla Enter (código 13)
                    e.preventDefault(); // Evita el comportamiento por defecto del Enter en el formulario
                    var botId = $(this).attr('id').split('-')[
                        2]; // Obtiene el ID del bot del campo de entrada
                    sendMessage(botId);
                }
            });

            function sendMessage(botId) {
                var userInput = $('#user-input-' + botId).val(); // Selecciona el input correcto

                if (userInput.trim()) {
                    var chatBox = $('#chat-box-' + botId); // Selecciona el chat-box correcto

                    // Agregar mensaje del usuario al chat
                    chatBox.append(
                        '<div class="chat-message user-message"><p>' + userInput + '</p></div>'
                    );
                    chatBox.scrollTop(chatBox[0]
                        .scrollHeight); // Desplazarse hacia el último mensaje
                    $('#user-input-' + botId).val(''); // Limpiar el input

                    // Enviar pregunta al servidor usando AJAX
                    $.ajax({
                        url: 'ask-bot', // La ruta que define en tu Laravel para interactuar con el controlador
                        method: 'POST',
                        data: {
                            question: userInput,
                            _token: '{{ csrf_token() }}', // Token CSRF
                            waId: '{{ Auth::user()->id }}', // ID del usuario autenticado
                            botId: botId // Enviar el ID del bot específico
                        },
                        success: function(response) {
                            var chatBox = $('#chat-box-' + botId);

                            // Usar `marked.parse` para convertir Markdown a HTML
                            var htmlContent = marked.parse(response.answer);

                            chatBox.append(
                                `<div class="chat-message bot-message">${htmlContent}</div>`
                            );
                            chatBox.scrollTop(chatBox[0]
                                .scrollHeight); // Desplazarse hacia el último mensaje
                        },
                        error: function() {
                            chatBox.append(
                                '<div class="chat-message bot-message"><p>Error al obtener respuesta, intenta de nuevo.</p></div>'
                            );
                        }
                    });
                }
            }
        });



        function copyCode(containerId) {
            // Obtener el contenedor del código
            var codeContainer = document.getElementById(containerId);

            // Obtener el texto dentro del contenedor de código
            var codeText = codeContainer.innerText;

            // Copiar el texto al portapapeles usando la API del portapapeles
            navigator.clipboard.writeText(codeText).then(function() {
                // Mostrar una alerta o mensaje de confirmación con sweetalert sin boton de confirmacion
                Swal.fire({
                    icon: 'success',
                    title: '¡Copiado!',
                    text: 'El código ha sido copiado al portapapeles.',
                    showConfirmButton: false,
                    timer: 1500
                });
            }).catch(function(err) {
                // En caso de error, mostrar un mensaje
                alert("Error al copiar el código: " + err);
            });
        }
    </script>

    <script>
        $(document).ready(function() {
            // Capturar el clic en el botón "Limpiar chat"
            $('.modal-header').on('click', '.clear-chat', function() {
                var botId = $(this).data('bot-id');

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esto eliminará todo el hilo de conversación.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, limpiar chat!',
                    cancelButtonText: 'No, cancelar!',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('deleteThread') }}",
                            type: 'DELETE',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                id: botId
                            },
                            success: function(response) {
                                Swal.fire(
                                    'Limpio!',
                                    response.message,
                                    'success'
                                );
                                // Limpia el contenido del chat en la interfaz
                                $('#chat-box-' + botId).empty().append(
                                    '<div class="chat-message bot-message"><p>¡Hola! ¿Cómo puedo ayudarte hoy?</p></div>'
                                );
                            },
                            error: function(xhr, status, error) {
                                Swal.fire(
                                    'Error!',
                                    xhr.responseJSON?.message ||
                                    'No se pudo eliminar el hilo de conversación.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
        });
    </script>
@stop
