<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $newsletter->subject }}</title>
</head>
<body style="line-height: 1.6; background-color: {{ e($emailTemplate->card_background_color) }}; margin: 0; padding: 0;">
    <div class="email-container"
        style="max-width: 850px; margin: 20px auto; background-color: #ffffff; border: 1px solid #ddd; border-radius: 5px; overflow: hidden;">

        <!-- Header -->
        <div class="logo-header" style="text-align: center; padding: 20px;">
            @if ($emailTemplate->logo)
                <img src="{{ asset(Storage::url($emailTemplate->logo)) }}" alt="Logo"
                    style="max-width: 150px; margin-bottom: 10px;">
            @endif
        </div>
        <div class="email-header"
            style="background-color: {{ e($emailTemplate->header_color) }}; padding: 10px;">
            <h1>{!! $emailTemplate->title !!}</h1>
        </div>

        <!-- Body -->
        <div class="email-body" style="padding: 20px;">
            <h3>{{ $newsletter->subject }}</h3>
            {!! $content !!}
        </div>

        <!-- Footer -->
        <div class="email-footer"
            style="background-color: {{ e($emailTemplate->footer_color) }}; padding: 10px">
            <p>{!! $emailTemplate->footer_text !!}</p>
        </div>
    </div>
</body>
</html>
