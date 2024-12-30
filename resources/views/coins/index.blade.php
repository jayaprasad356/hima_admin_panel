@extends('layouts.admin')

@section('page-title')
    {{ __('Coins List') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Coins List') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
         
            <div class="card-body">
                <!-- Filter by Status Form -->

                <!-- Table for user verifications -->
                <form action="{{ route('coins.updateStatus') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success" name="status" value="1">{{ __('Update Coins') }}</button>

                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table" id="pc-dt-simple">
                                <thead>
                                    <tr>
                                        <th>{{ __('Check Box') }}</th>
                                        <th>{{ __('ID') }}</th>
                                        <th>{{ __('Price') }}</th>
                                        <th>{{ __('Coins') }}</th>
                                        <th>{{ __('Save') }}</th>
                                        <th>{{ __('Popular') }}</th>
                                        <th>{{ __('Best Offer') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($coins as $coin)
                                        <tr class="selectable-row">
                                            <td><input type="checkbox" class="user-checkbox" name="coin_ids[]" value="{{ $coin->id }}"></td>
                                            <td>{{ $coin->id }}</td>
                                            <td>{{ $coin->price }}</td>
                                            <td>{{ $coin->coins }}</td>
                                            <td>{{ $coin->save }}</td>
                                            <td>{{ $coin->popular }}</td>
                                            <td>{{ $coin->best_offer }}</td>
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
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable (Optional, for sorting and pagination)
    $('#pc-dt-simple').DataTable();
});
</script>
@endsection
