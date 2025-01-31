<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $newsletter->subject }}</title>
    <style>
        body {
            font-family: {{ $emailTemplate->font_family }};
            background-color: {{ $emailTemplate->card_background_color }};
            color: #333;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 850px;
            margin: 20px auto;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .logo-header {
            text-align: center;
            padding: 20px;
        }
        .email-header {
            background-color: {{ $emailTemplate->header_color }};
            color: white;
            text-align: center;
            padding: 10px;
        }
        .email-body {
            padding: 20px;
        }
        .email-footer {
            background-color: {{ $emailTemplate->footer_color }};
            text-align: center;
            padding: 10px;
            font-size: 0.9em;
            color: #555;
        }
        .unsubscribe {
            color: #d9534f;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="logo-header">
            @if($emailTemplate->logo)
                <img src="{{ Storage::url($emailTemplate->logo) }}" alt="Logo" style="max-width: 150px;">
            @endif
        </div>
        <div class="email-header">
            <h1>{!! $emailTemplate->title !!}</h1>
        </div>

        <!-- Body -->
        <div class="email-body">
            <h2>{{ $newsletter->subject }}</h2>
            {!! $content !!}
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p>{!! $emailTemplate->footer_text !!}</p>
            <p>Powered by <strong>Maddigo</strong></p>
        </div>
    </div>
</body>
</html>
