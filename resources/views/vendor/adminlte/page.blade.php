@extends('adminlte::master')

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')
@inject('preloaderHelper', 'JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper')

@section('adminlte_css')
    @stack('css')
    @yield('css')
    <style>
        .notif-dot {
            display: inline-block;
            margin-left: 6px;
            width: 8px;
            height: 8px;
            background-color: red;
            border-radius: 50%;
            vertical-align: middle;
        }
    </style>

@stop

@section('classes_body', $layoutHelper->makeBodyClasses())

@section('body_data', $layoutHelper->makeBodyData())

@section('body')
    <div class="wrapper">

        {{-- Preloader Animation (fullscreen mode) --}}
        @if ($preloaderHelper->isPreloaderEnabled())
            @include('adminlte::partials.common.preloader')
        @endif

        {{-- Top Navbar --}}
        @if ($layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.navbar.navbar-layout-topnav')
        @else
            @include('adminlte::partials.navbar.navbar')
        @endif

        {{-- Left Main Sidebar --}}
        @if (!$layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.sidebar.left-sidebar')
        @endif

        {{-- Content Wrapper --}}
        @empty($iFrameEnabled)
            @include('adminlte::partials.cwrapper.cwrapper-default')
        @else
            @include('adminlte::partials.cwrapper.cwrapper-iframe')
        @endempty

        {{-- Footer --}}
        @hasSection('footer')
            @include('adminlte::partials.footer.footer')
        @endif

        {{-- Right Control Sidebar --}}
        @if (config('adminlte.right_sidebar'))
            @include('adminlte::partials.sidebar.right-sidebar')
        @endif

    </div>
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const localKey = 'has-unread-chats';
            let hasPlayed = false;
            let audio;

            // Permitir cargar el audio después de una interacción del usuario
            document.body.addEventListener("click", () => {
                if (!audio) {
                    audio = new Audio("/sounds/notification.mp3");
                }
            });

            // Añade punto rojo según el texto visible del ítem
            function addDotByText(menuText) {
                const allLinks = document.querySelectorAll('.nav-link');
                for (let link of allLinks) {
                    const p = link.querySelector('p');
                    if (p && p.textContent.trim().startsWith(menuText) && !p.querySelector('.notif-dot')) {
                        const dot = document.createElement('span');
                        dot.classList.add('notif-dot');
                        p.appendChild(dot);
                    }
                }
            }

            // Elimina todos los puntos rojos
            function removeDotsByText() {
                const allDots = document.querySelectorAll('.notif-dot');
                allDots.forEach(dot => dot.remove());
            }

            // Mostrar puntos si había notificaciones pendientes
            if (localStorage.getItem(localKey) === 'true') {
                ['Chats', 'WhatsApp', 'Gestión WhatsApp'].forEach(addDotByText);
            }

            // Inicializar Pusher
            const pusher = new Pusher('52c212ce563c5534e98c', {
                cluster: 'us2'
            });

            const channel = pusher.subscribe('webhooks');
            channel.bind('App\\Events\\Webhook', function (payload) {
                localStorage.setItem(localKey, 'true');
                ['Chats', 'WhatsApp', 'Gestión WhatsApp'].forEach(addDotByText);

                if (audio && !hasPlayed) {
                    audio.play().catch(() => {});
                    hasPlayed = true;
                }
            });

            // Eliminar puntos al hacer clic en “Chats”
            const chatsLink = document.querySelector('#menu-chats-3 a');
            if (chatsLink) {
                chatsLink.addEventListener('click', function () {
                    removeDotsByText();
                    localStorage.setItem(localKey, 'false');
                    hasPlayed = false;
                });
            }
        });
        $(document).ready(function() {
            function checkSession() {
                $.ajax({
                    url: '{{ url("/check-session") }}',
                    method: 'GET',
                    success: function(response) {
                        if (!response.is_logged_in) {
                            Swal.fire({
                                title: 'Sesión caducada',
                                text: 'Su sesión ha caducado. Por favor, inicie sesión de nuevo.',
                                icon: 'warning',
                                confirmButtonText: 'Iniciar sesión',
                                allowOutsideClick: false,
                                allowEscapeKey: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $.ajax({
                                        url: '{{ url('/refresh-csrf') }}',
                                        method: 'GET',
                                        success: function(data) {
                                            $('meta[name="csrf-token"]').attr(
                                                'content', data.csrf_token);
                                            window.location.href =
                                                '{{ route('login') }}';
                                        }
                                    });
                                }
                            });
                        }
                    }
                });
            }

            // Check session every 5 minutes (300000 milliseconds)
            setInterval(checkSession, 7200000);
        });
    </script>
@stop
