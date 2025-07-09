@extends('dashboard.layout.master')
@section('title', 'ویرایش صرافی مرجع')
@section('content')

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">ویرایش صرافی مرجع</h5>
                    <form method="POST" action="{{ route('admin.exchange.ref.update', $exchange->id) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label for="name" class="form-label">نام صرافی</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $exchange->name) }}" required>
                            @error('name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $exchange->slug) }}" required>
                            @error('slug')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="priority" class="form-label">اولویت</label>
                            <input type="number" class="form-control" id="priority" name="priority" value="{{ old('priority', $exchange->priority) }}" required>
                            @error('priority')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $exchange->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">فعال</label>
                        </div>
                        <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
        
                    </form>
                </div>
            </div>

@endsection 