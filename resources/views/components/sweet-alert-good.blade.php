<div
    x-data="{ open: false }"
    x-show="open"
    @sweet-alert-good.window="
        Swal.fire({
            title: event.detail.title,
            text: event.detail.text,
            icon: event.detail.icon,
            footer: event.detail.footer,
        })
    "
>
</div>
