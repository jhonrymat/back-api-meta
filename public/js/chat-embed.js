document.addEventListener('DOMContentLoaded', function () {
    // Agregar una hoja de estilos externa al DOM
    var link = document.createElement('link');
    link.rel = 'stylesheet';

    // local
    // link.href = 'http://127.0.0.1:8000/css/chat-embed.css';
    link.href = 'https://app.maddigo.com.co/css/chat-embed.css';
    document.head.appendChild(link);

    var faLink = document.createElement('link');
    faLink.rel = 'stylesheet';
    faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css';
    document.head.appendChild(faLink);

    // Agregar el script externo de marked.js
    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
    document.head.appendChild(script);



    // Función para generar un UUID
    function generateUUID() {
        return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, c =>
            (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
        );
    }

    // Obtener o crear el UUID del usuario
    function getUserIdentifier() {
        let userId = localStorage.getItem('userIdentifier');
        if (!userId) {
            userId = generateUUID();
            localStorage.setItem('userIdentifier', userId);
        }
        return userId;
    }

    const userIdentifier = getUserIdentifier();

    function getBotDataFromScript() {
        var scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            var script = scripts[i];
            if (script.src && script.src.includes('chat-embed.js')) {
                var urlParams = new URLSearchParams(script.src.split('?')[1]);
                return {
                    botId: urlParams.get('botId'),
                    botNombre: urlParams.get('botNombre')
                };
            }
        }
        return { botId: null, botNombre: null };
    }

    var { botId, botNombre } = getBotDataFromScript();

    if (!botId) {
        console.error('Bot ID no encontrado en la URL del script');
        return;
    }

    // Crear el botón flotante de chat
    var chatButton = document.createElement('button');
    chatButton.className = 'chat-embed-button';
    chatButton.innerHTML = `Preguntale a ${botNombre || 'Asistente'}`;
    document.body.appendChild(chatButton);

    // Crear el contenedor principal para el chat
    var chatEmbedContainer = document.createElement('div');
    chatEmbedContainer.className = 'chat-embed';
    chatEmbedContainer.innerHTML = `
        <div class="chat-embed-modal" id="embedded-chat-modal" style="display: none;">
            <div class="chat-embed-modal-header">
                <h5 class="chat-embed-modal-title" id="chatModalLabel">  ${botNombre || 'Asistente'}</h5>
                <button type="button" class="chat-embed-close" onclick="document.getElementById('embedded-chat-modal').style.display='none'">&times;</button>
            </div>
            <div class="chat-embed-modal-body" id="chat-box">
                <div class="chat-embed-message bot-message">
                    <p>¡Hola! ¿Cómo puedo ayudarte hoy?</p>
                </div>
            </div>
            <div class="chat-embed-modal-footer">
                <input type="text" id="user-input" name="user-input" class="chat-embed-input" placeholder="Escribe un mensaje..." />
                <!-- Botón para subir imágenes -->
                <label for="image-input" class="chat-embed-send-btn" id="image-preview-container">
                    <i class="fas fa-image" id="image-icon"></i>
                    <input type="file" id="image-input" style="display:none" accept="image/*" />
                </label>
                <button type="button" class="chat-embed-send-btn" id="send-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(chatEmbedContainer);

    // Mostrar el modal cuando se haga clic en el botón
    chatButton.addEventListener('click', function () {
        var modal = document.getElementById('embedded-chat-modal');
        if (modal) {
            modal.style.display = 'block';
        } else {
            console.error('No se pudo encontrar el modal en el DOM.');
        }
    });

    // Enviar mensaje del usuario
    var sendButton = document.getElementById('send-btn');
    var userInput = document.getElementById('user-input');
    var imageInput = document.getElementById('image-input');
    var chatBox = document.getElementById('chat-box');
    var imagePreviewContainer = document.getElementById('image-preview-container');
    var imageIcon = document.getElementById('image-icon');

    obtenerEstadoImagenes(botId); // Consultar si el bot permite imágenes después de definir los elementos

    // Variable para almacenar la URL de la imagen subida
    var imageUrl = null;
    var selectedImageFile = null; // 🔹 Guardar la imagen seleccionada sin subirla aún

    // Función para manejar la previsualización de la imagen
    imageInput.addEventListener('change', function () {
        var file = this.files[0];
        if (file) {
            selectedImageFile = file; // 🔹 Guardar la imagen sin subirla

            var reader = new FileReader();
            reader.onload = function (e) {
                // 🔹 Reemplazar el icono con la imagen seleccionada
                imagePreviewContainer.style.backgroundImage = `url(${e.target.result})`;
                imagePreviewContainer.style.backgroundSize = "cover";
                imagePreviewContainer.style.backgroundPosition = "center";
                imagePreviewContainer.style.borderRadius = "50%";
                imagePreviewContainer.style.width = "40px";
                imagePreviewContainer.style.height = "40px";
                imageIcon.style.display = "none"; // 🔹 Ocultar el icono de la imagen
            };
            reader.readAsDataURL(file);
        }
    });

    sendButton.addEventListener('click', function () {
        var userMessageValue = userInput.value.trim();

        // Si no hay texto ni imagen seleccionada, no hacemos nada
        if (!userMessageValue && !selectedImageFile) {
            console.error('No hay mensaje ni imagen para enviar.');
            return;
        }

        // 🔹 Deshabilitar el botón de enviar mientras se procesa la solicitud
        sendButton.disabled = true;
        userInput.disabled = true;
        imageInput.disabled = true;
        sendButton.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`; // 🔄 Mostrar animación de carga

        // **Si hay imagen, subirla primero antes de enviar el mensaje**
        if (selectedImageFile) {
            var formData = new FormData();
            formData.append('image', selectedImageFile);

            // fetch('http://127.0.0.1:8000/admin/upload-image', {
            fetch('https://app.maddigo.com.co/admin/upload-image', {
                method: 'POST',
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    if (data.imageUrl) {
                        imageUrl = data.imageUrl;

                        // **📌 Reemplazar el icono de carga con la imagen real en el botón**
                        imagePreviewContainer.style.backgroundImage = `url(${imageUrl})`;
                        imagePreviewContainer.innerHTML = ""; // Limpiar icono de carga

                        console.log("Imagen subida correctamente: ", imageUrl);
                    }
                })
                .catch(error => console.error('Error al subir la imagen:', error))
                .finally(() => {
                    // **Después de subir la imagen, permitir el envío del mensaje**
                    enviarMensaje(userMessageValue, imageUrl);
                });
        } else {
            // Si no hay imagen, enviar solo el texto
            enviarMensaje(userMessageValue, null);
        }
    });

    // 🔹 Función para enviar el mensaje al backend
    function enviarMensaje(userMessageValue, imageUrl) {
        // ✅ Si el usuario solo envió una imagen, agregar un mensaje por defecto
        if (!userMessageValue && imageUrl) {
            userMessageValue = "Describe esta imagen.";
        }
        // 🔹 Deshabilitar inputs mientras se procesa la IA
        sendButton.disabled = true;
        userInput.disabled = true;
        imageInput.disabled = true;
        sendButton.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`; // 🔄 Animación de carga

        // **Agregar el mensaje de texto al chat de inmediato**
        if (userMessageValue) {
            var userTextMessage = document.createElement('div');
            userTextMessage.classList.add('chat-embed-message', 'user-message');
            userTextMessage.innerHTML = `<p>${userMessageValue}</p>`;
            chatBox.appendChild(userTextMessage);
        }

        // **Si hay imagen, agregarla al chat de inmediato**
        if (imageUrl) {
            var imageMessage = document.createElement('div');
            imageMessage.classList.add('chat-embed-message', 'user-message');
            imageMessage.innerHTML = `<img src="${imageUrl}" class="chat-embed-image-preview" style="max-width: 200px; border-radius: 5px;" />`;
            chatBox.appendChild(imageMessage);
        }

        chatBox.scrollTop = chatBox.scrollHeight; // 🔹 Hacer scroll al final

        // 🔹 Enviar mensaje a la IA
        // fetch('http://127.0.0.1:8000/admin/ask-bot-embedded', {
        fetch('https://app.maddigo.com.co/admin/ask-bot-embedded', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                question: userMessageValue,
                image_url: imageUrl, // 🔹 Enviar la imagen si se subió
                botId: botId,
                userIdentifier: userIdentifier
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.answer) {
                    var botMessage = document.createElement('div');
                    botMessage.classList.add('chat-embed-message', 'bot-message');
                    botMessage.innerHTML = `<i class="fas fa-robot" style="margin-right: 8px;"></i>
                    <div class="chat-embed-message-content">${marked.parse(data.answer)}</div>`;
                    chatBox.appendChild(botMessage);
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            })
            .catch(error => console.error('Error al enviar la solicitud:', error))
            .finally(() => {
                // **Habilitar los inputs después de que la IA responda**
                sendButton.disabled = false;
                userInput.disabled = false;
                imageInput.disabled = false;
                sendButton.innerHTML = `<i class="fas fa-paper-plane"></i>`; // 🔹 Restaurar icono de enviar

                userInput.value = ""; // 🔹 Limpiar el input de texto


                // Restaurar input de imagen correctamente
                resetImageInput();
            });
    }

    function resetImageInput() {
        // Restaurar la apariencia original del botón de imagen
        imagePreviewContainer.style.backgroundImage = "";
        imagePreviewContainer.innerHTML = '<i class="fas fa-image" id="image-icon"></i>';

        // Verificar si el input de imagen aún existe dentro del contenedor, si no, volver a agregarlo
        var oldInput = document.getElementById("image-input");
        if (oldInput) {
            oldInput.value = ""; // 🔥 Esto limpia la imagen seleccionada
        } else {
            var newInput = document.createElement("input");
            newInput.type = "file";
            newInput.id = "image-input";
            newInput.style.display = "none";
            newInput.accept = "image/*";

            // Volver a agregar el evento change al nuevo input
            newInput.addEventListener("change", function () {
                var file = this.files[0];
                if (file) {
                    selectedImageFile = file;
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        imagePreviewContainer.style.backgroundImage = `url(${e.target.result})`;
                        imagePreviewContainer.style.backgroundSize = "cover";
                        imagePreviewContainer.style.backgroundPosition = "center";
                        imagePreviewContainer.style.borderRadius = "50%";
                        imagePreviewContainer.style.width = "40px";
                        imagePreviewContainer.style.height = "40px";
                        imageIcon.style.display = "none"; // Ocultar el icono
                    };
                    reader.readAsDataURL(file);
                }
            });

            imagePreviewContainer.appendChild(newInput);
        }

        // 🔥 Asegurar que la variable selectedImageFile también se vacíe
        selectedImageFile = null;
    }

});


document.addEventListener('DOMContentLoaded', function () {
    var sendButton = document.getElementById('send-btn');
    var userInput = document.getElementById('user-input');

    if (userInput && sendButton) {
        userInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // 🔹 Evita el comportamiento por defecto
                sendButton.click(); // 🔹 Simula un clic en el botón de enviar
            }
        });
    } else {
        console.error('Elementos no encontrados: userInput o sendButton');
    }
});

function obtenerEstadoImagenes(botId) {
    // fetch(`http://127.0.0.1:8000/api/permitir-imagenes/${botId}`)
    fetch(`https://app.maddigo.com.co/api/permitir-imagenes/${botId}`)
        .then(response => response.json())
        .then(data => {
            var imagePreviewContainer = document.getElementById('image-preview-container'); // Asegurar que el elemento existe
            if (!imagePreviewContainer) {
                console.error("El elemento imagePreviewContainer no se encontró en el DOM.");
                return;
            }

            if (data.permitirImagenes) {
                imagePreviewContainer.style.display = "flex"; // Mostrar el botón
            } else {
                imagePreviewContainer.style.display = "none"; // Ocultar el botón
            }
        })
        .catch(error => console.error("Error obteniendo el estado de imágenes:", error));
}

