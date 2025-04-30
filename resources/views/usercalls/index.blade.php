@extends('layouts.admin')

@section('page-title')
    {{ __('UserCalls List') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('UserCalls List') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <!-- Filter by Type and Buttons in the same row -->
                <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap">
    
    <!-- Filter Form (Aligned to the left) -->
    <form action="{{ route('usercalls.index') }}" method="GET" class="d-flex align-items-center flex-wrap">
        <!-- Entries per Page Dropdown -->
        <div class="me-5">
            <label for="per_page">{{ __('Show Entries') }}</label>
            <select name="per_page" id="per_page" class="form-control" onchange="this.form.submit()">
                @foreach([10, 25, 50, 100] as $limit)
                    <option value="{{ $limit }}" {{ request('per_page', 10) == $limit ? 'selected' : '' }}>
                        {{ $limit }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Filter by Type -->
        <div class="me-5">
            <label for="type">{{ __('Filter by Type') }}</label>
            <select name="type" id="type" class="form-control" onchange="this.form.submit()">
                <option value="">{{ __('All') }}</option>
                <option value="audio" {{ request('type') == 'audio' ? 'selected' : '' }}>{{ __('Audio') }}</option>
                <option value="video" {{ request('type') == 'video' ? 'selected' : '' }}>{{ __('Video') }}</option>
            </select>
        </div>

        <!-- Filter by Language -->
        <div class="me-5">
            <label for="language">{{ __('Filter by Language') }}</label>
            <select name="language" id="language" class="form-control" onchange="this.form.submit()">
                <option value="all" {{ request('language') == 'all' ? 'selected' : '' }}>All</option>
                <option value="Tamil" {{ request('language') == 'Tamil' ? 'selected' : '' }}>Tamil</option>
                <option value="Telugu" {{ request('language') == 'Telugu' ? 'selected' : '' }}>Telugu</option>
                <option value="Hindi" {{ request('language') == 'Hindi' ? 'selected' : '' }}>Hindi</option>
                <option value="Kannada" {{ request('language') == 'Kannada' ? 'selected' : '' }}>Kannada</option>
                <option value="Punjabi" {{ request('language') == 'Punjabi' ? 'selected' : '' }}>Punjabi</option>
                <option value="Malayalam" {{ request('language') == 'Malayalam' ? 'selected' : '' }}>Malayalam</option>
            </select>
        </div>

        <!-- Filter by Date -->
        <div class="me-5">
            <label for="filter_date">{{ __('Filter by Date') }}</label>
            <input type="date" name="filter_date" id="filter_date" 
                   class="form-control" 
                   value="{{ request('filter_date') }}" 
                   onchange="this.form.submit()">
        </div>
        
       <div class="me-5">
            <label for="search" class="form-label">{{ __('Search Users') }}</label>
            <input type="text" name="search" id="search" class="form-control"
                   value="{{ request('search') }}" placeholder="Enter Name, Mobile"
                   onkeydown="if(event.key === 'Enter') this.form.submit();">
        </div>
    </form>

    <!-- Search Section and Reset Buttons (Aligned to the right) -->
    <div class="d-flex flex-column align-items-end ms-auto">
        <br>
        <!-- Search Users -->
        <!-- Reset Buttons (Aligned to the right) -->
        <div class="d-flex">
            <!-- Reset Audio Call -->
            <form action="{{ route('usercalls.updateuser') }}" method="POST" class="me-2">
                @csrf
                <input type="hidden" name="audio_status" value="0">
                <button type="submit" class="btn btn-warning">{{ __('Reset Audio Call') }}</button>
            </form>

            <!-- Reset Video Call -->
            <form action="{{ route('usercalls.updateuser') }}" method="POST">
                @csrf
                <input type="hidden" name="video_status" value="0">
                <button type="submit" class="btn btn-danger">{{ __('Reset Video Call') }}</button>
            </form>
        </div>
    </div>

</div>

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
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <p class="mb-0">
                                Showing 
                                <strong>{{ $usercalls->firstItem() }}</strong> 
                                to 
                                <strong>{{ $usercalls->lastItem() }}</strong> 
                                of 
                                <strong>{{ $usercalls->total() }}</strong> usercalls
                            </p>
                        </div>
                        <div>
                            {{ $usercalls->appends(request()->query())->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#pc-dt-simple').DataTable();
});
</script>
@endsection
