<textarea id="{{ $selector }}" name="{{ $selector }}" value="{{$value}}"></textarea>


@push('scripts')
    <script type="module">

        Tinymce.init({
            selector: '#{{ $selector }}',
            license_key: 'gpl',
            plugins: 'directionality emoticons table',
            toolbar: 'undo redo | bold italic | blocks fontsize | bold italic underline strikethrough |forecolor backcolor | alignleft aligncenter alignright alignjustify |ltr rtl | bullist numlist | link image |emoticons', // Customize the toolbar
            skin: false,
            content_css: false
        });
    </script>
@endpush

