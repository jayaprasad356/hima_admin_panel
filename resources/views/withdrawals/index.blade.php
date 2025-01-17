@extends('layouts.admin')

@section('page-title')
    {{ __('Withdrawals List') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Withdrawals List') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <!-- Filter by Type Form -->
                <form action="{{ route('withdrawals.index') }}" method="GET" class="mb-3">
                    <div class="row align-items-end">
                        <!-- Existing Status Filter -->
                        <div class="col-md-3">
                            <label for="status">{{ __('Filter by Status') }}</label>
                            <select name="status" id="status" class="form-control status-filter" onchange="this.form.submit()">
                                <option value="0" {{ request()->get('status', 0) == '0' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                <option value="1" {{ request()->get('status') == '1' ? 'selected' : '' }}>{{ __('Paid') }}</option>
                                <option value="2" {{ request()->get('status') == '2' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter_date">{{ __('Filter by Date') }}</label>
                            <input type="date" name="filter_date" id="filter_date" class="form-control" value="{{ request()->get('filter_date') }}" onchange="this.form.submit()">
                        </div>

                        <div class="col-md-3 offset-md-3 d-flex justify-content-end">
                            <a href="{{ route('withdrawals.export', ['status' => request()->get('status', 0), 'filter_date' => request()->get('filter_date')]) }}" class="btn btn-primary">
                                {{ __('Export Withdrawals') }}
                            </a>
                        </div>

                    </div>
                </form>

                <form action="{{ route('withdrawals.bulkUpdateStatus') }}" method="POST">
    @csrf
    @method('PATCH')

    <div class="mb-3 d-flex align-items-center">
        <button type="submit" name="status" value="1" class="btn btn-success ml-3" 
            onclick="return confirm('{{ __('Are you sure you want to mark selected as Paid?') }}')">
            {{ __('Paid') }}
        </button>

        <button type="submit" name="status" value="2" class="btn btn-danger ml-2" 
            onclick="return confirm('{{ __('Are you sure you want to cancel selected withdrawals?') }}')">
            {{ __('Cancel') }}
        </button>
    </div>




                <div class="card-body table-border-style">
                <div class="table-responsive">
        <table class="table" id="pc-dt-simple">
            <thead>
                <tr>
                    <th>{{ __('Select') }}</th>
                    <th>{{ __('ID') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Mobile') }}</th>
                    <th>{{ __('Amount') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Bank') }}</th>
                    <th>{{ __('Branch') }}</th>
                    <th>{{ __('Ifsc Code') }}</th>
                    <th>{{ __('Account Number') }}</th>
                    <th>{{ __('Holder Name') }}</th>
                    <th>{{ __('Upi ID') }}</th>
                    <th>{{ __('Datetime') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($withdrawals as $withdrawal)
                    <tr>
                        <td>
                            <input type="checkbox" name="withdrawal_ids[]" value="{{ $withdrawal->id }}">
                        </td>
                        <td>{{ $withdrawal->id }}</td>
                        <td>{{ ucfirst($withdrawal->users->name ?? '') }}</td>
                        <td>{{ $withdrawal->users->mobile ?? '' }}</td>
                        <td>{{ $withdrawal->amount }}</td>
                        <td>{{ $withdrawal->type }}</td>
                        <td>
                            @if($withdrawal->status == 0)
                                <i class="fa fa-clock text-warning"></i> <span class="font-weight-bold">{{ __('Pending') }}</span>
                            @elseif($withdrawal->status == 1)
                                <i class="fa fa-check-circle text-success"></i> <span class="font-weight-bold">{{ __('Paid') }}</span>
                            @elseif($withdrawal->status == 2)
                                <i class="fa fa-times-circle text-danger"></i> <span class="font-weight-bold">{{ __('Cancelled') }}</span>
                            @else
                                <i class="fa fa-question-circle text-secondary"></i> <span class="font-weight-bold">{{ __('Unknown') }}</span>
                            @endif
                        </td>
                        <td>{{ $withdrawal->users->bank ?? '' }}</td>
                        <td>{{ $withdrawal->users->branch ?? '' }}</td>
                        <td>{{ $withdrawal->users->ifsc ?? '' }}</td>
                        <td>{{ $withdrawal->users->account_num ?? '' }}</td>
                        <td>{{ $withdrawal->users->holder_name ?? '' }}</td>
                        <td>{{ $withdrawal->users->upi_id ?? '' }}</td>
                        <td>{{ $withdrawal->datetime }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('select-all').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('input[name="withdrawal_ids[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    $(document).ready(function() {
        // Initialize DataTable
        $('#pc-dt-simple').DataTable();
    });
</script>
@endsection

