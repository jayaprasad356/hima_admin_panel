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
                                <option value="">{{ __('All') }}</option>
                                <option value="0" {{ request()->get('status') == '0' ? 'selected' : '' }}>{{ __('Pending') }}</option>
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
                        <!-- Select All Checkbox -->
                        <div class="mr-3">
                            <input type="checkbox" name="select_all" id="select-all">
                            <label for="select-all">{{ __('Select All') }}</label>
                        </div>

                        <!-- Paid Button -->
                        <button type="submit" name="status" value="1" class="btn btn-success ml-3" 
                            onclick="return confirm('{{ __('Are you sure you want to mark selected as Paid?') }}')">
                            {{ __('Paid') }}
                        </button>

                        <!-- Cancel Button -->
                        <button type="button" class="btn btn-danger ml-2" id="cancel-btn" data-bs-toggle="modal" data-bs-target="#cancelReasonModal">
                            {{ __('Cancel') }}
                        </button>
                    </div>

                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table" id="pc-dt-simple">
                                <thead>
                                    <tr>
                                        <th>{{ __('Select') }}</th>
                                        <th>{{ __('Actions') }}</th> 
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
                                            <td>
                                                <a href="#" data-url="{{ route('withdrawals.edit', $withdrawal->id) }}" data-ajax-popup="true" data-title="{{ __('Edit Bank Details') }}"
                                                   class="btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                    <i class="ti ti-pencil text-black"></i>
                                                </a>
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
                                            <td>{{ $withdrawal->bank ?? '-' }}</td>
                                            <td>{{ $withdrawal->branch ?? '-' }}</td>
                                            <td>{{ $withdrawal->ifsc ?? '-' }}</td>
                                            <td>{{ $withdrawal->account_num ?? '-' }}</td>
                                            <td>{{ $withdrawal->holder_name ?? '-' }}</td>
                                            <td>{{ $withdrawal->users->upi_id ?? '' }}</td>
                                            <td>{{ $withdrawal->datetime }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Cancellation Reason Modal -->
<div class="modal fade" id="cancelReasonModal" tabindex="-1" role="dialog" aria-labelledby="cancelReasonModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="cancel-form" method="POST" action="{{ route('withdrawals.bulkUpdateStatus') }}">
        @csrf
        @method('PATCH')

        <input type="hidden" name="status" value="2">
        <input type="hidden" name="reason" id="cancel-reason-input">

        <!-- Selected Withdrawal IDs -->
        <div id="selected-ids-container"></div>

        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Cancellation Reason') }}</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <label for="cancel-reason-textarea">{{ __('Please provide a reason for cancellation:') }}</label>
                <textarea id="cancel-reason-textarea" class="form-control" rows="3" required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="submit" class="btn btn-danger">{{ __('Submit') }}</button>
            </div>
        </div>
    </form>
  </div>
</div>

@endsection

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const selectAllCheckbox = document.getElementById('select-all');
    const cancelBtn = document.getElementById('cancel-btn');
    const cancelForm = document.getElementById('cancel-form');
    const cancelReasonInput = document.getElementById('cancel-reason-input');
    const selectedIdsContainer = document.getElementById('selected-ids-container');
    const reasonTextarea = document.getElementById('cancel-reason-textarea');
    const cancelModal = new bootstrap.Modal(document.getElementById('cancelReasonModal'));

    // Handle select all checkbox
    document.addEventListener('change', function (event) {
        if (event.target.matches('#select-all')) {
            document.querySelectorAll('input[name="withdrawal_ids[]"]').forEach(cb => {
                cb.checked = event.target.checked;
            });
        }

        if (event.target.matches('input[name="withdrawal_ids[]"]')) {
            if (!event.target.checked) {
                selectAllCheckbox.checked = false;
            } else {
                const allChecked = [...document.querySelectorAll('input[name="withdrawal_ids[]"]')]
                    .every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }
        }
    });

    // Show modal on Cancel click
    cancelBtn.addEventListener('click', function () {
        const selected = [...document.querySelectorAll('input[name="withdrawal_ids[]"]:checked')];
        if (selected.length === 0) {
            alert("{{ __('Please select at least one withdrawal to cancel.') }}");
            return;
        }

        // Clear previous inputs
        selectedIdsContainer.innerHTML = '';
        selected.forEach(input => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'withdrawal_ids[]';
            hiddenInput.value = input.value;
            selectedIdsContainer.appendChild(hiddenInput);
        });

        // Show modal
        cancelModal.show();
    });

    // On modal form submit, transfer reason and close the modal
    cancelForm.addEventListener('submit', function (e) {
        const reason = reasonTextarea.value.trim();
        if (!reason) {
            e.preventDefault();
            alert("{{ __('Please provide a reason for cancellation.') }}");
            return;
        }

        // Set the reason in the hidden input for submission
        cancelReasonInput.value = reason;

        // Close the modal after form submission
        cancelModal.hide();
    });

    // Clear textarea on modal close
    document.querySelector('.modal-close').addEventListener('click', function () {
        reasonTextarea.value = '';
    });
});

</script>
