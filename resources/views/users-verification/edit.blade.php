{{ Form::model($userVerification, ['route' => ['users-verification.update', $userVerification->id], 'method' => 'PUT']) }}

<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {{ Form::label('describe_yourself', __('Describe Yourself'), ['class' => 'form-label']) }}
            {{ Form::textarea('describe_yourself', null, ['class' => 'form-control', 'rows' => 4]) }}
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <button type="submit" class="btn btn-primary">{{ __('Update Details') }}</button>
</div>

{{ Form::close() }}
