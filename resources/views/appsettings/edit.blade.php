@extends('layouts.admin')

@section('page-title')
    {{ __('Edit App Settings') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit App Settings') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h5>{{ __('Edit App Settings') }}</h5>
            </div>
            <div class="card-body">
        <form action="{{ route('appsettings.update', $appsettings->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="link">Link</label>
                <input type="text" class="form-control" id="link" name="link" value="{{ old('link', $appsettings->link) }}" required>
            </div>

            <div class="form-group">
                <label for="app_version">App Version</label>
                <input type="text" class="form-control" id="app_version" name="app_version" value="{{ old('app_version', $appsettings->app_version) }}" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="10" required>{!! old('description', $appsettings->description) !!}</textarea>
            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>
<script src="//cdn.ckeditor.com/4.21.0/full-all/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        CKEDITOR.replace('privacy_policy', {
            extraPlugins: 'colorbutton'
        });
    });
</script>
@endsection
