@extends('layouts.admin')

@section('page-title')
    {{ __('Manage users') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('users') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
            <form action="{{ route('users.index') }}" method="GET" class="mb-3">
        <div class="col-md-3">
                            <label for="filter_date">{{ __('Filter by Date') }}</label>
                            <input type="date" name="filter_date" id="filter_date" class="form-control" value="{{ request()->get('filter_date') }}" onchange="this.form.submit()">
                        </div>
        </form>
            </div>
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table" id="pc-dt-simple">
                        <thead>
                            <tr>
                            <th>{{ __('Actions') }}</th>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Mobile') }}</th>
                                <th>{{ __('Age') }}</th>
                                <th>{{ __('Gender') }}</th>
                                <th>{{ __('Coins') }}</th>
                                <th>{{ __('Total Coins') }}</th>
                                <th>{{ __('Language') }}</th>
                                <th>{{ __('Balance') }}</th>
                                <th>{{ __('DateTime') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Audio Status') }}</th>
                                <th>{{ __('Video Status') }}</th>
                                <th>{{ __('Avatar') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                <td class="Action">
                                <div class="action-btn bg-info ms-2">
                                            <!-- Direct Link to Edit user Page -->
                                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                <i class="ti ti-pencil text-white"></i>
                                            </a>
                                        </div>
                                        <div class="action-btn bg-danger ms-2">
                                            <form method="POST" action="{{ route('users.destroy', $user->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm align-items-center bs-pass-para" 
                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                        onclick="return confirm('Are you sure you want to delete this user?');">
                                                    <i class="ti ti-trash text-white"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td>{{ $user->id }}</td>
                                    <td>{{ ucfirst($user->name) }}</td>
                                    <td>{{ $user->mobile }}</td>
                                    <td>{{ $user->age }}</td>
                                    <td>{{ ucfirst($user->gender) }}</td>
                                    <td>{{ $user->coins }}</td>
                                    <td>{{ $user->total_coins }}</td>
                                    <td>{{ ucfirst($user->language) }}</td>
                                    <td>{{ $user->balance }}</td>
                                    <td>{{ $user->datetime }}</td>
                                    <td>
                                        <!-- Display Status with values 1, 2, and 3 -->
                                        @if($user->status == 1)
                                            <i class="fa fa-clock text-warning"></i> <span class="font-weight-bold">{{ __('Pending') }}</span>
                                        @elseif($user->status == 2)
                                            <i class="fa fa-check-circle text-success"></i> <span class="font-weight-bold">{{ __('Verified') }}</span>
                                        @elseif($user->status == 3)
                                            <i class="fa fa-times-circle text-danger"></i> <span class="font-weight-bold">{{ __('Rejected') }}</span>
                                        @else
                                            <i class="fa fa-question-circle text-secondary"></i> <span class="font-weight-bold">{{ __('Unknown') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <!-- Display Audio Status -->
                                        @if($user->audio_status == 1)
                                            <i class="fa fa-volume-up text-success"></i> <span class="font-weight-bold">{{ __('Enabled') }}</span>
                                        @else
                                            <i class="fa fa-volume-mute text-danger"></i> <span class="font-weight-bold">{{ __('Disabled') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <!-- Display Video Status -->
                                        @if($user->video_status == 1)
                                            <i class="fa fa-video text-success"></i> <span class="font-weight-bold">{{ __('Enabled') }}</span>
                                        @else
                                            <i class="fa fa-video-slash text-danger"></i> <span class="font-weight-bold">{{ __('Disabled') }}</span>
                                        @endif
                                    </td>

                                    <!-- Avatar Image -->
                                    <td>
                                        @if($user->avatar && $user->avatar->image)
                                        <a href="{{ asset('storage/app/public/' . $user->avatar->image) }}" data-lightbox="image-{{ $user->avatar->id }}">
                                                <img class="user-img img-thumbnail img-fluid" 
                                                    src="{{ asset('storage/app/public/' . $user->avatar->image) }}" 
                                                    alt="Avatar Image" 
                                                    style="max-width: 100px; max-height: 100px;">
                                            </a>

                                        @else
                                            {{ __('No Avatar') }}
                                        @endif
                                    </td>
                                    <!-- Actions -->
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">

<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        $('#pc-dt-simple').DataTable();
    });
</script>
@endsection
