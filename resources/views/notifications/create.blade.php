@extends('layouts.admin')

@section('page-title')
    {{ __('Add Notifications') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('notifications.index') }}">{{ __('Notifications') }}</a></li>
    <li class="breadcrumb-item">{{ __('Add Notifications') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h5>{{ __('Add New Notifications') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('notifications.store') }}" method="POST">
                    @csrf

                    <!-- Gender Selection -->
                    <div class="form-group">
                        <label for="gender">{{ __('Select Gender') }}</label>
                        <select id="gender" name="gender" class="form-control" required>
                            <option value="">{{ __('Select Gender') }}</option>
                            <option value="male">{{ __('Male') }}</option>
                            <option value="female">{{ __('Female') }}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="title">{{ __('Title') }}</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                    
                    <!-- Text Input -->
                    <div class="form-group">
                        <label for="description" class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="3" required>{{ old('description') }}</textarea>
                    </div>

                    <!-- Save Button -->
                    <div class="form-group mt-4 text-center">
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        <a href="{{ route('speech_texts.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
<script>
    // Pass the gender data to JavaScript from Blade
    var userGender = "{{ $userGender }}";

    OneSignal.push(function() {
        // Send the gender tag to OneSignal
        OneSignal.sendTag("gender", userGender);
    });
</script>
