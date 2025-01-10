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
                <!-- Filter by Type Form -->
                <form action="{{ route('usercalls.index') }}" method="GET" class="mb-3">
                    <div class="row align-items-end">
                        <!-- Existing Status Filter -->
                        <div class="col-md-3">
                    <label for="type">{{ __('Filter by Type') }}</label>
                    <select name="type" id="type" class="form-control status-filter" onchange="this.form.submit()">
                        <option value="">{{ __('All') }}</option>
                        <option value="audio" {{ request()->get('type') == 'audio' ? 'selected' : '' }}>{{ __('Audio') }}</option>
                        <option value="video" {{ request()->get('type') == 'video' ? 'selected' : '' }}>{{ __('Video') }}</option>
                    </select>
                     </div>
                </form>
                </div>
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
