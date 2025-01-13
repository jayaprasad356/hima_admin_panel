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

                     <!-- User Dropdown -->
                     <div class="form-group">
                        <label for="user_id">{{ __('Select User') }}</label>
                        <select id="user_id" name="user_id" class="form-control select2" required>
                            <option value="">{{ __('Select User') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->mobile }})</option>
                            @endforeach
                        </select>
                    </div>

                <!-- User Details -->
                <div id="user-details" class="mt-3" style="display: none;">
                    <p><strong>{{ __('Name:') }}</strong> <span id="user-name"></span></p>
                    <p><strong>{{ __('Email:') }}</strong> <span id="user-email"></span></p>
                    <p><strong>{{ __('Mobile:') }}</strong> <span id="user-mobile"></span></p>
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
    document.getElementById('user_id').addEventListener('change', function () {
    const userId = this.value;

    if (userId) {
        fetch(`/users/${userId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('user-details').style.display = 'block';
                document.getElementById('user-name').textContent = data.name;
                document.getElementById('user-email').textContent = data.email;
                document.getElementById('user-mobile').textContent = data.mobile;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('user-details').style.display = 'none';
            });
    } else {
        document.getElementById('user-details').style.display = 'none';
    }
});

</script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "{{ __('Select User') }}",
            allowClear: true
        });
    });
</script>
<script>
    $('#user_id').select2({
        placeholder: "{{ __('Select User') }}",
        allowClear: true,
        ajax: {
            url: "{{ route('search.users') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        return {
                            id: item.id,
                            text: item.name + ' (' + item.mobile + ')'
                        };
                    })
                };
            },
            cache: true
        }
    });
</script>
