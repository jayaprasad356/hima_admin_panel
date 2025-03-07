@extends('layouts.admin')

@section('page-title')
    {{ __('Payments List') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Payments List') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <!-- Filter Form for Date Selection -->
                <form action="{{ route('payments.downloadBulkInvoice') }}" method="GET" id="downloadInvoiceForm">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="start_date">{{ __('Start Date') }}</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request()->get('start_date') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date">{{ __('End Date') }}</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request()->get('end_date') }}">
                        </div>
                        <div class="col-md-4">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-success d-block w-100" id="downloadInvoicesBtn">{{ __('Download Invoices') }}</button>
                        </div>
                    </div>
                </form>

                <div class="card-body table-border-style mt-3">
                    <div class="table-responsive">
                        <table class="table" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Mobile') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Coins') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Payment Type') }}</th>
                                    <th>{{ __('Invoice No') }}</th>
                                    <th>{{ __('Datetime') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->id }}</td>
                                        <td>{{ ucfirst($payment->users->name ?? '') }}</td>
                                        <td>{{ $payment->users->mobile ?? '' }}</td>
                                        <td>{{ $payment->type }}</td>
                                        <td>{{ $payment->coins }}</td>
                                        <td>{{ $payment->amount }}</td>
                                        <td>{{ $payment->payment_type }}</td>
                                        <td>{{ $payment->invoice_no }}</td>
                                        <td>{{ $payment->datetime }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
document.getElementById("downloadInvoiceForm").addEventListener("click", function () {
    let startDate = document.getElementById("start_date").value;
    let endDate = document.getElementById("end_date").value;

    if (!startDate || !endDate) {
        alert("Please select both start and end dates.");
        return;
    }

    window.location.href = "{{ route('payments.downloadBulkInvoice') }}?start_date=" + encodeURIComponent(startDate) + "&end_date=" + encodeURIComponent(endDate);

});

</script>
@endsection
