<div>
    @switch($platform)
        @case('Windows')
            fa-windows
            @break
        @case('OS X')
            fa-apple
            @break
        @case('iOS')
            fa-apple
            @break
        @case('AndroidOS')
            fa-android
            @break
        @case('Linux')
            fa-linux
            @break
        @case('Ubuntu')
            fa-linux
            @break
        @case('Chrome OS')
            fa-chrome
            @break
        @default
            fa-question
    @endswitch
</div>
