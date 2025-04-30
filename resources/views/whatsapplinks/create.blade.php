@extends('layouts.admin')

@section('page-title')
    {{ __('Add Whatsapp Link') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('whatsapplinks.index') }}">{{ __('Whatsapp Link') }}</a></li>
    <li class="breadcrumb-item">{{ __('Add Whatsapp Link') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h5>{{ __('Add New Whatsapp Link') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('whatsapplinks.store') }}" method="POST">
                    @csrf

                    <!-- Text Input -->
                    <div class="form-group">
                        <label for="link" class="form-label">{{ __('Link') }}</label>
                        <input type="text" id="link" name="link" class="form-control" value="{{ old('link') }}" required>
                    </div>

                    <!-- Language Dropdown -->
                    <div class="form-group mt-3">
                        <label for="language" class="form-label">{{ __('Language') }}</label>
                        <select name="language" class="form-control" required>
                            <option value='Hindi' {{ old('language') == 'Hindi' ? 'selected' : '' }}>Hindi</option>
                            <option value='Telugu' {{ old('language') == 'Telugu' ? 'selected' : '' }}>Telugu</option>
                            <option value='Malayalam' {{ old('language') == 'Malayalam' ? 'selected' : '' }}>Malayalam</option>
                            <option value='Kannada' {{ old('language') == 'Kannada' ? 'selected' : '' }}>Kannada</option>
                            <option value='Punjabi' {{ old('language') == 'Punjabi' ? 'selected' : '' }}>Punjabi</option>
                            <option value='Tamil' {{ old('language') == 'Tamil' ? 'selected' : '' }}>Tamil</option>
                        </select>
                    </div>

                    <!-- Save Button -->
                    <div class="form-group mt-4 text-center">
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        <a href="{{ route('whatsapplinks.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection