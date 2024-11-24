<select name="{{ $inputName }}" id="" class="select2 form-control" data-allow-clear="true"
        data-placeholder="{{$placeholderName}}">
    <option value="">استاد را انتخاب کنید</option>
    @foreach($admins as $admin)
        <option  {{$defaultValue == $admin->id ? 'selected' : null }}
           value="{{ $admin->id }}">{{ $admin->fullname() }}</option>
    @endforeach
</select>

