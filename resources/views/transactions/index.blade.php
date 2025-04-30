@extends('layouts.admin')

@section('page-title')
    {{ __('Transactions List') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Transactions List') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <!-- Filter by Type Form -->
                <form action="{{ route('transactions.index') }}" method="GET" class="mb-3" id="filterForm">
                    <div class="row align-items-end">
                    <div class="col-md-2">
                            <label for="per_page">{{ __('Show Entries') }}</label>
                            <select name="per_page" id="per_page" class="form-control" onchange="this.form.submit()">
                                @foreach([10, 25, 50, 100] as $limit)
                                    <option value="{{ $limit }}" {{ request('per_page', 10) == $limit ? 'selected' : '' }}>
                                        {{ $limit }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="type">{{ __('Filter by Type') }}</label>
                            <select name="type" id="type" class="form-control" onchange="document.getElementById('filterForm').submit();">
                                <option value="">{{ __('All') }}</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter_date">{{ __('Filter by Date') }}</label>
                            <input type="date" name="filter_date" id="filter_date" class="form-control" value="{{ request()->get('filter_date') }}" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">{{ __('Search Users') }}</label>
                            <input type="text" name="search" id="search" class="form-control"
                                value="{{ request('search') }}" placeholder="Enter Name, Mobile"
                                onkeydown="if(event.key === 'Enter') this.form.submit();">
                        </div>
                    </div>
                </form>

                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Actions') }}</th>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Mobile') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Coins') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Payment Type') }}</th>
                                    <th>{{ __('Datetime') }}</th>
                                    <th>{{ __('Download') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $transaction)
                                    <tr class="selectable-row">
                                    <td class="Action">
                                    <div class="action-btn bg-danger ms-2">
                                                {!! Form::open(['method' => 'DELETE', 'route' => ['transactions.destroy', $transaction->id], 'id' => 'delete-form-' . $transaction->id]) !!}
                                                    <a href="#" class="btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                    onclick="confirmDelete(event, '{{ $transaction->id }}')">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </a>
                                                {!! Form::close() !!}
                                            </div>
                                    </td>
                                        <td>{{ $transaction->id }}</td>
                                        <td>{{ ucfirst($transaction->users->name ?? '') }}</td>
                                        <td>{{ $transaction->users->mobile ?? '' }}</td>
                                        <td>{{ $transaction->type }}</td>
                                        <td>{{ $transaction->coins }}</td>
                                        <td>{{ $transaction->amount }}</td>
                                        <td>{{ $transaction->payment_type }}</td>
                                        <td>{{ $transaction->datetime }}</td>
                                        <td>
                                            @if ($transaction->type == 'add_coins')
                                                <a href="{{ route('transactions.download', $transaction->id) }}" class="btn btn-primary btn-sm">
                                                    {{ __('Download Invoice') }}
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <p class="mb-0">
                                Showing 
                                <strong>{{ $transactions->firstItem() }}</strong> 
                                to 
                                <strong>{{ $transactions->lastItem() }}</strong> 
                                of 
                                <strong>{{ $transactions->total() }}</strong> transactions
                            </p>
                        </div>
                        <div>
                            {{ $transactions->appends(request()->query())->links('pagination::bootstrap-4') }}
                        </div>
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
