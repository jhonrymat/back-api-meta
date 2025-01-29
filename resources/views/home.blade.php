@extends('adminlte::page')

@section('title', 'Inicio')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    <section class="content">
        <div class="container-fluid">
            {{-- mensaje de bienvenida para los usuarios --}}
            <div class="row">
                <div class="col-12">
                    <div class="callout callout-info">
                        <h5>Bienvenido al MaddiGo</h5>
                        {{-- p, aqui mostraremos un mensaje para la administracion de mensajes y bot por whatsapp --}}
                        <p>En esta sección podras administrar los mensajes y bot por whatsapp</p>
                    </div>
                </div>
            </div>
        </div>

    </section>
@stop

@section('css')
@stop

@section('js')
@stop
