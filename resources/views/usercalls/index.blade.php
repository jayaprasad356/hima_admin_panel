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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <!-- Filter by Type Form -->
                    <form action="{{ route('usercalls.index') }}" method="GET" class="d-flex align-items-center">
                        <label for="type" class="me-5">{{ __('Filter by Type') }}</label>
                        <select name="type" id="type" class="form-control status-filter me-2" onchange="this.form.submit()">
                            <option value="">{{ __('All') }}</option>
                            <option value="audio" {{ request()->get('type') == 'audio' ? 'selected' : '' }}>{{ __('Audio') }}</option>
                            <option value="video" {{ request()->get('type') == 'video' ? 'selected' : '' }}>{{ __('Video') }}</option>
                        </select>
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

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table" id="pc-dt-simple">
                        <thead>
                            <tr>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('User Name') }}</th>
                                <th>{{ __('Call User Name') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Started Time') }}</th>
                                <th>{{ __('Ended Time') }}</th>
                                <th>{{ __('Call Duration') }}</th>
                                <th>{{ __('Coins Before Spending') }}</th>
                                <th>{{ __('Coins After Spending') }}</th> 
                                <th>{{ __('Coins Spend') }}</th>
                                <th>{{ __('Income') }}</th>
                                <th>{{ __('Datetime') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($usercalls as $usercall)
                                <tr>
                                    <td>{{ $usercall->id }}</td>
                                    <td>{{ ucfirst($usercall->user->name ?? '') }}</td>
                                    <td>{{ ucfirst($usercall->callusers->name ?? '') }}</td>
                                    <td>{{ ucfirst($usercall->type) }}</td>
                                    <td>{{ $usercall->started_time }}</td>
                                    <td>{{ $usercall->ended_time }}</td>
                                    <td>{{ $usercall->duration }}</td>
                                    <td>{{ $usercall->coins_before_spending }}</td>
                                    <td>{{ $usercall->coins_after_spending }}</td>
                                    <td>{{ $usercall->coins_spend }}</td>
                                    <td>{{ $usercall->income }}</td>
                                    <td>{{ $usercall->datetime }}</td>
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
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#pc-dt-simple').DataTable();
});
</script>
@endsection
