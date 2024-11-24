@if(session()->has('toast'))
    @php
        $toastMessage= session()->get('toast')
    @endphp
    <script type="module">
        Toastify({
            text: "{{$toastMessage->message}}",
            duration: {{$toastMessage->duration}},
            close: true,
            gravity: "{{$toastMessage->gravity}}", // `top` or `bottom`
            position: "{{$toastMessage->position}}", // `left`, `center` or `right`
            stopOnFocus: true, // Prevents dismissing of toast on hover
            style: {
                background: "{{$toastMessage->color}}",
            },
            onClick: function(){} // Callback after click
        }).showToast();
    </script>

    <style>
        @media (max-width:768px) {
            .toastify{
                max-width: unset;
                width: 93%;
            }
        }
    </style>
@endif()
