<div>
    <select name="{{ $inputName }}"
            {{$multiple ? 'multiple' : ''}}
            id="selectUser"
            class="select2 form-control"
            data-selected="{{$selected}}"
            src="{{route('admin.users.select.index')}}"
            {{$required ? 'required' : ''}}
            {{$disabled ? 'disabled' : ''}}
    >
        <option value="{{ $selected }}" selected>{{ $selectedLabel }}</option>
    </select>
</div>

@section('vendor-script')
    @parent
    @vite(['resources/assets/vendor/libs/select2/select2.js',
            'resources/assets/vendor/js/forms-selects.js',
            'resources/assets/js/select-user.js',
          ])
@endsection
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection


