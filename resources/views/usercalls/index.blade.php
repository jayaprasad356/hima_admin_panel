@extends('layouts.admin')

@section('page-title')
    {{ __('UserCalls List') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('UserCalls List') }}</li>
@endsection

<style>
.pagination .page-item .page-link {
    color: #d67291 !important; /* Lighter pink shade */
    border: none !important;
    background: transparent !important;
    font-size: 14px; /* Decrease font size */
    padding: 10px 10px; /* Reduce padding */
    font-weight: bold;
}

.pagination .page-item.active .page-link {
    background: #f2f2f2 !important; /* Softer background */
    color: #d67291 !important; /* Keep lighter pink color */
    box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.15); /* Softer shadow */
    border-radius: 4px;
    font-size: 14px; /* Smaller font size */
    padding: 5px 8px;
}

.pagination .page-item .page-link:hover {
    background: rgba(214, 114, 145, 0.1) !important; /* Light hover effect */
    border-radius: 4px;
}

.pagination .page-item.disabled .page-link {
    color: #ccc !important;
}

</style>

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <!-- Filter by Type and Buttons in the same row -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <!-- Filter by Type Form -->
                    <form action="{{ route('usercalls.index') }}" method="GET" class="d-flex align-items-center">
                        <div class="me-5">
                            <label for="type">{{ __('Filter by Type') }}</label>
                            <select name="type" id="type" class="form-control status-filter" onchange="this.form.submit()">
                                <option value="">{{ __('All') }}</option>
                                <option value="audio" {{ request()->get('type') == 'audio' ? 'selected' : '' }}>{{ __('Audio') }}</option>
                                <option value="video" {{ request()->get('type') == 'video' ? 'selected' : '' }}>{{ __('Video') }}</option>
                            </select>
                        </div>

                        <div class="me-5">
                            <label for="language">{{ __('Filter by Language') }}</label>
                            <select name="language" id="language" class="form-control status-filter" onchange="this.form.submit()">
                            <option value="all" {{ request('language') == 'all' ? 'selected' : '' }}>All</option>
                            <option value="Tamil" {{ request('language') == 'Tamil' ? 'selected' : '' }}>Tamil</option>
                            <option value="Telugu" {{ request('language') == 'Telugu' ? 'selected' : '' }}>Telugu</option>
                            <option value="Hindi" {{ request('language') == 'Hindi' ? 'selected' : '' }}>Hindi</option>
                            <option value="Kannada" {{ request('language') == 'Kannada' ? 'selected' : '' }}>Kannada</option>
                            <option value="Punjabi" {{ request('language') == 'Punjabi' ? 'selected' : '' }}>Punjabi</option>
                            <option value="Malayalam" {{ request('language') == 'Malayalam' ? 'selected' : '' }}>Malayalam</option>
                            </select>
                        </div>

                        <div class="me-2">
                            <label for="filter_date">{{ __('Filter by Date') }}</label>
                            <input type="date" name="filter_date" id="filter_date" class="form-control" 
                             value="{{ request()->get('filter_date') }}" onchange="this.form.submit()">
                        </div>
                    </form>

                    <!-- Buttons aligned to the right -->
                    <div>
                        <!-- Reset Audio Call Form -->
                        <form action="{{ route('usercalls.updateuser') }}" method="POST" style="display: inline;">
                            @csrf
                            <input type="hidden" name="audio_status" value="0">
                            <button type="submit" class="btn btn-warning me-2">{{ __('Reset Audio Call') }}</button>
                        </form>

                        <!-- Reset Video Call Form -->
                        <form action="{{ route('usercalls.updateuser') }}" method="POST" style="display: inline;">
                            @csrf
                            <input type="hidden" name="video_status" value="0">
                            <button type="submit" class="btn btn-danger">{{ __('Reset Video Call') }}</button>
                        </form>
                    </div>
                </div>
                     <!-- Search Box -->
                <form action="{{ route('usercalls.index') }}" method="GET" class="mb-3">
                <div class="col-md-3 ms-auto">
                    <label for="search">{{ __('Search') }}</label>
                    <input type="text" name="search" id="search" class="form-control" 
                    value="{{ request()->get('search') }}" placeholder="Enter Name" onkeyup="startFilterTimer()">
                </div>
                </form>
                <br>
                <!-- Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('User Name') }}</th>
                                <th>{{ __('Call User Name') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Language') }}</th>
                                <th>{{ __('Started Time') }}</th>
                                <th>{{ __('Ended Time') }}</th>
                                <th>{{ __('Call Duration') }}</th>
                                <th>{{ __('User Coins') }}</th>
                                <th>{{ __('Coins Spend') }}</th>
                                <th>{{ __('Income') }}</th>
                                <th>{{ __('Datetime') }}</th>
                                <th>{{ __('Update Current Ended Time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($usercalls as $usercall)
                                <tr>
                                    <td>{{ $usercall->id }}</td>
                                    <td>{{ ucfirst($usercall->user->name ?? '') }}</td>
                                    <td>{{ ucfirst($usercall->callusers->name ?? '') }}</td>
                                    <td>{{ ucfirst($usercall->type) }}</td>
                                    <td>{{ ucfirst($usercall->user->language ?? '') }}</td>
                                    <td>{{ $usercall->started_time }}</td>
                                    <td>{{ $usercall->ended_time }}</td>
                                    <td>{{ $usercall->duration }}</td>
                                    <td>{{ $usercall->coins }}</td>
                                    <td>{{ $usercall->coins_spend }}</td>
                                    <td>{{ $usercall->income }}</td>
                                    <td>{{ $usercall->datetime }}</td>
                                    <td>{{ $usercall->update_current_endedtime }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <p class="m-3">Showing {{ $usercalls->firstItem() }} to {{ $usercalls->lastItem() }} of {{ $usercalls->total() }} entries</p>
                        {{ $usercalls->appends(request()->except('page'))->links('pagination::bootstrap-4') }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    let filterTimer;

    $(document).ready(function () {
        $('#search').on('input', function () {
            clearTimeout(filterTimer); // Clear previous timer
            filterTimer = setTimeout(() => {
                $('form').submit(); // Auto-submit form after 3 seconds
            }, 3000); // 3 seconds delay
        });
    });
</script>

@endsection
